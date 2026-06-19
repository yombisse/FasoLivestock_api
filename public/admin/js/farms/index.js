/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Farms Index JS
═══════════════════════════════════════════════════════ */

function farmsIndex() {
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
         * Archiver une ferme
         */
        deleteFarm(id, name) {
            this.openConfirm(
                'Archiver cette ferme',
                `Voulez-vous archiver la ferme <strong>${name}</strong> ? Cette action est réversible.`,
                () => this.submitForm(`form-delete-${id}`),
                'danger'
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