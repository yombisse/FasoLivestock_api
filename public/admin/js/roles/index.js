/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Roles Index JS
═══════════════════════════════════════════════════════ */

function rolesIndex() {
    return {
        confirmModal: {
            show:    false,
            title:   '',
            message: '',
            action:  null,
            type:    'danger',
        },

        openConfirm(title, message, action, type = 'danger') {
            this.confirmModal = { show: true, title, message, action, type };
        },

        confirmAction() {
            if (this.confirmModal.action) this.confirmModal.action();
            this.confirmModal.show = false;
        },

        deleteRole(id, name) {
            this.openConfirm(
                'Archiver ce rôle',
                `Voulez-vous archiver le rôle <strong>${name}</strong> ?
                 Cette action est réversible.`,
                () => document.getElementById(`form-delete-${id}`)?.submit()
            );
        },
    };
}