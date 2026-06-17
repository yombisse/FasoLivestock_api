@extends('admin.layouts.guest')

@section('title', 'Connexion')

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

        <h2 class="login-title">Connexion à l'Admin</h2>
        <p class="login-subtitle">Entrez vos identifiants pour accéder au tableau de bord.</p>

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
              action="{{ route('admin.login.post') }}"
              class="login-form"
              @submit.prevent="submit($event)">
            @csrf

            {{-- Email --}}
            <div class="form-group">
                <label class="form-label" for="login">Adresse Email</label>
                <div class="input-icon-wrapper">
                    <input type="text"
                           id="login"
                           name="login"
                           class="form-control @error('login') is-invalid @enderror"
                           placeholder="example@domaine.com"
                           value="{{ old('login') }}"
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
                           autocomplete="current-password"
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

            {{-- Se souvenir de moi --}}
            <div class="login-remember">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Se souvenir de moi</label>
            </div>

            {{-- Bouton --}}
            <button type="submit" class="btn-login" :disabled="loading">
                <span x-show="!loading">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Se Connecter
                </span>
                <span x-show="loading" class="d-flex align-items-center justify-content-center gap-2">
                    <span class="login-spinner"></span>
                    Connexion...
                </span>
            </button>

        </form>

        {{-- Liens --}}
        <div class="login-footer-links">
            <a href="#">Mot de passe oublié ?</a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/auth/login.js') }}"></script>
@endpush