# Architecture Base de Données - FasoLivestock API

## Vue d'ensemble

Cette documentation décrit l'architecture de la base de données pour faciliter la création du MCD (Modèle Conceptuel de Données), MLD (Modèle Logique de Données) et diagrammes de classe.

**Exclusions** :
- Module alimentation (aliments, rations)
- Tables système Spatie (permissions, roles, model_has_permissions, model_has_roles, role_has_permissions)
- Tables système Laravel (cache, jobs, password_reset_tokens, two_factor_verifications, personal_access_tokens)

---

## 1. Tables Principales

### 1.1 Users (Authentification Laravel)

**Table** : `users`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| name | string | - | Nom complet |
| email | string | UNIQUE | Email de connexion |
| password | string | - | Mot de passe hashé |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |

**Relations** :
- `farms` (hasMany via farms.owner_id)
- `farm_user` (belongsToMany via pivot)

---

### 1.2 Farms (Exploitations)

**Table** : `farms`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| name | string | - | Nom de la ferme |
| location | string | NULLABLE | Localisation |
| description | text | NULLABLE | Description |
| type_elevage | string | NULLABLE | Type d'élevage |
| photo | string | NULLABLE | URL photo |
| owner_id | UUID | FK → users.id | Propriétaire de la ferme |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id NULLABLE | Dernier modificateur |
| version | int | DEFAULT 1 | Version pour sync |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `owner` → users (belongsTo)
- `users` → users (belongsToMany via farm_user)
- `animals` (hasMany)
- `lots` (hasMany)
- `evenements` (hasMany)
- `naissances` (hasMany)
- `transactions` (hasMany)
- `sante_rappels` (hasMany)

**Contraintes** :
- `owner_id` cascade on delete

---

### 1.3 Farm_User (Pivot Users ↔ Farms)

**Table** : `farm_user`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| user_id | UUID | FK → users.id | Utilisateur |
| role | enum | DEFAULT 'worker' | Rôle : owner, manager, vet, worker |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id NULLABLE | Dernier modificateur |
| version | int | DEFAULT 1 | Version pour sync |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |

**Contraintes** :
- UNIQUE (`farm_id`, `user_id`)
- Cascade on delete sur les FK

---

### 1.4 Especes (Espèces animales)

**Table** : `especes`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| nom | string | UNIQUE | Nom de l'espèce (ex: Bovin, Ovin) |
| description | string | NULLABLE | Description |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `parametres` (hasOne via espece_parametres)
- `animals` (hasMany)
- `lots` (hasMany)

---

### 1.5 Espece_Parametres (Paramètres par espèce)

**Table** : `espece_parametres`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| espece_id | UUID | FK → especes.id UNIQUE | Espèce concernée |
| duree_gestation_jours | int | NULLABLE | Durée gestation (ex: 283 pour bovin) |
| age_reproduction_mois | int | NULLABLE | Âge reproduction (ex: 15 mois) |
| nombre_petits_typique | int | DEFAULT 1 | Nombre petits typique |
| intervalle_vaccin_jours | int | NULLABLE | Intervalle vaccins |
| age_sevrage_jours | int | NULLABLE | Âge sevrage |
| poids_naissance_moyen_kg | decimal(8,2) | NULLABLE | Poids naissance moyen |
| poids_adulte_moyen_kg | decimal(8,2) | NULLABLE | Poids adulte moyen |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |

**Relations** :
- `espece` → especes (belongsTo)

**Contraintes** :
- UNIQUE (`espece_id`) - Une espèce a exactement un jeu de paramètres
- Cascade on delete

---

### 1.6 Lots (Groupes d'animaux)

**Table** : `lots`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| nom_lot | string | - | Nom du lot |
| nombre | int | DEFAULT 0 | Nombre d'animaux |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `espece` → especes (belongsTo)
- `animals` (hasMany)

**Contraintes** :
- Cascade on delete sur farm_id

---

### 1.7 Categories (Catégories financières)

**Table** : `categories`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| nom_categorie | string | UNIQUE | Nom de la catégorie |
| type | string | NULLABLE | Type de catégorie |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `transactions` (hasMany)

---

### 1.8 Type_Evenements (Types d'événements)

