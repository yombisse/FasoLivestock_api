@extends('admin.layouts.app')

@section('title', 'Nouvelle espèce')
@section('page-title', 'Nouvelle espèce')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/especes/create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.especes.index') }}">Espèces</a>
    </li>
    <li class="breadcrumb-item active">Nouvelle espèce</li>
@endsection

@section('content')
<div x-data="especeCreateForm()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-tree me-2 text-success"></i>Nouvelle espèce</h2>
            <p>Ajoutez une nouvelle espèce animale</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.especes.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Formulaire ─────────────────────────────────────── --}}
    <div class="users-card">
        <form id="espece-create-form"
              method="POST"
              action="{{ route('admin.especes.store') }}">
            @csrf

            <div class="row">
                {{-- Nom --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                        <div class="input-with-icon">
                            <input type="text"
                                   id="nom"
                                   name="nom"
                                   class="form-control @error('nom') is-invalid @enderror"
                                   placeholder="Ex: Bovin, Ovin, Caprin..."
                                   value="{{ old('nom') }}"
                                   required>
                            <i class="bi bi-type field-icon"></i>
                        </div>
                        @error('nom')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <div class="input-with-icon">
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Description de l'espèce...">{{ old('description') }}</textarea>
                            <i class="bi bi-card-text field-icon"></i>
                        </div>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Actions ────────────────────────────────────── --}}
            <div class="form-actions">
                <a href="{{ route('admin.especes.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i>
                    Créer l'espèce
                </button>
            </div>

        </form>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/especes/create.js') }}"></script>
@endpush
