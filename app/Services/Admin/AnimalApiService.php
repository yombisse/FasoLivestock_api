<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class AnimalApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/animals', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/animals/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/animals', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/animals/{$id}", $data);
    }

    public function deleteAnimal(string $id): ApiResult
    {
        return $this->delete("/animals/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/animals/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/animals/{$id}/restore");
    }

    public function purchase(array $data): ApiResult
    {
        return $this->post('/animals/purchase', $data);
    }

    public function birth(array $data): ApiResult
    {
        return $this->post('/animals/birth', $data);
    }

    public function importBatch(array $data): ApiResult
    {
        return $this->post('/animals/import', $data);
    }
}
