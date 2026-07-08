/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Animals Edit JS
═══════════════════════════════════════════════════════ */

function animalsEdit() {
    return {
        loading: false,
        
        init() {
            // Initialisation
            console.log('Animals edit initialized');
        },

        // Charger les lots selon la ferme
        loadLots(farmId) {
            if (!farmId) {
                this.lots = [];
                return;
            }

            fetch(`/admin/animals/lots-by-farm?farm_id=${farmId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.lots = data.lots;
                    }
                })
                .catch(error => console.error('Error loading lots:', error));
        },

        // Prévisualiser la photo
        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    }
}
