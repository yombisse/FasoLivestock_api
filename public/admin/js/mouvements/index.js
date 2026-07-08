/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Mouvements Index JS
═══════════════════════════════════════════════════════ */

function mouvementsIndex() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Supprimer un mouvement
         */
        deleteMouvement(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Archiver ce mouvement ?',
                message: `Êtes-vous sûr de vouloir archiver le mouvement de <strong>${name}</strong> ?`,
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
