{{-- resources/views/admin/dashboard/index.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="fade-in">
    <div class="row g-3 mb-4">

        {{-- Stat cards --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card green">
                <div class="stat-icon green">
                    <i class="bi bi-house-heart"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Fermes</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card orange">
                <div class="stat-icon orange">
                    <i class="bi bi-box2-heart"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Animaux</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card green">
                <div class="stat-icon green">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Naissances</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card red">
                <div class="stat-icon red">
                    <i class="bi bi-heartbreak"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Décès</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card orange">
                <div class="stat-icon orange">
                    <i class="bi bi-shield-plus"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Vaccinations</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card blue">
                <div class="stat-icon blue">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">—</div>
                    <div class="stat-label">Dépenses</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Message bienvenue --}}
    <div class="card">
        <div class="card-body text-center py-5">
            <div style="font-size:3rem">🐄</div>
            <h4 class="mt-3">
                Bienvenue, {{ session('admin_user.name', 'Administrateur') }} !
            </h4>
            <p class="text-muted">
                Le tableau de bord complet sera disponible prochainement.
            </p>
        </div>
    </div>

@endsection