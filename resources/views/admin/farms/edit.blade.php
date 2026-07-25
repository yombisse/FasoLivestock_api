@extends('admin.layouts.systeme')

@section('title', 'Modifier ' . ($farm['name'] ?? 'Ferme'))
@section('page-title', 'Modifier une ferme')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/farms/create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.farms.index') }}">Fermes</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="form-page fade-in" x-data="farmCreateForm()">

    <form id="farm-edit-form"
          method="POST"
          action="{{ route('admin.farms.update', $farmId) }}">
        @csrf
        @method('PUT')

        {{-- ── Informations générales ────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-house-door"></i>
                </div>
                <h3>Informations de la ferme</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Nom --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="name">
                                Nom de la ferme <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="name"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Ex : Ferme Kaboré"
                                       value="{{ old('name', $farm['name'] ?? '') }}"
                                       required>
                                <i class="bi bi-house-door field-icon"></i>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Localisation --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="location">Localisation</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="location"
                                       name="location"
                                       class="form-control @error('location') is-invalid @enderror"
                                       placeholder="Ex : Ouagadougou, Kadiogo"
                                       value="{{ old('location', $farm['location'] ?? '') }}">
                                <i class="bi bi-geo-alt field-icon"></i>
                            </div>
                            @error('location')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Type d'élevage --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="type_elevage">Type d'élevage</label>
                            <div class="input-with-icon">
                                <select id="type_elevage"
                                        name="type_elevage"
                                        class="form-select @error('type_elevage') is-invalid @enderror">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="bovin" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'bovin' ? 'selected' : '' }}>Bovin</option>
                                    <option value="ovin" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'ovin' ? 'selected' : '' }}>Ovin</option>
                                    <option value="caprin" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'caprin' ? 'selected' : '' }}>Caprin</option>
                                    <option value="porcin" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'porcin' ? 'selected' : '' }}>Porcin</option>
                                    <option value="volaille" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'volaille' ? 'selected' : '' }}>Volaille</option>
                                    <option value="cunicole" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'cunicole' ? 'selected' : '' }}>Cunicole</option>
                                    <option value="autre" {{ old('type_elevage', $farm['type_elevage'] ?? '') === 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                                <i class="bi bi-box-seam field-icon"></i>
                            </div>
                            @error('type_elevage')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Photo URL --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="photo">URL de la photo</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="photo"
                                       name="photo"
                                       class="form-control @error('photo') is-invalid @enderror"
                                       placeholder="https://exemple.com/photo.jpg"
                                       value="{{ old('photo', $farm['photo'] ?? '') }}">
                                <i class="bi bi-image field-icon"></i>
                            </div>
                            @error('photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Décrivez brièvement la ferme…">{{ old('description', $farm['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Actions ────────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-actions">
                <a href="{{ route('admin.farms.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="button"
                        class="btn btn-primary btn-sm"
                        @click="submit()"
                        :disabled="loading">
                    <span x-show="!loading">
                        <i class="bi bi-check-lg"></i> Enregistrer les modifications
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm"></span>
                        Enregistrement…
                    </span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/farms/create.js') }}"></script>
@endpush
