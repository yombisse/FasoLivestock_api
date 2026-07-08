<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SanteApiService;
use Illuminate\Http\Request;

class SanteEvenementController extends Controller
{
    public function __construct(
        private SanteApiService $santeApi
    ) {}

    /**
     * Liste des événements sanitaires
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'type'     => $request->type,
            'animal_id' => $request->animal_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-evenements.index', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.sante-evenements.create');
    }

    /**
     * Enregistrer un événement sanitaire
     */
    public function store(Request $request)
    {
        $response = $this->santeApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-evenements.index')
            ->with('success', 'Événement sanitaire créé avec succès.');
    }

    /**
     * Détail d'un événement sanitaire
     */
    public function show(string $id)
    {
        $response = $this->santeApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-evenements.index')
                ->with('error', 'Événement sanitaire introuvable.');
        }

        return view('admin.sante-evenements.show', [
            'evenement' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->santeApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-evenements.index')
                ->with('error', 'Événement sanitaire introuvable.');
        }

        return view('admin.sante-evenements.edit', [
            'evenement' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un événement sanitaire
     */
    public function update(Request $request, string $id)
    {
        $response = $this->santeApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-evenements.index')
            ->with('success', 'Événement sanitaire mis à jour avec succès.');
    }

    /**
     * Archiver un événement sanitaire
     */
    public function destroy(string $id)
    {
        $response = $this->santeApi->deleteEvenement($id);

        return redirect()
            ->route('admin.sante-evenements.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement sanitaire archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Événements sanitaires archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-evenements.trashed', [
            'evenements' => $response->data['evenements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un événement sanitaire archivé
     */
    public function restore(string $id)
    {
        $response = $this->santeApi->restore($id);

        return redirect()
            ->route('admin.sante-evenements.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Événement sanitaire restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Statistiques sanitaires
     */
    public function statistiques()
    {
        $response = $this->santeApi->statistiques();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-evenements.statistiques', [
            'statistiques' => $response->data ?? [],
        ]);
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
