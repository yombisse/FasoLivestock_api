/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Especes Trashed JS
═══════════════════════════════════════════════════════ */

function especesTrashed() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Restaurer une espèce
         */
        restoreEspece(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Restaurer cette espèce ?',
                message: `Êtes-vous sûr de vouloir restaurer l'espèce <strong>${name}</strong> ?`,
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
