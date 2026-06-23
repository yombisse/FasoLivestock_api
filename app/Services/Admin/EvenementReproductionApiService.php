<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class EvenementReproductionApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/reproduction/evenements', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/reproduction/evenements/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/reproduction/evenements', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/reproduction/evenements/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/reproduction/evenements/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/reproduction/evenements/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/reproduction/evenements/{$id}/restore");
    }
}
