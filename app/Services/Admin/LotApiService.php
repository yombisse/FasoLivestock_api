<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class LotApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/lots', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/lots/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/lots', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/lots/{$id}", $data);
    }

    public function deleteLot(string $id): ApiResult
    {
        return $this->delete("/lots/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/lots/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/lots/{$id}/restore");
    }

    public function animals(string $id): ApiResult
    {
        return $this->get("/lots/{$id}/animals");
    }

    public function assignAnimals(string $id, array $data): ApiResult
    {
        return $this->post("/lots/{$id}/animals", $data);
    }

    public function removeAnimal(string $lotId, string $animalId): ApiResult
    {
        return $this->delete("/lots/{$lotId}/animals/{$animalId}");
    }

    public function statistiques(string $id): ApiResult
    {
        return $this->get("/lots/{$id}/stats");
    }
}
