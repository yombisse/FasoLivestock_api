/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Farms Create JS
═══════════════════════════════════════════════════════ */

function farmCreateForm() {
    return {
        loading: false,

        submit() {
            const name = document.getElementById('name')?.value?.trim();

            if (!name) {
                document.getElementById('name')?.focus();
                return;
            }

            this.loading = true;
            document.getElementById('farm-create-form')?.submit();
        },
    };
}