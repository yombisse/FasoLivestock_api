<?php

namespace App\Services\Admin;

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
        $this->token = session('admin_token');
    }

    // =========================================================
    // MÉTHODES PROTÉGÉES — utilisées par les sous-services
    // =========================================================

    protected function get(string $endpoint, array $params = []): array
    {
        return $this->internalRequest('GET', $endpoint, $params);
    }

    protected function post(string $endpoint, array $data = [], bool $authenticated = true): array
    {
        return $this->internalRequest('POST', $endpoint, $data, $authenticated);
    }

    protected function put(string $endpoint, array $data = []): array
    {
        return $this->internalRequest('PUT', $endpoint, $data);
    }

    protected function patch(string $endpoint, array $data = []): array
    {
        return $this->internalRequest('PATCH', $endpoint, $data);
    }

    protected function delete(string $endpoint): array
    {
        return $this->internalRequest('DELETE', $endpoint);
    }

    // =========================================================
    // MOTEUR — appel direct au kernel Laravel (sans HTTP)
    // =========================================================

    private function internalRequest(
        string $method,
        string $endpoint,
        array  $data = [],
        bool   $authenticated = true
    ): array {
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

            return [
                'success' => $response->getStatusCode() >= 200
                          && $response->getStatusCode() < 300,
                'status'  => $response->getStatusCode(),
                'data'    => $body,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status'  => 500,
                'data'    => [
                    'message' => 'Erreur interne : ' . $e->getMessage()
                ],
            ];
        }
    }
}