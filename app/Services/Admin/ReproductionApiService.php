<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class ReproductionApiService extends AdminApiService
{
    public function dashboard(array $params = []): ApiResult
    {
        return $this->get('/reproduction/dashboard', $params);
    }

    public function forecast(array $params = []): ApiResult
    {
        return $this->get('/reproduction/forecast', $params);
    }

    public function historiqueAnimal(string $animalId): ApiResult
    {
        return $this->get("/reproduction/animals/{$animalId}/historique");
    }

    public function statistiquesAnimal(string $animalId): ApiResult
    {
        return $this->get("/reproduction/animals/{$animalId}/stats");
    }

    public function femellesEligibles(string $farmId): ApiResult
    {
        return $this->get('/reproduction/femelles-eligibles', [
            'farm_id' => $farmId
        ]);
    }
}
