@extends('admin.layouts.app')

@section('title', 'Enregistrer une naissance')
@section('page-title', 'Enregistrer une naissance')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/form-create.css') }}">
    <style>
        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .85rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        .mode-badge.naissance { background: #e8f5e9; color: #2e7d32; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index') }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Naissance</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="animalCreateForm()">

    <div class="mode-badge naissance">
        <i class="bi bi-heart"></i>
        Naissance — un événement NAISSANCE sera automatiquement créé lors de l'enregistrement
    </div>

    <form id="animal-create-form"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('admin.animals.naissance') }}">
        @csrf

        {{-- ── Informations générales de l'animal ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-cow"></i></div>
                <h3>Informations du nouveau-né</h3>
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
                                <select id="farm_id" name="farm_id"
                                        class="form-select @error('farm_id') is-invalid @enderror"
                                        @change="loadLots($event.target.value)"
                                        required>
                                    <option value="">Sélectionner une ferme</option>
                                    @foreach($farms as $farm)
                                    <option value="{{ $farm['id'] }}"
                                            {{ (old('farm_id') ?? ($farm_id ?? null)) === $farm['id'] ? 'selected' : '' }}>
                                        {{ $farm['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-house field-icon"></i>
                            </div>
                            @error('farm_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Nom --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="nom">Nom</label>
                            <div class="input-with-icon">
                                <input type="text" id="nom" name="nom"
                                       class="form-control @error('nom') is-invalid @enderror"
                                       placeholder="Ex: Petit Bétel"
                                       value="{{ old('nom') }}">
                                <i class="bi bi-tag field-icon"></i>
                            </div>
                            @error('nom') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Numéro identification --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="numero_identification">Numéro d'identification</label>
                            <div class="input-with-icon">
                                <input type="text" id="numero_identification" name="numero_identification"
                                       class="form-control @error('numero_identification') is-invalid @enderror"
                                       placeholder="Ex: AN-2024-001"
                                       value="{{ old('numero_identification') }}">
                                <i class="bi bi-hash field-icon"></i>
                            </div>
                            @error('numero_identification') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Sexe --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="sexe">Sexe</label>
                            <div class="input-with-icon">
                                <select id="sexe" name="sexe"
                                        class="form-select @error('sexe') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    <option value="male"    {{ old('sexe') === 'male'    ? 'selected' : '' }}>Mâle</option>
                                    <option value="femelle" {{ old('sexe') === 'femelle' ? 'selected' : '' }}>Femelle</option>
                                </select>
                                <i class="bi bi-gender-ambiguous field-icon"></i>
                            </div>
                            @error('sexe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Espèce --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="espece_id">
                                Espèce <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="espece_id" name="espece_id"
                                        class="form-select @error('espece_id') is-invalid @enderror"
                                        required>
                                    <option value="">Sélectionner</option>
                                    @foreach($especes as $espece)
                                    <option value="{{ $espece['id'] }}"
                                            {{ old('espece_id') === $espece['id'] ? 'selected' : '' }}>
                                        {{ $espece['nom'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-tree field-icon"></i>
                            </div>
                            @error('espece_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Race --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="race">Race</label>
                            <div class="input-with-icon">
                                <input type="text" id="race" name="race"
                                       class="form-control @error('race') is-invalid @enderror"
                                       placeholder="Ex: Zébu"
                                       value="{{ old('race') }}">
                                <i class="bi bi-palette field-icon"></i>
                            </div>
                            @error('race') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Date de naissance (obligatoire pour naissance) --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="date_naissance">
                                Date de naissance <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="date" id="date_naissance" name="date_naissance"
                                       class="form-control @error('date_naissance') is-invalid @enderror"
                                       value="{{ old('date_naissance', date('Y-m-d')) }}"
                                       required>
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_naissance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Poids à la naissance --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="poids">Poids à la naissance (kg)</label>
                            <div class="input-with-icon">
                                <input type="number" id="poids" name="poids"
                                       class="form-control @error('poids') is-invalid @enderror"
                                       placeholder="Ex: 25" step="0.1" min="0"
                                       value="{{ old('poids') }}">
                                <i class="bi bi-speedometer field-icon"></i>
                            </div>
                            @error('poids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Lot --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="lot_id">Lot</label>
                            <div class="input-with-icon">
                                <select id="lot_id" name="lot_id"
                                        class="form-select @error('lot_id') is-invalid @enderror"
                                        x-model="selectedLot">
                                    <option value="">Sélectionner</option>
                                    <template x-for="lot in lots" :key="lot.id">
                                        <option :value="lot.id" x-text="lot.nom"></option>
                                    </template>
                                </select>
                                <i class="bi bi-grid field-icon"></i>
                            </div>
                            @error('lot_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Statut --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="statut">Statut</label>
                            <div class="input-with-icon">
                                <select id="statut" name="statut"
                                        class="form-select @error('statut') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    <option value="SAIN"           {{ old('statut') === 'SAIN'           ? 'selected' : '' }}>Sain</option>
                                    <option value="MALADE"         {{ old('statut') === 'MALADE'         ? 'selected' : '' }}>Malade</option>
                                    <option value="EN_TRAITEMENT"  {{ old('statut') === 'EN_TRAITEMENT'  ? 'selected' : '' }}>En traitement</option>
                                    <option value="VENDU"          {{ old('statut') === 'VENDU'          ? 'selected' : '' }}>Vendu</option>
                                    <option value="MORT"           {{ old('statut') === 'MORT'           ? 'selected' : '' }}>Mort</option>
                                    <option value="PERDU"          {{ old('statut') === 'PERDU'          ? 'selected' : '' }}>Perdu</option>
                                </select>
                                <i class="bi bi-heart-pulse field-icon"></i>
                            </div>
                            @error('statut') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Photo --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="photo">Photo</label>
                            <div class="input-with-icon">
                                <input type="file" id="photo" name="photo"
                                       class="form-control @error('photo') is-invalid @enderror"
                                       accept="image/*">
                                <i class="bi bi-camera field-icon"></i>
                            </div>
                            @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Informations de naissance ───────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-heart"></i></div>
                <h3>Informations de naissance</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Mère --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="mother_id">Mère</label>
                            <div class="input-with-icon">
                                <select id="mother_id" name="mother_id"
                                        class="form-select @error('mother_id') is-invalid @enderror"
                                        @change="onMotherChange($event.target.value)"
                                        :disabled="!selectedFarmId">
                                    <option value="">Sélectionner (optionnel)</option>
                                    <template x-for="mother in eligibleMothers" :key="mother.id">
                                        <option :value="mother.id" x-text="mother.nom + (mother.numero_identification ? ' (' + mother.numero_identification + ')' : '')"></option>
                                    </template>
                                </select>
                                <i class="bi bi-person-heart field-icon"></i>
                            </div>
                            @error('mother_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted" x-show="!selectedFarmId">Sélectionnez d'abord une ferme</small>
                            <small class="text-muted" x-show="selectedFarmId && eligibleMothers.length === 0">Aucune femelle éligible (en gestation) dans cette ferme</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i>
                                Un événement de type <strong>NAISSANCE</strong> sera automatiquement créé lors de l'enregistrement.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Actions ─────────────────────────────────── --}}
        <div class="form-actions">
            <a href="{{ route('admin.animals.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg"></i>
                Enregistrer la naissance
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/animals/create.js') }}"></script>
    <script>
        window.farmsData = @js($farms);
        window.lotsData = @js($lots);
        window.modeData = 'naissance';
        window.lotsByFarmUrl = '{{ route('admin.animals.lots-by-farm') }}';
        window.eligibleMothersUrl = '{{ route('api.animals.eligible.reproduction') }}';
    </script>
@endpush
