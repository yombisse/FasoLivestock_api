@extends('admin.layouts.app')

@section('title', 'Créer un événement sanitaire')
@section('page-title', 'Créer un événement sanitaire')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/form-create.css') }}">
    <style>
        .type-tabs {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0;
        }

        .type-tab {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.25rem;
            border: none;
            background: transparent;
            border-radius: 8px 8px 0 0;
            font-size: .9rem;
            font-weight: 500;
            color: #6c757d;
            cursor: pointer;
            transition: all .2s ease;
            position: relative;
        }

        .type-tab:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .type-tab.active {
            background: #fff;
            color: #0d6efd;
            font-weight: 600;
        }

        .type-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #0d6efd;
        }

        .type-tab i {
            font-size: 1rem;
        }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sante-evenements.index') }}">Événements sanitaires</a>
    </li>
    <li class="breadcrumb-item active">Créer un événement</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="santeEvenementForm()"
     x-init="initForm()">

    {{-- Tabs pour le type d'événement --}}
    <div class="type-tabs">
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'VACCINATION' }"
                @click="type = 'VACCINATION'">
            <i class="bi bi-capsule"></i>
            Vaccination
        </button>
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'TRAITEMENT' }"
                @click="type = 'TRAITEMENT'">
            <i class="bi bi-prescription2"></i>
            Traitement
        </button>
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'MALADIE' }"
                @click="type = 'MALADIE'">
            <i class="bi bi-activity"></i>
            Maladie
        </button>
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'CONTROLE' }"
                @click="type = 'CONTROLE'">
            <i class="bi bi-clipboard-check"></i>
            Contrôle
        </button>
    </div>

    <form id="sante-evenement-form"
          method="POST"
          action="{{ route('admin.sante-evenements.store') }}">
        @csrf
        <input type="hidden" name="type" x-model="type">

        {{-- ── Informations générales ─────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-info-circle"></i></div>
                <h3>Informations de l'événement</h3>
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
                                        @change="loadEligibleAnimals($event.target.value)"
                                        required>
                                    <option value="">Sélectionner une ferme</option>
                                    <template x-for="farm in farms" :key="farm.id">
                                        <option :value="farm.id" x-text="farm.name"></option>
                                    </template>
                                </select>
                                <i class="bi bi-house field-icon"></i>
                            </div>
                            @error('farm_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Date de l'événement --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="date_evenement">
                                Date <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="date" id="date_evenement" name="date_evenement"
                                       class="form-control @error('date_evenement') is-invalid @enderror"
                                       value="{{ old('date_evenement') ?? now()->format('Y-m-d') }}"
                                       required>
                                <i class="bi bi-calendar field-icon"></i>
                            </div>
                            @error('date_evenement') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Animal --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="animal_id">
                                Animal <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <select id="animal_id" name="animal_id"
                                        class="form-select @error('animal_id') is-invalid @enderror"
                                        :disabled="!selectedFarmId || loadingAnimals"
                                        required>
                                    <option value="">Sélectionner un animal</option>
                                    <template x-for="animal in eligibleAnimals" :key="animal.id">
                                        <option :value="animal.id" x-text="animal.nom + (animal.numero_identification ? ' (' + animal.numero_identification + ')' : '')"></option>
                                    </template>
                                </select>
                                <i class="bi bi-cow field-icon"></i>
                            </div>
                            @error('animal_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted" x-show="!selectedFarmId">Sélectionnez d'abord une ferme</small>
                            <small class="text-muted" x-show="selectedFarmId && loadingAnimals">Chargement...</small>
                            <small class="text-muted" x-show="selectedFarmId && !loadingAnimals && eligibleAnimals.length === 0">Aucun animal éligible dans cette ferme</small>
                        </div>
                    </div>

                    {{-- Coût --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="cout">Coût (FCFA)</label>
                            <div class="input-with-icon">
                                <input type="number" id="cout" name="cout"
                                       class="form-control @error('cout') is-invalid @enderror"
                                       placeholder="0"
                                       value="{{ old('cout') }}"
                                       min="0">
                                <i class="bi bi-currency-dollar field-icon"></i>
                            </div>
                            @error('cout') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Détails de l'événement...">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Champs spécifiques selon le type --}}
                    <template x-if="type === 'VACCINATION'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="nom_vaccin">
                                        Nom du vaccin <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <input type="text" id="nom_vaccin" name="metadonnees[nom_vaccin]"
                                               class="form-control"
                                               placeholder="Ex: Fièvre aphteuse"
                                               required>
                                        <i class="bi bi-capsule field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="veterinaire">Vétérinaire</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="veterinaire" name="metadonnees[veterinaire]"
                                               class="form-control"
                                               placeholder="Nom du vétérinaire">
                                        <i class="bi bi-person field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="dosage">Dosage</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="dosage" name="metadonnees[dosage]"
                                               class="form-control"
                                               placeholder="Ex: 2ml">
                                        <i class="bi bi-droplet field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="lot_vaccin">Lot du vaccin</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="lot_vaccin" name="metadonnees[lot_vaccin]"
                                               class="form-control"
                                               placeholder="Numéro de lot">
                                        <i class="bi bi-tag field-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="type === 'TRAITEMENT'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="nom_medicament">
                                        Nom du médicament <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <input type="text" id="nom_medicament" name="metadonnees[nom_medicament]"
                                               class="form-control"
                                               placeholder="Ex: Amoxicilline"
                                               required>
                                        <i class="bi bi-capsule field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="veterinaire">Vétérinaire</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="veterinaire" name="metadonnees[veterinaire]"
                                               class="form-control"
                                               placeholder="Nom du vétérinaire">
                                        <i class="bi bi-person field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="dosage">Dosage</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="dosage" name="metadonnees[dosage]"
                                               class="form-control"
                                               placeholder="Ex: 500mg">
                                        <i class="bi bi-droplet field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="duree">Durée</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="duree" name="metadonnees[duree]"
                                               class="form-control"
                                               placeholder="Ex: 7 jours">
                                        <i class="bi bi-clock field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="frequence">Fréquence</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="frequence" name="metadonnees[frequence]"
                                               class="form-control"
                                               placeholder="Ex: 2x/jour">
                                        <i class="bi bi-arrow-repeat field-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="type === 'MALADIE'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="nom_maladie">
                                        Nom de la maladie <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <input type="text" id="nom_maladie" name="metadonnees[nom_maladie]"
                                               class="form-control"
                                               placeholder="Ex: Fièvre aphteuse"
                                               required>
                                        <i class="bi bi-activity field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="gravite">Gravité</label>
                                    <div class="input-with-icon">
                                        <select id="gravite" name="metadonnees[gravite]"
                                                class="form-control">
                                            <option value="">Sélectionner...</option>
                                            <option value="legere">Légère</option>
                                            <option value="moderee">Modérée</option>
                                            <option value="grave">Grave</option>
                                        </select>
                                        <i class="bi bi-exclamation-triangle field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="symptomes">Symptômes</label>
                                    <div class="input-with-icon">
                                        <textarea id="symptomes" name="metadonnees[symptomes]"
                                                  class="form-control"
                                                  rows="2"
                                                  placeholder="Décrire les symptômes..."></textarea>
                                        <i class="bi bi-list-check field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="veterinaire">Vétérinaire</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="veterinaire" name="metadonnees[veterinaire]"
                                               class="form-control"
                                               placeholder="Nom du vétérinaire">
                                        <i class="bi bi-person field-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="type === 'CONTROLE'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="type_controle">Type de contrôle</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="type_controle" name="metadonnees[type_controle]"
                                               class="form-control"
                                               placeholder="Ex: Contrôle de routine">
                                        <i class="bi bi-clipboard-check field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="veterinaire">Vétérinaire</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="veterinaire" name="metadonnees[veterinaire]"
                                               class="form-control"
                                               placeholder="Nom du vétérinaire">
                                        <i class="bi bi-person field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="resultat">Résultat</label>
                                    <div class="input-with-icon">
                                        <textarea id="resultat" name="metadonnees[resultat]"
                                                  class="form-control"
                                                  rows="2"
                                                  placeholder="Résultat du contrôle..."></textarea>
                                        <i class="bi bi-check-circle field-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        {{-- ── Actions ─────────────────────────────────────────── --}}
        <div class="form-actions">
            <a href="{{ route('admin.sante-evenements.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg"></i>
                Enregistrer
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script>
        function santeEvenementForm() {
            return {
                type: '{{ request('type', 'VACCINATION') }}',
                typeLabels: {
                    'VACCINATION': 'Vaccination',
                    'TRAITEMENT': 'Traitement',
                    'MALADIE': 'Maladie',
                    'CONTROLE': 'Contrôle',
                },
                farms: @js($farms),
                selectedFarmId: null,
                eligibleAnimals: [],
                loadingAnimals: false,

                get typeLabel() {
                    return this.typeLabels[this.type] || 'Événement sanitaire';
                },

                async initForm() {
                    // Initialize type from URL parameter if provided
                    const urlParams = new URLSearchParams(window.location.search);
                    const typeParam = urlParams.get('type');
                    if (typeParam && this.typeLabels[typeParam]) {
                        this.type = typeParam;
                    }
                },

                async loadEligibleAnimals(farmId) {
                    this.selectedFarmId = farmId;
                    if (!farmId) {
                        this.eligibleAnimals = [];
                        return;
                    }

                    this.loadingAnimals = true;
                    try {
                        const response = await fetch('/admin/animals/eligible/sanitaire?farm_id=' + farmId + '&type_evenement=' + this.type, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        this.eligibleAnimals = data.success ? (data.data?.animals || []) : [];
                    } catch (error) {
                        console.error('Error loading eligible animals:', error);
                        this.eligibleAnimals = [];
                    } finally {
                        this.loadingAnimals = false;
                    }
                }
            };
        }
    </script>
@endpush
