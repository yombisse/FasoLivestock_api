@extends('admin.layouts.app')

@section('title', 'Modifier ' . $user['name'])
@section('page-title', 'Modifier un utilisateur')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/users/create.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Utilisateurs</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="form-page fade-in"
     x-data="userEditForm({{ json_encode(collect($user['roles'])->pluck('name')->toArray()) }})"
>

    <form id="user-edit-form"
          method="POST"
          action="{{ route('admin.users.update', $user['id']) }}">
        @csrf
        @method('PUT')

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
                                       value="{{ old('name', $user['name']) }}"
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
                                       value="{{ old('telephone', $user['telephone']) }}">
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
                                       value="{{ old('email', $user['email']) }}">
                                <i class="bi bi-envelope field-icon"></i>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Statut actif --}}
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $user['is_active']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active"
                                   style="font-size:.845rem;font-weight:500">
                                Compte actif
                            </label>
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

                {{-- Toggle changer le mot de passe --}}
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input"
                           type="checkbox"
                           id="change_password"
                           x-model="changePassword">
                    <label class="form-check-label"
                           for="change_password"
                           style="font-size:.845rem;font-weight:500">
                        Modifier le mot de passe
                    </label>
                </div>

                <div x-show="changePassword"
                     x-transition
                     x-cloak>
                    <div class="row g-3">

                        {{-- Password --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="password">
                                    Nouveau mot de passe
                                </label>
                                <div class="input-with-icon password-wrapper">
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Min. 12 caractères"
                                           x-model="password">
                                    <i class="bi bi-lock field-icon"></i>
                                    <button type="button"
                                            class="btn-toggle-pwd"
                                            @click="togglePassword('password')">
                                        <i class="bi"
                                           :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>

                                {{-- Strength --}}
                                <div class="password-strength" x-show="password.length > 0">
                                    <div class="strength-bars">
                                        <template x-for="(bar, i) in passwordStrength.bars" :key="i">
                                            <div class="strength-bar" :class="bar || ''"></div>
                                        </template>
                                    </div>
                                    <span class="strength-label"
                                          x-text="passwordStrength.label"></span>
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
                                    Confirmer le mot de passe
                                </label>
                                <div class="input-with-icon password-wrapper">
                                    <input type="password"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           class="form-control"
                                           placeholder="Répéter le mot de passe">
                                    <i class="bi bi-lock field-icon"></i>
                                    <button type="button"
                                            class="btn-toggle-pwd"
                                            @click="togglePassword('confirm')">
                                        <i class="bi"
                                           :class="showPasswordConfirm ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <p x-show="!changePassword"
                   style="font-size:.82rem;color:var(--text-muted);margin:0">
                    <i class="bi bi-info-circle me-1"></i>
                    Laissez décoché pour conserver le mot de passe actuel.
                </p>

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

                <div id="roles-error"
                     class="alert alert-warning mb-3"
                     style="display:none;font-size:.83rem">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Veuillez sélectionner au moins un rôle.
                </div>

                <div class="roles-grid">
                    @forelse($roles as $role)
                    <label class="role-check-item"
                           :class="{
                               selected: isRoleSelected('{{ $role['name'] }}'),
                               superadmin: '{{ $role['name'] }}' === 'superadmin'
                           }"
                           @click.prevent="toggleRole('{{ $role['name'] }}')">

                        <input type="checkbox"
                               value="{{ $role['name'] }}"
                               :checked="isRoleSelected('{{ $role['name'] }}')"
                               @click.stop>

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

                {{-- Champs hidden pour les rôles sélectionnés --}}
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
                        Enregistrer les modifications
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm"></span>
                        Enregistrement...
                    </span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/users/edit.js') }}"></script>
@endpush