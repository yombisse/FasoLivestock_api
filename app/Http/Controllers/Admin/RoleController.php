<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoleApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class RoleController extends Controller
{
    public function __construct(
        private RoleApiService $roleApi
    ) {}

    /**
     * Liste des rôles
     */
    public function index()
    {
        $response = $this->roleApi->getAll();
        if (!$response['success']) {
            return $this->handleApiError($response);
        }

        return view('admin.roles.index', [
            'roles'       => $response['data']['data']['roles'] ?? [],
            'permissions' => $response['data']['data']['permissions'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        $response = $this->roleApi->getPermissions();

        return view('admin.roles.create', [
            'permissions' => $response['data']['data'] ?? [],
        ]);
    }

    /**
     * Enregistrer un rôle
     */
    public function store(Request $request)
    {
        $response = $this->roleApi->create($request->all());

        if (!$response['success']) {
            return back()
                ->withErrors($response['data']['errors'] ?? [])
                ->with('error', $response['data']['message'] ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Détail d'un rôle
     */
    public function show(string $id)
    {
        $response = $this->roleApi->find($id);

        if (!$response['success']) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Rôle introuvable.');
        }

        return view('admin.roles.show', [
            'role' => $response['data']['data'] ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $roleResponse        = $this->roleApi->find($id);
        $permissionsResponse = $this->roleApi->getPermissions();

        if (!$roleResponse['success']) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Rôle introuvable.');
        }

        $role = $roleResponse['data']['data'] ?? [];

        // Bloquer l'édition du superadmin
        if (($role['name'] ?? '') === 'superadmin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Le rôle superadmin ne peut pas être modifié.');
        }

        return view('admin.roles.edit', [
            'role'        => $role,
            'permissions' => $permissionsResponse['data']['data'] ?? [],
        ]);
    }

    /**
     * Mettre à jour un rôle
     */
    public function update(Request $request, string $id)
    {
        $response = $this->roleApi->update($id, $request->all());

        if (!$response['success']) {
            return back()
                ->withErrors($response['data']['errors'] ?? [])
                ->with('error', $response['data']['message'] ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Archiver un rôle
     */
    public function destroy(string $id)
    {
        $response = $this->roleApi->delete($id);

        return redirect()
            ->route('admin.roles.index')
            ->with(
                $response['success'] ? 'success' : 'error',
                $response['success']
                    ? 'Rôle archivé avec succès.'
                    : ($response['data']['message'] ?? 'Erreur.')
            );
    }

    /**
     * Rôles archivés
     */
    public function trashed()
    {
        $response = $this->roleApi->trashed();

        return view('admin.roles.trashed', [
            'roles' => $response['data']['data'] ?? [],
        ]);
    }

    /**
     * Restaurer un rôle
     */
    public function restore(string $id)
    {
        $response = $this->roleApi->restore($id);

        return redirect()
            ->route('admin.roles.index')
            ->with(
                $response['success'] ? 'success' : 'error',
                $response['success']
                    ? 'Rôle restauré avec succès.'
                    : ($response['data']['message'] ?? 'Erreur.')
            );
    }

    // =========================================================
    // PRIVÉ
    // =========================================================

    private function handleApiError(array $response)
    {
        if ($response['status'] === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);
            return redirect()->route('admin.login')->with('error', 'Session expirée.');
        }

        return redirect()->back()
            ->with('error', $response['data']['message'] ?? 'Erreur serveur.');
    }
}