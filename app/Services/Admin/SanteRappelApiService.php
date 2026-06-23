<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class SanteRappelApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/sante/rappels', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/sante/rappels/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/sante/rappels', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/sante/rappels/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/sante/rappels/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/sante/rappels/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/sante/rappels/{$id}/restore");
    }

    public function aVenir(array $params = []): ApiResult
    {
        return $this->get('/sante/rappels/a-venir', $params);
    }

    public function enRetard(array $params = []): ApiResult
    {
        return $this->get('/sante/rappels/en-retard', $params);
    }

    public function marquerRealise(string $id): ApiResult
    {
        return $this->post("/sante/rappels/{$id}/marquer-realise");
    }
}
