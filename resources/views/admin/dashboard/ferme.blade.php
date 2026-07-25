@extends('admin.layouts.ferme')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/dashboard/index.css') }}">
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Tableau de bord</li>
@endsection

@section('content')
<div x-data="dashboardIndex()" x-init="init()" class="fade-in" data-farm-id="{{ $farmId }}">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord</h2>
            <p>Vue d'ensemble de votre activité</p>
        </div>
        <div class="d-flex gap-2">
            <button @click="refreshAll()" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise"></i>
                Actualiser
            </button>
        </div>
    </div>

    {{-- ── Stats globales (4 cartes KPI) ─────────────────── --}}
    <div class="kpi-cards-row">
        
        {{-- Animaux actifs --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-success">
                <i class="bi bi-cow"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Animaux actifs</div>
                <div class="kpi-value" x-text="formatNumber(stats.animaux_actifs)">
                    {{ $dashboard['cheptel']['animaux_actifs'] ?? 0 }}
                </div>
                <div class="kpi-sub">
                    <span class="text-muted">Total: </span>
                    <span x-text="formatNumber({{ $dashboard['cheptel']['total_animaux'] ?? 0 }})">
                        {{ $dashboard['cheptel']['total_animaux'] ?? 0 }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Revenus ce mois --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-primary">
                <i class="bi bi-currency-euro"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Revenus ce mois</div>
                <div class="kpi-value" x-text="formatCurrency(stats.revenus_ce_mois)">
                    {{ number_format($dashboard['finance']['revenus_ce_mois'] ?? 0, 0, ',', ' ') }}
                </div>
                <div class="kpi-sub">
                    <span class="text-muted">Bénéfice: </span>
                    <span :class="($dashboard['finance']['benefice_ce_mois'] ?? 0) >= 0 ? 'text-success' : 'text-danger'">
                        {{ number_format($dashboard['finance']['benefice_ce_mois'] ?? 0, 0, ',', ' ') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Alertes --}}
        <div class="kpi-card">
            <div class="kpi-icon" :class="stats.alertes > 0 ? 'bg-danger' : 'bg-secondary'">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Alertes</div>
                <div class="kpi-value" x-text="stats.alertes">
                    {{ $dashboard['alertes']['total_alertes'] ?? 0 }}
                </div>
                <div class="kpi-sub">
                    <span class="text-muted">Actions requises</span>
                </div>
            </div>
        </div>

        {{-- Événements santé --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-info">
                <i class="bi bi-heart-pulse"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Santé ce mois</div>
                <div class="kpi-value" x-text="stats.evenements_sante">
                    {{ $dashboard['sante']['evenements_sanitaires_ce_mois'] ?? 0 }}
                </div>
                <div class="kpi-sub">
                    <span class="text-muted">Rappels: </span>
                    <span>{{ $dashboard['sante']['rappels_a_venir_7j'] ?? 0 }}</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Section Alertes ───────────────────────────────── --}}
    @if(isset($dashboard['alertes']['alertes']) && count($dashboard['alertes']['alertes']) > 0)
    <div class="alerts-section">
        <h5 class="section-title">
            <i class="bi bi-bell"></i>
            Alertes actives
        </h5>
        <div class="alerts-list">
            @foreach($dashboard['alertes']['alertes'] as $alerte)
            <div class="alert-item alert-item-{{ $alerte['niveau'] }}">
                <div class="alert-icon">
                    <i class="bi bi-{{ $alerte['niveau'] === 'critique' ? 'x-circle' : ($alerte['niveau'] === 'urgent' ? 'exclamation-circle' : 'info-circle') }}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-message">{{ $alerte['message'] }}</div>
                    <div class="alert-count">{{ $alerte['count'] }} élément(s)</div>
                </div>
                <div class="alert-badge alert-badge-{{ $alerte['niveau'] }}">
                    {{ ucfirst($alerte['niveau']) }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Stats détaillées par module ──────────────────────── --}}
    <div class="stats-grid">

        {{-- Cheptel --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-cow me-2"></i>Cheptel</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Mâles</span>
                    <span class="stat-value">{{ $dashboard['cheptel']['males'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Femelles</span>
                    <span class="stat-value">{{ $dashboard['cheptel']['femelles'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Taux d'activité</span>
                    <span class="stat-value">{{ $dashboard['cheptel']['taux_activite'] ?? 0 }}%</span>
                </div>
                @if(isset($dashboard['cheptel']['par_espece']))
                <div class="stat-divider"></div>
                <div class="stat-subtitle">Par espèce</div>
                @foreach($dashboard['cheptel']['par_espece'] as $espece)
                <div class="stat-row">
                    <span class="stat-label">{{ $espece['espece'] }}</span>
                    <span class="stat-value">{{ $espece['effectif'] }}</span>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        {{-- Mouvements --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-arrow-left-right me-2"></i>Mouvements ce mois</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Achats</span>
                    <span class="stat-value text-success">{{ $dashboard['mouvements']['achats_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Ventes</span>
                    <span class="stat-value text-primary">{{ $dashboard['mouvements']['ventes_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Décès</span>
                    <span class="stat-value text-danger">{{ $dashboard['mouvements']['deces_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Pertes</span>
                    <span class="stat-value text-warning">{{ $dashboard['mouvements']['pertes_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-row">
                    <span class="stat-label">Taux mortalité</span>
                    <span class="stat-value">{{ $dashboard['mouvements']['taux_mortalite_mois'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        {{-- Santé --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-heart-pulse me-2"></i>Santé</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Événements ce mois</span>
                    <span class="stat-value">{{ $dashboard['sante']['evenements_sanitaires_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Rappels à venir (7j)</span>
                    <span class="stat-value text-info">{{ $dashboard['sante']['rappels_a_venir_7j'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">En retard</span>
                    <span class="stat-value text-danger">{{ $dashboard['sante']['rappels_en_retard'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Réalisés ce mois</span>
                    <span class="stat-value text-success">{{ $dashboard['sante']['rappels_realises_ce_mois'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Reproduction --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-heart me-2"></i>Reproduction</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Femelles actives</span>
                    <span class="stat-value">{{ $dashboard['reproduction']['femelles_actives'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Naissances ce mois</span>
                    <span class="stat-value text-success">{{ $dashboard['reproduction']['naissances_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Mises bas à venir (7j)</span>
                    <span class="stat-value text-warning">{{ $dashboard['reproduction']['mises_bas_a_venir_7j'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Chaleurs ce mois</span>
                    <span class="stat-value">{{ $dashboard['reproduction']['chaleurs_ce_mois'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Saillies ce mois</span>
                    <span class="stat-value">{{ $dashboard['reproduction']['saillies_ce_mois'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Alimentation --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-basket me-2"></i>Alimentation</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Total aliments</span>
                    <span class="stat-value">{{ $dashboard['alimentation']['total_aliments'] ?? 0 }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">En rupture</span>
                    <span class="stat-value" :class="($dashboard['alimentation']['aliments_en_rupture'] ?? 0) > 0 ? 'text-danger' : 'text-success'">
                        {{ $dashboard['alimentation']['aliments_en_rupture'] ?? 0 }}
                    </span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Rations ce mois</span>
                    <span class="stat-value">{{ $dashboard['alimentation']['rations_distribuees_ce_mois'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Finance --}}
        <div class="stat-card">
            <div class="stat-card-header">
                <h5><i class="bi bi-currency-euro me-2"></i>Finance ce mois</h5>
            </div>
            <div class="stat-card-body">
                <div class="stat-row">
                    <span class="stat-label">Revenus</span>
                    <span class="stat-value text-success">{{ number_format($dashboard['finance']['revenus_ce_mois'] ?? 0, 0, ',', ' ') }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Charges</span>
                    <span class="stat-value text-danger">{{ number_format($dashboard['finance']['charges_ce_mois'] ?? 0, 0, ',', ' ') }}</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-row">
                    <span class="stat-label">Bénéfice</span>
                    <span class="stat-value" :class="({{ $dashboard['finance']['benefice_ce_mois'] ?? 0 }}) >= 0 ? 'text-success' : 'text-danger'">
                        {{ number_format($dashboard['finance']['benefice_ce_mois'] ?? 0, 0, ',', ' ') }}
                    </span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Marge bénéficiaire</span>
                    <span class="stat-value">{{ $dashboard['finance']['marge_beneficiaire'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Graphiques ──────────────────────────────────────── --}}
    <div class="charts-section">
        <h5 class="section-title">
            <i class="bi bi-bar-chart"></i>
            Graphiques
        </h5>
        
        <div class="charts-row">
            
            {{-- Évolution financière --}}
            <div class="chart-card large">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Finances</span>
                        <h5>Évolution financière</h5>
                        <p>Revenus vs Charges sur 6 mois</p>
                    </div>
                    <div class="chart-legend">
                        <span><span class="legend-dot green"></span> Revenus</span>
                        <span><span class="legend-dot red"></span> Charges</span>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-financial-evolution"></canvas>
                </div>
            </div>

            {{-- Cheptel par espèce --}}
            <div class="chart-card small">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Cheptel</span>
                        <h5>Par espèce</h5>
                        <p>Répartition actuelle</p>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-herd-species"></canvas>
                </div>
            </div>

        </div>

        <div class="charts-row">
            
            {{-- Naissances par mois --}}
            <div class="chart-card large">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Reproduction</span>
                        <h5>Naissances par mois</h5>
                        <p>Évolution sur 12 mois</p>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-births-month"></canvas>
                </div>
            </div>

            {{-- Cheptel par sexe --}}
            <div class="chart-card small">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Cheptel</span>
                        <h5>Par sexe</h5>
                        <p>Répartition mâles/femelles</p>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-herd-sex"></canvas>
                </div>
            </div>

        </div>

        <div class="charts-row">
            
            {{-- Revenus par catégorie --}}
            <div class="chart-card small">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Finances</span>
                        <h5>Revenus par catégorie</h5>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-revenue-category"></canvas>
                </div>
            </div>

            {{-- Charges par catégorie --}}
            <div class="chart-card small">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Finances</span>
                        <h5>Charges par catégorie</h5>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-expense-category"></canvas>
                </div>
            </div>

            {{-- Statistiques mouvements --}}
            <div class="chart-card small">
                <div class="chart-card-header">
                    <div>
                        <span class="section-eyebrow">Mouvements</span>
                        <h5>Statistiques</h5>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-movements-stats"></canvas>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Données des graphiques passées depuis le contrôleur
        const dashboardChartsData = @json($charts);
        
        // Données du dashboard pour Alpine.js
        window.dashboardData = @json($dashboard);
    </script>
    <script src="{{ asset('admin/js/dashboard/index.js') }}"></script>
@endpush
