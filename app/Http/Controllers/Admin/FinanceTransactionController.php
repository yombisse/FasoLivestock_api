<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FinanceTransactionApiService;
use App\Services\Admin\FarmApiService;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    public function __construct(
        private FinanceTransactionApiService $financeTransactionApi,
        private FarmApiService $farmApi
    ) {}

    /**
     * Liste des transactions
     */
    public function index(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'type_transaction' => $request->type_transaction,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->financeTransactionApi->getAll($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Récupérer le bilan pour affichage en en-tête (avec les mêmes filtres)
        $bilanParams = array_filter([
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);
        $bilanResponse = $this->financeTransactionApi->bilan($bilanParams);
        $bilan = $bilanResponse->success ? ($bilanResponse->data ?? []) : [];

        return view('admin.finance.index', [
            'transactions' => $response->data['transactions'] ?? [],
            'meta'         => $response->data['meta'] ?? [],
            'bilan'        => $bilan,
        ]);
    }

    /**
     * Formulaire création
     */
    public function create()
    {
        return view('admin.finance.create');
    }

    /**
     * Enregistrer une transaction
     */
    public function store(Request $request)
    {
        $response = $this->financeTransactionApi->create($request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.finance.index')
            ->with('success', 'Transaction créée avec succès.');
    }

    /**
     * Détail d'une transaction
     */
    public function show(string $id)
    {
        $response = $this->financeTransactionApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.finance.index')
                ->with('error', 'Transaction introuvable.');
        }

        return view('admin.finance.show', [
            'transaction' => $response->data ?? [],
        ]);
    }

    /**
     * Formulaire édition
     */
    public function edit(string $id)
    {
        $response = $this->financeTransactionApi->find($id);

        if (!$response->success) {
            return redirect()
                ->route('admin.finance.index')
                ->with('error', 'Transaction introuvable.');
        }

        return view('admin.finance.edit', [
            'transaction' => $response->data ?? [],
        ]);
    }

    /**
     * Mettre à jour une transaction
     */
    public function update(Request $request, string $id)
    {
        $response = $this->financeTransactionApi->update($id, $request->all());

        if (!$response->success) {
            return back()
                ->withErrors($response->data['errors'] ?? [])
                ->with('error', $response->message ?? 'Erreur.')
                ->withInput();
        }

        return redirect()
            ->route('admin.finance.index')
            ->with('success', 'Transaction mise à jour avec succès.');
    }

    /**
     * Archiver une transaction
     */
    public function destroy(string $id)
    {
        $response = $this->financeTransactionApi->deleteTransaction($id);

        return redirect()
            ->route('admin.finance.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Transaction archivée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Transactions archivées
     */
    public function trashed(Request $request)
    {
        $params = [
            'search'   => $request->search,
            'page'     => $request->page,
            'per_page' => 15,
        ];

        $response = $this->financeTransactionApi->trashed($params);

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.finance.trashed', [
            'transactions' => $response->data['transactions'] ?? [],
            'meta'         => $response->data['meta'] ?? [],
        ]);
    }

    /**
     * Restaurer une transaction archivée
     */
    public function restore(string $id)
    {
        $response = $this->financeTransactionApi->restore($id);

        return redirect()
            ->route('admin.finance.index')
            ->with(
                $response->success ? 'success' : 'error',
                $response->success
                    ? 'Transaction restaurée avec succès.'
                    : ($response->message ?? 'Erreur.')
            );
    }

    /**
     * Bilan financier
     */
    public function bilan(Request $request)
    {
        $bilanParams = array_filter([
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);
        
        // Si une ferme est sélectionnée, utiliser l'endpoint par ferme
        if ($request->farm_id) {
            $response = $this->financeTransactionApi->bilanParFerme($request->farm_id, $bilanParams);
            $stats = []; // Pas de stats globales quand on filtre par ferme
        } else {
            $response = $this->financeTransactionApi->bilan($bilanParams);
            
            // Récupérer les statistiques globales pour plus de détails (seulement si pas de filtre ferme)
            $statsParams = array_filter([
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
            ]);
            $statsResponse = $this->financeTransactionApi->statistiquesGlobales($statsParams);
            $stats = $statsResponse->success ? ($statsResponse->data ?? []) : [];
        }

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        // Récupérer les fermes de l'utilisateur pour le filtre
        $farmsResponse = $this->farmApi->getAll();
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        return view('admin.finance.bilan', [
            'bilan' => $response->data ?? [],
            'stats' => $stats,
            'farms' => $farms,
            'selectedFarm' => $request->farm_id,
            'filters' => [
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
            ],
        ]);
    }

    /**
     * Statistiques globales financières
     */
    public function statistiquesGlobales()
    {
        $response = $this->financeTransactionApi->statistiquesGlobales();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.finance.statistiques-globales', [
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
