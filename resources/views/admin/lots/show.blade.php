@extends('admin.layouts.ferme')

@section('title', 'Détail lot')
@section('page-title', 'Détail lot')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/lots/show.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.lots.index', ['farm' => $farmId]) }}">Lots</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div x-data="lotShow()" class="fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-grid me-2 text-primary"></i>Détail lot</h2>
            <p>Informations détaillées du lot</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.lots.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.lots.assign', ['farm' => $farmId, 'lot' => $lot['id']]) }}" class="btn btn-info btn-sm">
                <i class="bi bi-box2-heart"></i> Affecter animaux
            </a>
            <a href="{{ route('admin.lots.edit', ['farm' => $farmId, 'lot' => $lot['id']]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Modifier
            </a>
        </div>
    </div>

    {{-- ── Badges ─────────────────────────────────────── --}}
    <div class="d-flex gap-2 mb-3">
        <span class="badge bg-info">
            <i class="bi bi-house"></i> {{ $lot['farm']['name'] ?? '—' }}
        </span>
        <span class="badge bg-success">
            <i class="bi bi-box2-heart"></i> {{ $lot['animals_count'] ?? 0 }} animal(s)
        </span>
    </div>

    {{-- ── Informations ───────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Nom</div>
                        <div class="value fw-semibold text-dark">{{ $lot['nom_lot'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Ferme</div>
                        <div class="value fw-semibold text-dark">{{ $lot['farm']['name'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Nombre d'animaux</div>
                        <div class="value fw-semibold text-dark">{{ count($lot['animals'] ?? []) }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Créé le</div>
                        <div class="value fw-semibold text-dark">
                            {{ !empty($lot['created_at']) ? \Carbon\Carbon::parse($lot['created_at'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value fw-semibold text-dark">{{ $lot['description'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Animaux dans ce lot ─────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-box2-heart me-2"></i>Animaux dans ce lot</h5>
        </div>
        <div class="card-body">
            @if(isset($lot['animals']) && count($lot['animals']) > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Espèce</th>
                            <th>Sexe</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lot['animals'] as $animal)
                        <tr>
                            <td>{{ $animal['nom'] ?? '—' }}</td>
                            <td>{{ $animal['espece']['nom'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $animal['sexe'] === 'male' ? 'bg-primary' : 'bg-pink' }}">
                                    {{ $animal['sexe'] === 'male' ? 'Mâle' : 'Femelle' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $animal['statut'] === 'actif' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($animal['statut'] ?? 'Inconnu') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.animals.show', ['farm' => $farmId, 'animal' => $animal['id']]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted py-4">
                <i class="bi bi-box2-heart" style="font-size: 2rem;"></i>
                <p class="mt-2">Aucun animal dans ce lot</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Statistiques (si disponibles) ───────────────── --}}
    @if(isset($lot['statistiques']))
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Statistiques</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($lot['statistiques'] as $key => $value)
                <div class="col-md-4">
                    <div class="field">
                        <div class="label">{{ ucfirst($key) }}</div>
                        <div class="value fw-semibold text-dark">{{ $value ?? '—' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/lots/show.js') }}"></script>
@endpush
