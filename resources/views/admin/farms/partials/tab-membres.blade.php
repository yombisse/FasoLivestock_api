@if(auth()->user()->hasRole('superadmin') || auth()->user()->can('farms.update'))
<div x-data="farmMembers('{{ $farm['id'] }}')" class="farm-members">
    
    {{-- Section A: Membres actuels --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-people"></i> Membres actuels
            <span class="badge bg-primary ms-2">{{ count($farm['users'] ?? []) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Rôle</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($farm['users'] ?? [] as $member)
                        <tr x-data="memberRow(@js($member['id']), @js($member['pivot']['role'] ?? 'worker'))">
                            {{-- Avatar + Nom + Email --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="member-avatar">
                                        {{ strtoupper(substr($member['name'] ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="member-info">
                                        <div class="member-name">{{ $member['name'] }}</div>
                                        <div class="member-email">{{ $member['email'] ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Rôle --}}
                            <td>
                                @if(($member['pivot']['role'] ?? 'worker') === 'owner')
                                    <span class="badge bg-dark">Propriétaire</span>
                                @else
                                    <div class="role-select-wrapper">
                                        <select class="form-select form-select-sm role-select"
                                                :class="{
                                                    'bg-primary text-white': currentRole === 'manager',
                                                    'bg-info text-white': currentRole === 'vet',
                                                    'bg-secondary text-white': currentRole === 'worker'
                                                }"
                                                x-model="currentRole"
                                                @change="updateRole(@js($member['id']))">
                                            <option value="manager">Gestionnaire</option>
                                            <option value="vet">Vétérinaire</option>
                                            <option value="worker">Ouvrier</option>
                                        </select>
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-end">
                                @if(($member['pivot']['role'] ?? 'worker') !== 'owner')
                                    <button type="button"
                                            class="btn btn-icon btn-outline-danger btn-sm"
                                            title="Retirer"
                                            @click="confirmRemove(@js($member['id']), @js($member['name']))">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <p>Aucun membre dans cette ferme</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Section B: Ajouter des membres --}}
    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-plus"></i> Ajouter des membres
        </div>
        <div class="card-body">
            
            {{-- Recherche utilisateur --}}
            <div class="member-search-wrapper">
                <label class="form-label">Rechercher un utilisateur</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text"
                           class="form-control"
                           placeholder="Nom, email..."
                           x-model="searchQuery"
                           @keyup.debounce.400ms="searchUsers"
                           x-ref="searchInput">
                </div>
                
                {{-- Résultats dropdown --}}
                <div x-show="searchResults.length > 0 && searchQuery.length >= 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="search-results-dropdown">
                    <template x-for="user in searchResults" :key="user.id">
                        <div class="search-result-item"
                             @click="addToPending(user)">
                            <div class="search-result-avatar">
                                <span x-text="user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="search-result-info">
                                <div class="search-result-name" x-text="user.name"></div>
                                <div class="search-result-email" x-text="user.email ?? user.telephone"></div>
                            </div>
                            <i class="bi bi-plus-circle text-success"></i>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Liste utilisateurs à ajouter --}}
            <div x-show="pendingUsers.length > 0" class="mt-4">
                <label class="form-label">Utilisateurs à ajouter</label>
                <div class="pending-users-list">
                    <template x-for="(user, index) in pendingUsers" :key="user.id">
                        <div class="pending-user-item">
                            <div class="pending-user-avatar">
                                <span x-text="user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="pending-user-info">
                                <div class="pending-user-name" x-text="user.name"></div>
                                <div class="pending-user-email" x-text="user.email ?? user.telephone"></div>
                            </div>
                            <select class="form-select form-select-sm pending-role-select"
                                    x-model="user.role">
                                <option value="manager">Gestionnaire</option>
                                <option value="vet">Vétérinaire</option>
                                <option value="worker">Ouvrier</option>
                            </select>
                            <button type="button"
                                    class="btn btn-icon btn-outline-danger btn-sm"
                                    @click="removeFromPending(index)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <form method="POST"
                      action="{{ route('admin.farms.members.add', $farm['id']) }}"
                      class="mt-3">
                    @csrf
                    <input type="hidden" name="users" :value="JSON.stringify(pendingUsers)">
                    <button type="submit"
                            class="btn btn-primary"
                            :disabled="pendingUsers.length === 0">
                        <i class="bi bi-check-lg"></i> Ajouter à la ferme
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- Modal confirmation retrait --}}
    <div x-cloak
         x-show="removeModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="removeModal.show = false"
         style="position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)">
    </div>

    <div x-cloak
         x-show="removeModal.show"
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
                        <i class="bi bi-person-x fs-4"></i>
                    </div>
                    <h6 class="confirm-modal-title">Retirer le membre</h6>
                    <p class="confirm-modal-message">
                        Voulez-vous vraiment retirer <strong x-text="removeModal.userName"></strong> de cette ferme ?
                    </p>
                    <div class="confirm-modal-actions">
                        <button type="button"
                                class="btn-confirm-cancel"
                                @click="removeModal.show = false">
                            Annuler
                        </button>
                        <button type="button"
                                class="btn-confirm-ok ok-danger"
                                @click="confirmRemoveMember()">
                            Retirer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-shield-lock fs-1 text-muted"></i>
        <p class="text-muted mt-3">Vous n'avez pas la permission de gérer les membres.</p>
    </div>
</div>
@endif
