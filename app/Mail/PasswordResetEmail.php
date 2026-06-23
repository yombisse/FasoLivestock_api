<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public string $email;
    public int $expiresMinutes;

    /**
     * @param string $token token de réinitialisation
     * @param string $email email de l'utilisateur
     * @param int $expiresMinutes temps d'expiration en minutes
     */
    public function __construct(string $token, string $email, int $expiresMinutes = 60)
    {
        $this->token = $token;
        $this->email = $email;
        $this->expiresMinutes = $expiresMinutes;
    }

    public function build(): static
    {
        return $this->subject('Réinitialisation de votre mot de passe')
            ->view('emails.password_reset')
            ->with([
                'token' => $this->token,
                'email' => $this->email,
                'expiresMinutes' => $this->expiresMinutes,
            ]);
    }
}
