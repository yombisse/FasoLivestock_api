/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Create JS
═══════════════════════════════════════════════════════ */

function animalCreateForm() {
    return {
        farms: window.farmsData || [],
        lots: window.lotsData || [],
        mode: window.modeData || 'enregistrement',
        selectedFarmId: null,
        eligibleMothers: [],
        loadingMothers: false,

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
        },

        async loadLots(farmId) {
            this.selectedFarmId = farmId;
            if (!farmId) {
                this.lots = [];
                this.eligibleMothers = [];
                return;
            }

            // Charger les lots
            try {
                const response = await fetch(window.lotsByFarmUrl + '?farm_id=' + farmId, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.lots = data.lots || [];
            } catch (error) {
                console.error('Error loading lots:', error);
                this.lots = [];
            }

            // Charger les mères éligibles si mode naissance
            if (this.isNaissance()) {
                this.loadEligibleMothers(farmId);
            }
        },

        async loadEligibleMothers(farmId) {
            if (!farmId) {
                this.eligibleMothers = [];
                return;
            }

            this.loadingMothers = true;
            try {
                const response = await fetch(window.eligibleMothersUrl + '?farm_id=' + farmId + '&type_reproduction=mise_bas', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.eligibleMothers = data.data?.animals || [];
            } catch (error) {
                console.error('Error loading eligible mothers:', error);
                this.eligibleMothers = [];
            } finally {
                this.loadingMothers = false;
            }
        },

        onMotherChange(motherId) {
            // Logique supplémentaire si nécessaire lors du changement de mère
            console.log('Mother selected:', motherId);
        }
    };
}
