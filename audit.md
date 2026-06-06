# Audit de la Base de Données - FasoLivestock API

## Vue d'ensemble

Cet audit présente une analyse complète de la structure de la base de données de l'application FasoLivestock API, un système de gestion d'élevage. L'audit identifie les tables existantes, leurs relations, et recommande des améliorations notamment l'ajout de la table `fermes` et autres tables manquantes.

---

## Tables Existantes

### 1. Tables de Gestion des Utilisateurs et Authentification

#### `users`
**Fichier:** `database/migrations/0001_01_01_000000_create_users_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `name` (string)
- `email` (string, unique)
- `telephone` (string, nullable)
- `email_verified_at` (timestamp, nullable)
- `password` (string)
- `is_active` (boolean, default: true)
- `last_sync_at` (timestamp, nullable)
- `remember_token` (string)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/User.php`

**Relations:**
- `hasMany` → `Transaction`
- `belongsToMany` → `Notification` (via table `recevoir`)
- Utilise `HasRoles` (Spatie Permission)

**Observations:**
- Pas de relation directe avec les fermes (manquant)
- Les rôles sont gérés via Spatie Permission (proprietaire, gerant)

---

#### `password_reset_tokens`
**Fichier:** `database/migrations/0001_01_01_000000_create_users_table.php` + `2026_05_23_000001_fix_password_reset_tokens_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `user_id` (UUID, foreign key → users)
- `identifier` (string, indexed)
- `token` (string)
- `expires_at` (timestamp)
- `used` (boolean, default: false)
- `used_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/PasswordResetToken.php`

**Relations:**
- `belongsTo` → `User`

---

#### `two_factor_verifications`
**Fichier:** `database/migrations/2026_05_23_200000_create_two_factor_verifications_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `user_id` (UUID, indexed, foreign key → users)
- `channel` (string, 20, indexed) - email | phone
- `identifier` (string, 255, indexed)
- `code_hash` (string, 255)
- `expires_at` (timestamp)
- `used` (boolean, default: false)
- `used_at` (timestamp, nullable)
- `attempts` (unsigned small integer, default: 0)
- `created_at`, `updated_at` (timestamps)

**Modèle:** `app/Models/TwoFactorVerification.php`

**Relations:**
- `belongsTo` → `User`

---

#### `personal_access_tokens`
**Fichier:** `database/migrations/2026_05_22_124201_create_personal_access_tokens_table.php`

**Colonnes:**
- `id` (big integer, primary key, auto-increment)
- `tokenable_type` (string, morphs)
- `tokenable_id` (UUID, morphs)
- `name` (text)
- `token` (string, 64, unique)
- `abilities` (text, nullable)
- `last_used_at` (timestamp, nullable)
- `expires_at` (timestamp, indexed)
- `created_at`, `updated_at` (timestamps)

**Observations:**
- Table Laravel Sanctum pour l'authentification API

---

#### `sessions`
**Fichier:** `database/migrations/0001_01_01_000000_create_users_table.php`

**Colonnes:**
- `id` (string, primary key)
- `user_id` (UUID, foreign key → users, nullable, indexed)
- `ip_address` (string, 45, nullable)
- `user_agent` (text, nullable)
- `payload` (longText)
- `last_activity` (integer, indexed)

---

### 2. Tables de Gestion des Animaux

