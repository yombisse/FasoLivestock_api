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
            <a href="{{ route('admin.farms.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nouvelle ferme
            </a>
        </div>
    </div>

    {{-- ── Stat cards ───────────────────────────────────── --}}
    <div class="stats-grid">

        <div class="stat-card" data-color="green">
            <div class="stat-icon">
                <i class="bi bi-house-door"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="farms">—</div>
                <div class="stat-label">Fermes</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="blue">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="users">—</div>
                <div class="stat-label">Utilisateurs</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="orange">
            <div class="stat-icon">
                <i class="bi bi-box2-heart"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="animals">—</div>
                <div class="stat-label">Animaux</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="green">
            <div class="stat-icon">
                <i class="bi bi-stars"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="births">—</div>
                <div class="stat-label">Naissances</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="red">
            <div class="stat-icon">
                <i class="bi bi-heartbreak"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="deaths">—</div>
                <div class="stat-label">Décès</div>
                <div class="stat-trend down">
                    <i class="bi bi-arrow-down-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="purple">
            <div class="stat-icon">
                <i class="bi bi-shield-plus"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="vaccinations">—</div>
                <div class="stat-label">Vaccinations</div>
                <div class="stat-trend up">
                    <i class="bi bi-arrow-up-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="teal">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="expenses">—</div>
                <div class="stat-label">Dépenses (FCFA)</div>
                <div class="stat-trend down">
                    <i class="bi bi-arrow-down-right"></i> —
                </div>
            </div>
        </div>

        <div class="stat-card" data-color="yellow">
            <div class="stat-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-stat="roles">—</div>
                <div class="stat-label">Rôles</div>
                <div class="stat-trend neutral">
                    <i class="bi bi-dash"></i> —
                </div>
            </div>
        </div>

    </div>

    {{-- ── Charts row ───────────────────────────────────── --}}
    <div class="charts-row">

        {{-- Evolution animaux --}}
        <div class="chart-card large">
            <div class="chart-card-header">
                <div>
                    <h5>Évolution des animaux</h5>
                    <p>Naissances vs Décès sur 6 mois</p>
                </div>
                <div class="chart-legend">
                    <span class="legend-dot green"></span> Naissances
                    <span class="legend-dot red"></span> Décès
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-animals-evolution"></canvas>
            </div>
        </div>

        {{-- Répartition fermes --}}
        <div class="chart-card small">
            <div class="chart-card-header">
                <div>
                    <h5>Animaux par ferme</h5>
                    <p>Répartition actuelle</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-farms-distribution"></canvas>
            </div>
        </div>

    </div>

    {{-- ── Second charts row ────────────────────────────── --}}
    <div class="charts-row">

        {{-- Vaccinations --}}
        <div class="chart-card small">
            <div class="chart-card-header">
                <div>
                    <h5>Vaccinations</h5>
                    <p>Par mois (12 derniers mois)</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-vaccinations"></canvas>
            </div>
        </div>

        {{-- Utilisateurs par rôle --}}
        <div class="chart-card large">
            <div class="chart-card-header">
                <div>
                    <h5>Utilisateurs par rôle</h5>
                    <p>Distribution des accès</p>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chart-users-roles"></canvas>
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