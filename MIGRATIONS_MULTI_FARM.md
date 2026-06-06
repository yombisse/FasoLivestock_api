# Liste des fichiers modifiés - Architecture Multi-Farms + Offline-First

## 📝 Migrations (Nouvelles et Modifiées)

### Nouvelles migrations créées:
1. `database/migrations/2026_05_21_120000_create_farms_table.php` - Table farms
2. `database/migrations/2026_05_21_120001_create_farm_user_table.php` - Table pivot farm_user

### Migrations modifiées (ajout farm_id, sync_status, last_modified_by):
3. `database/migrations/2026_05_21_120109_create_animals_table.php`
4. `database/migrations/2026_05_21_120128_create_transactions_table.php`
5. `database/migrations/2026_05_21_120109_create_evenements_table.php`
6. `database/migrations/2026_05_21_120045_create_lots_table.php`
7. `database/migrations/2026_05_21_120208_create_notifications_table.php`
8. `database/migrations/2026_05_21_120109_create_naissances_table.php`

### Migrations modifiées (ajout sync_status, last_modified_by uniquement - tables de référence):
9. `database/migrations/2026_05_21_120045_create_especes_table.php`
10. `database/migrations/2026_05_21_120046_create_categories_table.php`
11. `database/migrations/2026_05_21_120046_create_type_evenements_table.php`

---

## 🎨 Modèles (Nouveau et Modifiés)

### Nouveau modèle créé:
12. `app/Models/Farm.php` - Modèle Farm avec relations

### Modèles modifiés (ajout farm_id, sync_status, last_modified_by dans fillable + relation farm):
13. `app/Models/User.php` - Ajout relations ownedFarms() et farms()
14. `app/Models/Animal.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()
15. `app/Models/Transaction.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()
16. `app/Models/Evenement.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()
17. `app/Models/Lot.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()
18. `app/Models/Notification.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()
19. `app/Models/Naissance.php` - Ajout farm_id, sync_status, last_modified_by + relation farm()

### Modèles modifiés (ajout sync_status, last_modified_by uniquement - tables de référence):
20. `app/Models/Espece.php` - Remplacement synced/last_sync_at par sync_status/last_modified_by
21. `app/Models/Categorie.php` - Remplacement synced/last_sync_at par sync_status/last_modified_by
22. `app/Models/TypeEvenement.php` - Remplacement synced/last_sync_at par sync_status/last_modified_by

---

## 🛣️ Routes

### Fichier modifié:
23. `routes/api.php` - Ajout routes POST /sync/push et GET /sync/pull

---

## 🎮 Contrôleurs

### Nouveau contrôleur créé:
24. `app/Http/Controllers/Api/SyncController.php` - Contrôleur pour synchronisation mobile (push/pull)

---

## 📊 Résumé des changements

### Nouveaux fichiers créés: 4
- 2 migrations (farms, farm_user)
- 1 modèle (Farm)
- 1 contrôleur (SyncController)

### Fichiers modifiés: 20
- 9 migrations (animals, transactions, evenements, lots, notifications, naissances, especes, categories, type_evenements)
- 9 modèles (User, Animal, Transaction, Evenement, Lot, Notification, Naissance, Espece, Categorie, TypeEvenement)
- 1 route (api.php)
- 1 contrôleur (SyncController - nouveau)

**Total: 24 fichiers touchés**

---

## 🚀 Pour tester l'installation

```bash
# Nettoyer la base de données
php artisan migrate:fresh

# Optionnel: Seeder les rôles
php artisan db:seed --class=RoleSeeder
```

---

## 🔑 Points clés de l'architecture

### Multi-Farms
- Toutes les données métier sont liées à une farm via `farm_id`
- Relation many-to-many entre users et farms via `farm_user`
- Rôles: owner, manager, vet, worker

### Offline-First
- Chaque table métier a `sync_status` (pending, synced, conflict)
- Chaque table métier a `last_modified_by` (user_id)
- Soft deletes obligatoires (`deleted_at`)
- API de synchronisation: POST /sync/push et GET /sync/pull
- Gestion des conflits basée sur `updated_at`

### Isolation des données
- Aucun enregistrement sans farm_id (tables métier)
- Les tables de référence (especes, categories, type_evenements) sont globales mais synchronisables
- Validation des droits d'accès aux farms dans le SyncController
