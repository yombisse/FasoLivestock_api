<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AlimentApiService;
use Illuminate\Http\Request;

class AlimentController extends Controller
{
    public function __construct(
        private AlimentApiService $alimentApi
    ) {}

    /**
     * Liste des aliments
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->alimentApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.aliments.index', [
            'aliments' => $response->data['aliments'] ?? [],
            'meta'     => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.aliments.create');
    }

    /**
     * Enregistrer un aliment
     */
    public function store(Request $request)
    {
        $response = $this->alimentApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.aliments.index')
            ->with('success', 'Aliment créé avec succès.');
    }

    /**
     * Détail d'un aliment
     */
    public function show(string $id)
    {
        $response = $this->alimentApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.aliments.index')
                ->with('error', 'Aliment introuvable.');
        }

        return view('admin.aliments.show', [
            'aliment' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->alimentApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.aliments.index')
                ->with('error', 'Aliment introuvable.');
        }

        return view('admin.aliments.edit', [
            'aliment' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un aliment
     */
    public function update(Request $request, string $id)
    {
        $response = $this->alimentApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.aliments.index')
            ->with('success', 'Aliment mis à jour avec succès.');
    }

    /**
     * Archiver un aliment
     */
    public function destroy(string $id)
    {
        $response = $this->alimentApi->delete($id);

        return redirect()
            ->route('admin.aliments.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Aliment archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Aliments archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->alimentApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.aliments.trashed', [
            'aliments' => $response->data['aliments'] ?? [],
            'meta'     => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un aliment archivé
     */
    public function restore(string $id)
    {
        $response = $this->alimentApi->restore($id);

        return redirect()
            ->route('admin.aliments.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Aliment restauré avec succès.'
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
