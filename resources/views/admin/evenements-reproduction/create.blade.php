@extends('admin.layouts.app')

@section('title', 'Créer un événement de reproduction')
@section('page-title', 'Créer un événement de reproduction')

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
        <a href="{{ route('admin.evenements-reproduction.index') }}">Événements de reproduction</a>
    </li>
    <li class="breadcrumb-item active">Créer un événement</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="reproductionEvenementForm()"
     x-init="initForm()">

    {{-- Tabs pour le type d'événement --}}
    <div class="type-tabs">
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'SAILLIE' }"
                @click="type = 'SAILLIE'">
            <i class="bi bi-heart-pulse"></i>
            Saillie
        </button>
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'GESTATION' }"
                @click="type = 'GESTATION'">
            <i class="bi bi-emoji-smile"></i>
            Gestation
        </button>
        <button type="button"
                class="type-tab"
                :class="{ active: type === 'MISE_BAS' }"
                @click="type = 'MISE_BAS'">
            <i class="bi bi-baby"></i>
            Mise bas
        </button>
    </div>

    <form id="reproduction-evenement-form"
          method="POST"
          action="{{ route('admin.evenements-reproduction.store') }}">
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
                                Animal (femelle) <span class="text-danger">*</span>
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

                    {{-- Mâle (uniquement pour saillie) --}}
                    <template x-if="type === 'SAILLIE'">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="male_id">Mâle (optionnel)</label>
                                <div class="input-with-icon">
                                    <select id="male_id" name="male_id"
                                            class="form-select @error('male_id') is-invalid @enderror"
                                            :disabled="!selectedFarmId || loadingMales">
                                        <option value="">Aucun (insémination artificielle)</option>
                                        <template x-for="male in eligibleMales" :key="male.id">
                                            <option :value="male.id" x-text="male.nom + (male.numero_identification ? ' (' + male.numero_identification + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <i class="bi bi-gender-male field-icon"></i>
                                </div>
                                @error('male_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <small class="text-muted" x-show="!selectedFarmId">Sélectionnez d'abord une ferme</small>
                                <small class="text-muted" x-show="selectedFarmId && loadingMales">Chargement...</small>
                                <small class="text-muted" x-show="selectedFarmId && !loadingMales && eligibleMales.length === 0">Aucun mâle disponible dans cette ferme</small>
                            </div>
                        </div>
                    </template>

                    {{-- Champs spécifiques selon le type --}}
                    <template x-if="type === 'GESTATION'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="duree_gestation">Durée gestation (jours)</label>
                                    <div class="input-with-icon">
                                        <input type="number" id="duree_gestation" name="metadonnees[duree_gestation]"
                                               class="form-control"
                                               placeholder="Ex: 283">
                                        <i class="bi bi-calendar-week field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="date_prevue_mise_bas">Date prévue mise bas</label>
                                    <div class="input-with-icon">
                                        <input type="date" id="date_prevue_mise_bas" name="metadonnees[date_prevue_mise_bas]"
                                               class="form-control">
                                        <i class="bi bi-calendar field-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="type === 'MISE_BAS'">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="nombre_petits">Nombre de petits</label>
                                    <div class="input-with-icon">
                                        <input type="number" id="nombre_petits" name="metadonnees[nombre_petits]"
                                               class="form-control"
                                               placeholder="Ex: 1"
                                               min="1">
                                        <i class="bi bi-123 field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="poids_moyen">Poids moyen (kg)</label>
                                    <div class="input-with-icon">
                                        <input type="number" id="poids_moyen" name="metadonnees[poids_moyen]"
                                               class="form-control"
                                               placeholder="Ex: 30"
                                               min="0">
                                        <i class="bi bi-scale field-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="observations">Observations</label>
                                    <textarea id="observations" name="metadonnees[observations]"
                                              class="form-control"
                                              rows="2"
                                              placeholder="Détails de la mise bas..."></textarea>
                                </div>
                            </div>
                        </div>
                    </template>

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

                </div>
            </div>
        </div>

        {{-- ── Actions ─────────────────────────────────────────── --}}
        <div class="form-actions">
            <a href="{{ route('admin.evenements-reproduction.index') }}"
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
        function reproductionEvenementForm() {
            return {
                type: 'SAILLIE',
                typeLabels: {
                    'SAILLIE': 'Saillie',
                    'GESTATION': 'Gestation',
                    'MISE_BAS': 'Mise bas',
                },
                farms: @js($farms),
                selectedFarmId: null,
                eligibleAnimals: [],
                eligibleMales: [],
                loadingAnimals: false,
                loadingMales: false,

                get typeLabel() {
                    return this.typeLabels[this.type] || 'Événement de reproduction';
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
                        this.eligibleMales = [];
                        return;
                    }

                    this.loadingAnimals = true;
                    try {
                        const response = await fetch('/admin/animals/eligible/reproduction?farm_id=' + farmId + '&type_reproduction=' + this.type.toLowerCase(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        this.eligibleAnimals = data.success ? (data.data?.animals || []) : [];
                        console.log('Loaded eligible animals:', this.eligibleAnimals);
                    } catch (error) {
                        console.error('Error loading eligible animals:', error);
                        this.eligibleAnimals = [];
                    } finally {
                        this.loadingAnimals = false;
                    }

                    // Load eligible males if type is SAILLIE
                    if (this.type === 'SAILLIE') {
                        this.loadEligibleMales(farmId);
                    }
                },

                async loadEligibleMales(farmId) {
                    if (!farmId) {
                        this.eligibleMales = [];
                        return;
                    }

                    this.loadingMales = true;
                    try {
                        const response = await fetch('/admin/animals/eligible/male?farm_id=' + farmId, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        this.eligibleMales = data.success ? (data.data?.animals || []) : [];
                    } catch (error) {
                        console.error('Error loading eligible males:', error);
                        this.eligibleMales = [];
                    } finally {
                        this.loadingMales = false;
                    }
                }
            };
        }
    </script>
@endpush
