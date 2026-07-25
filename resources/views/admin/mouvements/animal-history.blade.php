@extends('admin.layouts.ferme')

@section('title', 'Historique animal')
@section('page-title', 'Historique animal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/animal-history.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.mouvements.index', ['farm' => $farmId]) }}">Mouvements</a>
    </li>
    <li class="breadcrumb-item active">Historique animal</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-clock-history me-2 text-primary"></i>Historique de {{ $animal['nom'] ?? '—' }}</h2>
            <p>Historique complet des mouvements de cet animal</p>
        </div>
        @if(isset($animal['id']))
        <a href="{{ route('admin.animals.show', ['farm' => $farmId, 'animal' => $animal['id']]) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            Retour à la fiche animal
        </a>
        @endif
    </div>

    {{-- ── Timeline des mouvements ───────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5>Timeline des mouvements</h5>
        </div>
        <div class="card-body">
            @if($historique && count($historique) > 0)
                <div class="timeline">
                    @foreach($historique as $index => $mvt)
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
                        <div class="timeline-item {{ $index === 0 ? 'timeline-item-first' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge {{ $badgeClass }} mb-2">{{ $type }}</span>
                                        <h6 class="mb-1">{{ $mvt['description'] ?? 'Sans description' }}</h6>
                                        <small class="text-muted">
                                            @if(!empty($mvt['date_evenement']))
                                                {{ \Carbon\Carbon::parse($mvt['date_evenement'])->format('d/m/Y H:i') }}
                                            @else
                                                —
                                            @endif
                                        </small>
                                    </div>
                                    @if(isset($mvt['cout']))
                                    <div class="text-end">
                                        <strong>{{ number_format($mvt['cout'], 0, ',', ' ') }} FCFA</strong>
                                    </div>
                                    @endif
                                </div>
                                @if(strtoupper($type) === 'TRANSFERT' && isset($mvt['farm_destination']))
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-arrow-right"></i>
                                        {{ $mvt['farm_destination']['name'] ?? '—' }}
                                    </small>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <p>Aucun mouvement enregistré pour cet animal.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/mouvements/animal-history.js') }}"></script>
@endpush
