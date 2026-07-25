@extends('admin.layouts.ferme')

@section('title', 'Import du cheptel existant')

@section('page-title', 'Import cheptel existant')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Import</li>
@endsection

@section('content')
<div class="container-fluid">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Import cheptel existant</h4>
            <small class="text-muted">Importez les animaux déjà présents dans votre ferme</small>
        </div>
        <a href="{{ route('admin.animals.index', ['farm' => $farmId]) }}"
           class="btn btn-outline-secondary btn-sm">
            ← Retour
        </a>
    </div>

    {{-- Alerte info --}}
    <div class="alert alert-info mb-4">
        <strong>Import initial :</strong> ces animaux seront enregistrés sans
        événement d'entrée. Utilisez ce formulaire uniquement pour les animaux
        déjà présents dans la ferme avant l'adoption de l'application.
    </div>

    {{-- Messages d'erreur globaux --}}
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Formulaire Alpine.js --}}
    <form action="{{ route('admin.animals.import.store', ['farm' => $farmId]) }}"
          method="POST"
          x-data="{
              animaux: [
                  { nom:'', espece_id:'', sexe:'', race:'',
                    date_naissance:'', poids:'', numero_identification:'' }
              ],
              ajouterLigne() {
                  this.animaux.push({
                      nom:'', espece_id:'', sexe:'', race:'',
                      date_naissance:'', poids:'', numero_identification:''
                  });
              },
              supprimerLigne(i) {
                  if (this.animaux.length > 1) this.animaux.splice(i, 1);
              }
          }">
        @csrf

        {{-- Ferme (hidden - from route) --}}
        <input type="hidden" name="farm_id" value="{{ $farmId }}">

        {{-- Tableau des animaux --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    Animaux à importer
                    <span class="badge bg-primary ms-1" x-text="animaux.length"></span>
                </span>
                <button type="button"
                        class="btn btn-success btn-sm"
                        @click="ajouterLigne()">
                    + Ajouter un animal
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Espèce *</th>
                                <th>Sexe *</th>
                                <th>Nom</th>
                                <th>Race</th>
                                <th>Date naissance</th>
                                <th>Poids (kg)</th>
                                <th>N° identification</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(animal, i) in animaux" :key="i">
                                <tr>
                                    <td class="text-center text-muted small"
                                        x-text="i + 1"></td>

                                    {{-- Espèce --}}
                                    <td>
                                        <select class="form-select form-select-sm"
                                                :name="`animaux[${i}][espece_id]`"
                                                x-model="animal.espece_id"
                                                required>
                                            <option value="">—</option>
                                            @foreach($especes as $espece)
                                                <option value="{{ $espece['id'] }}">
                                                    {{ $espece['nom'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Sexe --}}
                                    <td>
                                        <select class="form-select form-select-sm"
                                                :name="`animaux[${i}][sexe]`"
                                                x-model="animal.sexe"
                                                required>
                                            <option value="">—</option>
                                            <option value="male">Mâle</option>
                                            <option value="femelle">Femelle</option>
                                        </select>
                                    </td>

                                    {{-- Nom --}}
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               :name="`animaux[${i}][nom]`"
                                               x-model="animal.nom"
                                               maxlength="100">
                                    </td>

                                    {{-- Race --}}
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               :name="`animaux[${i}][race]`"
                                               x-model="animal.race"
                                               maxlength="100">
                                    </td>

                                    {{-- Date naissance --}}
                                    <td>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               :name="`animaux[${i}][date_naissance]`"
                                               x-model="animal.date_naissance">
                                    </td>

                                    {{-- Poids --}}
                                    <td>
                                        <input type="number"
                                               class="form-control form-control-sm"
                                               :name="`animaux[${i}][poids]`"
                                               x-model="animal.poids"
                                               min="0"
                                               step="0.1">
                                    </td>

                                    {{-- Numéro identification --}}
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               :name="`animaux[${i}][numero_identification]`"
                                               x-model="animal.numero_identification"
                                               maxlength="100">
                                    </td>

                                    {{-- Supprimer ligne --}}
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                @click="supprimerLigne(i)"
                                                :disabled="animaux.length === 1"
                                                title="Supprimer">
                                            ×
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    * Champs obligatoires
                </span>
                <button type="submit" class="btn btn-primary">
                    Valider l'import
                    (<span x-text="animaux.length"></span> animal(s))
                </button>
            </div>
        </div>

    </form>
</div>
@endsection
