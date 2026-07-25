@extends('admin.layouts.systeme')

@section('title', 'Nouvelle ferme')
@section('page-title', 'Nouvelle ferme')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/form-create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.farms.index') }}">Fermes</a>
    </li>
    <li class="breadcrumb-item active">Nouvelle ferme</li>
@endsection

@section('content')
<script>
    window.farmFormData = {
        showOwnerField: {{ $showOwnerField ? 'true' : 'false' }},
        ownerFieldRequired: {{ $ownerFieldRequired ? 'true' : 'false' }},
        potentialOwners: {{ \Illuminate\Support\Js::from($potentialOwners) }},
        currentUser: {{ \Illuminate\Support\Js::from($currentUser) }},
        oldOwnerId: '{{ old('owner_id') }}'
    };
</script>
<div class="form-page fade-in" x-data="farmCreateForm(window.farmFormData)">

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

                    {{-- Type d'élevage --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="type_elevage">Type d'élevage</label>
                            <div class="input-with-icon">
                                <select id="type_elevage"
                                        name="type_elevage"
                                        class="form-select @error('type_elevage') is-invalid @enderror">
                                    <option value="">Sélectionner...</option>
                                    <option value="bovin" {{ old('type_elevage') === 'bovin' ? 'selected' : '' }}>Bovin</option>
                                    <option value="ovin" {{ old('type_elevage') === 'ovin' ? 'selected' : '' }}>Ovin</option>
                                    <option value="caprin" {{ old('type_elevage') === 'caprin' ? 'selected' : '' }}>Caprin</option>
                                    <option value="porcin" {{ old('type_elevage') === 'porcin' ? 'selected' : '' }}>Porcin</option>
                                    <option value="volaille" {{ old('type_elevage') === 'volaille' ? 'selected' : '' }}>Volaille</option>
                                    <option value="cunicole" {{ old('type_elevage') === 'cunicole' ? 'selected' : '' }}>Cunicole</option>
                                    <option value="mixte" {{ old('type_elevage') === 'mixte' ? 'selected' : '' }}>Mixte</option>
                                    <option value="autre" {{ old('type_elevage') === 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                                <i class="bi bi-list-ul field-icon"></i>
                            </div>
                            @error('type_elevage')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Photo --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="photo">Photo de la ferme</label>
                            <div class="input-with-icon">
                                <input type="file"
                                       id="photo"
                                       name="photo"
                                       class="form-control @error('photo') is-invalid @enderror"
                                       accept="image/*"
                                       @change="handlePhotoPreview($event)">
                                <i class="bi bi-image field-icon"></i>
                            </div>
                            @error('photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div x-show="photoPreview" class="mt-2">
                                <img :src="photoPreview" class="img-thumbnail" style="max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="removePhoto()">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Propriétaire ───────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h3>Propriétaire de la ferme</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="owner_id">
                                Propriétaire <span x-show="ownerFieldRequired" class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="owner_search"
                                       class="form-control @error('owner_id') is-invalid @enderror"
                                       placeholder="Rechercher par nom ou email..."
                                       x-model="ownerSearch">
                                <input type="hidden"
                                       id="owner_id"
                                       name="owner_id"
                                       :value="selectedOwnerId"
                                       :required="ownerFieldRequired">
                                <i class="bi bi-person-check field-icon"></i>
                            </div>
                            @error('owner_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div x-show="potentialOwners.length > 0" class="mt-2 border rounded p-2" style="max-height: 250px; overflow-y: auto;">
                                <template x-for="user in potentialOwners" :key="user.id">
                                    <div class="p-2 hover:bg-light cursor-pointer d-flex justify-content-between align-items-center"
                                         @click="selectOwner(user)"
                                         :class="selectedOwnerId === user.id ? 'bg-light' : ''">
                                        <div>
                                            <div x-text="user.name" class="fw-bold"></div>
                                            <small class="text-muted" x-text="user.email"></small>
                                        </div>
                                        <span x-show="user.is_superadmin" class="badge bg-primary">Superadmin</span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedOwnerId" class="mt-2">
                                <span class="badge bg-success">
                                    <span x-text="getSelectedOwnerName()"></span>
                                    <button type="button" class="btn-close btn-close-white ms-2" @click="clearOwner()"></button>
                                </span>
                            </div>
                            <p class="form-hint mt-2" x-show="!ownerFieldRequired">
                                <i class="bi bi-info-circle"></i>
                                Laissez vide pour que vous deveniez le propriétaire.
                            </p>
                            <p class="form-hint mt-2" x-show="ownerFieldRequired">
                                <i class="bi bi-info-circle"></i>
                                Vous devez sélectionner un propriétaire différent de vous-même.
                            </p>
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

                <p class="form-hint" x-show="!showOwnerField">
                    <i class="bi bi-info-circle"></i>
                    Vous serez automatiquement défini comme propriétaire.
                    Vous pourrez ajouter des membres supplémentaires depuis la fiche de la ferme.
                </p>
                <p class="form-hint" x-show="showOwnerField && !ownerFieldRequired">
                    <i class="bi bi-info-circle"></i>
                    Vous serez ajouté comme manager de cette ferme.
                    Vous pourrez ajouter des membres supplémentaires depuis la fiche de la ferme.
                </p>

                <div class="mt-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Gérer les utilisateurs
                    </a>
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