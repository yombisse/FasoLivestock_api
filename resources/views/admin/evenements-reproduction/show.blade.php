@extends('admin.layouts.app')

@section('title', 'Détail événement de reproduction')
@section('page-title', 'Détail événement de reproduction')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/common/detail.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.evenements-reproduction.index') }}">Événements de reproduction</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div x-data="evenementShow()" class="detail-page fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-heart me-2 text-pink"></i>Détail événement de reproduction</h2>
            <p>Informations sur l'événement de reproduction</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.evenements-reproduction.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.evenements-reproduction.edit', $evenement['id']) }}" class="btn btn-primary btn-sm">
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
                        <div class="label">Animal femelle</div>
                        <div class="value">
                            @if(!empty($evenement['animal']))
                                <a href="{{ route('admin.animals.show', $evenement['animal']['id']) }}">
                                    {{ $evenement['animal']['nom'] ?? $evenement['animal']['numero_identification'] ?? '—' }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($evenement['male']))
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Animal mâle</div>
                        <div class="value">
                            <a href="{{ route('admin.animals.show', $evenement['male']['id']) }}">
                                {{ $evenement['male']['nom'] ?? $evenement['male']['numero_identification'] ?? '—' }}
                            </a>
                        </div>
                    </div>
                </div>
                @endif

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

                @if(!empty($evenement['description']))
                <div class="col-12">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value">{{ $evenement['description'] }}</div>
                    </div>
                </div>
                @endif

                @if(!empty($evenement['resultat']))
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Résultat</div>
                        <div class="value">
                            <span class="badge {{ $evenement['resultat'] === 'SUCCÈS' ? 'bg-success' : 'bg-warning' }}">
                                {{ $evenement['resultat'] }}
                            </span>
                        </div>
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
