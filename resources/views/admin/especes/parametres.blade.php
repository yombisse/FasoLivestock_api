@extends('admin.layouts.app')

@section('title', 'Paramètres espèce')
@section('page-title', 'Paramètres espèce')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/especes/parametres.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.especes.index') }}">Espèces</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.especes.show', $espece['id']) }}">{{ $espece['nom'] }}</a>
    </li>
    <li class="breadcrumb-item active">Paramètres</li>
@endsection

@section('content')
<div x-data="especeParametresForm()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-sliders me-2 text-success"></i>Paramètres - {{ $espece['nom'] }}</h2>
            <p>Configurez les paramètres biologiques de l'espèce</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.especes.show', $espece['id']) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Formulaire ─────────────────────────────────────── --}}
    <div class="users-card">
        <form id="espece-parametres-form"
              method="POST"
              action="{{ route('admin.especes.parametres.update', $espece['id']) }}">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Durée de gestation --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="duree_gestation_jours">Durée de gestation (jours)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="duree_gestation_jours"
                                   name="duree_gestation_jours"
                                   class="form-control @error('duree_gestation_jours') is-invalid @enderror"
                                   placeholder="Ex: 285"
                                   value="{{ old('duree_gestation_jours', $espece['parametre']['duree_gestation_jours'] ?? '') }}"
                                   min="1">
                            <i class="bi bi-calendar-event field-icon"></i>
                        </div>
                        @error('duree_gestation_jours')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Âge de reproduction --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="age_reproduction_mois">Âge de reproduction (mois)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="age_reproduction_mois"
                                   name="age_reproduction_mois"
                                   class="form-control @error('age_reproduction_mois') is-invalid @enderror"
                                   placeholder="Ex: 24"
                                   value="{{ old('age_reproduction_mois', $espece['parametre']['age_reproduction_mois'] ?? '') }}"
                                   min="1">
                            <i class="bi bi-clock-history field-icon"></i>
                        </div>
                        @error('age_reproduction_mois')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Nombre de petits typique --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="nombre_petits_typique">Nombre de petits typique</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="nombre_petits_typique"
                                   name="nombre_petits_typique"
                                   class="form-control @error('nombre_petits_typique') is-invalid @enderror"
                                   placeholder="Ex: 1"
                                   value="{{ old('nombre_petits_typique', $espece['parametre']['nombre_petits_typique'] ?? '') }}"
                                   min="1">
                            <i class="bi bi-people field-icon"></i>
                        </div>
                        @error('nombre_petits_typique')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Intervalle vaccin --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="intervalle_vaccin_jours">Intervalle vaccin (jours)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="intervalle_vaccin_jours"
                                   name="intervalle_vaccin_jours"
                                   class="form-control @error('intervalle_vaccin_jours') is-invalid @enderror"
                                   placeholder="Ex: 365"
                                   value="{{ old('intervalle_vaccin_jours', $espece['parametre']['intervalle_vaccin_jours'] ?? '') }}"
                                   min="1">
                            <i class="bi bi-shield-plus field-icon"></i>
                        </div>
                        @error('intervalle_vaccin_jours')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Âge de sevrage --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="age_sevrage_jours">Âge de sevrage (jours)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="age_sevrage_jours"
                                   name="age_sevrage_jours"
                                   class="form-control @error('age_sevrage_jours') is-invalid @enderror"
                                   placeholder="Ex: 180"
                                   value="{{ old('age_sevrage_jours', $espece['parametre']['age_sevrage_jours'] ?? '') }}"
                                   min="1">
                            <i class="bi bi-bottle-feed field-icon"></i>
                        </div>
                        @error('age_sevrage_jours')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Poids naissance moyen --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="poids_naissance_moyen_kg">Poids naissance moyen (kg)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="poids_naissance_moyen_kg"
                                   name="poids_naissance_moyen_kg"
                                   class="form-control @error('poids_naissance_moyen_kg') is-invalid @enderror"
                                   placeholder="Ex: 35"
                                   value="{{ old('poids_naissance_moyen_kg', $espece['parametre']['poids_naissance_moyen_kg'] ?? '') }}"
                                   min="0"
                                   step="0.1">
                            <i class="bi bi-activity field-icon"></i>
                        </div>
                        @error('poids_naissance_moyen_kg')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Poids adulte moyen --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="poids_adulte_moyen_kg">Poids adulte moyen (kg)</label>
                        <div class="input-with-icon">
                            <input type="number"
                                   id="poids_adulte_moyen_kg"
                                   name="poids_adulte_moyen_kg"
                                   class="form-control @error('poids_adulte_moyen_kg') is-invalid @enderror"
                                   placeholder="Ex: 500"
                                   value="{{ old('poids_adulte_moyen_kg', $espece['parametre']['poids_adulte_moyen_kg'] ?? '') }}"
                                   min="0"
                                   step="0.1">
                            <i class="bi bi-activity field-icon"></i>
                        </div>
                        @error('poids_adulte_moyen_kg')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Actions ────────────────────────────────────── --}}
            <div class="form-actions">
                <a href="{{ route('admin.especes.show', $espece['id']) }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i>
                    Enregistrer les paramètres
                </button>
            </div>

        </form>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/especes/parametres.js') }}"></script>
@endpush
