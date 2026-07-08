/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Mouvements Create JS
═══════════════════════════════════════════════════════ */

function mouvementCreateForm() {
    return {
        type: '',

        init() {
            this.type = '{{ old('type_evenement_id', '') }}';
        }
    };
}
