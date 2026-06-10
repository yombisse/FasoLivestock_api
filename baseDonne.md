# Audit architecture Base de données (FasoLivestock API)

> Source de vérité : **migrations Laravel** du dossier `database/migrations`.

## 1) Vue d’ensemble
- **Identifiants** : la majorité des tables métiers utilisent des **UUID** (`uuid('id')`).
- **Multi-fermes** :
  - `farms` est possédée par un utilisateur (`owner_id`).
  - l’accès des utilisateurs aux fermes est géré via `farm_user` (table pivot N:N) avec un champ `role`.
- **Soft deletes** : tables métiers (ex: `animals`, `farms`, etc.) contiennent `softDeletes()`.
- **Sync / Offline-first** :
  - tables métiers possèdent des champs type `sync_status` et `last_modified_by`.
  - tables de référence (`especes`, `categories`, `type_evenements`) ont été **modifiées** via la migration `2026_06_06_000002_remove_sync_fields_from_reference_tables` pour **retirer** `sync_status` et `last_modified_by`.
- **Versioning** : via `2026_06_06_000001_add_version_to_business_tables`, un champ `version` est ajouté à plusieurs tables métiers.

## 2) Tables & champs

### 2.1 Sécurité / Auth

#### `users`
- `id` (uuid, PK)
- `name` (string)
- `email` (string, unique)
- `telephone` (string, nullable)
- `email_verified_at` (timestamp, nullable)
- `password` (string)
- `is_active` (boolean, default true)
- `last_sync_at` (timestamp, nullable)
- `rememberToken()`
- `timestamps()`
- `softDeletes()`

#### `password_reset_tokens`
- `id` (uuid, PK)
- `identifier` (string, indexed)
- `token` (string)
- `type` (string, default `email`) *(d’origine; la migration 2026_05_23_000001 supprime certaines colonnes si elles existent)*
- `expires_at` (timestamp)
- `used` (boolean, default false)

> Migration `2026_05_23_000001_fix_password_reset_tokens_table.php` : ajoute `user_id` (FK users), `used_at`, timestamps/softDeletes, et supprime certaines colonnes (`email`, `telephone`, `type`) si présentes.

#### `sessions`
- `id` (string, PK)
- `user_id` (uuid, nullable, index)
- `ip_address` (string 45, nullable)
- `user_agent` (text, nullable)
- `payload` (longText)
- `last_activity` (integer, index)

#### `personal_access_tokens` (Laravel Sanctum)
- `id` (bigint, PK)
- `tokenable` (uuid morph)
- `name` (text)
- `token` (string 64, unique)
- `abilities` (text, nullable)
- `last_used_at` (timestamp, nullable)
- `expires_at` (timestamp, indexed)
- `timestamps()`

#### `two_factor_verifications`
- `id` (uuid, PK)
- `user_id` (uuid, FK → users, indexed)
- `channel` (enum: ici commenté `email | phone`, string 20)
- `identifier` (string 255)
- `code_hash` (string 255)
- `expires_at` (timestamp)
- `used` (boolean, default false)
- `used_at` (timestamp, nullable)
- `attempts` (unsignedSmallInteger, default 0)
- `created_at` / `updated_at` (timestamp; migration les crée explicitement)

#### `recevoir`
(Table join pour marquer une notification comme lue/non lue par user)
- Composite PK : (`notification_id`, `user_id`)
- `notification_id` (uuid, FK → notifications, cascadeOnDelete)
- `user_id` (uuid, FK → users, cascadeOnDelete)
- `is_read` (boolean, default false)
- `read_at` (timestamp, nullable)
- `timestamps()`
- Index : `user_id`, et index composite (`user_id`, `is_read`)

---

### 2.2 Multi-fermes / référentiels

#### `farms`
- `id` (uuid, PK)
- `name` (string)
- `location` (string, nullable)
- `description` (text, nullable)
- `owner_id` (uuid, FK → users, cascade)
- `timestamps()`
- `softDeletes()`
- Index : `owner_id`

#### `farm_user` (pivot)
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `user_id` (uuid, FK → users, cascade)
- `role` (enum: `owner`, `manager`, `vet`, `worker`, default `worker`)
- `timestamps()`
- Unique : (`farm_id`, `user_id`)
- Index : `farm_id`, `user_id`

#### `especes` (référence)
- `id` (uuid, PK)
- `nom` (string, unique)
- `description` (text, nullable)
- Champs sync **supprimés** par `2026_06_06_000002_remove_sync_fields_from_reference_tables`.
- À l’origine : `sync_status` + `last_modified_by`.
- `timestamps()`
- `softDeletes()`

#### `categories` (référence)
- `id` (uuid, PK)
- `nom_categorie` (string, unique)
- `type` (string, nullable)
- `timestamps()`
- `softDeletes()`
- Champs sync supprimés par `2026_06_06_000002_remove_sync_fields_from_reference_tables`.

