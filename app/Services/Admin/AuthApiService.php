<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class AuthApiService extends AdminApiService
{
    public function login(string $login, string $password): ApiResult
    {
        return $this->post('/auth/login', [
            'login'    => $login,
            'password' => $password,
        ], authenticated: false);
    }

    public function verify2fa(string $verificationId, string $code): ApiResult
    {
        return $this->post('/auth/verify-2fa', [
            'verification_id' => $verificationId,
            'code'            => $code,
        ], authenticated: false);
    }

    public function logout(): ApiResult
    {
        return $this->post('/auth/logout');
    }

    public function me(): ApiResult
    {
        return $this->get('/auth/me');
    }

    public function register(string $name, string $email, string $password, string $passwordConfirmation): ApiResult
    {
        return $this->post('/auth/register', [
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $passwordConfirmation,
        ], authenticated: false);
    }
}