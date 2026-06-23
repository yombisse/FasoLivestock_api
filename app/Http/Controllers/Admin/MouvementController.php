<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MouvementApiService;
use Illuminate\Http\Request;

class MouvementController extends Controller
{
    public function __construct(
        private MouvementApiService $mouvementApi
    ) {}

    /**
     * Liste des mouvements
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->mouvementApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.index', [
            'mouvements' => $response->data['mouvements'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.mouvements.create');
    }

    /**
     * Enregistrer un mouvement
     */
    public function store(Request $request)
    {
        $response = $this->mouvementApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.mouvements.index')
            ->with('success', 'Mouvement créé avec succès.');
    }

    /**
     * Détail d'un mouvement
     */
    public function show(string $id)
    {
        $response = $this->mouvementApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.mouvements.index')
                ->with('error', 'Mouvement introuvable.');
        }

        return view('admin.mouvements.show', [
            'mouvement' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->mouvementApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.mouvements.index')
                ->with('error', 'Mouvement introuvable.');
        }

        return view('admin.mouvements.edit', [
            'mouvement' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un mouvement
     */
    public function update(Request $request, string $id)
    {
        $response = $this->mouvementApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.mouvements.index')
            ->with('success', 'Mouvement mis à jour avec succès.');
    }

    /**
     * Archiver un mouvement
     */
    public function destroy(string $id)
    {
        $response = $this->mouvementApi->delete($id);

        return redirect()
            ->route('admin.mouvements.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Mouvement archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Historique des mouvements d'un animal
     */
    public function animalHistory(string $animalId)
    {
        $response = $this->mouvementApi->animalHistory($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.animal-history', [
            'animal'      => $response->data['animal'] ?? [],
            'historique'  => $response->data['historique'] ?? [],
        ]);
    }

    /**
     * Traçabilité complète d'un animal
     */
    public function trace(string $animalId)
    {
        $response = $this->mouvementApi->trace($animalId);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.trace', [
            'animal'  => $response->data['animal'] ?? [],
            'trace'   => $response->data['trace'] ?? [],
        ]);
    }

    /**
     * Statistiques des mouvements
     */
    public function statistiques()
    {
        $response = $this->mouvementApi->statistiques();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.mouvements.statistiques', [
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
