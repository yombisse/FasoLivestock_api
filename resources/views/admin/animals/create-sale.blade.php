@extends('admin.layouts.ferme')

@section('title', 'Vente d\'un animal')
@section('page-title', 'Vente d\'un animal')

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
        .mode-badge.vente { background: #ffebee; color: #c62828; }
        .form-page { padding: 1.5rem; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Vente</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="animalSaleForm()">

    <div class="mode-badge vente">
        <i class="bi bi-cart-x"></i>
        Vente — un événement VENTE et une transaction ENTRÉE seront enregistrés
    </div>

    <form id="animal-sale-form"
          method="POST"
          action="{{ route('admin.animals.sell', ['farm' => $farmId]) }}">
        @csrf

        {{-- ── Sélection de l'animal ──────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-cow"></i></div>
                <h3>Sélection de l'animal</h3>
            </div>

            <div class="form-grid">
                {{-- Ferme (hidden - from route) --}}
                <input type="hidden" name="farm_id" value="{{ $farmId }}">

                <div class="form-group">
                    <label class="form-label">Animal *</label>
                    <select name="animal_id" class="form-select" required x-model="selectedAnimal">
                        <option value="">-- Sélectionner un animal --</option>
                        <template x-for="animal in filteredAnimals" :key="animal.id">
                            <option :value="animal.id" x-text="animal.code + ' - ' + animal.nom"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Détails de la vente ───────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon"><i class="bi bi-currency-dollar"></i></div>
                <h3>Détails de la vente</h3>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Date de vente *</label>
                    <input type="date" name="date_vente" class="form-control" required x-model="formData.date_vente">
                </div>

                <div class="form-group">
                    <label class="form-label">Prix de vente (FCFA) *</label>
                    <input type="number" name="prix_vente" class="form-control" required min="0" x-model="formData.prix_vente">
                </div>

                <div class="form-group">
                    <label class="form-label">Acheteur</label>
                    <input type="text" name="acheteur" class="form-control" x-model="formData.acheteur">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" x-model="formData.notes"></textarea>
            </div>
        </div>

        {{-- ── Actions ────────────────────────────────────────────── --}}
        <div class="form-actions">
            <a href="{{ route('admin.finance.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i>
                Enregistrer la vente
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
        function animalSaleForm() {
            return {
                selectedFarm: '{{ $farmId }}',
                selectedAnimal: '',
                formData: {
                    date_vente: new Date().toISOString().split('T')[0],
                    prix_vente: '',
                    acheteur: '',
                    notes: ''
                },
                animals: @json($animals ?? []),
                filteredAnimals: [],

                init() {
                    this.loadAnimals();
                },

                loadAnimals() {
                    if (this.selectedFarm) {
                        this.filteredAnimals = this.animals.filter(animal =>
                            animal.farm_id === this.selectedFarm
                        );
                    } else {
                        this.filteredAnimals = [];
                    }
                    this.selectedAnimal = '';
                }
            }
        }
    </script>
@endpush
