<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class FinanceTransactionApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/finance/transactions', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/finance/transactions/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/finance/transactions', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/finance/transactions/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/finance/transactions/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/finance/transactions/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->post("/finance/transactions/{$id}/restore");
    }

    public function revenus(array $params = []): ApiResult
    {
        return $this->get('/finance/revenus', $params);
    }

    public function revenusParPeriode(array $params = []): ApiResult
    {
        return $this->get('/finance/revenus/par-periode', $params);
    }

    public function revenusParCategorie(array $params = []): ApiResult
    {
        return $this->get('/finance/revenus/par-categorie', $params);
    }

    public function revenusParAnimal(array $params = []): ApiResult
    {
        return $this->get('/finance/revenus/par-animal', $params);
    }

    public function charges(array $params = []): ApiResult
    {
        return $this->get('/finance/charges', $params);
    }

    public function chargesParPeriode(array $params = []): ApiResult
    {
        return $this->get('/finance/charges/par-periode', $params);
    }

    public function chargesParCategorie(array $params = []): ApiResult
    {
        return $this->get('/finance/charges/par-categorie', $params);
    }

    public function bilan(array $params = []): ApiResult
    {
        return $this->get('/finance/bilan', $params);
    }

    public function bilanParPeriode(array $params = []): ApiResult
    {
        return $this->get('/finance/bilan/par-periode', $params);
    }

    public function bilanParFerme(string $farmId): ApiResult
    {
        return $this->get("/finance/bilan/par-ferme/{$farmId}");
    }

    public function statistiquesGlobales(array $params = []): ApiResult
    {
        return $this->get('/finance/statistiques-globales', $params);
    }
}
