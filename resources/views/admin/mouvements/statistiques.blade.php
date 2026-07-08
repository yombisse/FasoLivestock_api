@extends('admin.layouts.app')

@section('title', 'Statistiques mouvements')
@section('page-title', 'Statistiques mouvements')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/statistiques.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.mouvements.index') }}">Mouvements</a>
    </li>
    <li class="breadcrumb-item active">Statistiques</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-bar-chart me-2 text-primary"></i>Statistiques des mouvements</h2>
            <p>Vue d'ensemble des mouvements du cheptel</p>
        </div>
        <a href="{{ route('admin.mouvements.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            Retour
        </a>
    </div>

    {{-- ── Cards statistiques ─────────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $statistiques['total_mouvements'] ?? 0 }}</div>
                    <div class="stat-label">Total mouvements</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <i class="bi bi-cart-plus"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $statistiques['total_achats'] ?? 0 }}</div>
                    <div class="stat-label">Total achats</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="bi bi-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $statistiques['total_ventes'] ?? 0 }}</div>
                    <div class="stat-label">Total ventes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ $statistiques['total_deces'] ?? 0 }}</div>
                    <div class="stat-label">Total décès</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Répartition par type ───────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Répartition par type</h5>
                </div>
                <div class="card-body">
                    @if(isset($statistiques['par_type']) && count($statistiques['par_type']) > 0)
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th class="text-end">Nombre</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total = array_sum($statistiques['par_type']);
                                @endphp
                                @foreach($statistiques['par_type'] as $type => $count)
                                @php
                                    $badgeClass = match(strtoupper($type)) {
                                        'ACHAT' => 'bg-success',
                                        'VENTE' => 'bg-primary',
                                        'DECES' => 'bg-danger',
                                        'PERTE' => 'bg-warning text-dark',
                                        'TRANSFERT' => 'bg-info text-dark',
                                        'ABATTAGE' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                                    </td>
                                    <td class="text-end">{{ $count }}</td>
                                    <td class="text-end">{{ $percentage }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <p>Aucune donnée disponible.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Coût total ───────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Coût total</h5>
                </div>
                <div class="card-body">
                    <div class="cost-summary">
                        <div class="cost-item">
                            <span class="cost-label">Achats</span>
                            <span class="cost-value">
                                {{ number_format($statistiques['cout_achats'] ?? 0, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="cost-item">
                            <span class="cost-label">Ventes</span>
                            <span class="cost-value">
                                {{ number_format($statistiques['cout_ventes'] ?? 0, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="cost-item cost-item-total">
                            <span class="cost-label">Solde</span>
                            <span class="cost-value">
                                {{ number_format(($statistiques['cout_ventes'] ?? 0) - ($statistiques['cout_achats'] ?? 0), 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Mouvements récents ─────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5>Mouvements récents</h5>
        </div>
        <div class="card-body">
            @if(isset($statistiques['recent']) && count($statistiques['recent']) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Animal</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Coût</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistiques['recent'] as $mvt)
                            @php
                                $type = $mvt['type_evenement']['nom_type'] ?? '—';
                                $badgeClass = match(strtoupper($type)) {
                                    'ACHAT' => 'bg-success',
                                    'VENTE' => 'bg-primary',
                                    'DECES' => 'bg-danger',
                                    'PERTE' => 'bg-warning text-dark',
                                    'TRANSFERT' => 'bg-info text-dark',
                                    'ABATTAGE' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <tr>
                                <td>
                                    @if(isset($mvt['animal']))
                                        {{ $mvt['animal']['nom'] ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                                </td>
                                <td>
                                    @if(!empty($mvt['date_evenement']))
                                        {{ \Carbon\Carbon::parse($mvt['date_evenement'])->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if(isset($mvt['cout']))
                                        {{ number_format($mvt['cout'], 0, ',', ' ') }} FCFA
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <p>Aucun mouvement récent.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/mouvements/statistiques.js') }}"></script>
@endpush
