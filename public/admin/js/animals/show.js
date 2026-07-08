/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Show JS
═══════════════════════════════════════════════════════ */

function animalsShow() {
    return {
        loading: false,
        showDeleteModal: false,
        
        init() {
            console.log('Animals show initialized');
        },

        // Confirmer la suppression
        confirmDelete() {
            this.showDeleteModal = true;
        },

        // Annuler la suppression
        cancelDelete() {
            this.showDeleteModal = false;
        },

        // Exécuter la suppression
        executeDelete() {
            const form = document.getElementById('delete-form');
            if (form) {
                form.submit();
            }
        }
    }
}
