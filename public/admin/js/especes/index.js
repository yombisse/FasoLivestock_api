/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Especes Index JS
═══════════════════════════════════════════════════════ */

function especesIndex() {
    return {
        // ─── Confirm modal ────────────────────────────────
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
        },

        /**
         * Archiver une espèce
         */
        deleteEspece(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Archiver cette espèce ?',
                message: `Êtes-vous sûr de vouloir archiver l'espèce <strong>${name}</strong> ?`,
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