#### `type_evenements` (référence)
- `id` (uuid, PK)
- `nom_type` (string, unique)
- `description` (string, nullable)
- `timestamps()`
- `softDeletes()`
- Champs sync supprimés par `2026_06_06_000002_remove_sync_fields_from_reference_tables`.

---

### 2.3 Tables métiers

#### `lots`
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `nom_lot` (string)
- `nombre` (integer unsigned, default 0)
- `sync_status` (enum: pending/synced/conflict)
- `last_modified_by` (uuid, FK → users, nullOnDelete)
- `timestamps()`
- `softDeletes()`
- Index : `farm_id`, `sync_status`
- **Versioning** : champ `version` ajouté par `2026_06_06_000001_add_version_to_business_tables`.

#### `animals`
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `nom` (string, nullable)
- `race` (string, nullable)
- `sexe` (enum `M`/`F`, nullable)
- `date_naissance` (date, nullable)
- `poids` (decimal 10,2, nullable)
- `statut` (enum `ACTIF`/`VENDU`/`MORT`/`PERDU`, default `ACTIF`)
- `espece_id` (uuid, FK → especes)
- `lot_id` (uuid, nullable, FK → lots)
- `mother_id` (uuid, nullable, auto-référence → animals)
- `sync_status` (enum pending/synced/conflict, default synced)
- `last_modified_by` (uuid, nullable, FK → users, nullOnDelete)
- `timestamps()`
- `softDeletes()`
- Indices : `farm_id`, `espece_id`, `lot_id`, `mother_id`, `statut`, `sync_status`
- **Versioning** : champ `version` ajouté par `2026_06_06_000001_add_version_to_business_tables`.

#### `evenements`
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `type_evenement_id` (uuid, FK → type_evenements, cascade)
- `animal_id` (uuid, FK → animals, cascade)
- `date_evenement` (date)
- `description` (string, nullable)
- `cout` (decimal 10,2, nullable)
- `sync_status` (enum pending/synced/conflict)
- `last_modified_by` (uuid, FK → users, nullOnDelete, nullable)
- `timestamps()`
- `softDeletes()`
- Index : `farm_id`, `type_evenement_id`, `animal_id`, `date_evenement`, `sync_status`
- **Versioning** : champ `version` ajouté.

#### `naissances`
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `mother_id` (uuid, FK → animals, cascade)
- `date_naissance` (date)
- `nombre_petits` (integer unsigned default 0)
- `poids_naissance` (decimal 10,2, nullable)
- `observation` (text, nullable)
- `sync_status` (enum)
- `last_modified_by` (uuid, nullable, FK → users, nullOnDelete)
- `timestamps()`
- `softDeletes()`
- Index : `farm_id`, `sync_status`
- **Versioning** : champ `version` ajouté.

#### `transactions`
- `id` (uuid, PK)
- `farm_id` (uuid, FK → farms, cascade)
- `type_transaction` (enum: `ENTREE`, `SORTIE`, `TRANSFERT`, `AJUSTEMENT`, default `ENTREE`)
- `montant` (decimal 12,2 unsigned)
- `date_transaction` (date)
- `user_id` (uuid, FK → users, cascade)
- `animal_id` (uuid, nullable, FK → animals, nullOnDelete)
- `categorie_id` (uuid, FK → categories, cascade)
- `description` (string, nullable)
- `sync_status` (enum)
- `last_modified_by` (uuid, nullable, FK → users, nullOnDelete)
- `timestamps()`
- `softDeletes()`
- Index : `farm_id`, `user_id`, `animal_id`, `categorie_id`, `date_transaction`, index composite (`user_id`, `date_transaction`), `sync_status`
- **Versioning** : champ `version` ajouté.

#### `notifications`
- `id` (uuid, PK)
- `farm_id` (uuid, nullable, FK → farms, nullOnDelete)
- `animal_id` (uuid, nullable, FK → animals, nullOnDelete)
- `titre` (string, nullable)
- `message` (text)
- `sent_at` (timestamp, nullable)
- `sync_status` (enum)
- `last_modified_by` (uuid, nullable, FK → users, nullOnDelete)
- `timestamps()`
- `softDeletes()`
- Index : `farm_id`, `animal_id`, `sent_at`, `sync_status`
- **Versioning** : champ `version` ajouté.

---

### 2.4 Permissions (spatie/laravel-permission)
Les tables sont construites à partir de `config/permission.php` (non lue ici), mais les migrations montrent clairement la structure.

#### `permissions`
- `id` (bigint, PK)
- `name` (string)
- `guard_name` (string)
- `timestamps()`
- Unique : (`name`, `guard_name`)