**Table** : `type_evenements`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| nom_type | string | UNIQUE | Nom du type (ex: Vaccination, Vente) |
| description | string | NULLABLE | Description |
| categorie | enum | NULLABLE | MOUVEMENT, REPRODUCTION, SANITAIRE, AUTRE |
| is_system | boolean | DEFAULT false | Type système (protégé) |
| farm_id | UUID | FK → farms.id NULLABLE | Ferme (NULL = global) |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `evenements` (hasMany)

**Types système** (is_system = true) :
- Chaleur, Saillie, Gestation confirmée, Mise bas, NAISSANCE
- Vaccination, Traitement, Contrôle, Pesée, Autre
- Vente, Achat, Transfert, Décès, Perte, Abattage

---

### 1.9 Animals (Animaux)

**Table** : `animals`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| nom | string | NULLABLE | Nom de l'animal |
| race | string | NULLABLE | Race |
| sexe | enum | NULLABLE | male, femelle |
| date_naissance | date | NULLABLE | Date de naissance |
| poids | decimal(10,2) | NULLABLE | Poids |
| statut | enum | DEFAULT 'ACTIF' | ACTIF, VENDU, MORT, PERDU |
| espece_id | UUID | FK → especes.id | Espèce |
| lot_id | UUID | FK → lots.id NULLABLE | Lot |
| mother_id | UUID | FK → animals.id NULLABLE | Mère |
| numero_identification | string | NULLABLE | Numéro d'identification |
| photo | string | NULLABLE | URL photo |
| naissance_id | UUID | FK → naissances.id NULLABLE | Naissance d'origine |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `espece` → especes (belongsTo)
- `lot` → lots (belongsTo)
- `mother` → animals (belongsTo, self-referencing)
- `naissance` → naissances (belongsTo)
- `petits` → animals (hasMany via mother_id)
- `evenements` (hasMany)
- `sante_rappels` (hasMany)

**Contraintes** :
- Cascade on delete sur farm_id, espece_id, lot_id

---

### 1.10 Evenements (Événements)

**Table** : `evenements`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| type_evenement_id | UUID | FK → type_evenements.id | Type d'événement |
| animal_id | UUID | FK → animals.id | Animal concerné |
| categorie | enum | NULLABLE | MOUVEMENT, REPRODUCTION, SANITAIRE, AUTRE |
| date_evenement | date | - | Date de l'événement |
| description | string | NULLABLE | Description |
| cout | decimal(10,2) | NULLABLE | Coût |
| farm_destination_id | UUID | FK → farms.id NULLABLE | Ferme destination (transfert) |
| statut_avant | enum | NULLABLE | ACTIF, VENDU, MORT, PERDU |
| statut_apres | enum | NULLABLE | ACTIF, VENDU, MORT, PERDU |
| transaction_id | UUID | FK → transactions.id NULLABLE | Transaction liée |
| metadonnees | json | NULLABLE | Métadonnées spécifiques |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `type` → type_evenements (belongsTo)
- `animal` → animals (belongsTo)
- `farm_destination` → farms (belongsTo)
- `transaction` → transactions (belongsTo)
- `naissance` → naissances (hasMany via evenement_id)
- `sante_rappels` (hasMany via evenement_id)

**Contraintes** :
- Cascade on delete sur farm_id, type_evenement_id, animal_id

**Métadonnées** (JSON) :
- Vaccination : nom_vaccin, veterinaire, dosage, lot_vaccin
- Traitement : nom_medicament, veterinaire, dosage, duree, frequence
- Maladie : nom_maladie, symptomes, veterinaire, gravite
- Contrôle : type_controle, veterinaire, resultat
- Saillie : taureau_id, methode, succes
- Chaleur : intensite, observation
- Gestation : methode_confirmation, veterinaire
- Mise bas : nombre_petits, naissance_id

---

### 1.11 Naissances (Naissances)

**Table** : `naissances`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| mother_id | UUID | FK → animals.id | Mère |
| date_naissance | date | - | Date de naissance |
| nombre_petits | int | DEFAULT 0 | Nombre de petits |
| poids_naissance | decimal(10,2) | NULLABLE | Poids à la naissance |
| observation | text | NULLABLE | Observations |
| evenement_id | UUID | FK → evenements.id NULLABLE | Événement MISE BAS lié |
| date_saillie | date | NULLABLE | Date de saillie |
| date_mise_bas_prevue | date | NULLABLE | Date mise bas prévue |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `mother` → animals (belongsTo)
- `evenement` → evenements (belongsTo)
- `petits` → animals (hasMany via naissance_id)

