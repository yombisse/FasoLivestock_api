@extends('admin.layouts.ferme')

@section('title', 'Affecter animaux au lot')
@section('page-title', 'Affecter animaux au lot')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/lots/assign.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.lots.index') }}">Lots</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.lots.show', $lot['id']) }}">{{ $lot['nom_lot'] }}</a>
    </li>
    <li class="breadcrumb-item active">Affecter animaux</li>
@endsection

@section('content')
<div x-data="lotAssign()" class="fade-in">

    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-box2-heart me-2 text-info"></i>Affecter animaux au lot</h2>
            <p>Lot : {{ $lot['nom_lot'] }} - Ferme : {{ $lot['farm']['name'] ?? '—' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.lots.show', $lot['id']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <form id="assign-form"
          method="POST"
          action="{{ route('admin.lots.storeAssign', $lot['id']) }}">
        @csrf

        <div class="row">
            {{-- Animaux disponibles --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-box2 me-2"></i>Animaux disponibles</h5>
                        <span class="badge bg-info">{{ count($animals) }} animal(s)</span>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        @if(count($animals) > 0)
                            @foreach($animals as $animal)
                            <div class="animal-card" @click="toggleAnimal('{{ $animal['id'] }}')">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="checkbox"
                                           name="animal_ids[]"
                                           value="{{ $animal['id'] }}"
                                           id="animal-{{ $animal['id'] }}"
                                           x-model="selectedAnimals"
                                           @click.stop>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $animal['nom'] ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $animal['espece']['nom'] ?? '—' }} | 
                                            {{ $animal['sexe'] === 'male' ? 'Mâle' : 'Femelle' }} |
                                            {{ $animal['race'] ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-box2" style="font-size: 2rem;"></i>
                                <p class="mt-2">Aucun animal disponible dans cette ferme</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Animaux déjà dans le lot --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-box2-heart me-2"></i>Animaux dans le lot</h5>
                        <span class="badge bg-success">{{ count($lotAnimals) }} animal(s)</span>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        @if(count($lotAnimals) > 0)
                            @foreach($lotAnimals as $animal)
                            <div class="animal-card selected">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar">
                                        <i class="bi bi-cow"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $animal['nom'] ?? '—' }}</div>
                                        <div class="text-muted small">
                                            {{ $animal['espece']['nom'] ?? '—' }} | 
                                            {{ $animal['sexe'] === 'male' ? 'Mâle' : 'Femelle' }} |
                                            {{ $animal['race'] ?? '—' }}
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.lots.removeAnimal', [$lot['id'], $animal['id']]) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Retirer du lot">
                                        <i class="bi bi-dash"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-box2-heart" style="font-size: 2rem;"></i>
                                <p class="mt-2">Aucun animal dans ce lot</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="form-actions">
            <a href="{{ route('admin.lots.show', $lot['id']) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Annuler
            </a>
            <button type="submit"
                    class="btn btn-primary btn-sm"
                    :disabled="selectedAnimals.length === 0">
                <i class="bi bi-check-lg"></i>
                Affecter <span x-text="selectedAnimals.length">0</span> animal(s)
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/lots/assign.js') }}"></script>
@endpush
