<?php

namespace App\Services\Admin;

class AuthApiService extends AdminApiService
{
    public function login(string $login, string $password): array
    {
        return $this->post('/auth/login', [
            'login'    => $login,
            'password' => $password,
        ], authenticated: false);
    }

    public function verify2fa(string $verificationId, string $code): array
    {
        return $this->post('/auth/verify-2fa', [
            'verification_id' => $verificationId,
            'code'            => $code,
        ], authenticated: false);
    }

    public function logout(): array
    {
        return $this->post('/auth/logout');
    }

    public function me(): array
    {
        return $this->get('/auth/me');
    }
}