/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Mouvements Edit JS
═══════════════════════════════════════════════════════ */

function mouvementEditForm() {
    return {
        type: '',

        init() {
            this.type = '{{ old('type_evenement_id', $mouvement['type_evenement']['nom_type'] ?? '') }}';
        }
    };
}
