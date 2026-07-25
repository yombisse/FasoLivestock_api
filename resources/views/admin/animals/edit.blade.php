@extends('admin.layouts.ferme')

@section('title', 'Modifier ' . $animal['nom'])
@section('page-title', 'Modifier un animal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/users/create.css') }}">
    <style>
        .origin-info-bar {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem 1rem;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }
        .origin-info-bar.birth  { background: #e8f5e9; color: #2e7d32; }
        .origin-info-bar.achat  { background: #e3f2fd; color: #1565c0; }
        .origin-info-bar.reg    { background: #f5f5f5; color: #555; border: 1px solid #e0e0e0; }
        .origin-info-bar .sep   { color: inherit; opacity: .4; margin: 0 .25rem; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="animalEditForm()">

    {{-- Barre d'information sur l'origine (lecture seule, non modifiable) --}}
    @if($animal['naissance_id'])
        <div class="origin-info-bar birth">
            <i class="bi bi-stars"></i>
            Origine : <strong>Naissance</strong>
            @if($animal['mother'])
                <span class="sep">|</span>
                Mère : <strong>{{ $animal['mother']['nom'] }}</strong>
            @endif
            <span class="sep">|</span>
            <small>L'origine n'est pas modifiable ici — voir le module <a href="{{ route('admin.naissances.index', ['farm' => request()->route('farm')]) }}" style="color:inherit;text-decoration:underline">Naissances</a>.</small>
        </div>
    @elseif(isset($animal['origine']) && $animal['origine'] === 'achat')
        <div class="origin-info-bar achat">
            <i class="bi bi-cart-check"></i>
            Origine : <strong>Achat</strong>
            <span class="sep">|</span>
            <small>Les détails financiers sont dans le module <a href="{{ route('admin.mouvements.index', ['farm' => request()->route('farm')]) }}" style="color:inherit;text-decoration:underline">Mouvements</a>.</small>
        </div>
    @else
        <div class="origin-info-bar reg">
            <i class="bi bi-box-arrow-in-down"></i>
            Origine : <strong>Import direct</strong>
        </div>
    @endif

    <form id="animal-edit-form"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('admin.animals.update', $animal['id']) }}">
        @csrf
        @method('PUT')

        {{-- ── Informations générales ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-cow"></i></div>
                <h3>Informations générales</h3>
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
                                            {{ old('farm_id', $animal['farm_id']) === $farm['id'] ? 'selected' : '' }}>
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
                            <label class="form-label" for="nom">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="text" id="nom" name="nom"
                                       class="form-control @error('nom') is-invalid @enderror"
                                       placeholder="Ex: Bétel"
                                       value="{{ old('nom', $animal['nom']) }}" required>
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
                                       value="{{ old('numero_identification', $animal['numero_identification']) }}">
                                <i class="bi bi-hash field-icon"></i>
                            </div>
                            @error('numero_identification') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Sexe --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="sexe">
                                Sexe <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="sexe" name="sexe"
                                        class="form-select @error('sexe') is-invalid @enderror"
                                        required>
                                    <option value="">Sélectionner</option>
                                    <option value="male"    {{ old('sexe', $animal['sexe']) === 'male'    ? 'selected' : '' }}>Mâle</option>
                                    <option value="femelle" {{ old('sexe', $animal['sexe']) === 'femelle' ? 'selected' : '' }}>Femelle</option>
                                </select>
                                <i class="bi bi-gender-ambiguous field-icon"></i>
                            </div>
                            @error('sexe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Espèce --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="espece_id">Espèce</label>
                            <div class="input-with-icon">
                                <select id="espece_id" name="espece_id"
                                        class="form-select @error('espece_id') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach($especes as $espece)
                                    <option value="{{ $espece['id'] }}"
                                            {{ old('espece_id', $animal['espece_id']) === $espece['id'] ? 'selected' : '' }}>
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
                                       value="{{ old('race', $animal['race']) }}">
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
                                       value="{{ old('date_naissance', $animal['date_naissance']) }}">
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
                                       value="{{ old('poids', $animal['poids']) }}">
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
                            @if($animal['photo'])
                                <div class="mt-2">
                                    <small class="text-muted">Photo actuelle:</small><br>
                                    <img src="{{ asset($animal['photo']) }}" alt="Photo actuelle" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        </div>
                    </div>

                    {{--
                        NOTE : le champ mother_id est intentionnellement absent ici.
                        La relation mère-enfant est établie par le module Naissances
                        et ne doit pas être modifiée directement sur l'animal.
                    --}}

                </div>
            </div>
        </div>

        {{-- ── Actions ─────────────────────────────────── --}}
        <div class="form-actions">
            <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg"></i>
                Enregistrer les modifications
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    function animalEditForm() {
        return {
            farmId: '{{ $farmId }}',
            farms: @js($farms),
            lots: @js($lots),
            animal: @js($animal),
            selectedLot: @js($animal['lot_id'] ?? ''),

            loadLots(farmId) {
                if (!farmId) {
                    this.lots = [];
                    this.selectedLot = '';
                    return;
                }
                fetch(`/admin/fermes/${farmId}/animals/lots-by-farm`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.lots = data.lots;
                            // Conserver le lot actuel si la ferme ne change pas
                            this.selectedLot = this.animal.lot_id || '';
                        }
                    })
                    .catch(err => console.error('Erreur chargement lots:', err));
            },

            init() {
                // Charger les lots de la ferme actuelle au montage
                if (this.farmId) {
                    this.loadLots(this.farmId);
                }
            }
        };
    }
</script>
@endpush