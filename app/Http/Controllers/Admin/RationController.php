<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RationApiService;
use Illuminate\Http\Request;

class RationController extends Controller
{
    public function __construct(
        private RationApiService $rationApi
    ) {}

    /**
     * Liste des rations
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->rationApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.rations.index', [
            'rations' => $response->data['rations'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.rations.create');
    }

    /**
     * Enregistrer une ration
     */
    public function store(Request $request)
    {
        $response = $this->rationApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.rations.index')
            ->with('success', 'Ration créée avec succès.');
    }

    /**
     * Détail d'une ration
     */
    public function show(string $id)
    {
        $response = $this->rationApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.rations.index')
                ->with('error', 'Ration introuvable.');
        }

        return view('admin.rations.show', [
            'ration' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->rationApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.rations.index')
                ->with('error', 'Ration introuvable.');
        }

        return view('admin.rations.edit', [
            'ration' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour une ration
     */
    public function update(Request $request, string $id)
    {
        $response = $this->rationApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.rations.index')
            ->with('success', 'Ration mise à jour avec succès.');
    }

    /**
     * Archiver une ration
     */
    public function destroy(string $id)
    {
        $response = $this->rationApi->deleteRation($id);

        return redirect()
            ->route('admin.rations.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ration archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Rations archivées
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->rationApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.rations.trashed', [
            'rations' => $response->data['rations'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer une ration archivée
     */
    public function restore(string $id)
    {
        $response = $this->rationApi->restore($id);

        return redirect()
            ->route('admin.rations.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Ration restaurée avec succès.'
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
