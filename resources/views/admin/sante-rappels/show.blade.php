@extends('admin.layouts.ferme')

@section('title', 'Détail rappel sanitaire')
@section('page-title', 'Détail rappel sanitaire')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/sante-rappels/show.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}">Rappels sanitaires</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            @php
                $statut = $rappel['statut'] ?? 'EN_ATTENTE';
                $badgeClass = match($statut) {
                    'EN_ATTENTE' => 'bg-warning text-dark',
                    'EFFECTUE' => 'bg-success',
                    'EN_RETARD' => 'bg-danger',
                    default => 'bg-secondary'
                };
            @endphp
            <h2>
                <span class="badge {{ $badgeClass }} me-2">{{ $statut }}</span>
                @if(isset($rappel['animal']))
                    {{ $rappel['animal']['nom'] ?? '—' }}
                @endif
            </h2>
            <p>
                @if(!empty($rappel['date_prevue']))
                    {{ \Carbon\Carbon::parse($rappel['date_prevue'])->format('d/m/Y') }}
                @else
                    —
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(in_array($statut, ['EN_ATTENTE', 'EN_RETARD']))
            <form id="form-realise"
                  method="POST"
                  action="{{ route('admin.sante-rappels.marquer-realise', ['farm' => $farmId, 'id' => $rappel['id']]) }}">
                @csrf
            </form>
            <button type="button"
                    onclick="document.getElementById('form-realise').submit()"
                    class="btn btn-outline-success btn-sm">
                <i class="bi bi-check-circle"></i>
                Marquer réalisé
            </button>
            @endif
            <a href="{{ route('admin.sante-rappels.edit', ['farm' => $farmId, 'sante_rappel' => $rappel['id']]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i>
                Modifier
            </a>
            <a href="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Détails du rappel ───────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5>Détails du rappel</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Type</div>
                        <div class="value">
                            <span class="badge bg-info text-dark">{{ $rappel['type'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut</div>
                        <div class="value">
                            <span class="badge {{ $badgeClass }}">{{ $statut }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Date prévue</div>
                        <div class="value">
                            @if(!empty($rappel['date_prevue']))
                                {{ \Carbon\Carbon::parse($rappel['date_prevue'])->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Produit utilisé</div>
                        <div class="value">{{ $rappel['produit_utilise'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Dose</div>
                        <div class="value">{{ $rappel['dose'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Vétérinaire</div>
                        <div class="value">{{ $rappel['veterinaire'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value">{{ $rappel['description'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Animal concerné ─────────────────────────────────── --}}
    @if(isset($rappel['animal']))
    <div class="card">
        <div class="card-header">
            <h5>Animal concerné</h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar">
                    {{ substr($rappel['animal']['nom'] ?? 'A', 0, 1) }}
                </div>
                <div>
                    <div class="fw-bold">{{ $rappel['animal']['nom'] ?? '—' }}</div>
                    <small class="text-muted">{{ $rappel['animal']['code'] ?? '' }}</small>
                </div>
                <a href="{{ route('admin.animals.show', ['farm' => $farmId, 'animal' => $rappel['animal']['id']]) }}"
                   class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="bi bi-eye"></i>
                    Voir la fiche
                </a>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/sante-rappels/show.js') }}"></script>
@endpush
