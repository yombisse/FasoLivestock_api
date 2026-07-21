/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Farm Manage Page JS
═══════════════════════════════════════════════════════════ */

// ─── Toast Bootstrap ─────────────────────────────────────────
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1100';
    document.body.appendChild(container);
    return container;
}

// ─── Alpine.js: Main Page Component ───────────────────────────
function farmManage(initialTab = 'informations') {
    return {
        activeTab: initialTab,
        confirmModal: {
            show: false,
            title: '',
            message: '',
            action: null,
            type: 'danger'
        },

        init() {
            // Sync tab with URL on load
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl) {
                this.activeTab = tabFromUrl;
            }
        },

        setTab(tabName) {
            this.activeTab = tabName;
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        },

        confirmArchiveFarm(farmId, farmName) {
            this.confirmModal = {
                show: true,
                title: 'Archiver la ferme',
                message: `Voulez-vous vraiment archiver la ferme <strong>${farmName}</strong> ?<br><small class="text-muted">Cette action peut être annulée.</small>`,
                action: () => document.getElementById(`form-archive-${farmId}`).submit(),
                type: 'danger'
            };
        },

        confirmAction() {
            if (this.confirmModal.action) {
                this.confirmModal.action();
            }
            this.confirmModal.show = false;
        }
    };
}

// ─── Alpine.js: Farm Members Component ────────────────────────
function farmMembers(farmId) {
    return {
        farmId: farmId,
        searchQuery: '',
        searchResults: [],
        pendingUsers: [],
        existingMemberIds: [],
        availableRoles: [],
        allUsers: [],
        selectedUserId: null,
        removeModal: {
            show: false,
            userId: null,
            userName: ''
        },

        init() {
            // Load available roles from API
            this.loadRoles();
            
            // Load all users from API
            this.loadAllUsers();
            
            // Collect existing member IDs to avoid duplicates
            const memberRows = document.querySelectorAll('[x-data^="memberRow"]');
            memberRows.forEach(row => {
                const match = row.getAttribute('x-data').match(/memberRow\(['"]([^'"]+)['"]/);
                if (match) {
                    this.existingMemberIds.push(match[1]);
                }
            });
        },

        async loadRoles() {
            console.log('Chargement des rôles depuis /admin/roles/all...');
            try {
                const response = await fetch('/admin/roles/all', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                
                if (response.ok) {
                    const roles = await response.json();
                    console.log('Rôles reçus:', roles);
                    this.availableRoles = roles.data || roles;
                    console.log('availableRoles après chargement:', this.availableRoles);
                } else {
                    console.error('Erreur response non OK:', response.status);
                    this.availableRoles = [
                        { id: 'manager', name: 'Gestionnaire' },
                        { id: 'vet', name: 'Vétérinaire' },
                        { id: 'worker', name: 'Ouvrier' }
                    ];
                }
            } catch (error) {
                console.error('Error loading roles:', error);
                this.availableRoles = [
                    { id: 'manager', name: 'Gestionnaire' },
                    { id: 'vet', name: 'Vétérinaire' },
                    { id: 'worker', name: 'Ouvrier' }
                ];
            }
        },

        async loadAllUsers() {
            try {
                const response = await fetch('/admin/users/all', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const users = await response.json();
                    this.allUsers = users.data || users;
                }
            } catch (error) {
                console.error('Error loading users:', error);
                this.allUsers = [];
            }
        },

        getAvailableUsers() {
            return this.allUsers.filter(user => 
                !this.existingMemberIds.includes(user.id) &&
                !this.pendingUsers.some(p => p.id === user.id)
            );
        },

        addUserFromSelect() {
            console.log('addUserFromSelect appelé, selectedUserId:', this.selectedUserId);
            if (this.selectedUserId) {
                const user = this.allUsers.find(u => u.id === this.selectedUserId);
                console.log('utilisateur trouvé:', user);
                if (user) {
                    console.log('Ajout de l utilisateur à pending:', user);
                    this.addToPending(user);
                    this.selectedUserId = null;
                } else {
                    console.log('Utilisateur non trouvé pour ID:', this.selectedUserId);
                }
            }
        },

        async searchUsers() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }

            try {
                const response = await fetch(`/admin/users/search?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const users = await response.json();
                    // Filter out users already in the farm
                    this.searchResults = users.filter(user => 
                        !this.existingMemberIds.includes(user.id) &&
                        !this.pendingUsers.some(p => p.id === user.id)
                    );
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        addToPending(user) {
            console.log('addToPending appelé avec utilisateur:', user);
            this.pendingUsers.push({
                id: user.id,
                name: user.name,
                email: user.email,
                telephone: user.telephone,
                role_id: this.availableRoles[0]?.id || null
            });
            console.log('pendingUsers après ajout:', this.pendingUsers);
            console.log('pendingUsers.length:', this.pendingUsers.length);
            console.log('La section utilisateurs à ajouter devrait s\'afficher');
            this.searchQuery = '';
            this.searchResults = [];
        },

        removeFromPending(index) {
            this.pendingUsers.splice(index, 1);
        },

        confirmRemove(userId, userName) {
            this.removeModal = {
                show: true,
                userId: userId,
                userName: userName
            };
        },

        async confirmRemoveMember() {
            try {
                const response = await fetch(`/admin/farms/${this.farmId}/members/${this.removeModal.userId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Membre retiré avec succès', 'success');
                    // Remove the row from DOM
                    const row = document.querySelector(`[x-data="memberRow('${this.removeModal.userId}'"]`);
                    if (row) {
                        row.closest('tr').remove();
                    }
                } else {
                    showToast(result.message || 'Erreur lors du retrait', 'danger');
                }
            } catch (error) {
                console.error('Remove error:', error);
                showToast('Erreur serveur', 'danger');
            }

            this.removeModal.show = false;
        }
    };
}

// ─── Alpine.js: Member Row Component ──────────────────────────
function memberRow(userId, initialRole, availableRoles) {
    return {
        userId: userId,
        currentRole: initialRole,
        availableRoles: availableRoles || [],

        async updateRole(userId) {
            try {
                const response = await fetch(`/admin/farms/${document.querySelector('[x-data^="farmMembers"]').getAttribute('x-data').match(/farmMembers\(['"]([^'"]+)['"]/)[1]}/members/update-role`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: this.userId,
                        role_id: this.currentRole
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Rôle mis à jour avec succès', 'success');
                } else {
                    showToast(result.message || 'Erreur lors de la mise à jour', 'danger');
                    // Revert to previous role
                    this.currentRole = initialRole;
                }
            } catch (error) {
                console.error('Update role error:', error);
                showToast('Erreur serveur', 'danger');
                this.currentRole = initialRole;
            }
        }
    };
}
