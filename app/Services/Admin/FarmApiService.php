<?php

namespace App\Services\Admin;

use App\DTOs\ApiResult;

class FarmApiService extends AdminApiService
{
    public function getAll(array $params = []): ApiResult
    {
        return $this->get('/farms', $params);
    }

    public function find(string $id): ApiResult
    {
        return $this->get("/farms/{$id}");
    }

    public function create(array $data): ApiResult
    {
        return $this->post('/farms', $data);
    }

    public function update(string $id, array $data): ApiResult
    {
        return $this->put("/farms/{$id}", $data);
    }

    public function delete(string $id): ApiResult
    {
        return $this->delete("/farms/{$id}");
    }

    public function trashed(array $params = []): ApiResult
    {
        return $this->get('/farms/trashed', $params);
    }

    public function restore(string $id): ApiResult
    {
        return $this->patch("/farms/{$id}/restore");
    }

    public function manageUsers(string $id, array $users): ApiResult
    {
        return $this->post("/farms/{$id}/users", ['users' => $users]);
    }

    public function removeUser(string $farmId, string $userId): ApiResult
    {
        return $this->delete("/farms/{$farmId}/users/{$userId}");
    }
}