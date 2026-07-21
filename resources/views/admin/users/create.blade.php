@extends('admin.layouts.app')

@section('title', 'Nouvel utilisateur')
@section('page-title', 'Nouvel utilisateur')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/form-create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Utilisateurs</a>
    </li>
    <li class="breadcrumb-item active">Nouvel utilisateur</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="userCreateForm()"
     x-init="selectedRoles = []">

    <form id="user-create-form"
          method="POST"
          action="{{ route('admin.users.store') }}">
        @csrf

        {{-- ── Informations personnelles ─────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-person"></i>
                </div>
                <h3>Informations personnelles</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Nom --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="name">
                                Nom complet <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="name"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Ex: Moussa Traoré"
                                       value="{{ old('name') }}"
                                       required>
                                <i class="bi bi-person field-icon"></i>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Téléphone --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="telephone">Téléphone</label>
                            <div class="input-with-icon">
                                <input type="text"
                                       id="telephone"
                                       name="telephone"
                                       class="form-control @error('telephone') is-invalid @enderror"
                                       placeholder="+226 XX XX XX XX"
                                       value="{{ old('telephone') }}">
                                <i class="bi bi-telephone field-icon"></i>
                            </div>
                            @error('telephone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label" for="email">Adresse email</label>
                            <div class="input-with-icon">
                                <input type="email"
                                       id="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="exemple@domaine.com"
                                       value="{{ old('email') }}">
                                <i class="bi bi-envelope field-icon"></i>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Email ou téléphone — au moins l'un des deux est requis.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Mot de passe ──────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-lock"></i>
                </div>
                <h3>Mot de passe</h3>
            </div>
            <div class="form-section-body">
                <div class="row g-3">

                    {{-- Password --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="password">
                                Mot de passe <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon password-wrapper">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Min. 12 caractères"
                                       x-model="password"
                                       required>
                                <i class="bi bi-lock field-icon"></i>
                                <button type="button"
                                        class="btn-toggle-pwd"
                                        @click="togglePassword('password')">
                                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>

                            {{-- Strength indicator --}}
                            <div class="password-strength" x-show="password.length > 0">
                                <div class="strength-bars">
                                    <template x-for="(bar, i) in passwordStrength.bars" :key="i">
                                        <div class="strength-bar" :class="bar || ''"></div>
                                    </template>
                                </div>
                                <span class="strength-label" x-text="passwordStrength.label"></span>
                            </div>

                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Confirmation --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">
                                Confirmer le mot de passe <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-icon password-wrapper">
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       class="form-control"
                                       placeholder="Répéter le mot de passe"
                                       required>
                                <i class="bi bi-lock field-icon"></i>
                                <button type="button"
                                        class="btn-toggle-pwd"
                                        @click="togglePassword('confirm')">
                                    <i class="bi" :class="showPasswordConfirm ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Rôles ─────────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="section-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3>Rôles & Permissions</h3>
            </div>
            <div class="form-section-body">

                @error('roles')
                    <div class="alert alert-danger mb-3" style="font-size:.83rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror

                <div class="roles-grid">
                    @forelse($roles as $role)
                    <label class="role-check-item"
                           :class="{
                               selected: isRoleSelected('{{ $role['name'] }}'),
                               superadmin: '{{ $role['name'] }}' === 'superadmin'
                           }"
                           @click="toggleRole('{{ $role['name'] }}')">

                        <input type="checkbox"
                               name="roles[]"
                               value="{{ $role['name'] }}"
                               :checked="isRoleSelected('{{ $role['name'] }}')"
                               {{ in_array($role['name'], old('roles', [])) ? 'checked' : '' }}>

                        <div>
                            <div class="role-check-name">
                                {{ ucfirst($role['name']) }}
                            </div>
                            <div class="role-check-count">
                                {{ $role['users_count'] ?? 0 }} utilisateur(s)
                            </div>
                        </div>
                    </label>
                    @empty
                        <p class="text-muted" style="font-size:.83rem">
                            Aucun rôle disponible.
                        </p>
                    @endforelse
                </div>

                {{-- Champs hidden pour les rôles sélectionnés via Alpine --}}
                <template x-for="role in selectedRoles" :key="role">
                    <input type="hidden" name="roles[]" :value="role">
                </template>

            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Annuler
                </a>
                <button type="button"
                        class="btn btn-primary btn-sm"
                        @click="submit()"
                        :disabled="loading">
                    <span x-show="!loading">
                        <i class="bi bi-check-lg"></i>
                        Créer l'utilisateur
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm"></span>
                        Création...
                    </span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/users/create.js') }}"></script>
@endpush