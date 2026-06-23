<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AnimalApiService;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function __construct(
        private AnimalApiService $animalApi
    ) {}

    /**
     * Liste des animaux
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->animalApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.animals.index', [
            'animals' => $response->data['animals'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.animals.create');
    }

    /**
     * Enregistrer un animal
     */
    public function store(Request $request)
    {
        $response = $this->animalApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.animals.index')
            ->with('success', 'Animal créé avec succès.');
    }

    /**
     * Détail d'un animal
     */
    public function show(string $id)
    {
        $response = $this->animalApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.animals.index')
                ->with('error', 'Animal introuvable.');
        }

        return view('admin.animals.show', [
            'animal' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->animalApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.animals.index')
                ->with('error', 'Animal introuvable.');
        }

        return view('admin.animals.edit', [
            'animal' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un animal
     */
    public function update(Request $request, string $id)
    {
        $response = $this->animalApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.animals.index')
            ->with('success', 'Animal mis à jour avec succès.');
    }

    /**
     * Archiver un animal
     */
    public function destroy(string $id)
    {
        $response = $this->animalApi->delete($id);

        return redirect()
            ->route('admin.animals.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Animal archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Animaux archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->animalApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.animals.trashed', [
            'animals' => $response->data['animals'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un animal archivé
     */
    public function restore(string $id)
    {
        $response = $this->animalApi->restore($id);

        return redirect()
            ->route('admin.animals.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Animal restauré avec succès.'
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
