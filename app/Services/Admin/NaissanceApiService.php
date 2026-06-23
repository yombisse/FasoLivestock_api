<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class NaissanceApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/reproduction/naissances', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/reproduction/naissances/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/reproduction/naissances', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/reproduction/naissances/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/reproduction/naissances/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/reproduction/naissances/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/reproduction/naissances/{$id}/restore");
    }

    public function previsions(array $params = []): ApiResult
    {
        return $this->get('/reproduction/naissances/previsions', $params);
    }
}
