<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\EspeceApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EspeceController extends Controller
{
    public function __construct(
        private EspeceApiService $especeApi
    ) {}

    /**
     * Liste des espèces
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->especeApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.especes.index', [
            'especes' => $response->data['especes'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.especes.create');
    }

    /**
     * Enregistrer une espèce
     */
    public function store(Request $request)
    {
        $response = $this->especeApi->create($request->all());
        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.especes.index')
            ->with('success', 'Espèce créée avec succès.');
    }

    /**
     * Détail d'une espèce
     */
    public function show(string $id)
    {
        $response = $this->especeApi->find($id);
        if (!$response->success) {
            return redirect()
                ->route('admin.especes.index')
                ->with('error', 'Espèce introuvable.');
        }

        return view('admin.especes.show', [
            'espece' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->especeApi->find($id);
        if (!$response->success) {
            return redirect()
                ->route('admin.especes.index')
                ->with('error', 'Espèce introuvable.');
        }

        return view('admin.especes.edit', [
            'espece' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour une espèce
     */
    public function update(Request $request, string $id)
    {
        $response = $this->especeApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.especes.index')
            ->with('success', 'Espèce mise à jour avec succès.');
    }

    /**
     * Archiver une espèce
     */
    public function destroy(string $id)
    {
        $response = $this->especeApi->deleteEspece($id);

        return redirect()
            ->route('admin.especes.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Espèce archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Espèces archivées
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->especeApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.especes.trashed', [
            'especes' => $response->data['especes'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer une espèce archivée
     */
    public function restore(string $id)
    {
        $response = $this->especeApi->restore($id);

        return redirect()
            ->route('admin.especes.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Espèce restaurée avec succès.'
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

    /**
     * Formulaire paramètres espèce
     */
    public function editParametres(string $id)
    {
        $response = $this->especeApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.especes.index')
                ->with('error', 'Espèce introuvable.');
        }

        return view('admin.especes.parametres', [
            'espece' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour les paramètres d'une espèce
     */
    public function updateParametres(Request $request, string $id)
    {
        $response = $this->especeApi->updateParametres($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.especes.show', $id)
            ->with('success', 'Paramètres mis à jour avec succès.');
    }
}
