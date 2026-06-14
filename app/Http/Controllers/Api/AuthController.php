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
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return ApiResponse::success($result, 'Utilisateur créé avec succès', 201);

        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return ApiResponse::success($result, 'Connexion réussie');

        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), null, 401);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->forgotPassword($request->validated());

            return ApiResponse::success($result, 'Token de réinitialisation généré');

        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resetPassword($request->validated());

            return ApiResponse::success($result, 'Mot de passe mis à jour');

        } catch (UserNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        } catch (InvalidTokenException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    public function verify2fa(Verify2FARequest $request): JsonResponse
    {
        try {
            $result = $this->authService->verify2fa($request->validated());

            return ApiResponse::success($result, '2FA vérifiée avec succès');

        } catch (InvalidTokenException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Route protégée — l'utilisateur est déjà résolu par Sanctum.
     * On utilise le type-hint User directement plutôt que $request->user()
     * pour profiter du model binding implicite de Laravel.
     */
    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return ApiResponse::success($result, 'Profil utilisateur');
    }

    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return ApiResponse::success($result, 'Déconnexion réussie');
    }
}