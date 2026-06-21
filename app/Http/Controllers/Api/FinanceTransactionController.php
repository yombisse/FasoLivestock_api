<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Helpers\ApiResponse;
use App\Models\Transaction;
use App\Services\Admin\FinanceTransactionApiService;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    private FinanceTransactionApiService $financeTransactionApiService;

    public function __construct(FinanceTransactionApiService $financeTransactionApiService)
    {
        $this->financeTransactionApiService = $financeTransactionApiService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister toutes les transactions avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'type_transaction' => $request->type_transaction,
            'animal_id' => $request->animal_id,
            'categorie_id' => $request->categorie_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'revenus' => $request->revenus,
            'charges' => $request->charges,
        ];

        $transactions = $this->financeTransactionApiService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'transactions' => $transactions->map(fn ($transaction) => $this->financeTransactionApiService->formatTransaction($transaction)),
            'meta'  => [
                'total'        => $transactions->total(),
                'per_page'     => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
            ],
        ], 'Transactions récupérées avec succès.');
    }

    /**
     * Créer une transaction.
     */
    public function store(StoreTransactionRequest $request)
    {
        $this->authorize('create', Transaction::class);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $transaction = $this->financeTransactionApiService->store($data, $userId);

            return ApiResponse::success(
                $this->financeTransactionApiService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
                'Transaction créée avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'une transaction — Route Model Binding.
     */
    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement']);

        return ApiResponse::success(
            $this->financeTransactionApiService->formatTransaction($transaction),
            'Transaction récupérée avec succès.'
        );
    }

    /**
     * Modifier une transaction — Route Model Binding.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $transaction = $this->financeTransactionApiService->update($transaction, $data, $userId);

            return ApiResponse::success(
                $this->financeTransactionApiService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
                'Transaction mise à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver une transaction — soft delete — Route Model Binding.
     */
    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $this->financeTransactionApiService->destroy($transaction);

        return ApiResponse::success(null, 'Transaction archivée avec succès.');
    }

    /**
     * Lister les transactions archivées.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'type_transaction' => $request->type_transaction,
            'animal_id' => $request->animal_id,
            'categorie_id' => $request->categorie_id,
        ];

        $transactions = $this->financeTransactionApiService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'transactions' => $transactions->map(fn ($transaction) => $this->financeTransactionApiService->formatTransaction($transaction)),
            'meta'  => [
                'total'        => $transactions->total(),
                'per_page'     => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
            ],
        ], 'Transactions archivées récupérées avec succès.');
    }

    /**
     * Restaurer une transaction archivée.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Transaction::class);

        $transaction = $this->financeTransactionApiService->restore($id);

        return ApiResponse::success(
            $this->financeTransactionApiService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
            'Transaction restaurée avec succès.'
        );
    }
}
