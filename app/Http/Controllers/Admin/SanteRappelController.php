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
    public function index(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeRappelApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.index', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
            'farmId'  => $farm,
        ]);
    }

    /**
     * Formulaire création
     */
    public function create(string $farm)
    {
        return view('admin.sante-rappels.create', [
            'farmId' => $farm,
        ]);
    }

    /**
     * Enregistrer un rappel sanitaire
     */
    public function store(Request $request, string $farm)
    {
        $data = $request->all();
        $data['current_farm_id'] = $farm;

        $response = $this->santeRappelApi->create($data);

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-rappels.index', ['farm' => $farm])
            ->with('success', 'Rappel sanitaire créé avec succès.');
    }

    /**
     * Détail d'un rappel sanitaire
     */
    public function show(string $farm, string $id)
    {
        $response = $this->santeRappelApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-rappels.index', ['farm' => $farm])
                ->with('error', 'Rappel sanitaire introuvable.');
        }

        return view('admin.sante-rappels.show', [
            'rappel' => $response->data ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $farm, string $id)
    {
        $response = $this->santeRappelApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.sante-rappels.index', ['farm' => $farm])
                ->with('error', 'Rappel sanitaire introuvable.');
        }

        return view('admin.sante-rappels.edit', [
            'rappel' => $response->data ?? [],
            'farmId' => $farm,
        ]);
    }

    /**
     * Mettre à jour un rappel sanitaire
     */
    public function update(Request $request, string $farm, string $id)
    {
        $response = $this->santeRappelApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.sante-rappels.index', ['farm' => $farm])
            ->with('success', 'Rappel sanitaire mis à jour avec succès.');
    }

    /**
     * Archiver un rappel sanitaire
     */
    public function destroy(string $farm, string $id)
    {
        $response = $this->santeRappelApi->deleteRappel($id);

        return redirect()
            ->route('admin.sante-rappels.index', ['farm' => $farm])
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
    public function trashed(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeRappelApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.trashed', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
            'farmId'  => $farm,
        ]);
    }

    /**
     * Restaurer un rappel sanitaire archivé
     */
    public function restore(string $farm, string $id)
    {
        $response = $this->santeRappelApi->restore($id);

        return redirect()
            ->route('admin.sante-rappels.index', ['farm' => $farm])
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
    public function aVenir(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeRappelApi->aVenir($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.a-venir', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
            'farmId'  => $farm,
        ]);
    }

    /**
     * Rappels en retard
     */
    public function enRetard(Request $request, string $farm)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
            'current_farm_id' => $farm,
        ];

        $response = $this->santeRappelApi->enRetard($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.sante-rappels.en-retard', [
            'rappels' => $response->data['rappels'] ?? [],
            'meta'    => $response->data['meta'] ?? [],
            'farmId'  => $farm,
        ]);
    }

    /**
     * Marquer un rappel comme réalisé
     */
    public function marquerRealise(string $farm, string $id)
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
