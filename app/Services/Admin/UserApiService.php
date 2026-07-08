<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class UserApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/users', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/users/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/users', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/users/{$id}", $data);
    }

    public function deleteUser(string $id): ApiResult
    {
        return $this->delete("/users/{$id}");
    }

    public function toggleActive(string $id): ApiResult
    {
        return $this->patch("/users/{$id}/toggle-active");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/users/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->patch("/users/{$id}/restore");
    }
}