<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class RationApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/alimentation/rations', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/alimentation/rations/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/alimentation/rations', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/alimentation/rations/{$id}", $data);
    }

    public function deleteRation(string $id): ApiResult
    {
        return $this->delete("/alimentation/rations/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/alimentation/rations/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/alimentation/rations/{$id}/restore");
    }

    public function distribuerLot(array $data): ApiResult
    {
        return $this->post('/alimentation/rations/distribuer-lot', $data);
    }

    public function distribuerAnimaux(array $data): ApiResult
    {
        return $this->post('/alimentation/rations/distribuer-animaux', $data);
    }

    public function historiqueAnimal(string $animalId): ApiResult
    {
        return $this->get("/alimentation/animals/{$animalId}/historique-alimentaire");
    }

    public function historiqueLot(string $lotId): ApiResult
    {
        return $this->get("/alimentation/lots/{$lotId}/historique-alimentaire");
    }

    public function consommationAnimal(string $animalId): ApiResult
    {
        return $this->get("/alimentation/animals/{$animalId}/consommation");
    }

    public function consommationLot(string $lotId): ApiResult
    {
        return $this->get("/alimentation/lots/{$lotId}/consommation");
    }

    public function statistiquesGlobales(array $params = []): ApiResult
    {
        return $this->get('/alimentation/statistiques-globales', $params);
    }
}
