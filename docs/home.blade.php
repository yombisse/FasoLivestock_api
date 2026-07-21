{{-- resources/views/admin/dashboard/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('admin/css/dashboard/index.css') }}">
@endpush

@section('content')
<div class="dashboard fade-in" data-farm-id="{{ session('current_farm_id') }}">

    {{-- ── Bienvenue ────────────────────────────────────── --}}
    <div class="dashboard-welcome">
        <div class="welcome-left">
            <div class="welcome-avatar">
                <span>{{ strtoupper(substr(session('admin_user.name', 'A'), 0, 1)) }}</span>
            </div>
            <div>
                <p class="welcome-eyebrow">{{ now()->translatedFormat('l d F Y') }}</p>
                <h2>Bonjour, {{ session('admin_user.name', 'Administrateur') }}</h2>
            </div>
        </div>
        <div class="welcome-right">
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <i class="bi bi-house-door"></i>
                    <div>
                        <span class="stat-number">{{ $global['total_ferms'] ?? 0 }}</span>
                        <span class="stat-label">Fermes</span>
                    </div>
                </div>
                <div class="welcome-stat">
                    <i class="bi bi-people"></i>
                    <div>
                        <span class="stat-number">{{ $global['total_users'] ?? 0 }}</span>
                        <span class="stat-label">Utilisateurs</span>
                    </div>
                </div>
                <div class="welcome-stat">
                    <i class="bi bi-box2-heart"></i>
                    <div>
                        <span class="stat-number">{{ $global['total_animaux'] ?? 0 }}</span>
                        <span class="stat-label">Animaux</span>
                    </div>
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
            <div>
                <span class="section-eyebrow">Vue d'ensemble</span>
                <h5>Fermes actives</h5>
            </div>
            <a href="{{ route('admin.farms.index') }}" class="btn-link">
                Voir toutes <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="farms-grid">
            @forelse($global['fermes'] ?? [] as $farm)
            <a href="{{ route('admin.farms.show', $farm['id']) }}" class="farm-card">
                <div class="farm-card-header">
                    <div class="farm-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <span class="farm-status {{ $farm['statut'] === 'ACTIF' ? 'active' : 'inactive' }}">
                        <i class="bi bi-circle-fill"></i> {{ $farm['statut'] }}
                    </span>
                </div>
                <div class="farm-card-body">
                    <h6>{{ $farm['nom'] }}</h6>
                    <p class="farm-location"><i class="bi bi-geo-alt"></i> {{ $farm['localisation'] }}</p>
                </div>
                <div class="farm-card-footer">
                    <span class="farm-metric"><i class="bi bi-box2-heart"></i> {{ $farm['animaux'] }} animaux</span>
                    <span class="farm-card-cta">Voir détails <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
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

    {{-- ─── Stat cards (simplifié à 4 essentielles) ───────────────────────────────────── --}}
    <div class="stats-grid" x-data="dashboardStats()" x-init="loadStats()">

        {{-- Animaux actifs --}}
        <div class="stat-card" data-color="orange">
            <template x-if="loading">
                <div class="stat-skeleton">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-value"></div>
                    <div class="skeleton-label"></div>
                </div>
            </template>
            <template x-if="!loading">
                <div style="display: contents;">
                    <div class="stat-icon">
                        <i class="bi bi-box2-heart"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" x-text="stats.animaux_actifs">0</div>
                        <div class="stat-label">Animaux actifs</div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Revenus ce mois --}}
        <div class="stat-card" data-color="teal">
            <template x-if="loading">
                <div class="stat-skeleton">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-value"></div>
                    <div class="skeleton-label"></div>
                </div>
            </template>
            <template x-if="!loading">
                <div style="display: contents;">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" x-text="formatNumber(stats.revenus_ce_mois)">0</div>
                        <div class="stat-label">Revenus ce mois</div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Alertes --}}
        <div class="stat-card" :data-color="stats.alertes > 0 ? 'red' : 'green'">
            <template x-if="loading">
                <div class="stat-skeleton">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-value"></div>
                    <div class="skeleton-label"></div>
                </div>
            </template>
            <template x-if="!loading">
                <div style="display: contents;">
                    <div class="stat-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" x-text="stats.alertes">0</div>
                        <div class="stat-label">Alertes actives</div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Événements santé --}}
        <div class="stat-card" data-color="purple">
            <template x-if="loading">
                <div class="stat-skeleton">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-value"></div>
                    <div class="skeleton-label"></div>
                </div>
            </template>
            <template x-if="!loading">
                <div style="display: contents;">
                    <div class="stat-icon">
                        <i class="bi bi-shield-plus"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value" x-text="stats.evenements_sante">0</div>
                        <div class="stat-label">Événements santé</div>
                    </div>
                </div>
            </template>
        </div>

    </div>

    {{-- ── Charts row (réduit à 3 essentiels) ───────────────────────────────────── --}}
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

    {{-- ── Second charts row ────────────────────────────── --}}
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

    </div>

    {{-- ── Activité récente ─────────────────────────────── --}}
    <div class="activity-card">
        <div class="chart-card-header">
            <div>
                <span class="section-eyebrow">Journal</span>
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