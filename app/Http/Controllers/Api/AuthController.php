<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\Verify2FARequest;
use App\Services\AuthService;
use App\Exceptions\AuthenticationException;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\UserNotFoundException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    /**
     * Injection du service
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Inscription utilisateur
     */
    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register(
                $request->validated()
            );

            return ApiResponse::success(
                $result,
                'Utilisateur créé avec succès'
            );
        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    /**
     * Connexion utilisateur
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login(
                $request->validated()
            );

            return ApiResponse::success(
                $result,
                'Connexion réussie'
            );
        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), null, 401);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $result = $this->authService->forgotPassword(
                $request->validated()
            );

            return ApiResponse::success(
                $result,
                'Token de réinitialisation généré'
            );
        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $result = $this->authService->resetPassword(
                $request->validated()
            );

            return ApiResponse::success(
                $result,
                'Mot de passe mis à jour'
            );
        } catch (InvalidTokenException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    public function verify2fa(Verify2FARequest $request)
    {
        try {
            $result = $this->authService->verify2fa(
                $request->validated()
            );

            return ApiResponse::success(
                $result,
                '2FA vérifiée avec succès'
            );
        } catch (InvalidTokenException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }




    public function me(Request $request)
    {
        $result = $this->authService->me(
            $request->user()
        );

        return ApiResponse::success(
            $result,
            'Profil utilisateur'
        );
    }

    public function logout(Request $request)
    {
        $result = $this->authService->logout(
            $request->user()
        );

        return ApiResponse::success(
            $result,
            'Déconnexion réussie'
        );
    }
}