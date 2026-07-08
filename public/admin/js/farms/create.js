/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Farms Create JS
═══════════════════════════════════════════════════════ */

function farmCreateForm(data) {
    console.log('Données reçues par Alpine.js:', data);
    console.log('potentialOwners:', data.potentialOwners);
    console.log('potentialOwners count:', data.potentialOwners?.length);

    return {
        loading: false,
        photoPreview: null,
        showOwnerField: data.showOwnerField || false,
        ownerFieldRequired: data.ownerFieldRequired || false,
        potentialOwners: data.potentialOwners || [],
        currentUser: data.currentUser || {},
        oldOwnerId: data.oldOwnerId || '',
        ownerSearch: '',
        selectedOwnerId: data.oldOwnerId || '',

        init() {
            console.log('Alpine.js initialized');
        },

        get filteredOwners() {
            if (!this.ownerSearch) return [];
            const search = this.ownerSearch.toLowerCase();
            return this.potentialOwners.filter(user =>
                user.name.toLowerCase().includes(search) ||
                user.email.toLowerCase().includes(search)
            );
        },

        get displayedOwners() {
            return this.ownerSearch ? this.filteredOwners : this.potentialOwners;
        },

        selectOwner(user) {
            this.selectedOwnerId = user.id;
            this.ownerSearch = user.name;
        },

        clearOwner() {
            this.selectedOwnerId = '';
            this.ownerSearch = '';
        },

        getSelectedOwnerName() {
            const owner = this.potentialOwners.find(u => u.id === this.selectedOwnerId);
            return owner ? owner.name : '';
        },

        handlePhotoPreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.photoPreview = URL.createObjectURL(file);
            }
        },

        removePhoto() {
            this.photoPreview = null;
            document.getElementById('photo').value = '';
        },

        submit() {
            const name = document.getElementById('name')?.value?.trim();
            const ownerId = document.getElementById('owner_id')?.value;

            if (!name) {
                document.getElementById('name')?.focus();
                return;
            }

            if (this.ownerFieldRequired && !ownerId) {
                document.getElementById('owner_search')?.focus();
                alert('Veuillez sélectionner un propriétaire.');
                return;
            }

            this.loading = true;
            document.getElementById('farm-create-form')?.submit();
        }
    };
}