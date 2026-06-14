<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\TwoFactorVerification;
use App\Mail\TwoFactorEmailVerification;
use App\Exceptions\AuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\UserNotFoundException;
use App\Helpers\AuthLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Inscription utilisateur
     */
    public function register(array $data): array
    {
        $user = User::create([
            'id'           => (string) Str::uuid(),
            'name'         => $data['name'],
            'email'        => $data['email'] ?? null,
            'telephone'    => $data['telephone'] ?? null,
            'password'     => Hash::make($data['password']),
            'last_sync_at' => now(),
        ]);

        $user->assignRole('superadmin'); // Par défaut, on assigne le rôle SuperAdmin (à changer en production)

        // Eager load roles une seule fois
        $user->load('roles');

        AuthLogger::userRegistered($user->id, $user->email, $user->telephone);

        $verification = $this->createAndSend2FA($user);

        return [
            'user'            => $user,
            'pending_2fa'     => true,
            'verification_id' => $verification->id,
            'channel'         => $verification->channel,
            'roles'           => $user->getRoleNames(),
        ];
    }

    /**
     * Connexion utilisateur
     */
    public function login(array $data): array
    {
        $login = $data['login'];

        $user = User::with('roles')
            ->where('email', $login)
            ->orWhere('telephone', $login)
            ->first();

        if (!$user) {
            AuthLogger::loginFailedUserNotFound($login);
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        if (!Hash::check($data['password'], $user->password)) {
            AuthLogger::loginFailedWrongPassword($user->id);
            throw new AuthenticationException('Mot de passe incorrect.');
        }

        $user->update(['last_sync_at' => now()]);

        AuthLogger::loginSuccessful($user->id, $user->email);

        $verification = $this->createAndSend2FA($user);

        return [
            'user'            => $user,
            'pending_2fa'     => true,
            'verification_id' => $verification->id,
            'channel'         => $verification->channel,
            'roles'           => $user->getRoleNames(),
        ];
    }

    /**
     * Demande de réinitialisation de mot de passe
     */
    public function forgotPassword(array $data): array
    {
        $user = User::where('email', $data['login'])
            ->orWhere('telephone', $data['login'])
            ->first();

        if (!$user) {
            AuthLogger::passwordResetRequestUserNotFound($data['login']);
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        $token = Str::random(64);

        // Upsert atomique : supprime l'ancien et crée le nouveau en une passe
        PasswordResetToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token'      => Hash::make($token),
                'expires_at' => now()->addMinutes(60),
                'used'       => false,
                'used_at'    => null,
            ]
        );

        AuthLogger::passwordResetTokenGenerated($user->id, $user->email);

        /**
         * TODO: envoyer le token par email / SMS
         */

        return [
            'reset_token' => $token,
            'message'     => 'Un email de réinitialisation a été envoyé.',
        ];
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(array $data): array
    {
        // Jointure directe user + token en une seule requête
        $reset = PasswordResetToken::with('user')
            ->whereHas('user', function ($q) use ($data) {
                $q->where('email', $data['login'])
                  ->orWhere('telephone', $data['login']);
            })
            ->valid()
            ->first();

        if (!$reset) {
            AuthLogger::passwordResetInvalidToken(null);
            throw new InvalidTokenException('Token invalide ou expiré.');
        }

        $user = $reset->user;

        if (!$user) {
            AuthLogger::passwordResetUserNotFound($data['login']);
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        if (!Hash::check($data['token'], $reset->token)) {
            AuthLogger::passwordResetInvalidToken($user->id);
            throw new InvalidTokenException('Token invalide.');
        }

        if ($reset->expires_at < now()) {
            AuthLogger::passwordResetExpiredToken($user->id);
            throw new InvalidTokenException('Token expiré.');
        }

        $user->update([
            'password'     => Hash::make($data['password']),
            'last_sync_at' => now(),
        ]);

        $reset->update([
            'used'    => true,
            'used_at' => now(),
        ]);

        AuthLogger::passwordResetSuccessful($user->id, $user->email);

        return ['message' => 'Mot de passe réinitialisé avec succès'];
    }

    /**
     * Profil utilisateur connecté
     */
    public function me(User $user): array
    {
        // Eager load en une seule requête si pas déjà chargé
        $user->loadMissing('roles', 'permissions');

        return [
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    /**
     * Déconnexion
     */
    public function logout(User $user): array
    {
        $user->currentAccessToken()->delete();

        AuthLogger::userLoggedOut($user->id, $user->email);

        return ['message' => 'Déconnexion réussie'];
    }

    /**
     * Vérification du code 2FA
     */
    public function verify2fa(array $data): array
    {
        // Eager load user + roles en une seule requête
        $verification = TwoFactorVerification::with(['user.roles'])
            ->where('id', $data['verification_id'])
            ->valid()
            ->first();

        if (!$verification) {
            throw new InvalidTokenException('2FA invalide ou expirée.');
        }

        if ($verification->attempts >= 5) {
            throw new InvalidTokenException('Trop de tentatives.');
        }

        if (!Hash::check($data['code'], $verification->code_hash)) {
            $verification->increment('attempts');
            throw new InvalidTokenException('Code 2FA incorrect.');
        }

        $verification->update([
            'used'    => true,
            'used_at' => now(),
        ]);

        $user  = $verification->user;
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
            'roles' => $user->getRoleNames(),
        ];
    }

    /**
     * Création et envoi du code 2FA.
     * Le channel est déduit automatiquement depuis le modèle User.
     */
    private function createAndSend2FA(User $user): TwoFactorVerification
    {
        // Priorité email, fallback téléphone
        if (!empty($user->email)) {
            $channel    = 'email';
            $identifier = $user->email;
        } elseif (!empty($user->telephone)) {
            $channel    = 'phone';
            $identifier = $user->telephone;
        } else {
            throw new AuthenticationException('Aucun contact valide pour envoyer le code 2FA.');
        }

        if ($channel !== 'email') {
            throw new AuthenticationException('2FA par téléphone non disponible pour le moment.');
        }

        $code     = (string) random_int(100000, 999999);
        $codeHash = Hash::make($code);

        $verification = TwoFactorVerification::create([
            'user_id'    => $user->id,
            'channel'    => $channel,
            'identifier' => $identifier,
            'code_hash'  => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
            'attempts'   => 0,
        ]);

        Mail::to($identifier)->send(
            new TwoFactorEmailVerification($code, $identifier, 10)
        );

        return $verification;
    }
}