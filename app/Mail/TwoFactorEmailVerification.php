<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $email;
    public int $expiresMinutes;

    /**
     * @param string $code code numérique
     */
    public function __construct(string $code, string $email, int $expiresMinutes = 10)
    {
        $this->code = $code;
        $this->email = $email;
        $this->expiresMinutes = $expiresMinutes;
    }

    public function build(): static
    {
        return $this->subject('Votre code de vérification (2FA)')
            ->view('emails.two_factor_email_verification')
            ->with([
                'code' => $this->code,
                'email' => $this->email,
                'expiresMinutes' => $this->expiresMinutes,
            ]);
    }
}

