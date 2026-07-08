/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Index JS
═══════════════════════════════════════════════════════ */

function animalsIndex() {
    return {
        originModal: { show: false },
        confirmModal: { show: false, title: '', message: '', action: null },

        autoSubmit() {
            document.getElementById('filter-form').submit();
        },

        deleteAnimal(id, name) {
            this.confirmModal = {
                show: true,
                title: 'Archiver cet animal ?',
                message: `Êtes-vous sûr de vouloir archiver l'animal <strong>${name}</strong> ?`,
                action: () => { document.getElementById(`form-delete-${id}`).submit(); }
            };
        },

        confirmAction() {
            if (this.confirmModal.action) this.confirmModal.action();
        }
    };
}
