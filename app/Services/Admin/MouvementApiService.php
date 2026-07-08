<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class MouvementApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/mouvements', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/mouvements/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/mouvements', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/mouvements/{$id}", $data);
    }

    public function deleteMouvement(string $id): ApiResult
    {
        return $this->delete("/mouvements/{$id}");
    }

    public function animalHistory(string $animalId): ApiResult
    {
        return $this->get("/animals/{$animalId}/mouvements");
    }

    public function trace(string $animalId): ApiResult
    {
        return $this->get("/mouvements/trace/{$animalId}");
    }

    public function statistiques(array $params = []): ApiResult
    {
        return $this->get('/mouvements/statistiques', $params);
    }
}
