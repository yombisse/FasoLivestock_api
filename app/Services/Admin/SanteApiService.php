<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class SanteApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/sante/evenements', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/sante/evenements/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/sante/evenements', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/sante/evenements/{$id}", $data);
    }

    public function deleteEvenement(string $id): ApiResult
    {
        return $this->delete("/sante/evenements/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/sante/evenements/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/sante/evenements/{$id}/restore");
    }

    public function statistiques(array $params = []): ApiResult
    {
        return $this->get('/sante/evenements/statistiques', $params);
    }

    public function resumeFerme(string $farmId): ApiResult
    {
        return $this->get("/sante/resume-ferme/{$farmId}");
    }

    public function alertesFerme(string $farmId): ApiResult
    {
        return $this->get("/sante/alertes-ferme/{$farmId}");
    }
}
