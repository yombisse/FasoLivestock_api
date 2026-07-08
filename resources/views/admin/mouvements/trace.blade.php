@extends('admin.layouts.app')

@section('title', 'Traçabilité animal')
@section('page-title', 'Traçabilité animal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/trace.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.mouvements.index') }}">Mouvements</a>
    </li>
    <li class="breadcrumb-item active">Traçabilité</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-geo-alt me-2 text-primary"></i>Traçabilité de {{ $animal['nom'] ?? '—' }}</h2>
            <p>Historique complet de traçabilité de cet animal</p>
        </div>
        @if(isset($animal['id']))
        <a href="{{ route('admin.animals.show', $animal['id']) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            Retour à la fiche animal
        </a>
        @endif
    </div>

    {{-- ── Timeline de traçabilité ───────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5>Timeline de traçabilité</h5>
        </div>
        <div class="card-body">
            @if($trace && count($trace) > 0)
                <div class="timeline">
                    @foreach($trace as $index => $event)
                        @php
                            $type = $event['type'] ?? '—';
                            $badgeClass = match(strtoupper($type)) {
                                'ACHAT' => 'bg-success',
                                'VENTE' => 'bg-primary',
                                'DECES' => 'bg-danger',
                                'PERTE' => 'bg-warning text-dark',
                                'TRANSFERT' => 'bg-info text-dark',
                                'ABATTAGE' => 'bg-secondary',
                                'NAISSANCE' => 'bg-success',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <div class="timeline-item {{ $index === 0 ? 'timeline-item-first' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge {{ $badgeClass }} mb-2">{{ $type }}</span>
                                        <h6 class="mb-1">{{ $event['description'] ?? 'Sans description' }}</h6>
                                        <small class="text-muted">
                                            @if(!empty($event['date']))
                                                {{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y H:i') }}
                                            @else
                                                —
                                            @endif
                                        </small>
                                    </div>
                                    @if(isset($event['location']))
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i>
                                            {{ $event['location'] }}
                                        </small>
                                    </div>
                                    @endif
                                </div>
                                @if(isset($event['details']))
                                <div class="mt-2">
                                    <small class="text-muted">{{ $event['details'] }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <p>Aucune information de traçabilité disponible pour cet animal.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/mouvements/trace.js') }}"></script>
@endpush
