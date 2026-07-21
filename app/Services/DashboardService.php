<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Evenement;
use App\Models\SanteRappel;
use App\Models\Naissance;
use App\Models\Transaction;
use App\Models\Aliment;
use App\Models\Ration;
use App\Models\TypeEvenement;
use App\Models\Farm;
use App\Models\User;

class DashboardService
{
    /**
     * Obtenir le tableau de bord global pour une ferme.
     */
    public function getDashboard(string $farmId): array
    {
        return [
            'global' => $this->getGlobalStats(),
            'cheptel' => $this->getCheptelStats($farmId),
            'mouvements' => $this->getMouvementStats($farmId),
            'sante' => $this->getSanteStats($farmId),
            'reproduction' => $this->getReproductionStats($farmId),
            'alimentation' => $this->getAlimentationStats($farmId),
            'finance' => $this->getFinanceStats($farmId),
            'alertes' => $this->getAlertes($farmId),
        ];
    }

    /**
     * Statistiques globales (toutes fermes).
     */
    public function getGlobalStats(): array
    {
        return [
            'total_ferms' => Farm::count(),
            'fermes_actives' => Farm::count(),
            'total_users' => User::count(),
            'users_actifs' => User::where('is_active', true)->count(),
            'total_animaux' => Animal::count(),
            'animaux_actifs' => Animal::where('statut', 'SAIN')->count(),
            'fermes' => Farm::select('id', 'name', 'location', 'type_elevage', 'owner_id')
                ->orderBy('name')
                ->get()
                ->map(fn ($farm) => [
                    'id' => $farm->id,
                    'nom' => $farm->name,
                    'localisation' => $farm->location,
                    'statut' => 'SAIN',
                    'animaux' => Animal::where('farm_id', $farm->id)->where('statut', 'SAIN')->count(),
                ]),
        ];
    }

    /**
     * Statistiques du cheptel.
     */
    private function getCheptelStats(string $farmId): array
    {
        $totalAnimaux = Animal::where('farm_id', $farmId)->count();
        $animauxActifs = Animal::where('farm_id', $farmId)->where('statut', 'SAIN')->count();
        $animauxInactifs = $totalAnimaux - $animauxActifs;

        // Par espèce
        $parEspece = Animal::where('farm_id', $farmId)
            ->where('statut', 'SAIN')
            ->with('espece')
            ->get()
            ->groupBy('espece_id')
            ->map(fn ($group) => [
                'espece' => $group->first()->espece->nom ?? 'Non défini',
                'effectif' => $group->count(),
            ])
            ->values();

        // Par sexe
        $males = Animal::where('farm_id', $farmId)->where('statut', 'SAIN')->where('sexe', 'male')->count();
        $femelles = Animal::where('farm_id', $farmId)->where('statut', 'SAIN')->where('sexe', 'femelle')->count();

        // Taux d'activité
        $tauxActivite = $totalAnimaux > 0 ? round(($animauxActifs / $totalAnimaux) * 100, 2) : 0;

        return [
            'total_animaux' => $totalAnimaux,
            'animaux_actifs' => $animauxActifs,
            'animaux_inactifs' => $animauxInactifs,
            'par_espece' => $parEspece,
            'males' => $males,
            'femelles' => $femelles,
            'taux_activite' => $tauxActivite,
        ];
    }

    /**
     * Statistiques des mouvements.
     */
    private function getMouvementStats(string $farmId): array
    {
        // Mouvements du mois
        $mouvementsMois = Evenement::where('farm_id', $farmId)
            ->mouvements()
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        // Par type de mouvement
        $achats = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'ACHAT'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        $ventes = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'VENTE'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        $deces = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'DECES'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        $pertes = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'PERTE'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        // Taux de mortalité du mois
        $totalAnimaux = Animal::where('farm_id', $farmId)->where('statut', 'SAIN')->count();
        $tauxMortalite = $totalAnimaux > 0 ? round((($deces + $pertes) / $totalAnimaux) * 100, 2) : 0;

        return [
            'mouvements_ce_mois' => $mouvementsMois,
            'achats_ce_mois' => $achats,
            'ventes_ce_mois' => $ventes,
            'deces_ce_mois' => $deces,
            'pertes_ce_mois' => $pertes,
            'taux_mortalite_mois' => $tauxMortalite,
        ];
    }

    /**
     * Statistiques sanitaires.
     */
    private function getSanteStats(string $farmId): array
    {
        // Événements sanitaires du mois
        $evenementsMois = Evenement::where('farm_id', $farmId)
            ->sanitaires()
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        // Rappels
        $rappelsAVenir = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [now(), now()->addDays(7)])
            ->count();

        $rappelsEnRetard = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->count();

