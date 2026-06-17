document.addEventListener('DOMContentLoaded', () => {

    const selectAll = document.getElementById('select-all');

    selectAll?.addEventListener('change', function () {

        document
            .querySelectorAll('input[name="permissions[]"]')
            .forEach(cb => cb.checked = this.checked);

        document
            .querySelectorAll('.module-checkbox')
            .forEach(cb => cb.checked = this.checked);
    });

    document
        .querySelectorAll('.module-checkbox')
        .forEach(module => {

            module.addEventListener('change', function(){

                const card =
                    this.closest('.permission-card');

                card.querySelectorAll(
                    'input[name="permissions[]"]'
                ).forEach(permission => {

                    permission.checked =
                        this.checked;

                });

            });

        });

});