#### `roles`
- `id` (bigint, PK)
- `name` (string)
- `guard_name` (string)
- `timestamps()`
- Unique : (`name`, `guard_name`) ou (selon teams)
- Index/colonnes team si `teams` activé dans config

#### `model_has_permissions` (pivot polymorphes)
- `permission_id` (unsignedBigInteger, FK → permissions.id)
- `model_type` (string)
- `model_id` (uuid, via `model_morph_key`)
- Composite PK selon `teams` ou non

#### `model_has_roles` (pivot polymorphes)
- `role_id` (unsignedBigInteger, FK → roles.id)
- `model_type` + `model_id` (uuid morph)
- Composite PK selon `teams` ou non

#### `role_has_permissions`
- `permission_id` + `role_id`
- FKs vers `permissions` et `roles` avec cascadeOnDelete
- Composite PK (`permission_id`, `role_id`)

## 3) Relations (cartographie)

### 3.1 Entités métier
- **User → Farm**
  - `farms.owner_id` → `users.id` (cascade)
- **User ↔ Farm**
  - `farm_user.user_id` → `users.id` (cascade)
  - `farm_user.farm_id` → `farms.id` (cascade)
  - `farm_user` porte `role` (owner/manager/vet/worker)

- **Farm → Lots**
  - `lots.farm_id` → `farms.id` (cascade)
- **Farm → Animals**
  - `animals.farm_id` → `farms.id` (cascade)

- **Animals → Especes**
  - `animals.espece_id` → `especes.id`
- **Animals → Lots**
  - `animals.lot_id` → `lots.id` (nullable, si lot supprimé ? la migration utilise `constrained('lots')` sans expliciter nullOnDelete côté FK : à confirmer si vous avez des règles spécifiques)

- **Animals → Animals (mère)**
  - `animals.mother_id` → `animals.id` (nullable, nullOnDelete, via migration dédiée)

- **Farm → Evenements**
  - `evenements.farm_id` → `farms.id` (cascade)
- **Evenements → Type_evenement**
  - `evenements.type_evenement_id` → `type_evenements.id` (cascade)
- **Evenements → Animal**
  - `evenements.animal_id` → `animals.id` (cascade)

- **Farm → Naissances**
  - `naissances.farm_id` → `farms.id` (cascade)
- **Naissances → Mother (Animal)**
  - `naissances.mother_id` → `animals.id` (cascade)

- **Farm → Transactions**
  - `transactions.farm_id` → `farms.id` (cascade)
- **Transaction → User**
  - `transactions.user_id` → `users.id` (cascade)
- **Transaction → Animal (nullable)**
  - `transactions.animal_id` → `animals.id` (nullable, nullOnDelete)
- **Transaction → Category**
  - `transactions.categorie_id` → `categories.id` (cascade)

- **Farm → Notifications (nullable)**
  - `notifications.farm_id` → `farms.id` (nullable, nullOnDelete)
- **Notification → Animal (nullable)**
  - `notifications.animal_id` → `animals.id` (nullable, nullOnDelete)
- **Notifications ↔ Users** (lecture par user)
  - `recevoir.notification_id` → `notifications.id` (cascade)
  - `recevoir.user_id` → `users.id` (cascade)

### 3.2 Sécurité
- `two_factor_verifications.user_id` → `users.id` (cascade)
- `sessions.user_id` → `users.id` (nullable, index)
- `personal_access_tokens` est **polymorphique** (`uuidMorphs('tokenable')`)

## 4) Alertes / observations d’architecture (issus des migrations)
1. **Tables de référence vs tables métiers** :
   - `especes`, `categories`, `type_evenements` ont été traitées comme “globales” : suppression de `sync_status` et `last_modified_by` via `2026_06_06_000002_remove_sync_fields_from_reference_tables`.
2. **Versioning** :
   - l’ajout de `version` sur les “business tables” est cohérent pour une stratégie de résolution de conflits (offline-first), appliqué via `2026_06_06_000001_add_version_to_business_tables`.
3. **Modèle mère auto-référence** :
   - `animals.mother_id` avec `nullOnDelete` évite que la suppression d’un animal “mère” casse l’historique des autres animaux.
4. **Permissions** :
   - utilisation de la librairie standard Spatie (morph + pivot tables). Les FK dépendent de la config `permission.php` (teams ou non).

---

## 5) Tables non détaillées dans ce document
Les migrations standards Laravel (`cache`, `jobs`, `job_batches`, `failed_jobs`) sont présentes dans le projet, mais le focus ici est sur la **structure métier** et les tables **auth/permission** directement liées à ton domaine.

> Si tu veux, je peux compléter ce fichier avec un inventaire “100%” de toutes les migrations incluses (y compris `cache`, `jobs`, etc.) en ajoutant leurs champs.

