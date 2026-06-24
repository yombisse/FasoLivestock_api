<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\AnimauxExport;
use App\Exports\TransactionsExport;
use App\Exports\SanteRappelsExport;
use App\Exports\NaissancesExport;
use App\Services\AnimalService;
use App\Services\SanteRappelService;
use App\Services\FinanceTransactionService;
use App\Models\Farm;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ExportController extends Controller
{
    private AnimalService $animalService;
    private SanteRappelService $santeRappelService;
    private FinanceTransactionService $financeTransactionService;

    public function __construct(
        AnimalService $animalService,
        SanteRappelService $santeRappelService,
        FinanceTransactionService $financeTransactionService
    ) {
        $this->animalService = $animalService;
        $this->santeRappelService = $santeRappelService;
        $this->financeTransactionService = $financeTransactionService;
    }
    /**
     * Export animaux to Excel
     */
    public function animaux(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $filters = [
            'statut' => $request->statut,
            'espece_id' => $request->espece_id,
        ];

        $fileName = 'animaux_' . $farmId . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new AnimauxExport($farmId, $filters), $fileName);
    }

    /**
     * Export transactions to Excel
     */
    public function transactions(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $filters = [
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'type_transaction' => $request->type_transaction,
        ];

        $fileName = 'transactions_' . $farmId . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new TransactionsExport($farmId, $filters), $fileName);
    }

    /**
     * Export santé rappels to Excel
     */
    public function santeRappels(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $filters = [
            'statut' => $request->statut,
            'type_rappel' => $request->type_rappel,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ];

        $fileName = 'sante-rappels_' . $farmId . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new SanteRappelsExport($farmId, $filters), $fileName);
    }

    /**
     * Export naissances to Excel
     */
    public function naissances(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $filters = [
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ];

        $fileName = 'naissances_' . $farmId . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new NaissancesExport($farmId, $filters), $fileName);
    }

    /**
     * Generate PDF report for cheptel
     */
    public function rapportCheptel(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $farm = Farm::find($farmId);
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;
        $format = $request->format ?? 'download';

        // Get animals data
        $filters = ['farm_id' => $farmId];
        if ($dateDebut) $filters['date_naissance_from'] = $dateDebut;
        if ($dateFin) $filters['date_naissance_to'] = $dateFin;
        
        $animaux = $this->animalService->index($filters, 1000)->items();
        $totalAnimaux = count($animaux);
        $animauxActifs = collect($animaux)->where('statut', 'ACTIF')->count();
        $animauxInactifs = $totalAnimaux - $animauxActifs;
        $tauxActivite = $totalAnimaux > 0 ? round(($animauxActifs / $totalAnimaux) * 100, 2) : 0;

        // Repartition by espece
        $repartitionEspece = collect($animaux)->groupBy(function($item) {
            return $item->espece->nom ?? 'Non défini';
        })->map(function($group) use ($totalAnimaux) {
            return [
                'nom' => $group->first()->espece->nom ?? 'Non défini',
                'count' => $group->count(),
                'percentage' => $totalAnimaux > 0 ? round(($group->count() / $totalAnimaux) * 100, 2) : 0
            ];
        })->values();

        // Repartition by statut
        $repartitionStatut = collect($animaux)->groupBy('statut')->map(function($group) use ($totalAnimaux) {
            return [
                'statut' => $group->first()->statut,
                'count' => $group->count(),
                'percentage' => $totalAnimaux > 0 ? round(($group->count() / $totalAnimaux) * 100, 2) : 0
            ];
        })->values();

        $data = [
            'farm' => $farm,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'totalAnimaux' => $totalAnimaux,
            'animauxActifs' => $animauxActifs,
            'animauxInactifs' => $animauxInactifs,
            'tauxActivite' => $tauxActivite,
            'repartitionEspece' => $repartitionEspece,
            'repartitionStatut' => $repartitionStatut,
            'animaux' => collect($animaux)->where('statut', 'ACTIF')->take(100),
        ];

        $fileName = 'rapport-cheptel_' . $farmId . '_' . now()->format('Y-m-d') . '.pdf';
        $pdf = PDF::loadView('pdf.rapport-cheptel', $data)->setPaper('a4');

        return $format === 'stream' ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * Generate PDF report for sanitaire
     */
    public function rapportSanitaire(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $farm = Farm::find($farmId);
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;
        $format = $request->format ?? 'download';

        // Get rappels data
        $filters = ['farm_id' => $farmId];
        if ($dateDebut) $filters['date_debut'] = $dateDebut;
        if ($dateFin) $filters['date_fin'] = $dateFin;
        
        $rappels = $this->santeRappelService->index($filters, 1000)->items();
        $totalRappels = count($rappels);
        $rappelsEnAttente = collect($rappels)->where('statut', 'EN_ATTENTE')->count();
        $rappelsRealises = collect($rappels)->where('statut', 'REALISE')->count();
        $rappelsEnRetard = collect($rappels)->where('statut', 'EN_RETARD')->count();
        $tauxRealisation = $totalRappels > 0 ? round(($rappelsRealises / $totalRappels) * 100, 2) : 0;
        $tauxRealisationClass = $tauxRealisation >= 80 ? 'taux-bon' : ($tauxRealisation >= 50 ? 'taux-moyen' : 'taux-mauvais');

        // Repartition by statut
        $repartitionStatut = collect($rappels)->groupBy('statut')->map(function($group) use ($totalRappels) {
            return [
                'statut' => $group->first()->statut,
                'count' => $group->count(),
                'percentage' => $totalRappels > 0 ? round(($group->count() / $totalRappels) * 100, 2) : 0
            ];
        })->values();

        // Rappels en retard
        $rappelsEnRetardList = collect($rappels)->where('statut', 'EN_RETARD')->values();

        // Rappels à venir
        $rappelsAVenir = $this->santeRappelService->aVenir(30);

        $data = [
            'farm' => $farm,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'totalRappels' => $totalRappels,
            'rappelsEnAttente' => $rappelsEnAttente,
            'rappelsRealises' => $rappelsRealises,
            'rappelsEnRetard' => $rappelsEnRetard,
            'tauxRealisation' => $tauxRealisation,
            'tauxRealisationClass' => $tauxRealisationClass,
            'repartitionStatut' => $repartitionStatut,
            'rappelsEnRetardList' => $rappelsEnRetardList,
            'rappelsAVenir' => $rappelsAVenir,
        ];

        $fileName = 'rapport-sanitaire_' . $farmId . '_' . now()->format('Y-m-d') . '.pdf';
        $pdf = PDF::loadView('pdf.rapport-sanitaire', $data)->setPaper('a4');

        return $format === 'stream' ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * Generate PDF report for financier
     */
    public function rapportFinancier(Request $request)
    {
        $farmId = $request->input('current_farm_id');
        
        if (!$farmId) {
            return response()->json(['error' => 'Farm-ID manquant'], 400);
        }

        $farm = Farm::find($farmId);
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;
        $format = $request->format ?? 'download';

        // Get financial data
        $bilanFilters = [];
        if ($dateDebut) $bilanFilters['date_debut'] = $dateDebut;
        if ($dateFin) $bilanFilters['date_fin'] = $dateFin;

        $bilan = $this->financeTransactionService->bilanParFerme($farmId, $dateDebut, $dateFin);
        $totalRecettes = $bilan['revenus'];
        $totalDepenses = $bilan['charges'];
        $beneficeNet = $bilan['benefice'];
        $margeBeneficiaire = $totalRecettes > 0 ? round(($beneficeNet / $totalRecettes) * 100, 2) : 0;

        // Recettes par catégorie
        $recettesParCategorie = collect($this->financeTransactionService->revenusParCategorie($farmId, $dateDebut, $dateFin)['revenus_par_categorie'])->map(function($item) use ($totalRecettes) {
            $item['percentage'] = $totalRecettes > 0 ? round(($item['total'] / $totalRecettes) * 100, 2) : 0;
            return $item;
        });

        // Dépenses par catégorie
        $depensesParCategorie = collect($this->financeTransactionService->chargesParCategorie($farmId, $dateDebut, $dateFin)['charges_par_categorie'])->map(function($item) use ($totalDepenses) {
            $item['percentage'] = $totalDepenses > 0 ? round(($item['total'] / $totalDepenses) * 100, 2) : 0;
            return $item;
        });

        // Évolution mensuelle (si période > 1 mois)
        $evolutionMensuelle = [];
        if ($dateDebut && $dateFin) {
            $debut = \Carbon\Carbon::parse($dateDebut);
            $fin = \Carbon\Carbon::parse($dateFin);
            $moisDiff = $debut->diffInMonths($fin);
            
            if ($moisDiff > 0) {
                for ($i = 0; $i <= $moisDiff; $i++) {
                    $mois = $debut->copy()->addMonths($i);
                    $moisDebut = $mois->copy()->startOfMonth();
                    $moisFin = $mois->copy()->endOfMonth();
                    
                    $moisBilan = $this->financeTransactionService->bilanParFerme($farmId, $moisDebut->format('Y-m-d'), $moisFin->format('Y-m-d'));
                    
                    $evolutionMensuelle[] = [
                        'mois' => $mois->format('F Y'),
                        'recettes' => $moisBilan['revenus'],
                        'depenses' => $moisBilan['charges'],
                        'benefice' => $moisBilan['benefice'],
                    ];
                }
            }
        }

        $data = [
            'farm' => $farm,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'totalRecettes' => $totalRecettes,
            'totalDepenses' => $totalDepenses,
            'beneficeNet' => $beneficeNet,
            'margeBeneficiaire' => $margeBeneficiaire,
            'recettesParCategorie' => $recettesParCategorie,
            'depensesParCategorie' => $depensesParCategorie,
            'evolutionMensuelle' => collect($evolutionMensuelle),
        ];

        $fileName = 'rapport-financier_' . $farmId . '_' . now()->format('Y-m-d') . '.pdf';
        $pdf = PDF::loadView('pdf.rapport-financier', $data)->setPaper('a4');

        return $format === 'stream' ? $pdf->stream($fileName) : $pdf->download($fileName);
    }
}
