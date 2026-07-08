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
        removeModal: {
            show: false,
            userId: null,
            userName: ''
        },

        init() {
            // Collect existing member IDs to avoid duplicates
            const memberRows = document.querySelectorAll('[x-data^="memberRow"]');
            memberRows.forEach(row => {
                const match = row.getAttribute('x-data').match(/memberRow\(['"]([^'"]+)['"]/);
                if (match) {
                    this.existingMemberIds.push(match[1]);
                }
            });
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
            this.pendingUsers.push({
                id: user.id,
                name: user.name,
                email: user.email,
                telephone: user.telephone,
                role: 'manager'
            });
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
function memberRow(userId, initialRole) {
    return {
        userId: userId,
        currentRole: initialRole,

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
                        role: this.currentRole
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
