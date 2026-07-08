<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class EspeceApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/especes', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/especes/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/especes', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/especes/{$id}", $data);
    }

    public function deleteEspece(string $id): ApiResult
    {
        return $this->delete("/especes/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/especes/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/especes/{$id}/restore");
    }

    public function showParametres(string $id): ApiResult
    {
        return $this->get("/especes/{$id}/parametres");
    }

    public function updateParametres(string $id, array $data): ApiResult
    {
        return $this->put("/especes/{$id}/parametres", $data);
    }
}
