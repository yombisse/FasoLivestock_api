@extends('admin.layouts.guest')

@section('title', 'Vérification 2FA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/auth/login.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/auth/verify-2fa.css') }}">
@endpush

@section('content')
<div class="login-page" x-data="twoFaForm()" x-init="init()" @destroy="destroy()">

    <div class="login-bg"></div>

    <div class="login-card">

        {{-- Logo --}}
        <div class="login-logo">
            <div class="login-logo-icon">🐄</div>
            <div class="login-brand-name">FasoLivestock</div>
        </div>

        <hr class="login-divider">

        {{-- Icône 2FA --}}
        <div class="twofa-icon">
            <i class="bi bi-shield-lock"></i>
        </div>

        <h2 class="login-title text-center">Vérification en 2 étapes</h2>

        <p class="twofa-info">
            Un code à 6 chiffres a été envoyé à<br>
            <strong>{{ session('2fa_identifier', 'votre email') }}</strong>
        </p>

        {{-- Erreur --}}
        <div x-show="error" x-transition class="login-alert alert-danger" x-cloak>
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span x-text="error"></span>
        </div>

        @if(session('error'))
        <div class="login-alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ session('error') }}
        </div>
        @endif

        {{-- Formulaire --}}
        <form id="form-2fa"
              method="POST"
              action="{{ route('admin.verify2fa') }}">
            @csrf
            <input type="hidden"
                   name="verification_id"
                   value="{{ session('2fa_verification_id') }}">
            <input type="hidden"
                   id="code-hidden"
                   name="code"
                   :value="fullCode">

            {{-- Inputs code --}}
            <div class="code-inputs">
                @for($i = 0; $i < 6; $i++)
                <input type="text"
                       class="code-input"
                       maxlength="1"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       x-model="code[{{ $i }}]"
                       @input="onInput({{ $i }}, $event)"
                       @keydown="onKeydown({{ $i }}, $event)"
                       @focus="resetError()"
                       :class="{ filled: code[{{ $i }}] !== '', error: error !== '' }"
                       autocomplete="off">
                @endfor
            </div>

            {{-- Timer --}}
            <div class="twofa-timer">
                Code valide pendant
                <span class="timer-value" :class="{ expiring: isExpiring }" x-text="timerDisplay"></span>
            </div>

            {{-- Bouton --}}
            <button type="button"
                    class="btn-login"
                    @click="submit()"
                    :disabled="fullCode.length !== 6 || loading">
                <span x-show="!loading">
                    <i class="bi bi-check-circle me-1"></i>
                    Vérifier le code
                </span>
                <span x-show="loading" class="d-flex align-items-center justify-content-center gap-2">
                    <span class="login-spinner"></span>
                    Vérification...
                </span>
            </button>

        </form>

        {{-- Renvoyer + retour --}}
        <div class="twofa-resend">
            Vous n'avez pas reçu le code ?
            <button class="btn-resend"
                    :disabled="timer > 540"
                    @click="startTimer()">
                Renvoyer
            </button>
        </div>

        <a href="{{ route('admin.login') }}" class="btn-back">
            <i class="bi bi-arrow-left"></i>
            Retour à la connexion
        </a>

    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/auth/verify-2fa.js') }}"></script>
@endpush