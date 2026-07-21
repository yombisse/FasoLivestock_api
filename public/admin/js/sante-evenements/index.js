/**
 * Gestion des événements sanitaires - Index
 * Utilise l'API via ApiService (pas d'appels AJAX directs)
 */

function santeEvenementsIndex() {
    return {
        confirmModal: {
            show: false,
            title: '',
            message: '',
            action: null,
            params: null
        },

        deleteEvenement(id, nomType) {
            this.confirmModal = {
                show: true,
                title: 'Archiver l\'événement',
                message: `Voulez-vous vraiment archiver l'événement <strong>${nomType}</strong> ?`,
                action: 'delete',
                params: { id }
            };
        },

        confirmAction() {
            if (this.confirmModal.action === 'delete') {
                this.performDelete(this.confirmModal.params.id);
            }
            this.confirmModal.show = false;
        },

        async performDelete(id) {
            try {
                // Utiliser le formulaire caché pour la suppression via POST
                const form = document.getElementById(`form-delete-${id}`);
                if (form) {
                    form.submit();
                }
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                alert('Erreur lors de la suppression de l\'événement');
            }
        }
    };
}
