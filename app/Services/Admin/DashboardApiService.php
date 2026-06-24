<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class DashboardApiService extends AdminApiService
{
    public function getDashboard(array $params = []): ApiResult
    {
        return $this->get('/dashboard', $params);
    }

    public function getGlobalStats(array $params = []): ApiResult
    {
        return $this->get('/dashboard/global', $params);
    }
}
