@extends('admin.layouts.ferme')

@section('title', 'Détail événement sanitaire')
@section('page-title', 'Détail événement sanitaire')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/common/detail.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sante-evenements.index', ['farm' => $farmId]) }}">Événements sanitaires</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div x-data="evenementShow()" class="detail-page fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-heart-pulse me-2 text-danger"></i>Détail événement sanitaire</h2>
            <p>Informations sur l'événement sanitaire</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sante-evenements.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.sante-evenements.edit', ['farm' => $farmId, 'sante_evenement' => $evenement['id']]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Modifier
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Type d'événement</div>
                        <div class="value">{{ $evenement['type']['nom_type'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Animal</div>
                        <div class="value">
                            @if(!empty($evenement['animal']))
                                <a href="{{ route('admin.animals.show', ['farm' => $farmId, 'animal' => $evenement['animal']['id']]) }}">
                                    {{ $evenement['animal']['nom'] ?? $evenement['animal']['numero_identification'] ?? '—' }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Date de l'événement</div>
                        <div class="value">
                            {{ !empty($evenement['date_evenement']) ? \Carbon\Carbon::parse($evenement['date_evenement'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Ferme</div>
                        <div class="value">
                            @if(!empty($evenement['farm']))
                                {{ $evenement['farm']['name'] ?? '—' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut avant</div>
                        <div class="value">
                            @if(!empty($evenement['statut_avant']))
                                <span class="badge bg-secondary">{{ $evenement['statut_avant'] }}</span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut après</div>
                        <div class="value">
                            @if(!empty($evenement['statut_apres']))
                                <span class="badge {{ $evenement['statut_apres'] === 'SAIN' ? 'bg-success' : 'bg-warning' }}">
                                    {{ $evenement['statut_apres'] }}
                                </span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($evenement['description']))
                <div class="col-12">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value">{{ $evenement['description'] }}</div>
                    </div>
                </div>
                @endif

                @if(!empty($evenement['cout']))
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Coût (FCFA)</div>
                        <div class="value">{{ number_format($evenement['cout'], 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>
                @endif

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Créé le</div>
                        <div class="value">
                            {{ !empty($evenement['created_at']) ? \Carbon\Carbon::parse($evenement['created_at'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut de synchronisation</div>
                        <div class="value">
                            @if(!empty($evenement['sync_status']))
                                <span class="badge {{ $evenement['sync_status'] === 'synced' ? 'bg-success' : 'bg-warning' }}">
                                    {{ $evenement['sync_status'] }}
                                </span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        function evenementShow() {
            return {};
        }
    </script>
@endpush
