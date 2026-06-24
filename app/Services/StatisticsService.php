<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\Naissance;
use App\Models\SanteRappel;
use Carbon\Carbon;

class StatisticsService
{
    /**
     * Obtenir les données pour le graphique d'évolution financière (ligne).
     */
    public function financialEvolution(string $farmId, ?string $dateDebut = null, ?string $dateFin = null, string $period = 'monthly'): array
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now()->subMonths(6);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        $query = Transaction::where('farm_id', $farmId)
            ->whereBetween('date_transaction', [$dateDebut, $dateFin]);

        $transactions = $query->get();

        // Grouper par période
        $grouped = $transactions->groupBy(function ($item) use ($period) {
            return match ($period) {
                'daily' => $item->date_transaction->format('Y-m-d'),
                'weekly' => $item->date_transaction->format('Y-W'),
                'monthly' => $item->date_transaction->format('Y-m'),
                'yearly' => $item->date_transaction->format('Y'),
                default => $item->date_transaction->format('Y-m'),
            };
        });

        $labels = [];
        $revenusData = [];
        $chargesData = [];

        foreach ($grouped as $key => $items) {
            $labels[] = $this->formatPeriodLabel($key, $period);
            $revenusData[] = $items->where('type_transaction', 'ENTREE')->sum('montant');
            $chargesData[] = $items->where('type_transaction', 'SORTIE')->sum('montant');
        }

