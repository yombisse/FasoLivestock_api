@extends('admin.layouts.ferme')

@section('title', 'Détail animal')
@section('page-title', 'Détail animal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/common/detail.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div x-data="animalShow()" class="detail-page fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-cow me-2 text-primary"></i>Détail animal</h2>
            <p>Informations détaillées de l'animal</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.animals.edit', ['farm' => $farmId, 'animal' => $animal['id']]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Modifier
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Nom</div>
                        <div class="value">{{ $animal['nom'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Numéro d'identification</div>
                        <div class="value">{{ $animal['numero_identification'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Race</div>
                        <div class="value">{{ $animal['race'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Sexe</div>
                        <div class="value">
                            <span class="badge {{ $animal['sexe'] === 'male' ? 'bg-primary' : 'bg-pink' }}">
                                {{ $animal['sexe'] === 'male' ? 'Mâle' : 'Femelle' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Date de naissance</div>
                        <div class="value">
                            {{ !empty($animal['date_naissance']) ? \Carbon\Carbon::parse($animal['date_naissance'])->format('d/m/Y') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Poids (kg)</div>
                        <div class="value">{{ $animal['poids'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut</div>
                        <div class="value">
                            <span class="badge {{ $animal['statut'] === 'actif' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($animal['statut'] ?? 'Inconnu') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Ferme</div>
                        <div class="value">{{ $animal['farm']['name'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Espèce</div>
                        <div class="value">{{ $animal['espece']['nom'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Lot</div>
                        <div class="value">{{ $animal['lot']['nom'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Mère</div>
                        <div class="value">{{ $animal['mother']['nom'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Créé le</div>
                        <div class="value">
                            {{ !empty($animal['created_at']) ? \Carbon\Carbon::parse($animal['created_at'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Supprimé (archivé)</div>
                        <div class="value">
                            {{ !empty($animal['deleted_at']) ? \Carbon\Carbon::parse($animal['deleted_at'])->format('d/m/Y H:i') : '—' }}
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
        function animalShow() {
            return {};
        }
    </script>
@endpush
