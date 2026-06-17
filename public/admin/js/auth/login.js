/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Login JS (Alpine.js component)
═══════════════════════════════════════════════════════ */

function loginForm() {
    return {
        showPassword: false,
        loading:      false,
        error:        '',

        togglePassword() {
            this.showPassword = !this.showPassword;
            const input = document.getElementById('password');
            if (input) input.type = this.showPassword ? 'text' : 'password';
        },

        async submit(event) {
            this.error   = '';
            this.loading = true;

            // On laisse le form submit normalement (Blade/Laravel)
            // Juste pour l'animation du bouton
            await new Promise(r => setTimeout(r, 300));

            event.target.submit();
        },
    };
}