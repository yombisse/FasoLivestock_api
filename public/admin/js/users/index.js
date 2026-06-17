/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Users Index JS
═══════════════════════════════════════════════════════ */

function usersIndex() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
            type:    'danger',
        },

        /**
         * Ouvrir la modal de confirmation
         */
        openConfirm(title, message, action, type = 'danger') {
            this.confirmModal = { show: true, title, message, action, type };
        },

        /**
         * Confirmer l'action
         */
        confirmAction() {
            if (this.confirmModal.action) {
                this.confirmModal.action();
            }
            this.confirmModal.show = false;
        },

        /**
         * Archiver un utilisateur
         */
        deleteUser(id, name) {
            this.openConfirm(
                'Archiver cet utilisateur',
                `Voulez-vous archiver <strong>${name}</strong> ? Cette action est réversible.`,
                () => this.submitForm(`form-delete-${id}`),
            );
        },

        /**
         * Basculer statut actif/inactif
         */
        toggleUser(id, name, isActive) {
            const action  = isActive ? 'désactiver' : 'activer';
            const type    = isActive ? 'warning' : 'success';
            this.openConfirm(
                `${isActive ? 'Désactiver' : 'Activer'} cet utilisateur`,
                `Voulez-vous ${action} <strong>${name}</strong> ?`,
                () => this.submitForm(`form-toggle-${id}`),
                type
            );
        },

        /**
         * Soumettre un formulaire caché par ID
         */
        submitForm(formId) {
            const form = document.getElementById(formId);
            if (form) form.submit();
        },

        /**
         * Filtres — soumettre le formulaire automatiquement
         */
        autoSubmit() {
            this.$nextTick(() => {
                document.getElementById('filter-form')?.submit();
            });
        },
    };
}