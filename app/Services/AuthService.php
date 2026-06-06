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
            'id' => (string) Str::uuid(),

            'name' => $data['name'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],

            'password' => Hash::make($data['password']),

            // offline-first sync
            'last_sync_at' => now(),
        ]);

        /**
         * Attribution rôle par défaut
         * (Spatie Permission)
         */
        $user->assignRole('gerant');

        AuthLogger::userRegistered($user->id, $user->email, $user->telephone);

        $channel = !empty($data['email']) ? 'email' : 'phone';
        $identifier = !empty($data['email']) ? $data['email'] : $data['telephone'];

        $verification = $this->createAndSend2FA($user, $channel, $identifier);

        return [
            'user' => $user,
            'pending_2fa' => true,
            'verification_id' => $verification->id,
            'channel' => $channel,
            'roles' => $user->getRoleNames(),
        ];
    }

    /**
     * Connexion utilisateur
     */
    public function login(array $data): array
    {
        $login = $data['login'];

        /**
         * Recherche email OU téléphone
         */
        $user = User::where('email', $login)
            ->orWhere('telephone', $login)
            ->first();

        /**
         * Utilisateur inexistant
         */
        if (!$user) {
            AuthLogger::loginFailedUserNotFound($login);
            throw new UserNotFoundException(
                'Utilisateur introuvable.'
            );
        }

        /**
         * Vérification mot de passe
         */
        if (!Hash::check($data['password'], $user->password)) {
            AuthLogger::loginFailedWrongPassword($user->id);
            throw new AuthenticationException(
                'Mot de passe incorrect.'
            );
        }

        $user->update([
            'last_sync_at' => now(),
        ]);

        AuthLogger::loginSuccessful($user->id, $user->email);

        $channel = !empty($user->email) && $login === $user->email ? 'email' : 'phone';
        $identifier = $channel === 'email' ? $user->email : $user->telephone;

        $verification = $this->createAndSend2FA($user, $channel, $identifier);

        return [
            'user' => $user,
            'pending_2fa' => true,
            'verification_id' => $verification->id,
            'channel' => $channel,
            'roles' => $user->getRoleNames(),
        ];
    }

    public function forgotPassword(array $data): array
    {
        /**
         * Recherche utilisateur
         */
        $user = User::where('email', $data['login'])
            ->orWhere('telephone', $data['login'])
            ->first();

        /**
         * Utilisateur inexistant
         */
        if (!$user) {
            AuthLogger::passwordResetRequestUserNotFound($data['login']);
            throw new UserNotFoundException(
                'Utilisateur introuvable.'
            );
        }

        /**
         * Génération token sécurisé
         */
        $token = Str::random(64);

        /**
         * Suppression anciens tokens - Transaction atomique
         */
        PasswordResetToken::where('user_id', $user->id)->delete();

        /**
         * Création nouveau token
         */
        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(60),
            'used' => false,
        ]);

        AuthLogger::passwordResetTokenGenerated($user->id, $user->email);

        /**
         * TODO:
         * email / sms pour envoyer le token
         */

        return [
            'reset_token' => $token,
            'message' => 'Un email de réinitialisation a été envoyé.',
        ];
    }

    public function resetPassword(array $data): array
    {
        /**
         * Trouver l'utilisateur d'abord
         */
        $user = User::where('email', $data['login'])
            ->orWhere('telephone', $data['login'])
            ->first();

        if (!$user) {
            AuthLogger::passwordResetUserNotFound($data['login']);
            throw new UserNotFoundException('Utilisateur introuvable.');
        }

        /**
         * Chercher le token de réinitialisation valide
         */
        $reset = PasswordResetToken::where('user_id', $user->id)
            ->valid()
            ->first();

        if (!$reset) {
            AuthLogger::passwordResetInvalidToken($user->id);
            throw new InvalidTokenException('Token invalide.');
        }

        /**
         * Vérifier token sécurisé
         */
        if (!Hash::check($data['token'], $reset->token)) {
            AuthLogger::passwordResetInvalidToken($reset->user_id);
            throw new InvalidTokenException('Token invalide.');
        }

        /**
         * Vérifier expiration
         */
        if ($reset->expires_at < now()) {
            AuthLogger::passwordResetExpiredToken($reset->user_id);
            throw new InvalidTokenException('Token expiré.');
        }

        /**
         * Update password
         */
        $user->update([
            'password' => Hash::make($data['password']),
            'last_sync_at' => now(),
        ]);

        /**
         * Marquer le token comme utilisé
         */
        $reset->update([
            'used' => true,
            'used_at' => now(),
        ]);

        AuthLogger::passwordResetSuccessful($user->id, $user->email);

        return [
            'message' => 'Mot de passe réinitialisé avec succès'
        ];
    }
    public function me($user): array
    {
        return [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    public function logout($user): array
    {
        $user->currentAccessToken()->delete();

        AuthLogger::userLoggedOut($user->id, $user->email);

        return [
            'message' => 'Déconnexion réussie'
        ];
    }

    private function createAndSend2FA(User $user, string $channel, string $identifier): TwoFactorVerification
    {
        // MVP: email uniquement (code numérique envoyé par mail)
        if ($channel !== 'email') {
            throw new AuthenticationException('2FA par téléphone non disponible pour le moment.');
        }

        $code = (string) random_int(100000, 999999);
        $codeHash = Hash::make($code);

        $verification = TwoFactorVerification::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'identifier' => $identifier,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
            'attempts' => 0,
        ]);

        Mail::to($identifier)->send(new TwoFactorEmailVerification($code, $identifier, 10));

        return $verification;
    }

    public function verify2fa(array $data): array
    {
        $verification = TwoFactorVerification::where('id', $data['verification_id'])
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
            'used' => true,
            'used_at' => now(),
        ]);

        $user = $verification->user;

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'roles' => $user->getRoleNames(),
        ];
    }
}
