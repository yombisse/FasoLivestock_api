@extends('admin.layouts.app')

@section('content')
<div class="alert alert-warning">
    Modification de mouvements désactivée : les mouvements ne doivent pas être gérés manuellement via l’interface web.
</div>
@endsection

@php
    // Rendu du reste du fichier désactivé.
@endphp

{{--
    Le contenu original est conservé mais ne sera pas rendu.
--}}

@section('title', 'Modifier mouvement')
@section('page-title', 'Modifier mouvement')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/edit.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.mouvements.index') }}">Mouvements</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div x-data="mouvementEditForm()" class="form-page fade-in">

    <form id="mouvement-edit-form"
          method="POST"
          action="{{ route('admin.mouvements.update', $mouvement['id']) }}">
        @csrf
        @method('PUT')

        {{-- ─── Informations générales ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h3>Modifier le mouvement</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Type d'événement --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="type_evenement_id">
                                Type d'événement <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="type_evenement_id"
                                        name="type_evenement_id"
                                        class="form-select @error('type_evenement_id') is-invalid @enderror"
                                        x-model="type"
                                        required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="ACHAT" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'ACHAT' ? 'selected' : '' }}>Achat</option>
                                    <option value="VENTE" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'VENTE' ? 'selected' : '' }}>Vente</option>
                                    <option value="DECES" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'DECES' ? 'selected' : '' }}>Décès</option>
                                    <option value="PERTE" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'PERTE' ? 'selected' : '' }}>Perte</option>
                                    <option value="TRANSFERT" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'TRANSFERT' ? 'selected' : '' }}>Transfert</option>
                                    <option value="ABATTAGE" {{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') === 'ABATTAGE' ? 'selected' : '' }}>Abattage</option>
                                </select>
                                <i class="bi bi-tag field-icon"></i>
                            </div>
                            @error('type_evenement_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

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

                    {{-- Date --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="date_evenement">
                                Date <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="date"
                                       id="date_evenement"
                                       name="date_evenement"
                                       class="form-control @error('date_evenement') is-invalid @enderror"
                                       value="{{ old('date_evenement', $mouvement['date_evenement'] ?? '') }}"
                                       required>
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_evenement')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Coût (conditionnel) --}}
                    <div class="col-md-6"
                         x-show="type === 'ACHAT' || type === 'VENTE' || type === 'ABATTAGE'">
                        <div class="form-group">
                            <label class="form-label" for="cout">Coût (FCFA)</label>
                            <div class="input-with-icon">
                                <input type="number"
                                       id="cout"
                                       name="cout"
                                       class="form-control @error('cout') is-invalid @enderror"
                                       placeholder="Ex: 50000"
                                       min="0"
                                       step="0.01"
                                       value="{{ old('cout', $mouvement['cout'] ?? '') }}">
                                <i class="bi bi-currency-dollar field-icon"></i>
                            </div>
                            @error('cout')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Ferme destination (conditionnel - TRANSFERT) --}}
                    <div class="col-md-6"
                         x-show="type === 'TRANSFERT'">
                        <div class="form-group">
                            <label class="form-label" for="farm_destination_id">
                                Ferme destination <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="farm_destination_id"
                                        name="farm_destination_id"
                                        class="form-select @error('farm_destination_id') is-invalid @enderror">
                                    <option value="">Sélectionner une ferme</option>
                                    {{-- TODO: Charger les fermes depuis l'API --}}
                                </select>
                                <i class="bi bi-house field-icon"></i>
                            </div>
                            @error('farm_destination_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Fournisseur (conditionnel - ACHAT) --}}
                    <div class="col-md-6"
                         x-show="type === 'ACHAT'">
                        <div class="form-group">
                            <label class="form-label" for="fournisseur">Fournisseur</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="fournisseur"
                                       name="fournisseur"
                                       class="form-control @error('fournisseur') is-invalid @enderror"
                                       placeholder="Nom du fournisseur"
                                       value="{{ old('fournisseur', $mouvement['fournisseur'] ?? '') }}">
                                <i class="bi bi-person field-icon"></i>
                            </div>
                            @error('fournisseur')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Acheteur (conditionnel - VENTE) --}}
                    <div class="col-md-6"
                         x-show="type === 'VENTE'">
                        <div class="form-group">
                            <label class="form-label" for="acheteur">Acheteur</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="acheteur"
                                       name="acheteur"
                                       class="form-control @error('acheteur') is-invalid @enderror"
                                       placeholder="Nom de l'acheteur"
                                       value="{{ old('acheteur', $mouvement['acheteur'] ?? '') }}">
                                <i class="bi bi-person field-icon"></i>
                            </div>
                            @error('acheteur')
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
                                      placeholder="Description du mouvement...">{{ old('description', $mouvement['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('admin.mouvements.index') }}"
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
    <script src="{{ asset('admin/js/mouvements/edit.js') }}"></script>
@endpush
