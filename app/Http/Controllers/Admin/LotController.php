<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\LotApiService;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function __construct(
        private LotApiService $lotApi
    ) {}

    /**
     * Liste des lots
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->lotApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.lots.index', [
            'lots' => $response->data['lots'] ?? [],
            'meta' => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.lots.create');
    }

    /**
     * Enregistrer un lot
     */
    public function store(Request $request)
    {
        $response = $this->lotApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.lots.index')
            ->with('success', 'Lot créé avec succès.');
    }

    /**
     * Détail d'un lot
     */
    public function show(string $id)
    {
        $response = $this->lotApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.lots.index')
                ->with('error', 'Lot introuvable.');
        }

        return view('admin.lots.show', [
            'lot' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->lotApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.lots.index')
                ->with('error', 'Lot introuvable.');
        }

        return view('admin.lots.edit', [
            'lot' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un lot
     */
    public function update(Request $request, string $id)
    {
        $response = $this->lotApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.lots.index')
            ->with('success', 'Lot mis à jour avec succès.');
    }

    /**
     * Archiver un lot
     */
    public function destroy(string $id)
    {
        $response = $this->lotApi->delete($id);

        return redirect()
            ->route('admin.lots.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Lot archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Lots archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->lotApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.lots.trashed', [
            'lots' => $response->data['lots'] ?? [],
            'meta' => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un lot archivé
     */
    public function restore(string $id)
    {
        $response = $this->lotApi->restore($id);

        return redirect()
            ->route('admin.lots.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Lot restauré avec succès.'
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
