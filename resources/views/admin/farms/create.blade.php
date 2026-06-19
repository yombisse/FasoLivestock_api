@extends('admin.layouts.app')

@section('title', 'Nouvelle ferme')
@section('page-title', 'Nouvelle ferme')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/farms/create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.farms.index') }}">Fermes</a>
    </li>
    <li class="breadcrumb-item active">Nouvelle ferme</li>
@endsection

@section('content')
<div class="form-page fade-in" x-data="farmCreateForm()">

    <form id="farm-create-form"
          method="POST"
          action="{{ route('admin.farms.store') }}">
        @csrf

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
                                       value="{{ old('name') }}"
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
                                       value="{{ old('location') }}">
                                <i class="bi bi-geo-alt field-icon"></i>
                            </div>
                            @error('location')
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
                                      placeholder="Décrivez brièvement la ferme…">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Membres ────────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3>Membres de la ferme</h3>
            </div>
            <div class="form-section-body">

                <p class="form-hint">
                    <i class="bi bi-info-circle"></i>
                    Vous serez automatiquement défini comme propriétaire.
                    Vous pourrez ajouter des membres supplémentaires depuis la fiche de la ferme.
                </p>

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
                        <i class="bi bi-check-lg"></i> Créer la ferme
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm"></span>
                        Création…
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