**Contraintes** :
- Cascade on delete sur farm_id, mother_id

**Workflow** :
- Déclaration de naissance → Crée automatiquement :
  - Événement MISE BAS pour la mère
  - Événement NAISSANCE pour chaque petit (mouvement d'entrée)

---

### 1.12 Transactions (Transactions financières)

**Table** : `transactions`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| type_transaction | enum | DEFAULT 'ENTREE' | ENTREE, SORTIE, TRANSFERT, AJUSTEMENT |
| montant | decimal(12,2) | UNSIGNED | Montant |
| date_transaction | date | - | Date de transaction |
| user_id | UUID | FK → users.id | Utilisateur |
| animal_id | UUID | FK → animals.id NULLABLE | Animal concerné |
| categorie_id | UUID | FK → categories.id | Catégorie |
| description | string | NULLABLE | Description |
| evenement_id | UUID | FK → evenements.id NULLABLE | Événement lié |
| tiers | string | NULLABLE | Tiers (acheteur/vendeur) |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `user` → users (belongsTo)
- `animal` → animals (belongsTo)
- `categorie` → categories (belongsTo)
- `evenement` → evenements (belongsTo)

**Contraintes** :
- Cascade on delete sur farm_id, user_id, categorie_id

**Lien événement-transaction** :
- Vente → Crée transaction SORTIE
- Achat → Crée transaction ENTREE
- Décès/Abattage → Peut créer transaction SORTIE

---

### 1.13 Sante_Rappels (Rappels sanitaires)

**Table** : `sante_rappels`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| farm_id | UUID | FK → farms.id | Ferme |
| animal_id | UUID | FK → animals.id | Animal |
| type_rappel | enum | - | VACCINATION, TRAITEMENT, CONTROLE |
| date_prevue | date | - | Date prévue |
| date_realisee | date | NULLABLE | Date réalisée |
| statut | enum | DEFAULT 'EN_ATTENTE' | EN_ATTENTE, REALISE, EN_RETARD |
| note | text | NULLABLE | Note |
| evenement_id | UUID | FK → evenements.id NULLABLE | Événement réalisé |
| sync_status | enum | DEFAULT 'synced' | pending, synced, conflict |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Relations** :
- `farm` → farms (belongsTo)
- `animal` → animals (belongsTo)
- `evenement` → evenements (belongsTo)

**Contraintes** :
- Cascade on delete sur farm_id, animal_id

**Workflow** :
- Création rappel → statut EN_ATTENTE
- Réalisation → statut REALISE + lien evenement_id
- Job quotidien → statut EN_RETARD si date_prevue passée

---

### 1.14 Notifications (Notifications)

**Table** : `notifications`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| id | UUID | PK | Identifiant unique |
| type | string | - | Type de notification |
| notifiable_type | string | - | Type d'entité notifiable |
| notifiable_id | UUID | - | ID de l'entité |
| data | json | - | Données de notification |
| read_at | timestamp | NULLABLE | Date de lecture |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |

**Relations** :
- `recevoir` (hasMany via pivot)

---

### 1.15 Recevoir (Pivot Notifications ↔ Users)

**Table** : `recevoir`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| notification_id | UUID | FK → notifications.id | Notification |
| user_id | UUID | FK → users.id | Utilisateur |
| is_read | boolean | DEFAULT false | Lu |
| read_at | timestamp | NULLABLE | Date de lecture |
| created_at | timestamp | - | Date de création |
| updated_at | timestamp | - | Date de modification |

**Contraintes** :
- PK composite (`notification_id`, `user_id`)
- Cascade on delete sur les FK

---

## 2. Relations Entre Tables

### 2.1 Diagramme des Relations (Simplifié)

```
users (1) ----< (N) farms
users (N) >< (N) farms (via farm_user)

farms (1) ----< (N) animals
farms (1) ----< (N) lots
farms (1) ----< (N) evenements
farms (1) ----< (N) naissances
farms (1) ----< (N) transactions
farms (1) ----< (N) sante_rappels

especes (1) ----< (N) animals
especes (1) ----< (1) espece_parametres
especes (1) ----< (N) lots

lots (1) ----< (N) animals

animals (1) ----< (N) evenements
animals (1) ----< (N) sante_rappels
animals (1) ----< (N) naissances (via mother_id)
animals (N) ----< (1) naissance (via naissance_id)
animals (1) ----< (N) transactions

type_evenements (1) ----< (N) evenements

evenements (1) ----< (N) transactions
evenements (1) ----< (N) naissances
evenements (1) ----< (N) sante_rappels

categories (1) ----< (N) transactions

notifications (N) >< (N) users (via recevoir)
```

### 2.2 Relations Clés

**Self-referencing** :
- `animals.mother_id` → `animals.id` (arbre généalogique)

**Polymorphisme** :
- `notifications` : système polymorphe Laravel (notifiable_type, notifiable_id)

**Pivot tables** :
- `farm_user` : users ↔ farms
- `recevoir` : notifications ↔ users

---

## 3. Contraintes et Index

### 3.1 Clés Étrangères Principales

| Table | FK | Référence | Action |
|-------|----|-----------|----------|
| farms | owner_id | users.id | CASCADE |
| farm_user | farm_id | farms.id | CASCADE |
| farm_user | user_id | users.id | CASCADE |
| animals | farm_id | farms.id | CASCADE |
| animals | espece_id | especes.id | CASCADE |
| animals | lot_id | lots.id | CASCADE |
| animals | mother_id | animals.id | - |
| animals | naissance_id | naissances.id | - |
| evenements | farm_id | farms.id | CASCADE |
| evenements | type_evenement_id | type_evenements.id | CASCADE |
| evenements | animal_id | animals.id | CASCADE |
| evenements | farm_destination_id | farms.id | NULL |
| evenements | transaction_id | transactions.id | NULL |
| naissances | farm_id | farms.id | CASCADE |
| naissances | mother_id | animals.id | CASCADE |
| naissances | evenement_id | evenements.id | NULL |
| transactions | farm_id | farms.id | CASCADE |
| transactions | user_id | users.id | CASCADE |
| transactions | animal_id | animals.id | NULL |
| transactions | categorie_id | categories.id | CASCADE |
| transactions | evenement_id | evenements.id | NULL |
| sante_rappels | farm_id | farms.id | CASCADE |
| sante_rappels | animal_id | animals.id | CASCADE |
| sante_rappels | evenement_id | evenements.id | NULL |

### 3.2 Index de Performance

**Tables avec index fréquents** :
- `animals` : farm_id, espece_id, lot_id, statut, sync_status
- `evenements` : farm_id, type_evenement_id, animal_id, date_evenement, sync_status
- `transactions` : farm_id, user_id, categorie_id, date_transaction, sync_status
- `sante_rappels` : farm_id, animal_id, statut, date_prevue, sync_status

---

## 4. Champs de Synchronisation

Toutes les tables métier contiennent des champs de synchronisation pour le mode offline-first :

| Champ | Type | Valeurs | Description |
|-------|------|---------|-------------|
| sync_status | enum | pending, synced, conflict | État de synchronisation |
| last_modified_by | UUID | FK → users.id | Dernier modificateur |
| version | int | - | Version pour gestion conflits |

**Workflow sync** :
- Création offline → `sync_status = 'pending'`
- Sync réussie → `sync_status = 'synced'`
- Conflit → `sync_status = 'conflict'`

---

## 5. Soft Delete

Les tables suivantes utilisent le soft delete (deleted_at) :

- farms
- especes
- lots
- categories
- type_evenements
- animals
- evenements
- naissances
- transactions
- sante_rappels

---

## 6. Enums et Valeurs

### 6.1 Enums Principaux

| Table | Champ | Valeurs |
|-------|-------|---------|
| animals | sexe | male, femelle |
| animals | statut | ACTIF, VENDU, MORT, PERDU |
| type_evenements | categorie | MOUVEMENT, REPRODUCTION, SANITAIRE, AUTRE |
| evenements | categorie | MOUVEMENT, REPRODUCTION, SANITAIRE, AUTRE |
| evenements | statut_avant | ACTIF, VENDU, MORT, PERDU |
| evenements | statut_apres | ACTIF, VENDU, MORT, PERDU |
| transactions | type_transaction | ENTREE, SORTIE, TRANSFERT, AJUSTEMENT |
| sante_rappels | type_rappel | VACCINATION, TRAITEMENT, CONTROLE |
| sante_rappels | statut | EN_ATTENTE, REALISE, EN_RETARD |
| farm_user | role | owner, manager, vet, worker |
| sync_status (global) | - | pending, synced, conflict |

---

## 7. Cas d'Utilisation Principaux

### 7.1 Gestion des Animaux

1. **Création animal** :
   - Insertion dans `animals`
   - Lien avec `farm`, `espece`, `lot`
   - Optionnel : lien avec `mother_id` et `naissance_id`

2. **Mouvement animal** (vente, achat, transfert, décès) :
   - Création événement dans `evenements`
   - Mise à jour statut animal (`statut_avant`, `statut_apres`)
   - Création transaction dans `transactions` (si financier)
   - Pour transfert : `farm_destination_id` renseigné

### 7.2 Reproduction

1. **Déclaration naissance** :
   - Création dans `naissances`
   - Création animaux petits dans `animals`
   - Création événement MISE BAS dans `evenements` (pour mère)
   - Création événement NAISSANCE dans `evenements` (pour chaque petit)

2. **Suivi cycle** :
   - Événements CHALEUR, SAILLIE, GESTATION dans `evenements`
   - Lien avec `type_evenements` appropriés

### 7.3 Santé

1. **Rappel sanitaire** :
   - Création dans `sante_rappels`
   - Statut EN_ATTENTE par défaut
   - Job quotidien → EN_RETARD si date dépassée

2. **Réalisation** :
   - Création événement dans `evenements`
   - Lien `evenement_id` dans `sante_rappels`
   - Statut → REALISE

### 7.4 Finance

1. **Transaction** :
   - Création dans `transactions`
   - Lien avec `categorie`
   - Optionnel : lien avec `evenement` (vente/achat)

---

## 8. Recommandations pour MCD/MLD

### 8.1 Entités Principales pour MCD

- **User** (Utilisateur)
- **Farm** (Exploitation)
- **Animal** (Animal)
- **Espece** (Espèce)
- **Lot** (Lot)
- **Evenement** (Événement)
- **TypeEvenement** (Type d'événement)
- **Naissance** (Naissance)
- **Transaction** (Transaction)
- **Categorie** (Catégorie financière)
- **SanteRappel** (Rappel sanitaire)
- **Notification** (Notification)

### 8.2 Associations Principales

- User *possède* Farm (1:N)
- User *appartient à* Farm (N:M via Farm_User)
- Farm *contient* Animal (1:N)
- Farm *contient* Lot (1:N)
- Animal *appartient à* Lot (N:1)
- Animal *appartient à* Espece (N:1)
- Animal *a pour mère* Animal (N:1, self-referencing)
- Animal *subit* Evenement (1:N)
- Evenement *est de type* TypeEvenement (N:1)
- Animal *a des* SanteRappel (1:N)
- Naissance *concerne* Animal comme mère (N:1)
- Naissance *produit* Animal comme petits (1:N)
- Transaction *concerne* Animal (N:1, optionnel)
- Transaction *est catégorisée* Categorie (N:1)
- Evenement *génère* Transaction (1:1, optionnel)
- SanteRappel *est réalisé par* Evenement (N:1, optionnel)

### 8.3 Attributs Clés pour MCD

**Animal** : nom, race, sexe, date_naissance, poids, statut
**Evenement** : date_evenement, description, cout, categorie
**Naissance** : date_naissance, nombre_petits, observation
**Transaction** : type_transaction, montant, date_transaction
**SanteRappel** : type_rappel, date_prevue, statut

---

## 9. Notes Techniques

### 9.1 UUID vs Auto-increment

- Toutes les tables utilisent des UUID comme PK
- Avantages : synchronisation offline, sécurité, distribution

### 9.2 Timestamps

- Toutes les tables ont `created_at` et `updated_at`
- Soft delete via `deleted_at`

### 9.3 JSON

- `evenements.metadonnees` : stockage flexible des données spécifiques par type
- `notifications.data` : données de notification Laravel

---

## 10. Glossaire

- **FK** : Foreign Key (Clé étrangère)
- **PK** : Primary Key (Clé primaire)
- **UUID** : Universally Unique Identifier
- **Soft Delete** : Suppression logique (marquage deleted_at)
- **Enum** : Énumération (liste de valeurs prédéfinies)
- **Pivot** : Table de liaison pour relations N:M