#### `animals`
**Fichier:** `database/migrations/2026_05_21_120109_create_animals_table.php` + `2026_05_21_120110_add_mother_foreign_to_animals_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `nom` (string, nullable)
- `race` (string, nullable)
- `sexe` (enum: 'M', 'F', nullable)
- `date_naissance` (date, nullable)
- `poids` (decimal 10,2, nullable)
- `statut` (enum: 'ACTIF', 'VENDU', 'MORT', 'PERDU', default: 'ACTIF')
- `espece_id` (UUID, foreign key → especes)
- `lot_id` (UUID, foreign key → lots, nullable)
- `mother_id` (UUID, foreign key → animals, nullable, self-reference)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Indices:**
- espece_id, lot_id, mother_id, statut

**Modèle:** `app/Models/Animal.php`

**Relations:**
- `belongsTo` → `Espece`
- `belongsTo` → `Lot`
- `belongsTo` → `Animal` (mother)
- `hasMany` → `Animal` (children)
- `hasMany` → `Evenement`
- `hasMany` → `Transaction`
- `hasMany` → `Notification`
- `hasMany` → `Naissance` (as mother)

**Observations:**
- Pas de relation avec les fermes (manquant)
- Pas de relation avec les utilisateurs/propriétaires
- Auto-référence pour la mère (mother_id)

---

#### `especes`
**Fichier:** `database/migrations/2026_05_21_120045_create_especes_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `nom` (string, unique)
- `description` (string, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/Espece.php`

**Relations:**
- `hasMany` → `Animal`

**Observations:**
- Table de référence pour les espèces animales

---

#### `lots`
**Fichier:** `database/migrations/2026_05_21_120045_create_lots_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `nom_lot` (string)
- `nombre` (integer unsigned, default: 0)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/Lot.php`

**Relations:**
- `hasMany` → `Animal`

**Observations:**
- Pas de relation avec les fermes (manquant)
- Le champ `nombre` semble redondant (peut être calculé via count sur animals)

---

#### `naissances`
**Fichier:** `database/migrations/2026_05_21_120109_create_naissances_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `mother_id` (UUID, foreign key → animals, cascade on delete)
- `date_naissance` (date)
- `nombre_petits` (integer unsigned, default: 0)
- `poids_naissance` (decimal 10,2, nullable)
- `observation` (text, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/Naissance.php`

**Relations:**
- `belongsTo` → `Animal` (mother)

**Observations:**
- Enregistre les événements de naissance
- Pas de lien direct avec les animaux nés (devraient être créés séparément)

---

### 3. Tables de Gestion des Événements

#### `type_evenements`
**Fichier:** `database/migrations/2026_05_21_120046_create_type_evenements_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `nom_type` (string, unique)
- `description` (string, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/TypeEvenement.php`

**Relations:**
- `hasMany` → `Evenement`

**Observations:**
- Table de référence pour les types d'événements

---

#### `evenements`
**Fichier:** `database/migrations/2026_05_21_120109_create_evenements_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `type_evenement_id` (UUID, foreign key → type_evenements, cascade on delete)
- `animal_id` (UUID, foreign key → animals, cascade on delete)
- `date_evenement` (date)
- `description` (string, nullable)
- `cout` (decimal 10,2, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Indices:**
- type_evenement_id, animal_id, date_evenement

**Modèle:** `app/Models/Evenement.php`

**Relations:**
- `belongsTo` → `TypeEvenement`
- `belongsTo` → `Animal`

**Observations:**
- Enregistre les événements sur les animaux (vaccination, traitement, etc.)

---

### 4. Tables de Gestion Financière

#### `transactions`
**Fichier:** `database/migrations/2026_05_21_120128_create_transactions_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `type_transaction` (enum: 'ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT', default: 'ENTREE')
- `montant` (decimal 12,2 unsigned)
- `date_transaction` (date)
- `user_id` (UUID, foreign key → users, cascade on delete)
- `animal_id` (UUID, foreign key → animals, nullable, null on delete)
- `categorie_id` (UUID, foreign key → categories, cascade on delete)
- `description` (string, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Indices:**
- user_id, animal_id, categorie_id, date_transaction, [user_id, date_transaction]

**Modèle:** `app/Models/Transaction.php`

**Relations:**
- `belongsTo` → `User`
- `belongsTo` → `Animal`
- `belongsTo` → `Categorie`

**Observations:**
- Gère les transactions financières
- Pas de relation avec les fermes (manquant)

---

#### `categories`
**Fichier:** `database/migrations/2026_05_21_120046_create_categories_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `nom_categorie` (string, unique)
- `type` (string, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Modèle:** `app/Models/Categorie.php`

**Relations:**
- `hasMany` → `Transaction`

**Observations:**
- Catégories pour les transactions (alimentation, santé, vente, etc.)

---

### 5. Tables de Gestion des Notifications

#### `notifications`
**Fichier:** `database/migrations/2026_05_21_120208_create_notifications_table.php`

**Colonnes:**
- `id` (UUID, primary key)
- `animal_id` (UUID, foreign key → animals, nullable, null on delete)
- `titre` (string, nullable)
- `message` (text)
- `sent_at` (timestamp, nullable)
- `synced` (boolean, default: false)
- `last_sync_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- `deleted_at` (soft deletes)

**Indices:**
- animal_id, sent_at

**Modèle:** `app/Models/Notification.php`

**Relations:**
- `belongsTo` → `Animal`
- `belongsToMany` → `User` (via table `recevoir`)

**Observations:**
- Pas de relation avec les fermes (manquant)

---

#### `recevoir`
**Fichier:** `database/migrations/2026_05_21_130600_create_recevoir_table.php`

**Colonnes:**
- `notification_id` (UUID, foreign key → notifications, cascade on delete)
- `user_id` (UUID, foreign key → users, cascade on delete)
- `is_read` (boolean, default: false)
- `read_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)

**Primary Key:** [notification_id, user_id]

**Indices:**
- user_id, [user_id, is_read]

**Observations:**
- Table pivot pour la relation many-to-many entre users et notifications

---

### 6. Tables de Gestion des Permissions (Spatie Permission)

#### `permissions`
**Fichier:** `database/migrations/2026_05_21_130447_create_permission_tables.php`

**Colonnes:**
- `id` (big integer, primary key, auto-increment)
- `name` (string)
- `guard_name` (string)
- `created_at`, `updated_at` (timestamps)

**Unique:** [name, guard_name]

---

#### `roles`
**Colonnes:**
- `id` (big integer, primary key, auto-increment)
- `team_foreign_key` (unsigned big integer, nullable) - si teams activé
- `name` (string)
- `guard_name` (string)
- `created_at`, `updated_at` (timestamps)

**Unique:** [team_foreign_key, name, guard_name] ou [name, guard_name]

**Observations:**
- Rôles définis dans RoleSeeder: 'proprietaire', 'gerant'

---

#### `model_has_permissions`
**Colonnes:**
- `permission_id` (unsigned big integer, foreign key → permissions)
- `model_type` (string)
- `model_morph_key` (UUID)
- `team_foreign_key` (unsigned big integer, nullable) - si teams activé

**Primary Key:** [team_foreign_key, permission_id, model_morph_key, model_type]

---

#### `model_has_roles`
**Colonnes:**
- `role_id` (unsigned big integer, foreign key → roles)
- `model_type` (string)
- `model_morph_key` (UUID)
- `team_foreign_key` (unsigned big integer, nullable) - si teams activé

**Primary Key:** [team_foreign_key, role_id, model_morph_key, model_type]

---

#### `role_has_permissions`
**Colonnes:**
- `permission_id` (unsigned big integer, foreign key → permissions)
- `role_id` (unsigned big integer, foreign key → roles)

**Primary Key:** [permission_id, role_id]

---

### 7. Tables Système Laravel

#### `cache`
**Fichier:** `database/migrations/0001_01_01_000001_create_cache_table.php`

**Colonnes:**
- `key` (string, primary key)
- `value` (text)
- `expiration` (integer, nullable)

---

#### `jobs`
**Fichier:** `database/migrations/0001_01_01_000002_create_jobs_table.php`

**Colonnes:**
- `id` (big integer, primary key, auto-increment)
- `queue` (string, nullable)
- `payload` (longText)
- `attempts` (unsigned integer, default: 0)
- `reserved_at` (integer, nullable)
- `available_at` (integer)
- `created_at` (integer)

---

---

## Diagramme des Relations Actuelles

```
users (1) ──────── (N) transactions
  │                    │
  │                    │
  │                    ├── (N) animal_id → animals
  │                    └── (N) categorie_id → categories
  │
  ├── (N) notifications ←→ (N) recevoir (pivot) ←── (N) users
  │
  └── (N) two_factor_verifications

animals (1) ──────── (N) evenements
  │                     │
  │                     └── (1) type_evenement_id → type_evenements
  │
  ├── (N) transactions
  ├── (N) notifications
  ├── (N) naissances (as mother)
  ├── (1) espece_id → especes
  ├── (1) lot_id → lots
  └── (1) mother_id → animals (self-reference)

especes (1) ──────── (N) animals

lots (1) ──────── (N) animals

type_evenements (1) ──────── (N) evenements

categories (1) ──────── (N) transactions

naissances (1) ──────── (1) mother_id → animals

permissions ←→ role_has_permissions ←→ roles ←→ model_has_roles ←→ models
```

---

## Problèmes Identifiés

### 1. Absence de la table `fermes` ⚠️ **CRITIQUE**

**Problème:** Le système de gestion d'élevage n'a pas de table pour gérer les fermes/propriétés.

**Impact:**
- Impossible d'associer les animaux à une ferme spécifique
- Impossible de gérer plusieurs fermes par utilisateur
- Impossible de séparer les données par ferme
- Les rôles 'proprietaire' et 'gerant' ne peuvent pas être associés à des fermes

**Recommandation:** Créer une table `fermes` avec les relations appropriées.

---

### 2. Absence de relation Users ↔ Fermes

**Problème:** Les utilisateurs ne sont pas associés à des fermes.

**Impact:**
- Un utilisateur ne peut pas être propriétaire ou gérant d'une ferme spécifique
- Impossible de gérer les permissions au niveau de la ferme

**Recommandation:** Ajouter une table pivot `ferme_user` ou une relation directe.

---

### 3. Absence de relation Animals ↔ Fermes

**Problème:** Les animaux ne sont pas associés à une ferme.

**Impact:**
- Impossible de savoir à quelle ferme appartient un animal
- Les lots ne sont pas associés à des fermes

**Recommandation:** Ajouter `ferme_id` dans la table `animals` et `lots`.

---

### 4. Redondance du champ `nombre` dans `lots`

**Problème:** Le champ `nombre` dans la table `lots` est manuel et peut être désynchronisé.

**Impact:**
- Risque d'incohérence entre le nombre stocké et le nombre réel d'animaux

**Recommandation:** Soit supprimer ce champ et calculer via count, soit ajouter des triggers pour le maintenir à jour.

---

### 5. Absence de table pour les races

**Problème:** Le champ `race` dans `animals` est une chaîne libre, pas une table de référence.

**Impact:**
- Pas de normalisation des races
- Difficile de faire des statistiques par race
- Risque d'incohérences (ex: "Charolais", "charolais", "CHAROLAIS")

**Recommandation:** Créer une table `races` avec une relation foreign key.

---

### 6. Absence de table pour les lieux/emplacements

**Problème:** Pas de gestion des emplacements (pâturages, enclos, bâtiments).

**Impact:**
- Impossible de savoir où se trouvent les animaux
- Pas de gestion des déplacements entre emplacements

**Recommandation:** Créer une table `emplacements` avec historique des déplacements.

---

### 7. Absence de table pour les vaccinations/santé

**Problème:** Les événements de santé sont gérés via la table générique `evenements`, mais il n'y a pas de structure spécifique.

**Impact:**
- Difficile de suivre le calendrier de vaccination
- Pas d'alertes automatiques pour les rappels

**Recommandation:** Créer des tables spécialisées ou enrichir la table `evenements` avec des champs spécifiques.

---

### 8. Absence de table pour la production

**Problème:** Pas de suivi de la production (lait, viande, œufs, etc.).

**Impact:**
- Impossible de suivre la productivité des animaux
- Pas d'analyse de rentabilité

**Recommandation:** Créer une table `productions` avec types de production.

---

### 9. Absence de table pour les achats/ventes d'animaux

**Problème:** Les transactions financières existent mais pas de table spécifique pour les achats/ventes d'animaux.

**Impact:**
- Difficile de suivre l'historique des transferts de propriété
- Pas de traçabilité complète des animaux

**Recommandation:** Créer une table `transferts_propriete` pour suivre les changements de propriétaire.

---

### 10. Champs de synchronisation (`synced`, `last_sync_at`) partout

**Problème:** Ces champs sont présents dans presque toutes les tables mais leur utilisation n'est pas claire.

**Impact:**
- Structure de base de données alourdie
- Risque d'incohérence si mal gérés

**Recommandation:** Clarifier l'utilisation ou créer une table séparée pour le suivi de synchronisation.

---

## Recommandations d'Amélioration

### Priorité 1: Table `fermes` (CRITIQUE)

```sql
CREATE TABLE fermes (
    id UUID PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    adresse TEXT,
    ville VARCHAR(255),
    region VARCHAR(255),
    pays VARCHAR(255) DEFAULT 'Burkina Faso',
    telephone VARCHAR(20),
    email VARCHAR(255),
    superficie DECIMAL(10,2) COMMENT 'Superficie en hectares',
    coordonnees_gps VARCHAR(255),
    date_creation DATE,
    statut ENUM('ACTIF', 'INACTIF', 'FERME') DEFAULT 'ACTIF',
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Modèle:** `app/Models/Ferme.php`

**Relations:**
- `hasMany` → `Animal`
- `hasMany` → `Lot`
- `hasMany` → `User` (via table pivot `ferme_user`)
- `hasMany` → `Transaction`

---

### Priorité 1: Table pivot `ferme_user`

```sql
CREATE TABLE ferme_user (
    ferme_id UUID NOT NULL,
    user_id UUID NOT NULL,
    role ENUM('PROPRIETAIRE', 'GERANT', 'EMPLOYE') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    PRIMARY KEY (ferme_id, user_id),
    FOREIGN KEY (ferme_id) REFERENCES fermes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Modèle:** Relation dans `User` et `Ferme`

---

### Priorité 1: Migration pour ajouter `ferme_id` aux tables existantes

**Migration pour `animals`:**
```php
Schema::table('animals', function (Blueprint $table) {
    $table->foreignUuid('ferme_id')->nullable()->constrained('fermes')->nullOnDelete();
    $table->index('ferme_id');
});
```

**Migration pour `lots`:**
```php
Schema::table('lots', function (Blueprint $table) {
    $table->foreignUuid('ferme_id')->nullable()->constrained('fermes')->nullOnDelete();
    $table->index('ferme_id');
});
```

**Migration pour `transactions`:**
```php
Schema::table('transactions', function (Blueprint $table) {
    $table->foreignUuid('ferme_id')->nullable()->constrained('fermes')->nullOnDelete();
    $table->index('ferme_id');
});
```

---

### Priorité 2: Table `races`

```sql
CREATE TABLE races (
    id UUID PRIMARY KEY,
    nom VARCHAR(255) NOT NULL UNIQUE,
    espece_id UUID NOT NULL,
    description TEXT,
    origine VARCHAR(255),
    caracteristiques TEXT,
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (espece_id) REFERENCES especes(id) ON DELETE CASCADE
);
```

**Migration pour `animals`:**
```php
Schema::table('animals', function (Blueprint $table) {
    $table->foreignUuid('race_id')->nullable()->constrained('races')->nullOnDelete();
    $table->dropColumn('race'); // Supprimer l'ancien champ string
});
```

---

### Priorité 2: Table `emplacements`

```sql
CREATE TABLE emplacements (
    id UUID PRIMARY KEY,
    ferme_id UUID NOT NULL,
    nom VARCHAR(255) NOT NULL,
    type ENUM('PATURE', 'ENCLOS', 'BATIMENT', 'PARC', 'AUTRE') NOT NULL,
    capacite INTEGER UNSIGNED NULL,
    superficie DECIMAL(10,2) NULL,
    description TEXT,
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (ferme_id) REFERENCES fermes(id) ON DELETE CASCADE
);

CREATE TABLE animal_emplacement (
    id UUID PRIMARY KEY,
    animal_id UUID NOT NULL,
    emplacement_id UUID NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    motif_deplacement VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (emplacement_id) REFERENCES emplacements(id) ON DELETE CASCADE
);
```

---

### Priorité 3: Table `productions`

```sql
CREATE TABLE type_productions (
    id UUID PRIMARY KEY,
    nom VARCHAR(255) NOT NULL UNIQUE,
    unite VARCHAR(50) NOT NULL COMMENT 'Litre, Kg, etc.',
    description TEXT,
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE TABLE productions (
    id UUID PRIMARY KEY,
    animal_id UUID NOT NULL,
    type_production_id UUID NOT NULL,
    date_production DATE NOT NULL,
    quantite DECIMAL(10,2) NOT NULL,
    qualite ENUM('A', 'B', 'C') NULL,
    prix_unitaire DECIMAL(10,2) NULL,
    observation TEXT NULL,
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (type_production_id) REFERENCES type_productions(id) ON DELETE CASCADE
);
```

---

### Priorité 3: Table `transferts_propriete`

```sql
CREATE TABLE transferts_propriete (
    id UUID PRIMARY KEY,
    animal_id UUID NOT NULL,
    ancien_proprietaire_id UUID NULL COMMENT 'User ID ou NULL si achat externe',
    nouveau_proprietaire_id UUID NOT NULL COMMENT 'User ID',
    ferme_source_id UUID NULL,
    ferme_destination_id UUID NOT NULL,
    date_transfert DATE NOT NULL,
    prix_vente DECIMAL(12,2) NULL,
    motif_transfert VARCHAR(255) NULL,
    document_reference VARCHAR(255) NULL,
    synced BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (ancien_proprietaire_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (nouveau_proprietaire_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ferme_source_id) REFERENCES fermes(id) ON DELETE SET NULL,
    FOREIGN KEY (ferme_destination_id) REFERENCES fermes(id) ON DELETE CASCADE
);
```

---

### Priorité 4: Amélioration de la table `evenements` pour la santé

**Ajouter des colonnes spécifiques:**
```sql
ALTER TABLE evenements ADD COLUMN type_sante ENUM('VACCINATION', 'TRAITEMENT', 'VERIFICATION', 'AUTRE') NULL;
ALTER TABLE evenements ADD COLUMN medicament VARCHAR(255) NULL;
ALTER TABLE evenements ADD COLUMN dosage VARCHAR(255) NULL;
ALTER TABLE evenements ADD COLUMN rappel_date DATE NULL COMMENT 'Date du prochain rappel si applicable';
ALTER TABLE evenements ADD COLUMN veterinaire_id UUID NULL;
```

---

## Résumé des Actions Recommandées

### Immédiat (Priorité 1)
1. ✅ Créer la table `fermes`
2. ✅ Créer la table pivot `ferme_user`
3. ✅ Ajouter `ferme_id` dans `animals`, `lots`, `transactions`
4. ✅ Créer le modèle `Ferme` avec les relations appropriées

### Court terme (Priorité 2)
5. ✅ Créer la table `races` et migrer le champ `race` de `animals`
6. ✅ Créer la table `emplacements` avec historique des déplacements

### Moyen terme (Priorité 3)
7. ✅ Créer les tables de production (`type_productions`, `productions`)
8. ✅ Créer la table `transferts_propriete`

### Long terme (Priorité 4)
9. ✅ Enrichir la table `evenements` pour la gestion de la santé
10. ✅ Revoir la gestion de la synchronisation (table séparée ou suppression des champs)

---

## Conclusion

La base de données actuelle de FasoLivestock API est bien structurée pour une application de base, mais elle manque des éléments critiques pour une gestion complète d'élevage. L'absence de la table `fermes` est le problème le plus important et doit être résolu en priorité.

Les recommandations proposées permettront de:
- Gérer plusieurs fermes avec des utilisateurs différents
- Associer clairement les animaux, lots et transactions à des fermes
- Normaliser les données (races, emplacements)
- Suivre la productivité et la traçabilité
- Améliorer la gestion de la santé des animaux

L'implémentation de ces améliorations se fera progressivement en commençant par les éléments critiques (fermes) puis en ajoutant les fonctionnalités avancées.
