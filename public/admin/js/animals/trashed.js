/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Trashed JS
═══════════════════════════════════════════════════════ */

function animalsTrashed() {
    return {
        loading: false,
        
        init() {
            // Focus sur le champ de recherche
            if (this.$refs.searchInput) {
                this.$refs.searchInput.focus();
            }
            console.log('Animals trashed initialized');
        },

        // Restaurer un animal
        restoreAnimal(id) {
            if (confirm('Êtes-vous sûr de vouloir restaurer cet animal ?')) {
                const form = document.getElementById(`form-restore-${id}`);
                if (form) {
                    form.submit();
                }
            }
        }
    }
}
