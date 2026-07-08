/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Create JS
═══════════════════════════════════════════════════════ */

function animalCreateForm() {
    return {
        farms: window.farmsData || [],
        lots: window.lotsData || [],
        selectedLot: '',
        mode: window.modeData || 'enregistrement',

        loadLots(farmId) {
            if (!farmId) {
                this.lots = [];
                this.selectedLot = '';
                return;
            }
            fetch(`${window.lotsByFarmUrl}?farm_id=${farmId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.lots = data.lots;
                        this.selectedLot = '';
                    }
                })
                .catch(err => console.error('Erreur chargement lots:', err));
        },

        isMode(mode) {
            return this.mode === mode;
        },

        isAchat() {
            return this.mode === 'achat';
        },

        isNaissance() {
            return this.mode === 'naissance';
        },

        isEnregistrement() {
            return this.mode === 'enregistrement';
        }
    };
}
