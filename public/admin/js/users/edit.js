/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Users Edit JS
═══════════════════════════════════════════════════════ */

function userEditForm(initialRoles) {
    return {
        showPassword:        false,
        showPasswordConfirm: false,
        password:            '',
        selectedRoles:       initialRoles || [],
        loading:             false,
        changePassword:      false,

        // ─── Toggle password visibility ───────────────────
        togglePassword(field) {
            if (field === 'password') {
                this.showPassword = !this.showPassword;
                const input = document.getElementById('password');
                if (input) input.type = this.showPassword ? 'text' : 'password';
            } else {
                this.showPasswordConfirm = !this.showPasswordConfirm;
                const input = document.getElementById('password_confirmation');
                if (input) input.type = this.showPasswordConfirm ? 'text' : 'password';
            }
        },

        // ─── Password strength ────────────────────────────
        get passwordStrength() {
            const pwd = this.password;
            if (!pwd) return { score: 0, label: '', bars: [false, false, false, false] };

            let score = 0;
            if (pwd.length >= 8)  score++;
            if (pwd.length >= 12) score++;
            if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
            if (/[0-9]/.test(pwd)) score++;
            if (/[^A-Za-z0-9]/.test(pwd)) score++;

            const levels = [
                { score: 0, label: '',          class: '' },
                { score: 1, label: 'Faible',    class: 'weak' },
                { score: 2, label: 'Moyen',     class: 'medium' },
                { score: 3, label: 'Bon',       class: 'medium' },
                { score: 4, label: 'Fort',      class: 'strong' },
                { score: 5, label: 'Très fort', class: 'strong' },
            ];

            const level = levels[Math.min(score, 5)];
            const bars  = Array(4).fill(false).map((_, i) => i < score ? level.class : false);

            return { score, label: level.label, bars };
        },

        // ─── Toggle role ──────────────────────────────────
        toggleRole(roleName) {
            const idx = this.selectedRoles.indexOf(roleName);
            if (idx === -1) {
                this.selectedRoles.push(roleName);
            } else {
                this.selectedRoles.splice(idx, 1);
            }
        },

        isRoleSelected(roleName) {
            return this.selectedRoles.includes(roleName);
        },

        // ─── Submit ───────────────────────────────────────
        submit() {
            if (this.selectedRoles.length === 0) {
                const el = document.getElementById('roles-error');
                if (el) {
                    el.style.display = 'block';
                    setTimeout(() => el.style.display = 'none', 4000);
                }
                return;
            }
            this.loading = true;
            document.getElementById('user-edit-form')?.submit();
        },
    };
}