        return [
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenus',
                    'data' => $revenusData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Charges',
                    'data' => $chargesData,
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique des revenus par catégorie (pie/doughnut).
     */
    public function revenueByCategory(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'ENTREE');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->with('categorie')->get();

        $grouped = $transactions->groupBy('categorie_id');

        $labels = [];
        $data = [];
        $colors = $this->generateColors($grouped->count());

        foreach ($grouped as $items) {
            $labels[] = $items->first()->categorie->nom_categorie ?? 'Non catégorisé';
            $data[] = $items->sum('montant');
        }

        return [
            'type' => 'doughnut',
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique des charges par catégorie (pie/doughnut).
     */
    public function expenseByCategory(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'SORTIE');

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
        }

        $transactions = $query->with('categorie')->get();

        $grouped = $transactions->groupBy('categorie_id');

        $labels = [];
        $data = [];
        $colors = $this->generateColors($grouped->count());

        foreach ($grouped as $items) {
            $labels[] = $items->first()->categorie->nom_categorie ?? 'Non catégorisé';
            $data[] = $items->sum('montant');
        }

        return [
            'type' => 'doughnut',
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique du cheptel par espèce (pie/doughnut).
     */
    public function herdBySpecies(string $farmId): array
    {
        $animals = Animal::where('farm_id', $farmId)
            ->where('statut', 'ACTIF')
            ->with('espece')
            ->get();

        $grouped = $animals->groupBy('espece_id');

        $labels = [];
        $data = [];
        $colors = $this->generateColors($grouped->count());

        foreach ($grouped as $items) {
            $labels[] = $items->first()->espece->nom ?? 'Non défini';
            $data[] = $items->count();
        }

        return [
            'type' => 'pie',
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique du cheptel par sexe (bar).
     */
    public function herdBySex(string $farmId): array
    {
        $males = Animal::where('farm_id', $farmId)
            ->where('statut', 'ACTIF')
            ->where('sexe', 'M')
            ->count();

        $femelles = Animal::where('farm_id', $farmId)
            ->where('statut', 'ACTIF')
            ->where('sexe', 'F')
            ->count();

        return [
            'type' => 'bar',
            'labels' => ['Mâles', 'Femelles'],
            'datasets' => [
                [
                    'label' => 'Effectif',
                    'data' => [$males, $femelles],
                    'backgroundColor' => ['#3B82F6', '#EC4899'],
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique des mouvements (bar).
     */
    public function movementsStats(string $farmId, ?string $dateDebut = null, ?string $dateFin = null, string $period = 'monthly'): array
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now()->subMonths(6);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        $query = Evenement::where('farm_id', $farmId)
            ->mouvements()
            ->whereBetween('date_evenement', [$dateDebut, $dateFin]);

        $evenements = $query->with('type')->get();

        $types = ['ACHAT', 'VENTE', 'DECES', 'PERTE', 'TRANSFERT', 'ABATTAGE'];
        $grouped = $evenements->groupBy(fn ($e) => strtoupper($e->type->nom_type ?? ''));

        $labels = [];
        $data = [];

        foreach ($types as $type) {
            $labels[] = ucfirst(strtolower($type));
            $data[] = $grouped->get($type)?->count() ?? 0;
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de mouvements',
                    'data' => $data,
                    'backgroundColor' => ['#10B981', '#F59E0B', '#EF4444', '#6B7280', '#8B5CF6', '#EC4899'],
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique des événements sanitaires (line).
     */
    public function healthEventsEvolution(string $farmId, ?string $dateDebut = null, ?string $dateFin = null, string $period = 'monthly'): array
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now()->subMonths(6);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        $query = Evenement::where('farm_id', $farmId)
            ->sanitaires()
            ->whereBetween('date_evenement', [$dateDebut, $dateFin]);

        $evenements = $query->with('type')->get();

        $grouped = $evenements->groupBy(function ($item) use ($period) {
            return match ($period) {
                'daily' => $item->date_evenement->format('Y-m-d'),
                'weekly' => $item->date_evenement->format('Y-W'),
                'monthly' => $item->date_evenement->format('Y-m'),
                'yearly' => $item->date_evenement->format('Y'),
                default => $item->date_evenement->format('Y-m'),
            };
        });

        $labels = [];
        $vaccinationsData = [];
        $traitementsData = [];
        $maladiesData = [];
        $controlesData = [];

        foreach ($grouped as $key => $items) {
            $labels[] = $this->formatPeriodLabel($key, $period);
            $vaccinationsData[] = $items->filter(fn ($e) => strtoupper($e->type->nom_type ?? '') === 'VACCINATION')->count();
            $traitementsData[] = $items->filter(fn ($e) => strtoupper($e->type->nom_type ?? '') === 'TRAITEMENT')->count();
            $maladiesData[] = $items->filter(fn ($e) => strtoupper($e->type->nom_type ?? '') === 'MALADIE')->count();
            $controlesData[] = $items->filter(fn ($e) => strtoupper($e->type->nom_type ?? '') === 'CONTROLE')->count();
        }

        return [
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Vaccinations',
                    'data' => $vaccinationsData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Traitements',
                    'data' => $traitementsData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Maladies',
                    'data' => $maladiesData,
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'Contrôles',
                    'data' => $controlesData,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique de reproduction (bar).
     */
    public function reproductionStats(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now()->subMonths(6);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        // Naissances
        $naissances = Naissance::where('farm_id', $farmId)
            ->whereBetween('date_naissance', [$dateDebut, $dateFin])
            ->count();

        // Chaleurs
        $chaleurs = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CHALEUR'))
            ->whereBetween('date_evenement', [$dateDebut, $dateFin])
            ->count();

        // Saillies
        $saillies = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'SAILLIE'))
            ->whereBetween('date_evenement', [$dateDebut, $dateFin])
            ->count();

        return [
            'type' => 'bar',
            'labels' => ['Naissances', 'Chaleurs', 'Saillies'],
            'datasets' => [
                [
                    'label' => 'Événements reproductifs',
                    'data' => [$naissances, $chaleurs, $saillies],
                    'backgroundColor' => ['#10B981', '#F59E0B', '#8B5CF6'],
                ],
            ],
        ];
    }

    /**
     * Obtenir les données pour le graphique des naissances par mois (line).
     */
    public function birthsByMonth(string $farmId, int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $naissances = Naissance::where('farm_id', $farmId)
            ->whereBetween('date_naissance', [$startDate, $endDate])
            ->get();

        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $labels[] = $date->format('M Y');
            $data[] = $naissances->filter(fn ($n) => $n->date_naissance >= $monthStart && $n->date_naissance <= $monthEnd)->count();
        }

        return [
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Naissances',
                    'data' => $data,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
        ];
    }

    /**
     * Obtenir toutes les données pour le tableau de bord graphique.
     */
    public function dashboardCharts(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        return [
            'financial_evolution' => $this->financialEvolution($farmId, $dateDebut, $dateFin),
            'revenue_by_category' => $this->revenueByCategory($farmId, $dateDebut, $dateFin),
            'expense_by_category' => $this->expenseByCategory($farmId, $dateDebut, $dateFin),
            'herd_by_species' => $this->herdBySpecies($farmId),
            'herd_by_sex' => $this->herdBySex($farmId),
            'movements_stats' => $this->movementsStats($farmId, $dateDebut, $dateFin),
            'health_events_evolution' => $this->healthEventsEvolution($farmId, $dateDebut, $dateFin),
            'reproduction_stats' => $this->reproductionStats($farmId, $dateDebut, $dateFin),
            'births_by_month' => $this->birthsByMonth($farmId),
        ];
    }

    /**
     * Formater le label de période pour les graphiques.
     */
    private function formatPeriodLabel(string $key, string $period): string
    {
        return match ($period) {
            'daily' => Carbon::parse($key)->format('d/m/Y'),
            'weekly' => 'Sem ' . explode('-', $key)[1] . ' ' . explode('-', $key)[0],
            'monthly' => Carbon::parse($key . '-01')->format('M Y'),
            'yearly' => $key,
            default => $key,
        };
    }

    /**
     * Générer des couleurs pour les graphiques.
     */
    private function generateColors(int $count): array
    {
        $baseColors = [
            '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16',
        ];

        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }

        return $colors;
    }
}
