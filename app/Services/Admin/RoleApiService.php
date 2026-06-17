<?php

namespace App\Services\Admin;

class RoleApiService extends AdminApiService
{
    public function getAll(): array
    {
        return $this->get('/roles');
    }

    public function find(string $id): array
    {
        return $this->get("/roles/{$id}");
    }

    public function getPermissions(): array
    {
        return $this->get('/roles/permissions');
    }

    public function create(array $data): array
    {
        return $this->post('/roles', $data);
    }

    public function update(string $id, array $data): array
    {
        return $this->put("/roles/{$id}", $data);
    }

    public function delete(string $id): array
    {
        return $this->delete("/roles/{$id}");
    }

    public function trashed(): array
    {
        return $this->get('/roles/trashed');
    }

    public function restore(string $id): array
    {
        return $this->patch("/roles/{$id}/restore");
    }
}