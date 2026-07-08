/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Lots Assign JS
═══════════════════════════════════════════════════════ */

function lotAssign() {
    return {
        selectedAnimals: [],

        /**
         * Toggle animal selection
         */
        toggleAnimal(id) {
            const index = this.selectedAnimals.indexOf(id);
            if (index > -1) {
                this.selectedAnimals.splice(index, 1);
            } else {
                this.selectedAnimals.push(id);
            }
        }
    };
}
