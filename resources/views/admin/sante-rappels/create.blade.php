@extends('admin.layouts.ferme')

@section('title', 'Nouveau rappel sanitaire')
@section('page-title', 'Nouveau rappel sanitaire')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/form-create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}">Rappels sanitaires</a>
    </li>
    <li class="breadcrumb-item active">Nouveau rappel</li>
@endsection

@section('content')
<div class="form-page fade-in">

    <form id="rappel-create-form"
          method="POST"
          action="{{ route('admin.sante-rappels.store', ['farm' => $farmId]) }}">
        @csrf

        {{-- ─── Informations générales ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-bell"></i>
                </div>
                <h3>Nouveau rappel sanitaire</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Animal --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="animal_id">
                                Animal <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="animal_id"
                                        name="animal_id"
                                        class="form-select @error('animal_id') is-invalid @enderror"
                                        required>
                                    <option value="">Sélectionner un animal</option>
                                    {{-- TODO: Charger les animaux depuis l'API --}}
                                </select>
                                <i class="bi bi-cow field-icon"></i>
                            </div>
                            @error('animal_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="type">
                                Type <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="type"
                                        name="type"
                                        class="form-select @error('type') is-invalid @enderror"
                                        required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="VACCINATION" {{ old('type') === 'VACCINATION' ? 'selected' : '' }}>Vaccination</option>
                                    <option value="TRAITEMENT" {{ old('type') === 'TRAITEMENT' ? 'selected' : '' }}>Traitement</option>
                                    <option value="CONTROLE" {{ old('type') === 'CONTROLE' ? 'selected' : '' }}>Contrôle</option>
                                </select>
                                <i class="bi bi-tag field-icon"></i>
                            </div>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Date prévue --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="date_prevue">
                                Date prévue <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="date"
                                       id="date_prevue"
                                       name="date_prevue"
                                       class="form-control @error('date_prevue') is-invalid @enderror"
                                       value="{{ old('date_prevue', now()->format('Y-m-d')) }}"
                                       required>
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_prevue')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Produit utilisé --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="produit_utilise">Produit utilisé</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="produit_utilise"
                                       name="produit_utilise"
                                       class="form-control @error('produit_utilise') is-invalid @enderror"
                                       placeholder="Nom du produit"
                                       value="{{ old('produit_utilise') }}">
                                <i class="bi bi-capsule field-icon"></i>
                            </div>
                            @error('produit_utilise')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Dose --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="dose">Dose</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="dose"
                                       name="dose"
                                       class="form-control @error('dose') is-invalid @enderror"
                                       placeholder="Ex: 5ml"
                                       value="{{ old('dose') }}">
                                <i class="bi bi-droplet field-icon"></i>
                            </div>
                            @error('dose')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Vétérinaire --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="veterinaire">Vétérinaire</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="veterinaire"
                                       name="veterinaire"
                                       class="form-control @error('veterinaire') is-invalid @enderror"
                                       placeholder="Nom du vétérinaire"
                                       value="{{ old('veterinaire') }}">
                                <i class="bi bi-person field-icon"></i>
                            </div>
                            @error('veterinaire')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Description du rappel...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Annuler
                </a>
                <button type="submit"
                        class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg"></i>
                    Créer le rappel
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/sante-rappels/create.js') }}"></script>
@endpush
