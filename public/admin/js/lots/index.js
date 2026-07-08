/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Lots Index JS
═══════════════════════════════════════════════════════ */

function lotsIndex() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Archiver un lot
         */
        deleteLot(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Archiver ce lot ?',
                message: `Êtes-vous sûr de vouloir archiver le lot <strong>${name}</strong> ?`,
                action: () => {
                    document.getElementById(`form-delete-${id}`).submit();
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
