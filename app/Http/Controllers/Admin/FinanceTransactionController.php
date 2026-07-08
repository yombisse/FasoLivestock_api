<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FinanceTransactionApiService;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    public function __construct(
        private FinanceTransactionApiService $financeTransactionApi
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

        // Récupérer le bilan pour affichage en en-tête
        $bilanResponse = $this->financeTransactionApi->bilan();
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
    public function bilan()
    {
        $response = $this->financeTransactionApi->bilan();

        if (!$response->success) {
            return $this->handleApiError($response);
        }

        return view('admin.finance.bilan', [
            'bilan' => $response->data ?? [],
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
