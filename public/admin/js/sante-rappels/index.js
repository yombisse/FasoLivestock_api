/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — SanteRappels Index JS
═══════════════════════════════════════════════════════ */

function santeRappelsIndex() {
    return {
        // ─── Confirm modal (marquer réalisé) ─────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        // ─── Delete modal ────────────────────────────────────
        deleteModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Marquer un rappel comme réalisé
         */
        marquerRealise(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Marquer comme réalisé ?',
                message: `Êtes-vous sûr de vouloir marquer le rappel de <strong>${name}</strong> comme réalisé ?`,
                action: () => {
                    document.getElementById(`form-realise-${id}`).submit();
                }
            };
        },

        /**
         * Supprimer un rappel
         */
        deleteRappel(id, name) {
            this.deleteModal = {
                show: true,
                title: 'Archiver ce rappel ?',
                message: `Êtes-vous sûr de vouloir archiver le rappel de <strong>${name}</strong> ?`,
                action: () => {
                    document.getElementById(`form-delete-${id}`).submit();
                }
            };
        },

        /**
         * Confirmer l'action (marquer réalisé)
         */
        confirmAction() {
            if (this.confirmModal.action) {
                this.confirmModal.action();
            }
        },

        /**
         * Confirmer la suppression
         */
        confirmDelete() {
            if (this.deleteModal.action) {
                this.deleteModal.action();
            }
        }
    };
}
