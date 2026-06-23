<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class SanteAnimalApiService extends AdminApiService
{
    public function historiqueMedical(string $animalId): ApiResult
    {
        return $this->get("/sante/animals/{$animalId}/historique-medical");
    }

    public function statistiquesSanitaires(string $animalId): ApiResult
    {
        return $this->get("/sante/animals/{$animalId}/statistiques-sanitaires");
    }

    public function resumeFerme(array $params = []): ApiResult
    {
        return $this->get('/sante/resume-ferme', $params);
    }

    public function alertesFerme(array $params = []): ApiResult
    {
        return $this->get('/sante/alertes-ferme', $params);
    }
}
