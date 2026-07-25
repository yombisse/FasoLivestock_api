@extends('admin.layouts.app')

@section('title', 'Détail mouvement')
@section('page-title', 'Détail mouvement')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/show.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.mouvements.index', ['farm' => $farmId]) }}">Mouvements</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            @php
                $type = $mouvement['type_evenement']['nom_type'] ?? '—';
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
            <h2>
                <span class="badge {{ $badgeClass }} me-2">{{ $type }}</span>
                @if(isset($mouvement['animal']))
                    {{ $mouvement['animal']['nom'] ?? '—' }}
                @endif
            </h2>
            <p>
                @if(!empty($mouvement['date_evenement']))
                    {{ \Carbon\Carbon::parse($mouvement['date_evenement'])->format('d/m/Y H:i') }}
                @else
                    —
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.mouvements.edit', ['farm' => $farmId, 'mouvement' => $mouvement['id']]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i>
                Modifier
            </a>
            <a href="{{ route('admin.mouvements.index', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Détails du mouvement ───────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5>Détails du mouvement</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Type</div>
                        <div class="value">
                            <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Date</div>
                        <div class="value">
                            @if(!empty($mouvement['date_evenement']))
                                {{ \Carbon\Carbon::parse($mouvement['date_evenement'])->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Coût</div>
                        <div class="value">
                            @if(isset($mouvement['cout']))
                                {{ number_format($mouvement['cout'], 0, ',', ' ') }} FCFA
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value">{{ $mouvement['description'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Animal concerné ─────────────────────────────────── --}}
    @if(isset($mouvement['animal']))
    <div class="card mb-4">
        <div class="card-header">
            <h5>Animal concerné</h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar">
                    {{ substr($mouvement['animal']['nom'] ?? 'A', 0, 1) }}
                </div>
                <div>
                    <div class="fw-bold">{{ $mouvement['animal']['nom'] ?? '—' }}</div>
                    <small class="text-muted">{{ $mouvement['animal']['code'] ?? '' }}</small>
                </div>
                <a href="{{ route('admin.animals.show', ['farm' => $farmId, 'animal' => $mouvement['animal']['id']]) }}"
                   class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="bi bi-eye"></i>
                    Voir la fiche
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Transfert (si applicable) ─────────────────────────── --}}
    @if(strtoupper($type) === 'TRANSFERT')
    <div class="card mb-4">
        <div class="card-header">
            <h5>Transfert</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Ferme source</div>
                        <div class="value">
                            @if(isset($mouvement['animal']['farm']))
                                {{ $mouvement['animal']['farm']['name'] ?? '—' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Ferme destination</div>
                        <div class="value">
                            @if(isset($mouvement['farm_destination']))
                                {{ $mouvement['farm_destination']['name'] ?? '—' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Informations supplémentaires ─────────────────────── --}}
    @if(isset($mouvement['fournisseur']) || isset($mouvement['acheteur']))
    <div class="card">
        <div class="card-header">
            <h5>Informations supplémentaires</h5>
        </div>
        <div class="card-body">
            @if(isset($mouvement['fournisseur']))
            <div class="field">
                <div class="label">Fournisseur</div>
                <div class="value">{{ $mouvement['fournisseur'] }}</div>
            </div>
            @endif
            @if(isset($mouvement['acheteur']))
            <div class="field">
                <div class="label">Acheteur</div>
                <div class="value">{{ $mouvement['acheteur'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/mouvements/show.js') }}"></script>
@endpush
