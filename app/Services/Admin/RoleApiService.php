<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class RoleApiService extends AdminApiService
{
    public function getAll(): ApiResult
    {
        return $this->get('/roles');
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/roles/{$id}");
    }

    public function getPermissions(): ApiResult
    {
        return $this->get('/roles/permissions');
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/roles', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/roles/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/roles/{$id}");
    }

    public function trashed(): ApiResult
    {
        return $this->get('/roles/trashed');
    }

    public function restore(string $id): ApiResult
    {
        return $this->patch("/roles/{$id}/restore");
    }
}