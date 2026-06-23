<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SanteRappelApiService;
use Illuminate\Http\Request;

class SanteRappelController extends Controller
{
    public function __construct(
        private SanteRappelApiService $santeRappelApi
    ) {}

    /**
     * Liste des rappels sanitaires
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeRappelApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.index', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.sante-rappels.create');
    }

    /**
     * Enregistrer un rappel sanitaire
     */
    public function store(Request $request)
    {
        $response = $this->santeRappelApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-rappels.index')
            ->with('success', 'Rappel sanitaire créé avec succès.');
    }

    /**
     * Détail d'un rappel sanitaire
     */
    public function show(string $id)
    {
        $response = $this->santeRappelApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-rappels.index')
                ->with('error', 'Rappel sanitaire introuvable.');
        }

        return view('admin.sante-rappels.show', [
            'rappel' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->santeRappelApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-rappels.index')
                ->with('error', 'Rappel sanitaire introuvable.');
        }

        return view('admin.sante-rappels.edit', [
            'rappel' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour un rappel sanitaire
     */
    public function update(Request $request, string $id)
    {
        $response = $this->santeRappelApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-rappels.index')
            ->with('success', 'Rappel sanitaire mis à jour avec succès.');
    }

    /**
     * Archiver un rappel sanitaire
     */
    public function destroy(string $id)
    {
        $response = $this->santeRappelApi->delete($id);

        return redirect()
            ->route('admin.sante-rappels.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Rappel sanitaire archivé avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Rappels sanitaires archivés
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeRappelApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.trashed', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer un rappel sanitaire archivé
     */
    public function restore(string $id)
    {
        $response = $this->santeRappelApi->restore($id);

        return redirect()
            ->route('admin.sante-rappels.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Rappel sanitaire restauré avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Rappels à venir
     */
    public function aVenir(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeRappelApi->aVenir($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.a-venir', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Rappels en retard
     */
    public function enRetard(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->santeRappelApi->enRetard($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.en-retard', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Marquer un rappel comme réalisé
     */
    public function marquerRealise(string $id)
    {
        $response = $this->santeRappelApi->marquerRealise($id);

        return back()->with(
            $response->success ? 'success' : 'error',
            $response->success
                ? 'Rappel marqué comme réalisé.'
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
