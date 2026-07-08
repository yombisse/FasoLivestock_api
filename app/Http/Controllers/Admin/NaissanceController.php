<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\NaissanceApiService;
use Illuminate\Http\Request;

class NaissanceController extends Controller
{
    public function __construct(
        private NaissanceApiService $naissanceApi
    ) {}

    /**
     * Liste des naissances
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->naissanceApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.naissances.index', [
            'naissances' => $response->data['naissances'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        $farmId = session('current_farm_id');
        $femellesEligibles = [];
        $warningMessage = null;

        if ($farmId) {
            $response = $this->reproductionApi->femellesEligibles($farmId);
            
            if ($response->success) {
                $femellesEligibles = $response->data ?? [];
                
                if (empty($femellesEligibles)) {
                    $warningMessage = 'Aucune femelle avec une gestation confirmée en cours. Veuillez d\'abord enregistrer une GESTATION_CONFIRMEE dans les événements reproductifs.';
                }
            }
        }

        return view('admin.naissances.create', [
            'femellesEligibles' => $femellesEligibles,
            'warningMessage' => $warningMessage,
        ]);
    }

    /**
     * Enregistrer une naissance
     */
    public function store(Request $request)
    {
        $response = $this->naissanceApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.naissances.index')
            ->with('success', 'Naissance créée avec succès.');
    }

    /**
     * Détail d'une naissance
     */
    public function show(string $id)
    {
        $response = $this->naissanceApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.naissances.index')
                ->with('error', 'Naissance introuvable.');
        }

        return view('admin.naissances.show', [
            'naissance' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->naissanceApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.naissances.index')
                ->with('error', 'Naissance introuvable.');
        }

        return view('admin.naissances.edit', [
            'naissance' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour une naissance
     */
    public function update(Request $request, string $id)
    {
        $response = $this->naissanceApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.naissances.index')
            ->with('success', 'Naissance mise à jour avec succès.');
    }

    /**
     * Archiver une naissance
     */
    public function destroy(string $id)
    {
        $response = $this->naissanceApi->deleteNaissance($id);

        return redirect()
            ->route('admin.naissances.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Naissance archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Naissances archivées
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->naissanceApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.naissances.trashed', [
            'naissances' => $response->data['naissances'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer une naissance archivée
     */
    public function restore(string $id)
    {
        $response = $this->naissanceApi->restore($id);

        return redirect()
            ->route('admin.naissances.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Naissance restaurée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Prévisions de mises bas
     */
    public function previsions(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->naissanceApi->previsions($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.naissances.previsions', [
            'previsions' => $response->data['previsions'] ?? [],
            'meta'       => $response->data['meta'] ?? [],
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
