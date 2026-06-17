<?php

namespace App\Services\Admin;

class UserApiService extends AdminApiService
{
    public function getAll(array $params = []): array
    {
        return $this->get('/users', $params);
    }

    public function find(string $id): array
    {
        return $this->get("/users/{$id}");
    }

    public function create(array $data): array
    {
        return $this->post('/users', $data);
    }

    public function update(string $id, array $data): array
    {
        return $this->put("/users/{$id}", $data);
    }

    public function delete(string $id): array
    {
        return $this->delete("/users/{$id}");
    }

    public function toggleActive(string $id): array
    {
        return $this->patch("/users/{$id}/toggle-active");
    }

    public function trashed(array $params = []): array
    {
        return $this->get('/users/trashed', $params);
    }

    public function restore(string $id): array
    {
        return $this->patch("/users/{$id}/restore");
    }
}