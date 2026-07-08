@extends('admin.layouts.app')

@section('title', 'Administrer la ferme')
@section('page-title', 'Administrer la ferme')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/farms/manage.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.farms.index') }}">Fermes</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.farms.show', $farm['id']) }}">{{ $farm['name'] ?? 'Ferme' }}</a></li>
    <li class="breadcrumb-item active">Administration</li>
@endsection

@section('content')
<div x-data="farmManage('{{ $activeTab }}')" class="farm-manage fade-in">

    {{-- ─── Header ferme ───────────────────────────────────── --}}
    <div class="farm-manage-header">
        <div class="farm-header-left">
            <div class="farm-header-avatar">
                {{ strtoupper(substr($farm['name'] ?? 'F', 0, 1)) }}
            </div>
            <div class="farm-header-info">
                <h2>{{ $farm['name'] ?? 'Ferme' }}</h2>
                <div class="farm-header-meta">
                    <span class="farm-location">
                        <i class="bi bi-geo-alt"></i>
                        {{ $farm['location'] ?? 'Non défini' }}
                    </span>
                    <span class="farm-type">
                        <i class="bi bi-box2-heart"></i>
                        {{ $farm['type_elevage'] ?? 'Non défini' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="farm-header-right">
            <span class="farm-status-badge {{ ($farm['statut'] ?? 'ACTIF') === 'ACTIF' ? 'active' : 'archived' }}">
                {{ $farm['statut'] ?? 'ACTIF' }}
            </span>
            <a href="{{ route('admin.farms.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour aux fermes
            </a>
        </div>
    </div>

    {{-- ─── Onglets Bootstrap ───────────────────────────────── --}}
    <ul class="nav nav-tabs farm-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'informations' ? 'active' : '' }}"
                    :class="{ 'active': activeTab === 'informations' }"
                    @click="setTab('informations')"
                    type="button">
                <i class="bi bi-info-circle"></i> Informations
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'membres' ? 'active' : '' }}"
                    :class="{ 'active': activeTab === 'membres' }"
                    @click="setTab('membres')"
                    type="button">
                <i class="bi bi-people"></i> Membres
                <span class="badge bg-primary">{{ count($farm['users'] ?? []) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'animaux' ? 'active' : '' }}"
                    :class="{ 'active': activeTab === 'animaux' }"
                    @click="setTab('animaux')"
                    type="button">
                <i class="bi bi-box2-heart"></i> Animaux
                <span class="badge bg-success">{{ $animalsMeta['total'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'statistiques' ? 'active' : '' }}"
                    :class="{ 'active': activeTab === 'statistiques' }"
                    @click="setTab('statistiques')"
                    type="button">
                <i class="bi bi-bar-chart"></i> Statistiques
            </button>
        </li>
    </ul>

    {{-- ─── Contenu des onglets ─────────────────────────────── --}}
    <div class="tab-content farm-tab-content">
        
        {{-- TAB 1: Informations --}}
        <div class="tab-pane fade {{ $activeTab === 'informations' ? 'show active' : '' }}"
             :class="{ 'show active': activeTab === 'informations' }"
             id="tab-informations">
            @include('admin.farms.partials.tab-informations')
        </div>

        {{-- TAB 2: Membres --}}
        <div class="tab-pane fade {{ $activeTab === 'membres' ? 'show active' : '' }}"
             :class="{ 'show active': activeTab === 'membres' }"
             id="tab-membres">
            @include('admin.farms.partials.tab-membres')
        </div>

        {{-- TAB 3: Animaux --}}
        <div class="tab-pane fade {{ $activeTab === 'animaux' ? 'show active' : '' }}"
             :class="{ 'show active': activeTab === 'animaux' }"
             id="tab-animaux">
            @include('admin.farms.partials.tab-animaux')
        </div>

        {{-- TAB 4: Statistiques --}}
        <div class="tab-pane fade {{ $activeTab === 'statistiques' ? 'show active' : '' }}"
             :class="{ 'show active': activeTab === 'statistiques' }"
             id="tab-statistiques">
            @include('admin.farms.partials.tab-statistiques')
        </div>

    </div>

    {{-- ─── Modal confirmation archivage ───────────────────── --}}
    <div x-cloak
         x-show="confirmModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="confirmModal.show = false"
         style="position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)">
    </div>

    <div x-cloak
         x-show="confirmModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="position:fixed;inset:0;z-index:1055;overflow-y:auto;pointer-events:none">

        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none">
            <div style="width:100%;max-width:420px;pointer-events:auto">
                <div class="confirm-modal-box">
                    <div class="confirm-modal-icon icon-danger">
                        <i class="bi bi-archive fs-4"></i>
                    </div>
                    <h6 class="confirm-modal-title" x-text="confirmModal.title"></h6>
                    <p class="confirm-modal-message" x-html="confirmModal.message"></p>
                    <div class="confirm-modal-actions">
                        <button type="button"
                                class="btn-confirm-cancel"
                                @click="confirmModal.show = false">
                            Annuler
                        </button>
                        <button type="button"
                                class="btn-confirm-ok ok-danger"
                                @click="confirmAction()">
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/farms/manage.js') }}"></script>
@endpush
