<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserApiService;
use App\Services\Admin\RoleApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class UserController extends Controller
{
    public function __construct(
        private UserApiService $userApi,
        private RoleApiService $roleApi,
    ) {}

    /**
     * Liste des utilisateurs actifs
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'role'     => $request->role,
            'per_page' => 15,
        ];

        if ($request->filled('is_active')) {
            $params['is_active'] = $request->is_active;
        }

        $response = $this->userApi->getAll($params);

        $roles = $this->roleApi->getAll();
    
        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.users.index', [
            // ApiResponse::success() retourne: { success, message, data }
            // Ici: $response['data'] = { success, message, data: { users, meta } }
            'users' => $response->data['users'] ?? [],
            'meta'  => $response->data['meta'] ?? [],
            'roles' => $roles->data['roles'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        $roles = $this->roleApi->getAll();

        return view('admin.users.create', [
            'roles' => $roles->data['roles'] ?? [],
        ]);
    }

    /**
     * Enregistrer un utilisateur
     */
    public function store(Request $request)
    {
        $response = $this->userApi->create($request->all());
     

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Détail d'un utilisateur
     */
    public function show(string $id)
    {
        $response = $this->userApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Utilisateur introuvable: ' . ($response->message ?? 'Erreur inconnue'));
        }

        $user = $response->data ?? [];
        
        // Charger les rôles si non présents
        if (empty($user['roles'])) {
            $rolesResponse = $this->roleApi->getAll();
            $allRoles = $rolesResponse->data['roles'] ?? [];
            
            // Filtrer les rôles de l'utilisateur
            // Note: l'API retourne déjà les rôles dans formatUser, donc ce cas ne devrait pas arriver
            $user['roles'] = array_filter($allRoles, function($role) use ($user) {
                return in_array($role['id'], $user['role_ids'] ?? []);
            });
        }

        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $userResponse = $this->userApi->find($id);
        $rolesResponse = $this->roleApi->getAll();
        

        if (!$userResponse->success) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Utilisateur introuvable.');
        }

        return view('admin.users.edit', [
            'user'  => $userResponse->data ?? [],
            'roles' => $rolesResponse->data['roles'] ?? [],
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, string $id)
    {
        $response = $this->userApi->update($id, $request->all());
        
        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Archiver un utilisateur
     */
    public function destroy(string $id)
    {
        $response = $this->userApi->deleteUser($id);

        return redirect()
            ->route('admin.users.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Utilisateur archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Activer / Désactiver
     */
    public function toggleActive(string $id)
    {
        $response = $this->userApi->toggleActive($id);

        return redirect()->back()->with(
            $response->success ? 'success' : 'error',
            $response->message ?? 'Erreur.'
        );
    }

    /**
     * Liste des utilisateurs archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'per_page' => 15,
        ];

        $response = $this->userApi->trashed($params);

        return view('admin.users.trashed', [
            'users' => $response->data['users'] ?? [],
            'meta'  => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un utilisateur archivé
     */
    public function restore(string $id)
    {
        $response = $this->userApi->restore($id);

        return redirect()
            ->route('admin.users.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Utilisateur restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Recherche utilisateurs pour autocomplete
     */
    public function search(Request $request)
    {
        $response = $this->userApi->getAll([
            'search'   => $request->q,
            'per_page' => 20,
        ]);

        if (!$response->success) {
            return response()->json(['success' => false, 'message' => $response->message], 500);
        }

        return response()->json($response->data['users'] ?? []);
    }

    /**
     * Tous les utilisateurs pour select
     */
    public function all(Request $request)
    {
        $response = $this->userApi->getAll([
            'per_page' => 1000,
        ]);

        if (!$response->success) {
            return response()->json(['success' => false, 'message' => $response->message], 500);
        }

        return response()->json([
            'data' => $response->data['users'] ?? []
        ]);
    }

    // =========================================================
    // PRIVÉ
    // =========================================================

    private function handleApiError($response)
    {
        if ($response->status === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);
            return redirect()->route('admin.login')->with('error', 'Session expirée.');
        }

        return redirect()->back()
            ->with('error', $response->message ?? 'Erreur serveur.');
    }
}