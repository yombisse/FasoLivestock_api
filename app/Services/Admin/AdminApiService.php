<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Contracts\Http\Kernel;

/**
 * Classe de base — appel interne au kernel Laravel
 * Pas de HTTP externe → fonctionne en dev et production
 */
abstract class AdminApiService
{
    protected ?string $token;

    public function __construct()
    {
        $this->token = $this->getBearerToken();
    }

    // =========================================================
    // MÉTHODES PROTÉGÉES — utilisées par les sous-services
    // =========================================================

    protected function get(string $endpoint, array $params = []): ApiResult
    {
        return $this->internalRequest('GET', $endpoint, $params);
    }

    protected function post(string $endpoint, array $data = [], bool $authenticated = true): ApiResult
    {
        return $this->internalRequest('POST', $endpoint, $data, $authenticated);
    }

    protected function put(string $endpoint, array $data = []): ApiResult
    {
        return $this->internalRequest('PUT', $endpoint, $data);
    }

    protected function patch(string $endpoint, array $data = []): ApiResult
    {
        return $this->internalRequest('PATCH', $endpoint, $data);
    }

    protected function delete(string $endpoint): ApiResult
    {
        return $this->internalRequest('DELETE', $endpoint);
    }

    // =========================================================
    // MOTEUR — appel direct au kernel Laravel (sans HTTP)
    // =========================================================

    /**
     * Ce token doit être un token Sanctum valide émis par /api/auth/login.
     * Il est stocké en session lors du login admin.
     */
    private function getBearerToken(): string
    {
        return session('admin_token', '');
    }

    private function internalRequest(
        string $method,
        string $endpoint,
        array  $data = [],
        bool   $authenticated = true
    ): ApiResult {
        try {
            $headers = [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT'  => 'application/json',
            ];

            if ($authenticated && $this->token) {
                $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token;
            }

            $request = LaravelRequest::create(
                '/api' . $endpoint,
                $method,
                $method === 'GET' ? $data : [],
                [],
                [],
                $headers,
                $method !== 'GET' ? json_encode($data) : null
            );

            $kernel   = app(Kernel::class);
            $response = $kernel->handle($request);

            $body = json_decode($response->getContent(), true) ?? [];
            $statusCode = $response->getStatusCode();

            // L'API retourne une structure { success, message, data }
            // On détecte les erreurs via le code HTTP ou le champ success
            $apiSuccess = $body['success'] ?? true;
            $isError = $statusCode >= 400 || !$apiSuccess;

            if ($isError) {
                return ApiResult::error(
                    $body['message'] ?? 'Erreur serveur',
                    $statusCode,
                    $body['data'] ?? $body
                );
            }

            // Succès : on extrait uniquement le 'data' pour le ApiResult
            $data = $body['data'] ?? $body;

            return ApiResult::success(
                $data,
                $body['message'] ?? 'OK',
                $statusCode
            );

        } catch (\Throwable $e) {
            return ApiResult::error(
                'Erreur interne : ' . $e->getMessage(),
                500,
                ['message' => 'Erreur interne : ' . $e->getMessage()]
            );
        }
    }
}