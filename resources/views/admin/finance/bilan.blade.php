@extends('admin.layouts.ferme')

@section('title', 'Bilan financier')
@section('page-title', 'Bilan financier')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/table-list.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.finance.index', ['farm' => $farmId]) }}">Finance</a>
    </li>
    <li class="breadcrumb-item active">Bilan</li>
@endsection

@section('content')
<div x-data="financeBilan()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-graph-up me-2 text-success"></i>Bilan financier</h2>
            <p>Aperçu global des entrées et sorties</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            @if($selectedFarm)
            <a href="{{ route('admin.rapports.export.transactions', ['farm' => $selectedFarm, 'date_debut' => request('date_debut'), 'date_fin' => request('date_fin')]) }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
            <a href="{{ route('admin.rapports.pdf.financier', ['farm' => $selectedFarm, 'date_debut' => request('date_debut'), 'date_fin' => request('date_fin')]) }}"
               class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
            @else
            <button class="btn btn-success btn-sm" disabled title="Sélectionnez une ferme pour exporter">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </button>
            <button class="btn btn-danger btn-sm" disabled title="Sélectionnez une ferme pour exporter">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
            @endif
        </div>
    </div>

    {{-- ── Filtres ───────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.finance.bilan', ['farm' => $farmId]) }}">
                <div class="row g-3">
                    {{-- Ferme (hidden - from route) --}}
                    <input type="hidden" name="farm_id" value="{{ $farmId }}">
                    <div class="col-md-3">
                        <label class="form-label">Date début</label>
                        <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date fin</label>
                        <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        @if(request()->hasAny(['farm_id', 'date_debut', 'date_fin']))
                        <a href="{{ route('admin.finance.bilan', ['farm' => $farmId]) }}" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-lg"></i> Réinitialiser
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Bilan global ───────────────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-arrow-down-circle text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Total Entrées</h6>
                            <h3 class="mb-0 fw-bold text-success">{{ number_format($bilan['total_entrees'] ?? 0, 0, ',', ' ') }}</h3>
                            <small class="text-muted">FCFA</small>
                            @if($selectedFarm)
                            <div class="badge bg-success bg-opacity-10 text-success mt-2">Ferme sélectionnée</div>
                            @else
                            <div class="text-muted mt-2" style="font-size: 0.85rem;">{{ $stats['nombre_revenus'] ?? 0 }} transaction(s)</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-arrow-up-circle text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Total Sorties</h6>
                            <h3 class="mb-0 fw-bold text-danger">{{ number_format($bilan['total_sorties'] ?? 0, 0, ',', ' ') }}</h3>
                            <small class="text-muted">FCFA</small>
                            @if($selectedFarm)
                            <div class="badge bg-danger bg-opacity-10 text-danger mt-2">Ferme sélectionnée</div>
                            @else
                            <div class="text-muted mt-2" style="font-size: 0.85rem;">{{ $stats['nombre_charges'] ?? 0 }} transaction(s)</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4 {{ ($bilan['solde'] ?? 0) >= 0 ? 'border-primary' : 'border-warning' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="{{ ($bilan['solde'] ?? 0) >= 0 ? 'bg-primary bg-opacity-10' : 'bg-warning bg-opacity-10' }} rounded-circle p-3">
                                <i class="bi {{ ($bilan['solde'] ?? 0) >= 0 ? 'bi-wallet2 text-primary' : 'bi-exclamation-triangle text-warning' }} fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Solde Net</h6>
                            <h3 class="mb-0 fw-bold {{ ($bilan['solde'] ?? 0) >= 0 ? 'text-primary' : 'text-warning' }}">
                                {{ number_format($bilan['solde'] ?? 0, 0, ',', ' ') }}
                            </h3>
                            <small class="text-muted">FCFA</small>
                            @if($selectedFarm)
                            <div class="badge {{ ($bilan['solde'] ?? 0) >= 0 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-warning bg-opacity-10 text-warning' }} mt-2">Bilan ferme</div>
                            @else
                            <div class="text-muted mt-2" style="font-size: 0.85rem;">Bénéfice net global</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Statistiques détaillées (seulement si pas de filtre ferme) ── --}}
    @if(!$selectedFarm && !empty($stats))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">Statistiques détaillées</h6>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted" style="font-size: 0.75rem;">Moy. Entrée</div>
                            <div class="fw-bold">{{ number_format($stats['moyenne_revenu'] ?? 0, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-graph-down-arrow text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted" style="font-size: 0.75rem;">Moy. Sortie</div>
                            <div class="fw-bold">{{ number_format($stats['moyenne_charge'] ?? 0, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-list-check text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted" style="font-size: 0.75rem;">Total Transactions</div>
                            <div class="fw-bold">{{ ($stats['nombre_revenus'] ?? 0) + ($stats['nombre_charges'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-percent text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted" style="font-size: 0.75rem;">Ratio E/S</div>
                            <div class="fw-bold">
                                @if(($stats['total_charges'] ?? 0) > 0)
                                    {{ round(($stats['total_revenus'] ?? 0) / ($stats['total_charges'] ?? 1), 2) }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Informations de période ───────────────────────── --}}
    @if(request('date_debut') || request('date_fin'))
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Bilan calculé sur la période :
        @if(request('date_debut')) du {{ \Carbon\Carbon::parse(request('date_debut'))->format('d/m/Y') }} @endif
        @if(request('date_fin')) au {{ \Carbon\Carbon::parse(request('date_fin'))->format('d/m/Y') }} @endif
        @if(request('farm_id')) pour la ferme sélectionnée @endif
    </div>
    @endif

</div>
@endsection

@push('scripts')
    <script>
        function financeBilan() {
            return {};
        }
    </script>
@endpush
