<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class AlimentApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/alimentation/aliments', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/alimentation/aliments/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/alimentation/aliments', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/alimentation/aliments/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/alimentation/aliments/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/alimentation/aliments/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/alimentation/aliments/{$id}/restore");
    }

    public function approvisionner(string $id, array $data): ApiResult
    {
        return $this->post("/alimentation/aliments/{$id}/approvisionner", $data);
    }

    public function ajusterStock(string $id, array $data): ApiResult
    {
        return $this->post("/alimentation/aliments/{$id}/ajuster-stock", $data);
    }

    public function enRupture(array $params = []): ApiResult
    {
        return $this->get('/alimentation/aliments/en-rupture', $params);
    }

    public function historiqueStock(string $id): ApiResult
    {
        return $this->get("/alimentation/aliments/{$id}/historique-stock");
    }

    public function statistiques(string $id): ApiResult
    {
        return $this->get("/alimentation/aliments/{$id}/statistiques");
    }
}
