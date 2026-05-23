<?php

namespace App\Services;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Exceptions\AuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\UserNotFoundException;
use App\Helpers\AuthLogger;
use Illuminate\Support\Facades\Hash;
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

        /**
         * Création token Sanctum
         */
        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        AuthLogger::userRegistered($user->id, $user->email, $user->telephone);

        return [
            'user' => $user,
            'token' => $token,
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

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        AuthLogger::loginSuccessful($user->id, $user->email);

        return [
            'user' => $user,
            'token' => $token,
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

    
}