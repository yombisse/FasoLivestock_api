function financeIndex() {
    return {
        confirmModal: {
            show: false,
            title: '',
            message: '',
            action: null
        },
        bilan: {
            total_entrees: {{ $bilan['total_entrees'] ?? 0 }},
            total_sorties: {{ $bilan['total_sorties'] ?? 0 }},
            solde: {{ $bilan['solde'] ?? 0 }}
        },

        init() {
            // Écouter les changements de filtres pour rafraîchir le bilan
            const filterForm = document.getElementById('filter-form');
            if (filterForm) {
                filterForm.addEventListener('submit', () => {
                    // Le formulaire sera soumis normalement, le bilan sera recalculé côté serveur
                });
            }
        },

        deleteTransaction(id, description) {
            this.confirmModal = {
                show: true,
                title: 'Archiver la transaction',
                message: `Êtes-vous sûr de vouloir archiver la transaction <strong>${description}</strong> ?`,
                action: () => {
                    document.getElementById(`form-delete-${id}`).submit();
                }
            };
        },

        confirmAction() {
            if (this.confirmModal.action) {
                this.confirmModal.action();
            }
            this.confirmModal.show = false;
        },

        formatNumber(num) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num || 0);
        }
    };
}
