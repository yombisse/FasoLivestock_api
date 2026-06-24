{{-- resources/views/admin/dashboard/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/dashboard/index.css') }}">
@endpush

@section('content')
<div class="dashboard fade-in">

    {{-- ── Bienvenue ────────────────────────────────────── --}}
    <div class="dashboard-welcome">
        <div class="welcome-left">
            <div class="welcome-avatar">
                {{ strtoupper(substr(session('admin_user.name', 'A'), 0, 1)) }}
            </div>
            <div>
                <h2>Bonjour, {{ session('admin_user.name', 'Administrateur') }} 👋</h2>
                <p>{{ now()->translatedFormat('l d F Y') }}</p>
            </div>
        </div>
        <div class="welcome-right">
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <span class="stat-number">{{ $global['total_ferms'] ?? 0 }}</span>
                    <span class="stat-label">Fermes</span>
                </div>
                <div class="welcome-stat">
                    <span class="stat-number">{{ $global['total_users'] ?? 0 }}</span>
                    <span class="stat-label">Utilisateurs</span>
                </div>
                <div class="welcome-stat">
                    <span class="stat-number">{{ $global['total_animaux'] ?? 0 }}</span>
                    <span class="stat-label">Animaux</span>
                </div>
            </div>
            <a href="{{ route('admin.farms.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nouvelle ferme
            </a>
        </div>
    </div>

    {{-- ── Liste des fermes ───────────────────────────────── --}}
    <div class="farms-overview">
        <div class="farms-overview-header">
            <h5>Fermes actives</h5>
            <a href="{{ route('admin.farms.index') }}" class="btn-link">Voir toutes</a>
        </div>
        <div class="farms-grid">
            @forelse($global['fermes'] ?? [] as $farm)
            <div class="farm-card">
                <div class="farm-card-header">
                    <div class="farm-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <div class="farm-status {{ $farm['statut'] === 'ACTIF' ? 'active' : 'inactive' }}">
                        {{ $farm['statut'] }}
                    </div>
                </div>
                <div class="farm-card-body">
                    <h6>{{ $farm['nom'] }}</h6>
                    <p class="farm-location">{{ $farm['localisation'] }}</p>
                    <div class="farm-stats">
                        <span><i class="bi bi-box2-heart"></i> {{ $farm['animaux'] }} animaux</span>
                    </div>
                </div>
                <div class="farm-card-footer">
                    <a href="{{ route('admin.farms.show', $farm['id']) }}" class="btn btn-sm btn-outline-primary">
                        Voir détails
                    </a>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-house-door"></i>
                <p>Aucune ferme enregistrée</p>
                <a href="{{ route('admin.farms.create') }}" class="btn btn-primary btn-sm">
                    Créer une ferme
                </a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Stat cards ───────────────────────────────────── --}}
    <div class="stats-grid">

        {{-- Animaux actifs --}}
        <div class="stat-card" data-color="orange">
            <div class="stat-icon">
                <i class="bi bi-box2-heart"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['cheptel']['animaux_actifs'] ?? 0 }}</div>
                <div class="stat-label">Animaux actifs</div>
                <div class="stat-trend {{ ($dashboard['cheptel']['animaux_actifs'] ?? 0) > 0 ? 'up' : 'neutral' }}">
                    <i class="bi bi-arrow-up-right"></i> {{ $dashboard['cheptel']['taux_activite'] ?? 0 }}%
                </div>
            </div>
        </div>

        {{-- Naissances ce mois --}}
        <div class="stat-card" data-color="green">
            <div class="stat-icon">
                <i class="bi bi-stars"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['reproduction']['naissances_ce_mois'] ?? 0 }}</div>
                <div class="stat-label">Naissances ce mois</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> +{{ $dashboard['reproduction']['naissances_ce_mois'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Décès ce mois --}}
        <div class="stat-card" data-color="red">
            <div class="stat-icon">
                <i class="bi bi-heartbreak"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['mouvements']['deces_ce_mois'] ?? 0 }}</div>
                <div class="stat-label">Décès ce mois</div>
                <div class="stat-trend {{ ($dashboard['mouvements']['taux_mortalite_mois'] ?? 0) > 5 ? 'down' : 'neutral' }}">
                    <i class="bi bi-arrow-down-right"></i> {{ $dashboard['mouvements']['taux_mortalite_mois'] ?? 0 }}%
                </div>
            </div>
        </div>

        {{-- Vaccinations --}}
        <div class="stat-card" data-color="purple">
            <div class="stat-icon">
                <i class="bi bi-shield-plus"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['sante']['evenements_sanitaires_ce_mois'] ?? 0 }}</div>
                <div class="stat-label">Événements santé</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> {{ $dashboard['sante']['rappels_realises_ce_mois'] ?? 0 }} réalisés
                </div>
            </div>
        </div>

        {{-- Revenus ce mois --}}
        <div class="stat-card" data-color="teal">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($dashboard['finance']['revenus_ce_mois'] ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-label">Revenus (FCFA)</div>
                <div class="stat-trend {{ ($dashboard['finance']['benefice_ce_mois'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    <i class="bi bi-arrow-up-right"></i> {{ number_format($dashboard['finance']['benefice_ce_mois'] ?? 0, 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Charges ce mois --}}
        <div class="stat-card" data-color="yellow">
            <div class="stat-icon">
                <i class="bi bi-cash"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($dashboard['finance']['charges_ce_mois'] ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-label">Charges (FCFA)</div>
                <div class="stat-trend down">
                    <i class="bi bi-arrow-down-right"></i> {{ $dashboard['finance']['nombre_charges'] ?? 0 }} tx
                </div>
            </div>
        </div>

        {{-- Mises bas à venir --}}
        <div class="stat-card" data-color="blue">
            <div class="stat-icon">
                <i class="bi bi-heart"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['reproduction']['mises_bas_a_venir_7j'] ?? 0 }}</div>
                <div class="stat-label">Mises bas (7j)</div>
                <div class="stat-trend {{ ($dashboard['reproduction']['mises_bas_a_venir_7j'] ?? 0) > 0 ? 'up' : 'neutral' }}">
                    <i class="bi bi-arrow-up-right"></i> À venir
                </div>
            </div>
        </div>

        {{-- Alertes --}}
        <div class="stat-card" data-color="{{ ($dashboard['alertes']['total_alertes'] ?? 0) > 0 ? 'red' : 'green' }}">
            <div class="stat-icon">
                <i class="bi bi-bell"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $dashboard['alertes']['total_alertes'] ?? 0 }}</div>
                <div class="stat-label">Alertes actives</div>
                <div class="stat-trend {{ ($dashboard['alertes']['total_alertes'] ?? 0) > 0 ? 'down' : 'neutral' }}">
                    <i class="bi bi-bell"></i> Action requise
                </div>
            </div>
        </div>

    </div>

    {{-- ── Charts row ───────────────────────────────────── --}}
    <div class="charts-row">

        {{-- Évolution financière --}}
        <div class="chart-card large">
            <div class="chart-card-header">
                <div>
                    <h5>Évolution financière</h5>
                    <p>Revenus vs Charges sur 6 mois</p>
                </div>
                <div class="chart-legend">
                    <span class="legend-dot green"></span> Revenus
                    <span class="legend-dot red"></span> Charges
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
                    <h5>Cheptel par espèce</h5>
                    <p>Répartition actuelle</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-herd-species"></canvas>
            </div>
        </div>

    </div>

    {{-- ── Second charts row ────────────────────────────── --}}
    <div class="charts-row">

        {{-- Cheptel par sexe --}}
        <div class="chart-card small">
            <div class="chart-card-header">
                <div>
                    <h5>Cheptel par sexe</h5>
                    <p>Mâles vs Femelles</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-herd-sex"></canvas>
            </div>
        </div>

        {{-- Événements sanitaires --}}
        <div class="chart-card large">
            <div class="chart-card-header">
                <div>
                    <h5>Événements sanitaires</h5>
                    <p>Vaccinations, Traitements, Maladies, Contrôles</p>
                </div>
                <div class="chart-legend">
                    <span class="legend-dot green"></span> Vaccinations
                    <span class="legend-dot blue"></span> Traitements
                    <span class="legend-dot red"></span> Maladies
                    <span class="legend-dot yellow"></span> Contrôles
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-health-events"></canvas>
            </div>
        </div>

    </div>

    {{-- ── Third charts row ─────────────────────────────── --}}
    <div class="charts-row">

        {{-- Mouvements --}}
        <div class="chart-card small">
            <div class="chart-card-header">
                <div>
                    <h5>Mouvements</h5>
                    <p>Achats, Ventes, Décès, Pertes</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-movements"></canvas>
            </div>
        </div>

        {{-- Reproduction --}}
        <div class="chart-card small">
            <div class="chart-card-header">
                <div>
                    <h5>Reproduction</h5>
                    <p>Naissances, Chaleurs, Saillies</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-reproduction"></canvas>
            </div>
        </div>

        {{-- Naissances par mois --}}
        <div class="chart-card large">
            <div class="chart-card-header">
                <div>
                    <h5>Naissances par mois</h5>
                    <p>Évolution sur 12 mois</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-births-month"></canvas>
            </div>
        </div>

    </div>

    {{-- ── Activité récente ─────────────────────────────── --}}
    <div class="activity-card">
        <div class="chart-card-header">
            <div>
                <h5>Activité récente</h5>
                <p>Dernières actions sur la plateforme</p>
            </div>
        </div>
        <div class="activity-list" id="activity-list">
            @foreach(range(1, 5) as $i)
            <div class="activity-item skeleton">
                <div class="activity-dot"></div>
                <div class="activity-content">
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line long"></div>
                </div>
                <div class="activity-time skeleton-line tiny"></div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('admin/js/dashboard/index.js') }}"></script>
@endpush