<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class AuthLogger
{
    /**
     * Log pour une inscription réussie
     */
    public static function userRegistered(string $userId, string $email, ?string $phone = null): void
    {
        Log::info('New user registered', [
            'user_id' => $userId,
            'email' => $email,
            'telephone' => $phone,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une tentative de connexion - utilisateur non trouvé
     */
    public static function loginFailedUserNotFound(string $login): void
    {
        Log::warning('Failed login attempt - user not found', [
            'login' => $login,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une tentative de connexion - mot de passe incorrect
     */
    public static function loginFailedWrongPassword(string $userId): void
    {
        Log::warning('Failed login - incorrect password', [
            'user_id' => $userId,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une connexion réussie
     */
    public static function loginSuccessful(string $userId, string $email): void
    {
        Log::info('User successfully logged in', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une demande de réinitialisation - utilisateur non trouvé
     */
    public static function passwordResetRequestUserNotFound(string $login): void
    {
        Log::warning('Password reset request for non-existent user', [
            'login' => $login,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour la génération d'un token de réinitialisation
     */
    public static function passwordResetTokenGenerated(string $userId, string $email): void
    {
        Log::info('Password reset token generated', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une tentative de réinitialisation - token invalide
     */
    public static function passwordResetInvalidToken(?string $userId = null): void
    {
        $data = [
            'ip' => request()->ip(),
            'timestamp' => now(),
        ];

        if ($userId) {
            $data['user_id'] = $userId;
        }

        Log::warning('Password reset with invalid token', $data);
    }

    /**
     * Log pour une tentative de réinitialisation - token expiré
     */
    public static function passwordResetExpiredToken(string $userId): void
    {
        Log::warning('Password reset with expired token', [
            'user_id' => $userId,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une tentative de réinitialisation - utilisateur non trouvé
     */
    public static function passwordResetUserNotFound(string $login): void
    {
        Log::warning('Password reset for non-existent user', [
            'login' => $login,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une réinitialisation de mot de passe réussie
     */
    public static function passwordResetSuccessful(string $userId, string $email): void
    {
        Log::info('Password successfully reset', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log pour une déconnexion
     */
    public static function userLoggedOut(string $userId, string $email): void
    {
        Log::info('User logged out', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log générique pour les événements d'authentification
     */
    public static function logEvent(string $event, string $level = 'info', array $context = []): void
    {
        $context = array_merge([
            'ip' => request()->ip(),
            'timestamp' => now(),
        ], $context);

        Log::{$level}($event, $context);
    }
}
