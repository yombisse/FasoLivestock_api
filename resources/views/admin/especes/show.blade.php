@extends('admin.layouts.app')

@section('title', 'Détail espèce')
@section('page-title', 'Détail espèce')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/especes/show.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.especes.index') }}">Espèces</a>
    </li>
    <li class="breadcrumb-item active">{{ $espece['nom'] }}</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-tree me-2 text-success"></i>{{ $espece['nom'] }}</h2>
            <p>Détails de l'espèce</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.especes.edit', $espece['id']) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i>
                Modifier
            </a>
            <a href="{{ route('admin.especes.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Informations générales ─────────────────────────── --}}
    <div class="users-card">
        <h5 class="card-title mb-4">Informations générales</h5>

        <div class="row">
            <div class="col-md-6">
                <div class="info-group">
                    <label>Nom</label>
                    <p>{{ $espece['nom'] }}</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-group">
                    <label>Créé le</label>
                    <p>{{ \Carbon\Carbon::parse($espece['created_at'])->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="col-md-12">
                <div class="info-group">
                    <label>Description</label>
                    <p>{{ $espece['description'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Paramètres ─────────────────────────────────────── --}}
    <div class="users-card mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">Paramètres de l'espèce</h5>
            <a href="{{ route('admin.especes.parametres', $espece['id']) }}"
               class="btn btn-sm btn-outline-info">
                <i class="bi bi-sliders"></i>
                Paramétrer
            </a>
        </div>

        @if($espece['parametre'])
        <div class="row">
            <div class="col-md-4">
                <div class="info-group">
                    <label>Durée de gestation</label>
                    <p>{{ $espece['parametre']['duree_gestation_jours'] }} jours</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Âge de reproduction</label>
                    <p>{{ $espece['parametre']['age_reproduction_mois'] }} mois</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Nombre de petits typique</label>
                    <p>{{ $espece['parametre']['nombre_petits_typique'] }}</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Intervalle vaccin</label>
                    <p>{{ $espece['parametre']['intervalle_vaccin_jours'] }} jours</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Âge de sevrage</label>
                    <p>{{ $espece['parametre']['age_sevrage_jours'] }} jours</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Poids naissance moyen</label>
                    <p>{{ $espece['parametre']['poids_naissance_moyen_kg'] }} kg</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-group">
                    <label>Poids adulte moyen</label>
                    <p>{{ $espece['parametre']['poids_adulte_moyen_kg'] }} kg</p>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Aucun paramètre configuré pour cette espèce.
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/especes/show.js') }}"></script>
@endpush
