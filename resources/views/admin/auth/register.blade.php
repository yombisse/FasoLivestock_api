@extends('admin.layouts.guest')

@section('title', 'Créer un compte')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/auth/login.css') }}">
@endpush

@section('content')
<div class="login-page" x-data="loginForm()">

    {{-- Background --}}
    <div class="login-bg">
        <!-- <img src="{{ asset('admin/images/login_bg.jpg') }}" alt="Background"> -->
    </div>

    {{-- Card --}}
    <div class="login-card">

        {{-- Logo --}}
        <div class="login-logo">
            <div class="login-logo-icon">
                <img src="{{ asset('admin/images/logo.png') }}" alt="Logo FasoLivestock">
            </div>
            <div class="login-brand-name">FasoLivestock</div>
        </div>

        <hr class="login-divider">

        <h2 class="login-title">Créer un compte</h2>
        <p class="login-subtitle">Remplissez le formulaire pour créer votre compte administrateur.</p>

        {{-- Erreur session --}}
        @if(session('error'))
        <div class="login-alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ session('error') }}
        </div>
        @endif

        {{-- Erreurs validation --}}
        @if($errors->any())
        <div class="login-alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Formulaire --}}
        <form method="POST"
              action="{{ route('admin.register.submit') }}"
              class="login-form"
              @submit.prevent="submit($event)">
            @csrf

            {{-- Nom --}}
            <div class="form-group">
                <label class="form-label" for="name">Nom complet</label>
                <div class="input-icon-wrapper">
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="Jean Dupont"
                           value="{{ old('name') }}"
                           autocomplete="name"
                           required>
                    <i class="bi bi-person input-icon"></i>
                </div>
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label class="form-label" for="email">Adresse Email</label>
                <div class="input-icon-wrapper">
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="example@domaine.com"
                           value="{{ old('email') }}"
                           autocomplete="email"
                           required>
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            {{-- Mot de passe --}}
            <div class="form-group">
                <label class="form-label" for="password">Mot de Passe</label>
                <div class="input-icon-wrapper input-password-wrapper">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••••••"
                           autocomplete="new-password"
                           required>
                    <i class="bi bi-lock input-icon"></i>
                    <button type="button"
                            class="btn-toggle-password"
                            @click="togglePassword()"
                            tabindex="-1">
                        <i class="bi"
                           :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
            </div>

            {{-- Confirmation mot de passe --}}
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmer le Mot de Passe</label>
                <div class="input-icon-wrapper input-password-wrapper">
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control @error('password_confirmation') is-invalid @enderror"
                           placeholder="••••••••••••"
                           autocomplete="new-password"
                           required>
                    <i class="bi bi-lock input-icon"></i>
                    <button type="button"
                            class="btn-toggle-password"
                            @click="togglePassword()"
                            tabindex="-1">
                        <i class="bi"
                           :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
            </div>

            {{-- Bouton --}}
            <button type="submit" class="btn-login" :disabled="loading">
                <span x-show="!loading">
                    <i class="bi bi-person-plus me-1"></i>
                    S'inscrire
                </span>
                <span x-show="loading" class="d-flex align-items-center justify-content-center gap-2">
                    <span class="login-spinner"></span>
                    Inscription...
                </span>
            </button>

        </form>

        {{-- Liens --}}
        <div class="login-footer-links">
            <a href="{{ route('admin.login') }}">Déjà un compte ? Se connecter</a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/auth/login.js') }}"></script>
@endpush
