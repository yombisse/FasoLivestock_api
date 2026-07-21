@extends('admin.layouts.app')

@section('title', 'Achat d\'un animal')
@section('page-title', 'Achat d\'un animal')

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
        .mode-badge.achat { background: #e3f2fd; color: #1565c0; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index') }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Achat</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="animalCreateForm()">

    <div class="mode-badge achat">
        <i class="bi bi-cart-plus"></i>
        Achat — un événement ACHAT et une transaction SORTIE seront enregistrés
    </div>

    <form id="animal-create-form"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('admin.animals.purchase') }}">
        @csrf

        {{-- ── Informations générales de l'animal ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-cow"></i></div>
                <h3>Informations de l'animal</h3>
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
                                       placeholder="Ex: Bétel"
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

                    {{-- Date de naissance --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="date_naissance">Date de naissance</label>
                            <div class="input-with-icon">
                                <input type="date" id="date_naissance" name="date_naissance"
                                       class="form-control @error('date_naissance') is-invalid @enderror"
                                       value="{{ old('date_naissance') }}">
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_naissance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Poids --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="poids">Poids (kg)</label>
                            <div class="input-with-icon">
                                <input type="number" id="poids" name="poids"
                                       class="form-control @error('poids') is-invalid @enderror"
                                       placeholder="Ex: 250" step="0.1" min="0"
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

        {{-- ── Informations de l'achat (Transaction) ─────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-receipt"></i></div>
                <h3>Informations de l'achat</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Date d'achat --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="date_achat">
                                Date d'achat <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="date" id="date_achat" name="date_achat"
                                       class="form-control @error('date_achat') is-invalid @enderror"
                                       value="{{ old('date_achat', date('Y-m-d')) }}"
                                       required>
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_achat') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Prix d'achat --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="prix_achat">
                                Prix d'achat <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="number" id="prix_achat" name="prix_achat"
                                       class="form-control @error('prix_achat') is-invalid @enderror"
                                       placeholder="Ex: 150000" step="0.01" min="0"
                                       value="{{ old('prix_achat') }}"
                                       required>
                                <i class="bi bi-currency-dollar field-icon"></i>
                            </div>
                            @error('prix_achat') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Catégorie de transaction --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="categorie_id">Catégorie de dépense</label>
                            <div class="input-with-icon">
                                <select id="categorie_id" name="categorie_id"
                                        class="form-select @error('categorie_id') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach($categories as $categorie)
                                    <option value="{{ $categorie['id'] }}"
                                            {{ old('categorie_id') === $categorie['id'] ? 'selected' : '' }}>
                                        {{ $categorie['nom_categorie'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-tags field-icon"></i>
                            </div>
                            @error('categorie_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Source de l'animal --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Source de l'animal</label>
                            <div class="btn-group w-100 mb-2" role="group">
                                <input type="radio" class="btn-check" name="source_type" id="source_farm" value="farm" @if(old('source_type') === 'farm' || old('farm_source_id')) checked @endif>
                                <label class="btn btn-outline-primary" for="source_farm">
                                    <i class="bi bi-house"></i> Ferme du système
                                </label>
                                <input type="radio" class="btn-check" name="source_type" id="source_external" value="external" @if(old('source_type') === 'external' || old('provenance')) checked @endif>
                                <label class="btn btn-outline-primary" for="source_external">
                                    <i class="bi bi-shop"></i> Externe
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Ferme source (si ferme du système) --}}
                    <div class="col-md-4" x-show="document.querySelector('input[name=\"source_type\"]:checked')?.value === 'farm'">
                        <div class="form-group">
                            <label class="form-label" for="farm_source_id">Ferme source</label>
                            <div class="input-with-icon">
                                <select id="farm_source_id" name="farm_source_id"
                                        class="form-select @error('farm_source_id') is-invalid @enderror">
                                    <option value="">Sélectionner une ferme</option>
                                    @foreach($farms as $farm)
                                    <option value="{{ $farm['id'] }}"
                                            {{ old('farm_source_id') === $farm['id'] ? 'selected' : '' }}>
                                        {{ $farm['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-house field-icon"></i>
                            </div>
                            @error('farm_source_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Provenance externe (si externe) --}}
                    <div class="col-md-4" x-show="document.querySelector('input[name=\"source_type\"]:checked')?.value === 'external'">
                        <div class="form-group">
                            <label class="form-label" for="provenance">Provenance / Fournisseur</label>
                            <div class="input-with-icon">
                                <input type="text" id="provenance" name="provenance"
                                       class="form-control @error('provenance') is-invalid @enderror"
                                       placeholder="Ex: Marché de Bobo"
                                       value="{{ old('provenance') }}">
                                <i class="bi bi-shop field-icon"></i>
                            </div>
                            @error('provenance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                Enregistrer l'achat
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
        window.modeData = 'achat';
        window.lotsByFarmUrl = '{{ route('admin.animals.lots-by-farm') }}';
    </script>
@endpush
