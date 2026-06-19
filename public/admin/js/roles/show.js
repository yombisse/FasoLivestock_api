document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.roleShow !== 'function') return;
    // nothing: roleShow() is used by x-data
});

window.roleShow = function () {
    return {
        roleId: null,
        usersSelect: null,

        users: [],
        isLoading: false,

        init() {
            // roleId comes from DOM dataset
            const root = document.querySelector('[data-role-id]');
            this.roleId = root?.dataset?.roleId || null;

            if (!this.roleId) return;

            this.usersSelect = document.getElementById('role-user-select');
            this.attachBtn = document.getElementById('role-user-attach-btn');

            this.errorBox = document.getElementById('role-users-error');
            this.successBox = document.getElementById('role-users-success');

            this.loadingEl = document.getElementById('role-users-loading');
            this.containerEl = document.getElementById('role-users-container');
            this.tbodyEl = document.getElementById('role-users-tbody');
            this.emptyEl = document.getElementById('role-users-empty');
            this.countEl = document.getElementById('role-users-count');

            this.loadUsers();
        },

        setAlert(el, msg, type) {
            if (!el) return;
            el.classList.remove('d-none');
            el.classList.toggle('alert-danger', type === 'error');
            el.classList.toggle('alert-success', type === 'success');
            el.textContent = msg;
        },

        clearAlerts() {
            [this.errorBox, this.successBox].forEach((el) => {
                if (!el) return;
                el.classList.add('d-none');
                el.textContent = '';
            });
        },

        apiUrl(path) {
            return path; // keep relative: routes are handled by backend proxy
        },

        async loadUsers() {
            this.clearAlerts();

            this.isLoading = true;
            this.loadingEl?.classList.remove('d-none');
            this.containerEl?.classList.add('d-none');
            this.emptyEl?.classList.add('d-none');
            if (this.tbodyEl) this.tbodyEl.innerHTML = '';

            try {
                const res = await fetch(this.apiUrl(`/admin/role-users/${this.roleId}/users`), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                });

                const json = await res.json();

                if (!json.success && json.data?.success === false) {
                    // tolerate different shapes
                    throw new Error(json.data?.message || json.message || 'Erreur');
                }

                const payload = json.data?.data ? json.data.data : json.data ?? json;
                // Expected: { users: [], count: N }
                const users = payload.users ?? json.data?.users ?? [];
                const count = payload.count ?? users.length;

                this.users = users;
                if (this.countEl) this.countEl.textContent = count;

                this.loadingEl?.classList.add('d-none');
                this.containerEl?.classList.remove('d-none');

                if (!users || users.length === 0) {
                    this.emptyEl?.classList.remove('d-none');
                    return;
                }

                if (this.tbodyEl) {
                    this.tbodyEl.innerHTML = '';
                    users.forEach((u) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <div class="fw-semibold">${u.name ?? ''}</div>
                                <div class="text-muted small">${u.email ?? ''}</div>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-user-id="${u.id}"
                                >
                                    <i class="bi bi-trash"></i> Retirer
                                </button>
                            </td>
                        `;
                        this.tbodyEl.appendChild(tr);
                    });
                }

                // wire detach buttons
                document.querySelectorAll('[data-user-id]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const userId = btn.getAttribute('data-user-id');
                        this.detachUser(userId);
                    });
                });

            } catch (e) {
                this.setAlert(this.errorBox, e.message || 'Erreur de chargement', 'error');
                this.isLoading = false;
                this.loadingEl?.classList.add('d-none');
            } finally {
                this.isLoading = false;
            }
        },

        async detachUser(userId) {
            if (!userId) return;
            this.clearAlerts();

            try {
                const res = await fetch(this.apiUrl(`/admin/role-users/${this.roleId}/detach/${userId}`), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        // CSRF might be required depending on web routes; fallback to headerless if not
                    },
                    body: JSON.stringify({ user_id: userId }),
                });

                const json = await res.json();
                if (!json.success && json.data?.success === false) {
                    throw new Error(json.data?.message || json.message || 'Erreur');
                }

                this.setAlert(this.successBox, 'Utilisateur retiré avec succès.', 'success');
                await this.loadUsers();
            } catch (e) {
                this.setAlert(this.errorBox, e.message || 'Erreur de retrait', 'error');
            }
        },
    };
};
