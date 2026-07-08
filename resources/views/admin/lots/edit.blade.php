@extends('admin.layouts.app')

@section('title', 'Modifier le lot')
@section('page-title', 'Modifier le lot')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/lots/edit.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.lots.index') }}">Lots</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div x-data="lotEditForm()" class="form-page fade-in">

    <form id="lot-edit-form"
          method="POST"
          action="{{ route('admin.lots.update', $lot['id']) }}">
        @csrf
        @method('PUT')

        {{-- ── Informations générales ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-grid"></i>
                </div>
                <h3>Modifier le lot : {{ $lot['nom'] ?? '' }}</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Ferme --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="farm_id">
                                Ferme <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="farm_id"
                                        name="farm_id"
                                        class="form-select @error('farm_id') is-invalid @enderror"
                                        required>
                                    <option value="">Sélectionner une ferme</option>
                                    @foreach($farms as $farm)
                                    <option value="{{ $farm['id'] }}"
                                            {{ old('farm_id', $lot['farm_id'] ?? '') === $farm['id'] ? 'selected' : '' }}>
                                        {{ $farm['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-house field-icon"></i>
                            </div>
                            @error('farm_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Nom --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="nom_lot">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="nom_lot"
                                       name="nom_lot"
                                       class="form-control @error('nom_lot') is-invalid @enderror"
                                       placeholder="Ex: Lot Bovins A"
                                       value="{{ old('nom_lot', $lot['nom_lot'] ?? '') }}"
                                       required>
                                <i class="bi bi-tag field-icon"></i>
                            </div>
                            @error('nom_lot')
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
                                      placeholder="Description du lot...">{{ old('description', $lot['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('admin.lots.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Annuler
                </a>
                <button type="submit"
                        class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg"></i>
                    Mettre à jour
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/lots/edit.js') }}"></script>
@endpush
