<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Helpers\ApiResponse;
use App\Models\Transaction;
use App\Models\Animal;
use App\Services\FinanceTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinanceTransactionController extends Controller
{
    private FinanceTransactionService $financeTransactionService;

    public function __construct(FinanceTransactionService $financeTransactionService)
    {
        $this->financeTransactionService = $financeTransactionService;
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

        $transactions = $this->financeTransactionService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'transactions' => $transactions->map(fn ($transaction) => $this->financeTransactionService->formatTransaction($transaction)),
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
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function store(StoreTransactionRequest $request)
    {
        $this->authorize('create', Transaction::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /finance/transactions',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $transaction = $this->financeTransactionService->store($data, $userId);

            return ApiResponse::success(
                $this->financeTransactionService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
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
            $this->financeTransactionService->formatTransaction($transaction),
            'Transaction récupérée avec succès.'
        );
    }

    /**
     * Modifier une transaction — Route Model Binding.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'PUT /finance/transactions/{id}',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'transaction_id' => $transaction->id,
        ]);

        if ($transaction->animal_id !== null) {
            return response()->json(['error' => 'Les transactions liées aux mouvements de cheptel sont immuables.'], 403);
        }

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $transaction = $this->financeTransactionService->update($transaction, $data, $userId);

            return ApiResponse::success(
                $this->financeTransactionService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
                'Transaction mise à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver une transaction — soft delete — Route Model Binding.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'DELETE /finance/transactions/{id}',
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'transaction_id' => $transaction->id,
        ]);

        if ($transaction->animal_id !== null) {
            return response()->json(['error' => 'Les transactions liées aux mouvements de cheptel sont immuables.'], 403);
        }

        $this->financeTransactionService->destroy($transaction);

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

        $transactions = $this->financeTransactionService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'transactions' => $transactions->map(fn ($transaction) => $this->financeTransactionService->formatTransaction($transaction)),
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

        $transaction = $this->financeTransactionService->restore($id);

        return ApiResponse::success(
            $this->financeTransactionService->formatTransaction($transaction->load(['farm', 'user', 'animal', 'categorie', 'evenement'])),
            'Transaction restaurée avec succès.'
        );
    }

    /**
     * Obtenir le bilan financier.
     */
    public function bilan(Request $request)
    {
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $bilan = $this->financeTransactionService->bilan($dateDebut, $dateFin);

        return ApiResponse::success($bilan, 'Bilan financier récupéré avec succès.');
    }

    /**
     * Obtenir l'historique transactionnel d'un animal.
     */
    public function historiqueAnimal(Request $request, string $animalId)
    {
        $animal = Animal::find($animalId);
        
        if (!$animal) {
            return ApiResponse::error(
                'Animal non trouvé. Veuillez synchroniser vos données locales avec le serveur avant de charger l\'historique.',
                null,
                404
            );
        }

        $this->authorize('view', $animal);

        $perPage = $request->per_page ?? 15;
        $historique = $this->financeTransactionService->historiqueAnimal($animalId, $perPage);

        return ApiResponse::success($historique, 'Historique transactionnel de l\'animal récupéré avec succès.');
    }
}
