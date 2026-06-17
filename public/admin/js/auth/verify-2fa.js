/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — 2FA Verify JS (Alpine.js component)
═══════════════════════════════════════════════════════ */

function twoFaForm() {
    return {
        code:    ['', '', '', '', '', ''],
        loading: false,
        error:   '',
        timer:   600, // 10 minutes en secondes
        interval: null,

        get fullCode() {
            return this.code.join('');
        },

        get timerDisplay() {
            const m = Math.floor(this.timer / 60).toString().padStart(2, '0');
            const s = (this.timer % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        get isExpiring() {
            return this.timer <= 60;
        },

        init() {
            this.startTimer();
            this.$nextTick(() => {
                document.querySelector('.code-input')?.focus();
            });
        },

        startTimer() {
            this.interval = setInterval(() => {
                if (this.timer > 0) {
                    this.timer--;
                } else {
                    clearInterval(this.interval);
                    this.error = 'Le code a expiré. Veuillez vous reconnecter.';
                }
            }, 1000);
        },

        onInput(index, event) {
            const val = event.target.value.replace(/\D/g, '');
            this.code[index] = val.slice(-1);
            event.target.value = this.code[index];

            // Marquer comme rempli
            event.target.classList.toggle('filled', this.code[index] !== '');

            // Passer au suivant automatiquement
            if (this.code[index] && index < 5) {
                this.$nextTick(() => {
                    document.querySelectorAll('.code-input')[index + 1]?.focus();
                });
            }

            // Soumettre automatiquement si 6 chiffres
            if (this.fullCode.length === 6) {
                this.$nextTick(() => this.submit());
            }
        },

        onKeydown(index, event) {
            // Retour arrière → champ précédent
            if (event.key === 'Backspace' && !this.code[index] && index > 0) {
                this.$nextTick(() => {
                    document.querySelectorAll('.code-input')[index - 1]?.focus();
                });
            }

            // Coller le code complet
            if (event.key === 'v' && (event.ctrlKey || event.metaKey)) {
                navigator.clipboard.readText().then(text => {
                    const digits = text.replace(/\D/g, '').slice(0, 6);
                    digits.split('').forEach((d, i) => {
                        this.code[i] = d;
                        const inputs = document.querySelectorAll('.code-input');
                        if (inputs[i]) {
                            inputs[i].value = d;
                            inputs[i].classList.add('filled');
                        }
                    });
                    if (digits.length === 6) this.submit();
                });
            }
        },

        submit() {
            if (this.fullCode.length !== 6 || this.loading) return;
            this.loading = true;
            this.error   = '';

            // Mettre le code dans le champ hidden et soumettre
            const hiddenInput = document.getElementById('code-hidden');
            if (hiddenInput) hiddenInput.value = this.fullCode;

            document.getElementById('form-2fa')?.submit();
        },

        resetError() {
            const inputs = document.querySelectorAll('.code-input');
            inputs.forEach(i => i.classList.remove('error'));
            this.error = '';
        },

        destroy() {
            clearInterval(this.interval);
        },
    };
}