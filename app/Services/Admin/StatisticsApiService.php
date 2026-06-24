<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class StatisticsApiService extends AdminApiService
{
    public function getDashboardCharts(array $params = []): ApiResult
    {
        return $this->get('/statistics/dashboard-charts', $params);
    }

    public function getFinancialEvolution(array $params = []): ApiResult
    {
        return $this->get('/statistics/financial-evolution', $params);
    }

    public function getRevenueByCategory(array $params = []): ApiResult
    {
        return $this->get('/statistics/revenue-by-category', $params);
    }

    public function getExpenseByCategory(array $params = []): ApiResult
    {
        return $this->get('/statistics/expense-by-category', $params);
    }

    public function getHerdBySpecies(array $params = []): ApiResult
    {
        return $this->get('/statistics/herd-by-species', $params);
    }

    public function getHerdBySex(array $params = []): ApiResult
    {
        return $this->get('/statistics/herd-by-sex', $params);
    }

    public function getMovementsStats(array $params = []): ApiResult
    {
        return $this->get('/statistics/movements-stats', $params);
    }

    public function getHealthEventsEvolution(array $params = []): ApiResult
    {
        return $this->get('/statistics/health-events-evolution', $params);
    }

    public function getReproductionStats(array $params = []): ApiResult
    {
        return $this->get('/statistics/reproduction-stats', $params);
    }

    public function getBirthsByMonth(array $params = []): ApiResult
    {
        return $this->get('/statistics/births-by-month', $params);
    }
}
