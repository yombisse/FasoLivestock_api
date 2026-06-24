<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FarmApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FarmController extends Controller
{
    public function __construct(
        private FarmApiService $farmApi
    ) {}

    /**
     * Liste des fermes
     */
    public function index(Request $request)
    {
        $response = $this->farmApi->getAll($request->only([
            'search', 'page', 'per_page'
        ]));

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.farms.index', [
            'farms' => $response->data['farms'] ?? [],
            'meta'  => $response->data['meta']  ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.farms.create');
    }

    /**
     * Enregistrer une ferme
     */
    public function store(Request $request)
    {
        $response = $this->farmApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Ferme créée avec succès.');
    }

    /**
     * Détail d'une ferme
     */
    public function show(string $id)
    {
        $response = $this->farmApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.farms.index')
                ->with('error', $response->message ?? 'Ferme introuvable.');
        }

        return view('admin.farms.show', [
            'farm' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->farmApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.farms.index')
                ->with('error', $response->message ?? 'Ferme introuvable.');
        }

        $farm = $response->data;
        
        // Convert to array if it's an object
        if (is_object($farm)) {
            $farm = (array)$farm;
        }
        
        // Ensure the ID is available
        if (!isset($farm['id'])) {
            $farm['id'] = $id;
        }

        return view('admin.farms.edit', [
            'farm' => $farm,
            'farmId' => $id,
        ]);
    }

    /**
     * Mettre à jour une ferme
     */
    public function update(Request $request, string $id)
    {
        $response = $this->farmApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Ferme mise à jour avec succès.');
    }

    /**
     * Archiver une ferme
     */
    public function destroy(string $id)
    {
        $response = $this->farmApi->delete($id);

        return redirect()
            ->route('admin.farms.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ferme archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Fermes archivées
     */
    public function trashed(Request $request)
    {
        $response = $this->farmApi->trashed($request->only(['page', 'per_page']));

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        $data = $response->data ?? [];

        return view('admin.farms.trashed', [
            'farms' => $data['farms'] ?? [],
            'meta'  => $data['meta']  ?? [],
        ]);
    }

    /**
     * Restaurer une ferme
     */
    public function restore(string $id)
    {
        $response = $this->farmApi->restore($id);

        return redirect()
            ->route('admin.farms.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ferme restaurée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Gérer les membres d'une ferme
     */
    public function manageUsers(Request $request, string $id)
    {
        $response = $this->farmApi->manageUsers($id, $request->users ?? []);

        return back()->with(
            $response->success ? 'success' : 'error',
            $response->success
                ? 'Membres mis à jour avec succès.'
                : ($response->message ?? 'Erreur.')
        );
    }

    /**
     * Retirer un utilisateur d'une ferme
     */
    public function removeUser(string $farmId, string $userId)
    {
        $response = $this->farmApi->removeUser($farmId, $userId);

        return back()->with(
            $response->success ? 'success' : 'error',
            $response->success
                ? 'Utilisateur retiré avec succès.'
                : ($response->message ?? 'Erreur.')
        );
    }

    /**
     * Gestion des erreurs API
     */
    private function handleApiError($response)
    {
        if (($response->status ?? 500) === 401) {
            session()->forget(['admin_token', 'admin_user', 'admin_token_expires_at']);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Session expirée.');
        }

        return redirect()->back()->with(
            'error',
            $response->message ?? 'Erreur serveur.'
        );
    }
}