/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Lots Trashed JS
═══════════════════════════════════════════════════════ */

function lotsTrashed() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Restaurer un lot
         */
        restoreLot(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Restaurer ce lot ?',
                message: `Êtes-vous sûr de vouloir restaurer le lot <strong>${name}</strong> ?`,
                action: () => {
                    document.getElementById(`form-restore-${id}`).submit();
                }
            };
        },

        /**
         * Confirmer l'action
         */
        confirmAction() {
            if (this.confirmModal.action) {
                this.confirmModal.action();
            }
        }
    };
}
