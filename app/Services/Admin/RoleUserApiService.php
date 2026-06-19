<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class RoleUserApiService extends AdminApiService
{
    public function getUsers(string $roleId): ApiResult
    {
        return $this->get("/roles/{$roleId}/users");
    }

    public function attachUser(string $roleId, string $userId): ApiResult
    {
        return $this->post("/roles/{$roleId}/users", [
            'user_id' => $userId,
        ]);
    }

    public function detachUser(string $roleId, string $userId): ApiResult
    {
        return $this->delete("/roles/{$roleId}/users/{$userId}");
    }
}
