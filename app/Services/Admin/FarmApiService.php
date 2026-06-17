<?php

namespace App\Services\Admin;

class FarmApiService extends AdminApiService
{
    public function getAll(array $params = []): array
    {
        return $this->get('/farms', $params);
    }

    public function find(string $id): array
    {
        return $this->get("/farms/{$id}");
    }

    public function create(array $data): array
    {
        return $this->post('/farms', $data);
    }

    public function update(string $id, array $data): array
    {
        return $this->put("/farms/{$id}", $data);
    }

    public function delete(string $id): array
    {
        return $this->delete("/farms/{$id}");
    }

    public function trashed(array $params = []): array
    {
        return $this->get('/farms/trashed', $params);
    }

    public function restore(string $id): array
    {
        return $this->patch("/farms/{$id}/restore");
    }

    public function manageUsers(string $id, array $users): array
    {
        return $this->post("/farms/{$id}/users", ['users' => $users]);
    }

    public function removeUser(string $farmId, string $userId): array
    {
        return $this->delete("/farms/{$farmId}/users/{$userId}");
    }
}