        $rappelsRealises = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'REALISE')
            ->whereMonth('date_realisee', now()->month)
            ->whereYear('date_realisee', now()->year)
            ->count();

        return [
            'evenements_sanitaires_ce_mois' => $evenementsMois,
            'rappels_a_venir_7j' => $rappelsAVenir,
            'rappels_en_retard' => $rappelsEnRetard,
            'rappels_realises_ce_mois' => $rappelsRealises,
        ];
    }

    /**
     * Statistiques de reproduction.
     */
    private function getReproductionStats(string $farmId): array
    {
        // Femelles actives
        $femellesActives = Animal::where('farm_id', $farmId)
            ->where('sexe', 'femelle')
            ->where('statut', 'SAIN')
            ->count();

        // Naissances du mois
        $naissancesMois = Naissance::where('farm_id', $farmId)
            ->whereMonth('date_naissance', now()->month)
            ->whereYear('date_naissance', now()->year)
            ->count();

        // Mises bas à venir (7 jours)
        $misesBasAVenir = Naissance::where('farm_id', $farmId)
            ->whereNotNull('date_mise_bas_prevue')
            ->where('date_mise_bas_prevue', '>=', now())
            ->where('date_mise_bas_prevue', '<=', now()->addDays(7))
            ->count();

        // Chaleurs du mois
        $chaleursMois = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CHALEUR'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        // Saillies du mois
        $sailliesMois = Evenement::where('farm_id', $farmId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'SAILLIE'))
            ->whereMonth('date_evenement', now()->month)
            ->whereYear('date_evenement', now()->year)
            ->count();

        return [
            'femelles_actives' => $femellesActives,
            'naissances_ce_mois' => $naissancesMois,
            'mises_bas_a_venir_7j' => $misesBasAVenir,
            'chaleurs_ce_mois' => $chaleursMois,
            'saillies_ce_mois' => $sailliesMois,
        ];
    }

    /**
     * Statistiques d'alimentation.
     */
    private function getAlimentationStats(string $farmId): array
    {
        // Aliments en rupture
        $alimentsEnRupture = Aliment::where('farm_id', $farmId)
            ->where('stock_actuel', '<=', 0)
            ->count();

        // Total aliments
        $totalAliments = Aliment::where('farm_id', $farmId)->count();

        // Rations distribuées ce mois
        $rationsMois = Ration::where('farm_id', $farmId)
            ->whereMonth('date_distribution', now()->month)
            ->whereYear('date_distribution', now()->year)
            ->count();

        return [
            'total_aliments' => $totalAliments,
            'aliments_en_rupture' => $alimentsEnRupture,
            'rations_distribuees_ce_mois' => $rationsMois,
        ];
    }

    /**
     * Statistiques financières.
     */
    private function getFinanceStats(string $farmId): array
    {
        // Transactions du mois
        $revenusMois = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'ENTREE')
            ->whereMonth('date_transaction', now()->month)
            ->whereYear('date_transaction', now()->year)
            ->sum('montant');

        $chargesMois = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'SORTIE')
            ->whereMonth('date_transaction', now()->month)
            ->whereYear('date_transaction', now()->year)
            ->sum('montant');

        $beneficeMois = $revenusMois - $chargesMois;

        // Marge bénéficiaire
        $margeBeneficiaire = $revenusMois > 0 ? round(($beneficeMois / $revenusMois) * 100, 2) : 0;

        // Nombre de transactions
        $nbRevenus = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'ENTREE')
            ->whereMonth('date_transaction', now()->month)
            ->whereYear('date_transaction', now()->year)
            ->count();

        $nbCharges = Transaction::where('farm_id', $farmId)
            ->where('type_transaction', 'SORTIE')
            ->whereMonth('date_transaction', now()->month)
            ->whereYear('date_transaction', now()->year)
            ->count();

        return [
            'revenus_ce_mois' => $revenusMois,
            'charges_ce_mois' => $chargesMois,
            'benefice_ce_mois' => $beneficeMois,
            'marge_beneficiaire' => $margeBeneficiaire,
            'nombre_revenus' => $nbRevenus,
            'nombre_charges' => $nbCharges,
        ];
    }

    /**
     * Alertes actives pour la ferme.
     */
    private function getAlertes(string $farmId): array
    {
        $alertes = [];

        // Rappels sanitaires en retard
        $rappelsEnRetard = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->count();

        if ($rappelsEnRetard > 0) {
            $alertes[] = [
                'type' => 'sante',
                'niveau' => 'critique',
                'message' => "{$rappelsEnRetard} rappel(s) sanitaire(s) en retard",
                'count' => $rappelsEnRetard,
            ];
        }

        // Mises bas imminentes (3 jours)
        $misesBasImminentes = Naissance::where('farm_id', $farmId)
            ->whereNotNull('date_mise_bas_prevue')
            ->where('date_mise_bas_prevue', '>=', now())
            ->where('date_mise_bas_prevue', '<=', now()->addDays(3))
            ->count();

        if ($misesBasImminentes > 0) {
            $alertes[] = [
                'type' => 'reproduction',
                'niveau' => 'urgent',
                'message' => "{$misesBasImminentes} mise(s) bas imminente(s)",
                'count' => $misesBasImminentes,
            ];
        }

        // Aliments en rupture
        $alimentsRupture = Aliment::where('farm_id', $farmId)
            ->where('stock_actuel', '<=', 0)
            ->count();

        if ($alimentsRupture > 0) {
            $alertes[] = [
                'type' => 'alimentation',
                'niveau' => 'critique',
                'message' => "{$alimentsRupture} aliment(s) en rupture de stock",
                'count' => $alimentsRupture,
            ];
        }

        // Rappels à venir (7 jours)
        $rappelsAVenir = SanteRappel::where('farm_id', $farmId)
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [now(), now()->addDays(7)])
            ->count();

        if ($rappelsAVenir > 0) {
            $alertes[] = [
                'type' => 'sante',
                'niveau' => 'info',
                'message' => "{$rappelsAVenir} rappel(s) sanitaire(s) à venir",
                'count' => $rappelsAVenir,
            ];
        }

        return [
            'total_alertes' => count($alertes),
            'alertes' => $alertes,
        ];
    }
}
