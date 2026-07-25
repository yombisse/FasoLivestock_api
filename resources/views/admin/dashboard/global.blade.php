@extends('admin.layouts.systeme')

@section('title', 'Tableau de bord global')
@section('page-title', 'Tableau de bord global')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/dashboard/index.css') }}">
    <style>
        [x-cloak] { display: none !important; }
        .farm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .farm-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            overflow: hidden;
        }
        .farm-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .farm-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem;
        }
        .farm-card-body {
            padding: 1.25rem;
        }
        .farm-card-footer {
            padding: 1rem 1.25rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Tableau de bord global</li>
@endsection

@section('content')
<div x-data="dashboardGlobal()" x-init="init()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-speedometer2 me-2 text-primary"></i>Tableau de bord global</h2>
            <p>Vue d'ensemble de toutes vos fermes</p>
        </div>
        <div class="d-flex gap-2">
            <button @click="refreshAll()" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise"></i>
                Actualiser
            </button>
        </div>
    </div>

    {{-- ── Stats globales (4 cartes KPI) ─────────────────── --}}
    <div class="kpi-cards-row">
        
        {{-- Total fermes --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-primary">
                <i class="bi bi-house"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total fermes</div>
                <div class="kpi-value" x-text="formatNumber({{ $global['total_fermes'] ?? 0 }})">
                    {{ $global['total_fermes'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Total animaux --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-success">
                <i class="bi bi-cow"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total animaux</div>
                <div class="kpi-value" x-text="formatNumber({{ $global['total_animaux'] ?? 0 }})">
                    {{ $global['total_animaux'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Total transactions --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-info">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total transactions</div>
                <div class="kpi-value" x-text="formatNumber({{ $global['total_transactions'] ?? 0 }})">
                    {{ $global['total_transactions'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Total utilisateurs --}}
        <div class="kpi-card">
            <div class="kpi-icon bg-warning">
                <i class="bi bi-people"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total utilisateurs</div>
                <div class="kpi-value" x-text="formatNumber({{ $global['total_users'] ?? 0 }})">
                    {{ $global['total_users'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Grille des fermes ─────────────────────────────── --}}
    <div class="section-header mt-5">
        <h3><i class="bi bi-grid me-2"></i>Vos fermes</h3>
        <p>Sélectionnez une ferme pour accéder à son tableau de bord détaillé</p>
    </div>

    @if(empty($farms))
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucune ferme disponible. <a href="{{ route('admin.farms.create') }}" class="alert-link">Créer une ferme</a>
        </div>
    @else
        <div class="farm-grid">
            @foreach($farms as $farm)
                <a href="{{ route('admin.ferme.dashboard', $farm['id']) }}" class="farm-card">
                    <div class="farm-card-header">
                        <h5 class="mb-0">{{ $farm['name'] ?? 'Ferme sans nom' }}</h5>
                        <small class="text-white-50">{{ $farm['location'] ?? 'Localisation non définie' }}</small>
                    </div>
                    <div class="farm-card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted">Animaux</small>
                                <div class="fw-bold">{{ $farm['total_animaux'] ?? 0 }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Lots</small>
                                <div class="fw-bold">{{ $farm['total_lots'] ?? 0 }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Membres</small>
                                <div class="fw-bold">{{ $farm['total_membres'] ?? 0 }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Transactions</small>
                                <div class="fw-bold">{{ $farm['total_transactions'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="farm-card-footer">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Créée le {{ \Carbon\Carbon::parse($farm['created_at'] ?? now())->format('d/m/Y') }}
                        </small>
                        <i class="bi bi-arrow-right float-end text-primary"></i>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
    <script>
        function dashboardGlobal() {
            return {
                init() {
                    // Initialisation si nécessaire
                },
                refreshAll() {
                    window.location.reload();
                },
                formatNumber(value) {
                    return new Intl.NumberFormat('fr-FR').format(value);
                }
            }
        }
    </script>
@endpush
@endsection
