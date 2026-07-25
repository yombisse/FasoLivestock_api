# FasoLivestock API Documentation
## Documentation complète pour l'intégration Mobile (WatermelonDB)

---

## Table des matières

1. [Architecture Générale](#architecture-générale)
2. [Stratégie de Synchronisation](#stratégie-de-synchronisation)
3. [Architecture WatermelonDB](#architecture-watermelondb)
4. [Endpoints Sync (Push/Pull)](#endpoints-sync-pushpull)
5. [Endpoints par Module](#endpoints-par-module)
6. [Événements Automatiques Côté Backend](#événements-automatiques-côté-backend)
7. [Règles d'Éligibilité des Animaux](#règles-déligibilité-des-animaux)
8. [Validation des Données](#validation-des-données)
9. [Formats et Conventions](#formats-et-conventions)

---

## Architecture Générale

### Stratégie Offline-First

L'API utilise une architecture **offline-first** avec synchronisation bidirectionnelle via WatermelonDB.

**⚠️ IMPORTANT :** Les endpoints REST traditionnels (`POST /animals`, `PUT /animals/{id}`, etc.) sont marqués comme **LEGACY**. Le mobile doit **exclusivement** utiliser le canal sync (`/sync/push` et `/sync/pull`) pour les opérations CRUD.

### Authentification

- **Méthode** : Bearer Token via Laravel Sanctum
- **Header** : `Authorization: Bearer {token}`
- **Middleware** : `auth:sanctum` sur tous les endpoints

### Contexte Ferme

- **Middleware** : `farm.context`
- **Header requis** : `X-Farm-ID: {farm_id}` ou paramètre `farm_id` dans le body
- **Exception** : `/sync/initial` (résout le problème de l'œuf et la poule)

### Permissions

Les permissions sont gérées via Spatie Permission :
- `animals.view`, `animals.create`, `animals.update`, `animals.delete`
- `evenements.view`, `evenements.create`, `evenements.update`, `evenements.delete`
- `transactions.view`, `transactions.create`, `transactions.update`, `transactions.delete`
- `sante.view`, `sante.create`, `sante.update`, `sante.delete`
- `reproduction.view`, `reproduction.create`, `reproduction.update`, `reproduction.delete`
- `lots.view`, `lots.create`, `lots.update`, `lots.delete`
- `farms.view`, `farms.create`, `farms.update`, `farms.delete`

---

## Stratégie de Synchronisation

### Flux de Synchronisation

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   Mobile    │         │   API       │         │  Database   │
│ (Watermelon)│◄────────┤  (Laravel)  │◄────────┤ (PostgreSQL) │
└─────────────┘         └─────────────┘         └─────────────┘
       │                       │                       │
       │ 1. POST /sync/push    │                       │
       ├──────────────────────►│                       │
       │                       │                       │
       │                       │ 2. Apply changes     │
       │                       ├──────────────────────►│
       │                       │                       │
       │                       │ 3. Return results    │
       │                       │◄──────────────────────┤
       │                       │                       │
       │ 4. Response           │                       │
       │◄──────────────────────┤                       │
       │                       │                       │
       │ 5. POST /sync/pull     │                       │
       ├──────────────────────►│                       │
       │                       │                       │
       │                       │ 6. Query changes      │
       │                       ├──────────────────────►│
       │                       │                       │
       │                       │ 7. Return changes     │
       │                       │◄──────────────────────┤
       │                       │                       │
       │ 8. Response           │                       │
       │◄──────────────────────┤                       │
```

### Ordre des Opérations

1. **Initial Sync** : `POST /sync/initial` (sans farm.context)
2. **Pull** : `POST /sync/pull` (avec farm.context)
3. **Push** : `POST /sync/push` (avec farm.context)
4. **Verify Consistency** : `POST /sync/verify-consistency` (optionnel)

---

## Architecture WatermelonDB

### Schéma de Base de Données

```javascript
// Tables principales avec leurs relations

// 1. farms
Farm {
  id: string (PK)
  nom: string
  statut: string ('ACTIF', 'INACTIF')
  adresse: string?
  telephone: string?
  email: string?
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
}

// 2. animals
Animal {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  farm_source_id: string?
  nom: string
  race: string
  sexe: string ('male', 'femelle')
  date_naissance: date?
  poids: decimal?
  espece_id: string (FK -> especes.id)
  lot_id: string? (FK -> lots.id)
  mother_id: string? (FK -> animals.id)
  origine: string ('import', 'achat', 'naissance')
  numero_identification: string
  photo: string?
  naissance_id: string? (FK -> naissances.id)
  statut: string ('SAIN', 'MALADE', 'DECEDÉ', 'VENDU', 'TRANSFÉRÉ', 'PERDU', 'ABATTU')
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  
  // Relations
  farm: belongsTo Farm
  espece: belongsTo Espece
  lot: belongsTo Lot
  mother: belongsTo Animal (self)
  naissance: belongsTo Naissance
  evenements: hasMany Evenement
  transactions: hasMany Transaction
}

// 3. evenements
Evenement {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  type_evenement_id: string (FK -> type_evenements.id)
  categorie: string ('SANITAIRE', 'REPRODUCTION', 'MOUVEMENT', 'ALIMENTATION', 'AUTRE')
  animal_id: string? (FK -> animals.id)
  male_id: string? (FK -> animals.id)
  date_evenement: date
  description: string?
  metadonnees: json?
  cout: decimal (default: 0)
  statut: string? ('EN_COURS', 'TERMINE', 'ANNULE')
  date_fin: date?
  farm_destination_id: string? (FK -> farms.id)
  statut_avant: string?
  statut_apres: string?
  transaction_id: string? (FK -> transactions.id)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  type: belongsTo TypeEvenement
  animal: belongsTo Animal
  male: belongsTo Animal
  farmDestination: belongsTo Farm
  transaction: belongsTo Transaction
}

// 4. transactions
Transaction {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  numero_transaction: string (format: TRX-YYYY-XXXXXX)
  type_transaction: string ('ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT')
  montant: decimal
  date_transaction: date
  user_id: string (FK -> users.id)
  animal_id: string? (FK -> animals.id)
  categorie_id: string?
  description: string?
  evenement_id: string? (FK -> evenements.id)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  user: belongsTo User
  animal: belongsTo Animal
  evenement: belongsTo Evenement
}

// 5. naissances
Naissance {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  mother_id: string (FK -> animals.id)
  date_naissance: date
  nombre_petits: integer
  poids_naissance: decimal?
  observation: string?
  evenement_id: string? (FK -> evenements.id)
  date_saillie: date?
  date_mise_bas_prevue: date?
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  mother: belongsTo Animal
  evenement: belongsTo Evenement
  petits: hasMany Animal (foreign key: naissance_id)
}

// 6. sante_rappels
SanteRappel {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  animal_id: string (FK -> animals.id)
  type_rappel: string ('VACCINATION', 'TRAITEMENT', 'CONTROLE', 'MISE_BAS', 'CHALEUR')
  date_prevue: date
  date_realisee: date?
  statut: string ('EN_ATTENTE', 'REALISE', 'EN_RETARD')
  note: string?
  evenement_id: string? (FK -> evenements.id)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  animal: belongsTo Animal
  evenement: belongsTo Evenement
}

// 7. lots
Lot {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  nom: string
  description: string?
  espece_id: string? (FK -> especes.id)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  espece: belongsTo Espece
  animals: hasMany Animal
}

// 8. especes
Espece {
  id: string (PK)
  nom: string
  description: string?
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  parametre: hasOne EspeceParametre
  categories: hasMany Categorie
  animals: hasMany Animal
}

// 9. espece_parametres
EspeceParametre {
  id: string (PK)
  espece_id: string (FK -> especes.id)
  age_reproduction_mois: integer?
  duree_gestation_jours: integer?
  intervalle_vaccin_jours: integer?
  intervalle_chaleur_jours: integer? (default: 21)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  espece: belongsTo Espece
}

// 10. categories
Categorie {
  id: string (PK)
  espece_id: string (FK -> especes.id)
  nom: string
  description: string?
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  espece: belongsTo Espece
  animals: hasMany Animal
}

// 11. type_evenements
TypeEvenement {
  id: string (PK)
  nom_type: string
  categorie: string ('SANITAIRE', 'REPRODUCTION', 'MOUVEMENT', 'ALIMENTATION', 'AUTRE')
  description: string?
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  evenements: hasMany Evenement
}

// 12. users
User {
  id: string (PK)
  nom: string
  email: string
  telephone: string?
  role: string
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farms: belongsToMany Farm (via farm_user)
}

// 13. notifications
Notification {
  id: string (PK)
  farm_id: string (FK -> farms.id)
  user_id: string (FK -> users.id)
  type: string
  titre: string
  message: string
  lue: boolean (default: false)
  metadonnees: json?
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (FK -> users.id)
  version: integer
  created_at: datetime
  updated_at: datetime
  deleted_at: datetime?
  
  // Relations
  farm: belongsTo Farm
  user: belongsTo User
}
```

### Champs de Synchronisation

Toutes les tables doivent inclure ces champs obligatoires pour la synchronisation :

```javascript
{
  id: string (EXACTEMENT 20 caractères, UUID-like, généré côté mobile ou serveur)
  sync_status: string ('synced', 'pending', 'conflict')
  last_modified_by: string (ID utilisateur)
  version: integer (incrémenté à chaque modification)
  created_at: datetime (ISO 8601)
  updated_at: datetime (ISO 8601)
  deleted_at: datetime? (soft delete)
}
```

---

## Endpoints Sync (Push/Pull)

### POST /api/sync/initial

**Description** : Synchronisation initiale pour obtenir toutes les données d'une ferme. Nécessite pas de farm.context (résout le problème de l'œuf et la poule).

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** :
```json
{
  "farm_id": "string (obligatoire)",
  "schema_version": "integer (optionnel)"
}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "farms": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "animals": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "evenements": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "transactions": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "naissances": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "sante_rappels": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "lots": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "especes": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "categories": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "type_evenements": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "espece_parametres": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "notifications": {
      "created": [],
      "updated": [],
      "deleted": []
    },
    "users": {
      "created": [],
      "updated": [],
      "deleted": []
    }
  },
  "meta": {
    "last_pulled_at": "datetime (ISO 8601)",
    "schema_version": "integer"
  }
}
```

---

### POST /api/sync/pull

**Description** : Récupérer les modifications depuis la dernière synchronisation.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
X-Farm-ID: {farm_id}
```

**Body** :
```json
{
  "farm_id": "string (obligatoire)",
  "last_pulled_at": "datetime (ISO 8601, optionnel - null pour sync initial)",
  "schema_version": "integer (optionnel)"
}
```

**Response** : Même format que `/sync/initial`

---

### POST /api/sync/push

**Description** : Envoyer les modifications locales au serveur.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
X-Farm-ID: {farm_id}
```

**Body** :
```json
{
  "changes": {
    "animals": {
      "created": [
        {
          "id": "string (obligatoire)",
          "farm_id": "string (obligatoire)",
          "nom": "string (obligatoire)",
          "race": "string",
          "sexe": "string ('male' ou 'femelle', obligatoire)",
          "date_naissance": "date (ISO 8601)",
          "poids": "decimal",
          "espece_id": "string (obligatoire)",
          "lot_id": "string",
          "mother_id": "string",
          "origine": "string ('import', 'achat', 'naissance')",
          "numero_identification": "string",
          "photo": "string",
          "naissance_id": "string",
          "statut": "string ('SAIN' par défaut)",
          "sync_status": "string ('pending')",
          "last_modified_by": "string",
          "version": "integer"
        }
      ],
      "updated": [
        {
          "id": "string (obligatoire)",
          "version": "integer (obligatoire)",
          // ... autres champs modifiables
        }
      ],
      "deleted": [
        {
          "id": "string (obligatoire)",
          "version": "integer (obligatoire)"
        }
      ]
    },
    "evenements": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    },
    "transactions": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    },
    "naissances": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    },
    "sante_rappels": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    },
    "lots": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    },
    "notifications": {
      "created": [...],
      "updated": [...],
      "deleted": [...]
    }
  }
}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "applied": {
      "animals": {
        "created": ["id1", "id2"],
        "updated": ["id3"],
        "deleted": ["id4"]
      },
      // ... autres tables
    },
    "rejected": {
      "animals": [
        {
          "id": "string",
          "error": "string",
          "error_code": "VERSION_CONFLICT | UUID_INVALID | FK_MISSING | VALIDATION_ERROR"
        }
      ],
      // ... autres tables
    },
    "conflicts": {
      "animals": [
        {
          "id": "string",
          "local_version": "integer",
          "server_version": "integer",
          "server_data": "object"
        }
      ],
      // ... autres tables
    }
  },
  "meta": {
    "last_pushed_at": "datetime (ISO 8601)",
    "server_time": "datetime (ISO 8601)"
  }
}
```

**Codes d'erreur** :
- `VERSION_CONFLICT` : Version locale != version serveur
- `UUID_INVALID` : ID invalide ou déjà existant
- `ID_EXISTS` : ID déjà utilisé sur une autre table
- `FK_MISSING` : Clé étrangère manquante
- `PERMISSION_DENIED` : Permission insuffisante
- `VALIDATION_ERROR` : Validation échouée
- `SERVER_ERROR` : Erreur serveur
- `CHUNK_TOO_LARGE` : Trop d'éléments (max 200 par chunk)

---

### POST /api/sync/verify-consistency

**Description** : Vérifier la cohérence des données entre mobile et serveur.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
X-Farm-ID: {farm_id}
```

**Body** :
```json
{
  "farm_id": "string (obligatoire)",
  "checksums": {
    "animals": "string (hash MD5 des IDs locaux)",
    "evenements": "string (hash MD5 des IDs locaux)",
    // ... autres tables
  }
}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "consistent": boolean,
    "differences": {
      "animals": {
        "missing_on_server": ["id1", "id2"],
        "missing_on_local": ["id3"],
        "version_mismatch": [
          {
            "id": "string",
            "local_version": "integer",
            "server_version": "integer"
          }
        ]
      },
      // ... autres tables
    }
  }
}
```

---

### GET /api/sync/errors

**Description** : Récupérer les erreurs de synchronisation.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "errors": [
      {
        "id": "string",
        "table": "string",
        "error_type": "string",
        "error_message": "string",
        "created_at": "datetime",
        "resolved": boolean
      }
    ]
  }
}
```

---

## Endpoints par Module

### Module Authentification

#### POST /api/auth/login

**Description** : Connexion utilisateur.

**Body** :
```json
{
  "email": "string (obligatoire)",
  "password": "string (obligatoire)"
}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "token": "string (Sanctum token)",
    "user": {
      "id": "string",
      "nom": "string",
      "email": "string",
      "telephone": "string",
      "role": "string"
    }
  }
}
```

#### POST /api/auth/logout

**Description** : Déconnexion.

**Headers** :
```
Authorization: Bearer {token}
```

**Response** :
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

#### GET /api/auth/me

**Description** : Obtenir l'utilisateur connecté.

**Headers** :
```
Authorization: Bearer {token}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "id": "string",
    "nom": "string",
    "email": "string",
    "telephone": "string",
    "role": "string",
    "farms": [
      {
        "id": "string",
        "nom": "string",
        "statut": "string",
        "role": "string (owner, member)"
      }
    ]
  }
}
```

---

### Module Fermes

#### GET /api/farms

**Description** : Lister les fermes de l'utilisateur.

**Headers** :
```
Authorization: Bearer {token}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "farms": [
      {
        "id": "string",
        "nom": "string",
        "statut": "string",
        "adresse": "string",
        "telephone": "string",
        "email": "string",
        "animals_count": "integer",
        "users_count": "integer",
        "created_at": "datetime",
        "updated_at": "datetime"
      }
    ],
    "meta": {
      "total": "integer",
      "per_page": "integer",
      "current_page": "integer",
      "last_page": "integer"
    }
  }
}
```

#### POST /api/farms

**Description** : Créer une ferme.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** :
```json
{
  "nom": "string (obligatoire)",
  "adresse": "string",
  "telephone": "string",
  "email": "string",
  "statut": "string ('ACTIF' par défaut)"
}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "id": "string",
    "nom": "string",
    "statut": "string",
    "adresse": "string",
    "telephone": "string",
    "email": "string",
    "owner_id": "string",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

#### GET /api/farms/{farm}

**Description** : Détail d'une ferme.

**Headers** :
```
Authorization: Bearer {token}
```

**Response** :
```json
{
  "success": true,
  "data": {
    "id": "string",
    "nom": "string",
    "statut": "string",
    "adresse": "string",
    "telephone": "string",
    "email": "string",
    "owner_id": "string",
    "animals_count": "integer",
    "users_count": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

#### PUT /api/farms/{farm}

**Description** : Modifier une ferme.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** :
```json
{
  "nom": "string",
  "adresse": "string",
  "telephone": "string",
  "email": "string",
  "statut": "string"
}
```

#### DELETE /api/farms/{farm}

**Description** : Supprimer une ferme (soft delete).

**Headers** :
```
Authorization: Bearer {token}
```

#### POST /api/farms/{farm}/users

**Description** : Gérer les membres d'une ferme.

**Headers** :
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** :
```json
{
  "user_ids": ["string", "string"],
  "role": "string ('member')"
}
```

#### DELETE /api/farms/{farm}/users/{user}

**Description** : Retirer un membre d'une ferme.

**Headers** :
```
Authorization: Bearer {token}
```

---

### Module Animaux - Éligibilité

⚠️ **IMPORTANT** : Les endpoints d'éligibilité ci-dessous sont destinés à l'admin web panel uniquement. Le mobile doit calculer l'éligibilité localement à partir des données synchronisées via `/sync/pull`.

#### GET /api/animals/eligible/sanitaire (ADMIN WEB ONLY)

**Description** : Animaux éligibles aux événements sanitaires.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Query Parameters** :
- `farm_id` : string (obligatoire si pas dans header)
- `type_evenement` : string (optionnel - 'VACCINATION', 'TRAITEMENT', 'CONTRÔLE')

**Règles d'éligibilité** :
- **VACCINATION** : Animaux vivants (statut = 'SAIN' ou 'MALADE')
- **TRAITEMENT** : Animaux malades (statut = 'MALADE')
- **CONTRÔLE** : Animaux vivants (statut = 'SAIN' ou 'MALADE')

**Calcul local pour mobile** :
Le mobile doit filtrer localement les animaux selon ces règles :
```javascript
// Pour VACCINATION ou CONTRÔLE
const animalsEligibles = animals.filter(animal =>
  ['SAIN', 'MALADE'].includes(animal.statut)
);

// Pour TRAITEMENT
const animalsEligibles = animals.filter(animal =>
  animal.statut === 'MALADE'
);
```

**Response** :
```json
{
  "success": true,
  "data": {
    "animals": [
      {
        "id": "string",
        "nom": "string",
        "numero_identification": "string",
        "race": "string",
        "sexe": "string",
        "statut": "string",
        "espece_id": "string",
        "espece_nom": "string",
        "lot_id": "string",
        "lot_nom": "string",
        "date_naissance": "date",
        "poids": "decimal"
      }
    ],
    "meta": {
      "total": "integer",
      "farm_id": "string",
      "type_evenement": "string"
    }
  }
}
```

#### GET /api/animals/eligible/mouvement (ADMIN WEB ONLY)

**Description** : Animaux éligibles aux événements de mouvement.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Query Parameters** :
- `farm_id` : string (obligatoire si pas dans header)
- `type_mouvement` : string (obligatoire - 'vente', 'transfert', 'deces', 'perte', 'abattage')

**Règles d'éligibilité** :
- **VENTE** : Animaux vivants (statut = 'SAIN' ou 'MALADE')
- **TRANSFERT** : Animaux vivants (statut = 'SAIN' ou 'MALADE')
- **DECES** : Animaux vivants (statut = 'SAIN' ou 'MALADE')
- **PERTE** : Animaux vivants (statut = 'SAIN' ou 'MALADE')
- **ABATTAGE** : Animaux vivants (statut = 'SAIN' ou 'MALADE')

**Calcul local pour mobile** :
Le mobile doit filtrer localement les animaux selon ces règles :
```javascript
// Pour tous les types de mouvement
const animalsEligibles = animals.filter(animal =>
  ['SAIN', 'MALADE'].includes(animal.statut)
);
```

**Response** :
```json
{
  "success": true,
  "data": {
    "animals": [
      {
        "id": "string",
        "nom": "string",
        "numero_identification": "string",
        "race": "string",
        "sexe": "string",
        "statut": "string",
        "espece_id": "string",
        "espece_nom": "string",
        "lot_id": "string",
        "lot_nom": "string",
        "date_naissance": "date",
        "poids": "decimal"
      }
    ],
    "meta": {
      "total": "integer",
      "farm_id": "string",
      "type_mouvement": "string"
    }
  }
}
```

#### GET /api/animals/eligible/reproduction (ADMIN WEB ONLY)

**Description** : Animaux éligibles aux événements de reproduction.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Query Parameters** :
- `farm_id` : string (obligatoire si pas dans header)
- `type_reproduction` : string (obligatoire - 'saillie', 'gestation', 'mise_bas')

**Règles d'éligibilité** :
- **SAILLIE** : Femelles vivantes, âge de reproduction atteint (selon espece_parametre.age_reproduction_mois), pas de gestation EN_COURS
- **GESTATION** : Femelles vivantes, saillie EN_COURS existante, pas de gestation EN_COURS déjà
- **MISE_BAS** : Femelles vivantes, gestation EN_COURS existante

**Calcul local pour mobile** :
Le mobile doit filtrer localement les animaux selon ces règles :
```javascript
// Pour SAILLIE
const femellesEligibles = animals.filter(animal =>
  animal.statut === 'SAIN' &&
  animal.sexe === 'femelle' &&
  animal.age_mois >= animal.espece_parametre.age_reproduction_mois &&
  !evenements.some(evenement =>
    evenement.animal_id === animal.id &&
    evenement.categorie === 'REPRODUCTION' &&
    evenement.type_nom === 'GESTATION CONFIRMÉE' &&
    evenement.statut === 'EN_COURS'
  )
);

// Pour GESTATION
const femellesEligibles = animals.filter(animal =>
  animal.statut === 'SAIN' &&
  animal.sexe === 'femelle' &&
  evenements.some(evenement =>
    evenement.animal_id === animal.id &&
    evenement.categorie === 'REPRODUCTION' &&
    evenement.type_nom === 'SAILLIE' &&
    evenement.statut === 'EN_COURS'
  ) &&
  !evenements.some(evenement =>
    evenement.animal_id === animal.id &&
    evenement.categorie === 'REPRODUCTION' &&
    evenement.type_nom === 'GESTATION CONFIRMÉE' &&
    evenement.statut === 'EN_COURS'
  )
);

// Pour MISE_BAS
const femellesEligibles = animals.filter(animal =>
  animal.statut === 'SAIN' &&
  animal.sexe === 'femelle' &&
  evenements.some(evenement =>
    evenement.animal_id === animal.id &&
    evenement.categorie === 'REPRODUCTION' &&
    evenement.type_nom === 'GESTATION CONFIRMÉE' &&
    evenement.statut === 'EN_COURS'
  )
);
```

**Response** :
```json
{
  "success": true,
  "data": {
    "animals": [
      {
        "id": "string",
        "nom": "string",
        "numero_identification": "string",
        "race": "string",
        "sexe": "string",
        "statut": "string",
        "espece_id": "string",
        "espece_nom": "string",
        "lot_id": "string",
        "lot_nom": "string",
        "date_naissance": "date",
        "poids": "decimal",
        "age_mois": "integer",
        "est_en_age_reproduction": "boolean",
        "gestation_en_cours": "boolean",
        "saillie_en_cours": "boolean"
      }
    ],
    "meta": {
      "total": "integer",
      "farm_id": "string",
      "type_reproduction": "string"
    }
  }
}
```

---

### Module Reproduction

⚠️ **IMPORTANT** : Les endpoints d'éligibilité ci-dessous sont destinés à l'admin web panel uniquement. Le mobile doit calculer l'éligibilité localement à partir des données synchronisées via `/sync/pull`.

#### GET /api/reproduction/femelles-eligibles (ADMIN WEB ONLY)

**Description** : Femelles éligibles à une déclaration de naissance.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Règles d'éligibilité** :
- Femelles vivantes
- Gestation EN_COURS existante
- Date de mise bas prévue dépassée ou atteinte

**Calcul local pour mobile** :
Le mobile doit filtrer localement les animaux selon ces règles :
```javascript
const femellesEligibles = animals.filter(animal =>
  animal.statut === 'SAIN' &&
  animal.sexe === 'femelle' &&
  evenements.some(evenement =>
    evenement.animal_id === animal.id &&
    evenement.categorie === 'REPRODUCTION' &&
    evenement.type_nom === 'GESTATION CONFIRMÉE' &&
    evenement.statut === 'EN_COURS' &&
    new Date(evenement.date_mise_bas_prevue) <= new Date()
  )
);
```

**Response (admin web)** :
```json
{
  "success": true,
  "data": {
    "femelles": [
      {
        "id": "string",
        "nom": "string",
        "numero_identification": "string",
        "race": "string",
        "statut": "string",
        "espece_nom": "string",
        "date_naissance": "date",
        "gestation_en_cours": {
          "id": "string",
          "date_evenement": "date",
          "date_mise_bas_prevue": "date",
          "jours_restants": "integer"
        }
      }
    ]
  }
}
```

#### GET /api/reproduction/naissances/previsions

**Description** : Prévisions de mises bas.

**Headers** :
```
Authorization: Bearer {token}
X-Farm-ID: {farm_id}
```

**Query Parameters** :
- `jours` : integer (optionnel, défaut 30 - nombre de jours à venir)

**Response** :
```json
{
  "success": true,
  "data": {
    "previsions": [
      {
        "mother_id": "string",
        "mother_nom": "string",
        "mother_numero": "string",
        "date_mise_bas_prevue": "date",
        "jours_restants": "integer",
        "statut": "string ('EN_COURS', 'EN_RETARD', 'TERMINE')"
      }
    ],
    "meta": {
      "total": "integer",
      "jours": "integer"
    }
  }
}
```

---

## Événements Automatiques Côté Backend

### Observer : EvenementObserver

L'observer `EvenementObserver` écoute les événements `created` et `updated` sur le modèle `Evenement` et génère automatiquement :

#### 1. Rappels Sanitaires Automatiques

**Déclencheur** : Création d'un événement sanitaire (VACCINATION, TRAITEMENT, CONTRÔLE)

**Logique** :
- Récupère les paramètres de l'espèce de l'animal
- Calcule la date du prochain rappel selon l'intervalle défini
- Crée un `SanteRappel` avec statut `EN_ATTENTE`

**Intervalles par défaut** :
- VACCINATION : `espece_parametre.intervalle_vaccin_jours`
- TRAITEMENT : 30 jours
- CONTRÔLE : 90 jours

**Données générées** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "animal_id": "string",
  "type_rappel": "string ('VACCINATION' | 'TRAITEMENT' | 'CONTROLE')",
  "date_prevue": "date (date_evenement + intervalle)",
  "statut": "EN_ATTENTE",
  "evenement_id": "string",
  "sync_status": "synced",
  "last_modified_by": "string",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer les nouveaux rappels. Ne PAS recréer localement.

---

#### 2. Rappels Reproductifs Automatiques

**Déclencheur** : Création d'un événement reproductif (GESTATION CONFIRMÉE, CHALEUR)

**Logique** :
- Pour GESTATION CONFIRMÉE : Crée un rappel MISE_BAS selon `duree_gestation_jours`
- Pour CHALEUR : Crée un rappel CHALEUR suivant (intervalle 21 jours)

**Données générées (Gestation)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "animal_id": "string",
  "type_rappel": "MISE_BAS",
  "date_prevue": "date (date_evenement + duree_gestation_jours)",
  "statut": "EN_ATTENTE",
  "evenement_id": "string",
  "note": "Mise bas prévue selon durée de gestation de l'espèce",
  "sync_status": "synced",
  "last_modified_by": "string",
  "version": 1
}
```

**Données générées (Chaleur)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "animal_id": "string",
  "type_rappel": "CHALEUR",
  "date_prevue": "date (date_evenement + 21 jours)",
  "statut": "EN_ATTENTE",
  "evenement_id": "string",
  "note": "Chaleur suivante estimée (intervalle moyen 21 jours)",
  "sync_status": "synced",
  "last_modified_by": "string",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer les nouveaux rappels. Ne PAS recréer localement.

---

#### 3. Gestion Automatique des Statuts d'Événements

**Déclencheur** : Création d'un événement

**Logique** :
- **SAILLIE** : Ne change pas le statut animal (saillie ne confirme pas la gestation)
- **GESTATION CONFIRMÉE** : Met statut événement = `EN_COURS`, clore les saillies EN_COURS
- **MISE BAS** : Clore la gestation EN_COURS associée
- **MOUVEMENT (VENTE, DECES, etc.)** : Clore tous les événements reproductifs EN_COURS

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer les mises à jour de statuts. Ne PAS modifier localement.

---

#### 4. Transactions Financières Automatiques

**Déclencheur** : Création d'un événement reproductif ou sanitaire avec coût > 0

**Logique** :
- Crée automatiquement une `Transaction` de type `SORTIE`
- Catégorie : `FRAIS_REPRODUCTION` ou `FRAIS_SANITAIRE` selon le type d'événement
- Montant : égal au coût de l'événement
- Lien : `evenement_id` renseigné
- Idempotent : ne crée pas de doublon si une transaction existe déjà

**Données générées** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "numero_transaction": "string (TRX-YYYY-XXXXXX)",
  "type_transaction": "SORTIE",
  "montant": "decimal",
  "date_transaction": "date",
  "user_id": "string",
  "animal_id": "string",
  "categorie_id": "string",
  "description": "string",
  "evenement_id": "string",
  "sync_status": "synced",
  "last_modified_by": "string",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer les nouvelles transactions. Ne PAS recréer localement.

---

### Opérations Automatiques sur les Mouvements d'Animaux

Le service `EvenementMouvementService` gère automatiquement les opérations de mouvement avec création de transactions et événements associés.

#### 5. Achat d'Animal

**Déclencheur** : Action utilisateur d'achat d'un animal

**Opérations automatiques** :
1. Crée l'animal avec `origine = 'achat'`
2. Crée une `Transaction` de type `SORTIE` (dépense)
3. Catégorie : "Achat d'animaux"
4. Montant : `prix_achat`
5. Crée un événement de type ACHAT
6. Met `statut` animal = `SAIN`
7. Lie l'événement à la transaction via `transaction_id`

**Données générées (Transaction)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "numero_transaction": "string (TRX-YYYY-XXXXXX)",
  "type_transaction": "SORTIE",
  "montant": "decimal",
  "date_transaction": "date",
  "animal_id": "string",
  "categorie_id": "string",
  "description": "string (nom ferme source ou provenance)",
  "user_id": "string",
  "sync_status": "synced",
  "version": 1
}
```

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type ACHAT)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string",
  "cout": "decimal (prix_achat)",
  "statut_avant": "string",
  "statut_apres": "SAIN",
  "transaction_id": "string",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer l'animal, la transaction et l'événement. Ne PAS recréer localement.

---

#### 6. Vente d'Animal

**Déclencheur** : Action utilisateur de vente d'un animal

**Opérations automatiques** :
1. Crée une `Transaction` de type `ENTREE` (revenu)
2. Catégorie : "Vente d'animaux"
3. Montant : `prix_vente`
4. Crée un événement de type VENTE
5. Met `statut` animal = `VENDU`
6. Lie l'événement à la transaction via `transaction_id`

**Données générées (Transaction)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "numero_transaction": "string (TRX-YYYY-XXXXXX)",
  "type_transaction": "ENTREE",
  "montant": "decimal",
  "date_transaction": "date",
  "animal_id": "string",
  "categorie_id": "string",
  "description": "string (acheteur)",
  "user_id": "string",
  "sync_status": "synced",
  "version": 1
}
```

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type VENTE)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string (acheteur)",
  "cout": "decimal (prix_vente)",
  "statut_avant": "string",
  "statut_apres": "VENDU",
  "transaction_id": "string",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer la transaction, l'événement et le statut animal mis à jour. Ne PAS recréer localement.

---

#### 7. Transfert d'Animal

**Déclencheur** : Action utilisateur de transfert d'un animal vers une autre ferme

**Opérations automatiques** :
1. Crée un événement de type TRANSFERT
2. Met `statut` animal = `TRANSFERE`
3. Met `farm_destination_id` dans l'événement
4. **PAS de transaction automatique**

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type TRANSFERT)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string (motif)",
  "statut_avant": "string",
  "statut_apres": "TRANSFERE",
  "farm_destination_id": "string",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer l'événement et le statut animal mis à jour. Ne PAS recréer localement.

---

#### 8. Décès d'Animal

**Déclencheur** : Action utilisateur de déclaration de décès

**Opérations automatiques** :
1. Crée un événement de type DÉCÈS
2. Met `statut` animal = `MORT`
3. **PAS de transaction automatique**

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type DÉCÈS)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string (cause + observation)",
  "statut_avant": "string",
  "statut_apres": "MORT",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer l'événement et le statut animal mis à jour. Ne PAS recréer localement.

---

#### 9. Perte d'Animal

**Déclencheur** : Action utilisateur de déclaration de perte

**Opérations automatiques** :
1. Crée un événement de type PERTE
2. Met `statut` animal = `PERDU`
3. **PAS de transaction automatique**

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type PERTE)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string (motif + observation)",
  "statut_avant": "string",
  "statut_apres": "PERDU",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer l'événement et le statut animal mis à jour. Ne PAS recréer localement.

---

#### 10. Abattage d'Animal

**Déclencheur** : Action utilisateur d'abattage

**Opérations automatiques** :
1. Crée un événement de type ABATTAGE
2. Met `statut` animal = `MORT`
3. **PAS de transaction automatique** (mais peut avoir `cout` dans l'événement)

**Données générées (Événement)** :
```json
{
  "id": "string (auto-généré)",
  "farm_id": "string",
  "type_evenement_id": "string (type ABATTAGE)",
  "categorie": "MOUVEMENT",
  "animal_id": "string",
  "date_evenement": "date",
  "description": "string (motif + poids carcasse)",
  "cout": "decimal (valeur_carcasse)",
  "statut_avant": "string",
  "statut_apres": "MORT",
  "sync_status": "synced",
  "version": 1
}
```

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer l'événement et le statut animal mis à jour. Ne PAS recréer localement.

---

#### 11. Vente de Lot

**Déclencheur** : Action utilisateur de vente d'un lot complet

**Opérations automatiques** :
1. Pour chaque animal SAIN du lot :
   - Appelle la méthode `vente()` pour chaque animal
   - Génère une transaction par animal vendu
   - Génère un événement par animal vendu
   - Met `statut` animal = `VENDU`
2. Optionnel : Archive le lot si tous les animaux sont vendus

**Données générées** :
- Une transaction par animal vendu (type ENTREE)
- Un événement par animal vendu (type VENTE)
- Statut animal mis à jour pour chaque animal

**Action mobile** : Le mobile doit synchroniser via `/sync/pull` pour récupérer toutes les transactions, événements et statuts animaux mis à jour. Ne PAS recréer localement.

---

### Tableau Récapitulatif des Opérations Automatiques

| Action Utilisateur | Transaction Auto | Type Transaction | Événement Auto | Statut Animal | Note |
|--------------------|------------------|-----------------|----------------|--------------|-------|
| Achat animal | ✅ OUI | SORTIE (dépense) | ACHAT | SAIN | Catégorie "Achat d'animaux" |
| Vente animal | ✅ OUI | ENTREE (revenu) | VENTE | VENDU | Catégorie "Vente d'animaux" |
| Transfert animal | ❌ NON | - | TRANSFERT | TRANSFERE | Pas de transaction |
| Décès animal | ❌ NON | - | DÉCÈS | MORT | Pas de transaction |
| Perte animal | ❌ NON | - | PERTE | PERDU | Pas de transaction |
| Abattage animal | ❌ NON | - | ABATTAGE | MORT | Pas de transaction (peut avoir cout) |
| Vente lot | ✅ OUI (par animal) | ENTREE (revenu) | VENTE (par animal) | VENDU (par animal) | Une transaction par animal |
| Événement reproductif avec coût | ✅ OUI | SORTIE (dépense) | - | - | Catégorie "FRAIS_REPRODUCTION" |
| Événement sanitaire avec coût | ✅ OUI | SORTIE (dépense) | - | - | Catégorie "FRAIS_SANITAIRE" |

---

### Création de Rappels Sanitaires via Métadonnées

**IMPORTANT** : Pour les événements de type TRAITEMENT et CONTRÔLE, les rappels ne sont créés automatiquement que si l'utilisateur fournit une date de rappel via le champ `metadonnees`.

#### Règles de Création de Rappels

| Type d'Événement | Création Rappel | Source Date | Configuration |
|------------------|-----------------|-------------|---------------|
| VACCINATION | Automatique | `espece_parametre.intervalle_vaccin_jours` | Configurable par espèce |
| TRAITEMENT | Conditionnel | `metadonnees.date_rappel_suggeree` | Saisie utilisateur |
| CONTRÔLE | Conditionnel | `metadonnees.date_prochain_controle` | Saisie utilisateur |
| MISE_BAS | Automatique | `espece_parametre.duree_gestation_jours` | Configurable par espèce |
| CHALEUR | Automatique | 21 jours (hardcodé) | Fixe |

#### Utilisation Mobile pour TRAITEMENT

**Formulaire de création de traitement :**
- L'utilisateur doit pouvoir saisir une date de rappel optionnelle
- Si l'utilisateur saisit une date → l'envoyer dans `metadonnees.date_rappel_suggeree`
- Si l'utilisateur ne saisit pas → ne pas inclure le champ (pas de rappel automatique)

**Exemple sync push pour TRAITEMENT avec rappel :**

```json
{
  "changes": {
    "evenements": {
      "created": [
        {
          "id": "string (ID de l'événement)",
          "farm_id": "string",
          "type_evenement_id": "string (ID type TRAITEMENT)",
          "categorie": "SANITAIRE",
          "animal_id": "string",
          "date_evenement": "2026-07-22",
          "description": "Traitement antibiotique infection",
          "cout": 50.00,
          "metadonnees": {
            "date_rappel_suggeree": "2026-07-29"
          },
          "sync_status": "pending",
          "last_modified_by": "string",
          "version": 1
        }
      ]
    }
  }
}
```

**Comportement backend :**
- Le système détecte `metadonnees.date_rappel_suggeree`
- Crée automatiquement un `SanteRappel` de type TRAITEMENT à cette date
- Lie le rappel à l'événement via `evenement_id`

---

#### Utilisation Mobile pour CONTRÔLE

**Formulaire de création de contrôle :**
- L'utilisateur doit pouvoir saisir une date de prochain contrôle optionnelle
- Si l'utilisateur saisit une date → l'envoyer dans `metadonnees.date_prochain_controle`
- Si l'utilisateur ne saisit pas → ne pas inclure le champ (pas de rappel automatique)

**Exemple sync push pour CONTRÔLE avec rappel :**

```json
{
  "changes": {
    "evenements": {
      "created": [
        {
          "id": "string (ID de l'événement)",
          "farm_id": "string",
          "type_evenement_id": "string (ID type CONTRÔLE)",
          "categorie": "SANITAIRE",
          "animal_id": "string",
          "date_evenement": "2026-07-22",
          "description": "Contrôle de routine",
          "cout": 0,
          "metadonnees": {
            "date_prochain_controle": "2026-10-20"
          },
          "sync_status": "pending",
          "last_modified_by": "string",
          "version": 1
        }
      ]
    }
  }
}
```

**Comportement backend :**
- Le système détecte `metadonnees.date_prochain_controle`
- Crée automatiquement un `SanteRappel` de type CONTRÔLE à cette date
- Lie le rappel à l'événement via `evenement_id`

---

#### Exemple sans Rappel (TRAITEMENT ou CONTRÔLE)

Si l'utilisateur ne souhaite pas de rappel, ne pas inclure le champ dans `metadonnees` :

```json
{
  "changes": {
    "evenements": {
      "created": [
        {
          "id": "string",
          "farm_id": "string",
          "type_evenement_id": "string",
          "categorie": "SANITAIRE",
          "animal_id": "string",
          "date_evenement": "2026-07-22",
          "description": "Traitement ponctuel sans suivi",
          "cout": 25.00,
          // PAS de champ metadonnees.date_rappel_suggeree
          "sync_status": "pending",
          "last_modified_by": "string",
          "version": 1
        }
      ]
    }
  }
}
```

**Comportement backend :**
- Aucun rappel n'est créé
- L'événement est enregistré normalement

---

### Opérations sur les Rappels Sanitaires

⚠️ **IMPORTANT** : Les endpoints REST suivants sont marqués LEGACY. Le mobile doit utiliser le canal sync push/pull pour ces opérations.

#### POST /api/sante/rappels/{id}/marquer-realise (LEGACY)

**Description** : Marquer un rappel sanitaire comme réalisé en créant un événement médical.

**Opérations automatiques côté serveur** :
1. Crée un `Evenement` sanitaire avec les données fournies
2. Met à jour le `SanteRappel` :
   - `statut` = `REALISE`
   - `date_realisee` = `date_evenement` de l'événement
   - `evenement_id` = ID de l'événement créé
   - Incrémente `version`

**Comment utiliser via sync push** :

Le mobile doit envoyer les modifications suivantes via `/sync/push` :

```json
{
  "changes": {
    "sante_rappels": {
      "updated": [
        {
          "id": "string (ID du rappel)",
          "version": "integer (version actuelle)",
          "statut": "REALISE",
          "date_realisee": "date (ISO 8601)",
          "evenement_id": "string (ID de l'événement créé localement)"
        }
      ]
    },
    "evenements": {
      "created": [
        {
          "id": "string (ID de l'événement créé localement)",
          "farm_id": "string",
          "type_evenement_id": "string",
          "categorie": "SANITAIRE",
          "animal_id": "string",
          "date_evenement": "date (ISO 8601)",
          "description": "string",
          "cout": "decimal",
          "sync_status": "pending",
          "last_modified_by": "string",
          "version": 1
        }
      ]
    }
  }
}
```

**Comportement backend au push** :
- Le backend accepte les modifications du rappel et la création de l'événement
- Le backend ne recrée PAS automatiquement l'événement (déjà créé par le mobile)
- Le backend vérifie la cohérence des données (rappel.evenement_id correspond à l'événement créé)

---

#### POST /api/sante/rappels/{id}/reprogrammer (LEGACY)

**Description** : Reprogrammer un rappel sanitaire à une nouvelle date.

**Opérations automatiques côté serveur** :
1. Met à jour `date_prevue` avec la nouvelle date
2. Met `statut` = `EN_ATTENTE`
3. Si la nouvelle date est passée, met `statut` = `EN_RETARD`
4. Incrémente `version`

**Comment utiliser via sync push** :

Le mobile doit envoyer les modifications suivantes via `/sync/push` :

```json
{
  "changes": {
    "sante_rappels": {
      "updated": [
        {
          "id": "string (ID du rappel)",
          "version": "integer (version actuelle)",
          "date_prevue": "date (ISO 8601, nouvelle date)",
          "statut": "EN_ATTENTE"
        }
      ]
    }
  }
}
```

**Comportement backend au push** :
- Le backend accepte la modification de la date
- Le backend recalcule automatiquement le statut :
  - Si `date_prevue` est passée → `EN_RETARD`
  - Si `date_prevue` est future → `EN_ATTENTE`
- Le backend incrémente la version

---

#### DELETE /api/sante/rappels/{id} (LEGACY)

**Description** : Supprimer (soft delete) un rappel sanitaire.

**Opérations automatiques côté serveur** :
1. Met `sync_status` = `synced`
2. Incrémente `version`
3. Soft delete (met `deleted_at` à la date courante)

**Comment utiliser via sync push** :

Le mobile doit envoyer les modifications suivantes via `/sync/push` :

```json
{
  "changes": {
    "sante_rappels": {
      "deleted": [
        {
          "id": "string (ID du rappel)",
          "version": "integer (version actuelle)"
        }
      ]
    }
  }
}
```

**Comportement backend au push** :
- Le backend effectue un soft delete du rappel
- Le backend incrémente la version avant suppression
- Le backend met `deleted_at` à la date courante
- L'événement associé (si existant) n'est PAS supprimé automatiquement

---

### Résumé pour les Rappels Sanitaires

| Opération | Endpoint REST (LEGACY) | Via Sync Push | Événement Auto | Note |
|-----------|------------------------|---------------|----------------|-------|
| Marquer réalisé | POST /sante/rappels/{id}/marquer-realise | Créer événement + mettre à jour rappel | ❌ NON (créé par mobile) | Mobile doit créer l'événement localement |
| Reprogrammer | POST /sante/rappels/{id}/reprogrammer | Mettre à jour date_prevue | ❌ NON | Backend recalcule statut automatiquement |
| Supprimer | DELETE /sante/rappels/{id} | Envoyer dans deleted | ❌ NON | Soft delete, événement associé conservé |

**Recommandation pour le mobile** :
- Toujours utiliser `/sync/push` pour ces opérations
- Pour "marquer réalisé" : créer l'événement localement, puis mettre à jour le rappel avec l'ID de l'événement
- Pour "reprogrammer" : simplement mettre à jour `date_prevue`, le backend recalcule le statut
- Pour "supprimer" : envoyer l'ID dans le tableau `deleted`

---

### Auto-génération des IDs

**Modèles concernés** : Tous les modèles principaux

**Logique** :
- Les IDs sont générés automatiquement côté serveur si non fournis
- Format : EXACTEMENT 20 caractères aléatoires (UUID-like)
- Exception : `Animal.numero_identification` (format ANI-YYYY-XXXXXX)
- Exception : `Transaction.numero_transaction` (format TRX-YYYY-XXXXXX via séquence PostgreSQL)

**Action mobile** :
- Le mobile PEUT générer des IDs localement (recommandé pour offline-first)
- Si le mobile génère un ID, il doit être unique et respecter le format EXACTEMENT 20 caractères
- En cas de conflit, le serveur rejettera avec erreur `UUID_INVALID`

---

### Auto-génération du Statut Animal

**Modèle** : Animal

**Logique** :
- Statut par défaut : `SAIN`
- Généré automatiquement si non fourni à la création

**Action mobile** : Le mobile peut omettre le champ `statut` à la création, le serveur le définira à `SAIN`.

---

### Auto-génération du Numéro d'Identification

**Modèle** : Animal

**Logique** :
- Format : `ANI-YYYY-XXXXXX` (ex: ANI-2026-000001)
- Généré automatiquement si non fourni à la création
- Utilise une séquence par année

**Action mobile** : Le mobile peut omettre le champ `numero_identification` à la création, le serveur le générera.

---

## Règles d'Éligibilité des Animaux

### Résumé par Type d'Événement

| Type d'Événement | Endpoint Éligibilité | Règles |
|------------------|----------------------|--------|
| VACCINATION | `/api/animals/eligible/sanitaire?type_evenement=VACCINATION` | Animaux vivants (SAIN ou MALADE) |
| TRAITEMENT | `/api/animals/eligible/sanitaire?type_evenement=TRAITEMENT` | Animaux malades (MALADE uniquement) |
| CONTRÔLE | `/api/animals/eligible/sanitaire?type_evenement=CONTRÔLE` | Animaux vivants (SAIN ou MALADE) |
| VENTE | `/api/animals/eligible/mouvement?type_mouvement=vente` | Animaux vivants (SAIN ou MALADE) |
| TRANSFERT | `/api/animals/eligible/mouvement?type_mouvement=transfert` | Animaux vivants (SAIN ou MALADE) |
| DÉCÈS | `/api/animals/eligible/mouvement?type_mouvement=deces` | Animaux vivants (SAIN ou MALADE) |
| PERTE | `/api/animals/eligible/mouvement?type_mouvement=perte` | Animaux vivants (SAIN ou MALADE) |
| ABATTAGE | `/api/animals/eligible/mouvement?type_mouvement=abattage` | Animaux vivants (SAIN ou MALADE) |
| SAILLIE | `/api/animals/eligible/reproduction?type_reproduction=saillie` | Femelles vivantes, âge reproduction atteint, pas de gestation EN_COURS |
| GESTATION | `/api/animals/eligible/reproduction?type_reproduction=gestation` | Femelles vivantes, saillie EN_COURS existe, pas de gestation EN_COURS |
| MISE BAS | `/api/animals/eligible/reproduction?type_reproduction=mise_bas` | Femelles vivantes, gestation EN_COURS existe |
| DÉCLARATION NAISSANCE | `/api/reproduction/femelles-eligibles` | Femelles vivantes, gestation EN_COURS, date mise bas prévue atteinte |

### Validation Métier (Côté Serveur)

Le service `ReproductionRuleService` valide les règles suivantes avant création :

#### Saillie
- Animal doit être femelle
- Âge de reproduction atteint (selon espece_parametre.age_reproduction_mois)
- Pas de gestation EN_COURS existante
- Si male_id fourni : doit être un mâle

#### Gestation Confirmée
- Animal doit être femelle
- Saillie EN_COURS doit exister
- Pas de gestation EN_COURS déjà existante
- Date de confirmation >= date de saillie

#### Mise Bas
- Gestation EN_COURS doit exister
- Date de mise bas >= date de confirmation de gestation

**Action mobile** : Le mobile doit appeler les endpoints d'éligibilité avant de présenter les options à l'utilisateur. Les validations côté serveur sont appliquées en plus pour garantir l'intégrité.

---

## Validation des Données

### Formats de Données

#### Dates
- **Format** : ISO 8601 (`YYYY-MM-DD`)
- **Exemple** : `2026-07-22`
- **Timezone** : UTC

#### Datetimes
- **Format** : ISO 8601 avec timezone (`YYYY-MM-DDTHH:mm:ssZ`)
- **Exemple** : `2026-07-22T10:30:00Z`

#### Decimals
- **Format** : Nombre décimal avec 2 décimales
- **Exemple** : `125.50`

#### IDs
- **Format** : 20 caractères aléatoires (UUID-like)
- **Exemple** : `a1b2c3d4e5f6g7h8i9j0`

#### Enums
- **Statut Animal** : `SAIN`, `MALADE`, `DECEDÉ`, `VENDU`, `TRANSFÉRÉ`, `PERDU`, `ABATTU`
- **Sexe** : `male`, `femelle`
- **Origine** : `import`, `achat`, `naissance`
- **Type Transaction** : `ENTREE`, `SORTIE`, `TRANSFERT`, `AJUSTEMENT`
- **Statut Événement** : `EN_COURS`, `TERMINE`, `ANNULE`
- **Statut Rappel** : `EN_ATTENTE`, `REALISE`, `EN_RETARD`
- **Type Rappel** : `VACCINATION`, `TRAITEMENT`, `CONTROLE`, `MISE_BAS`, `CHALEUR`
- **Sync Status** : `synced`, `pending`, `conflict`

### Champs Obligatoires par Table

#### Animal
- `id` (si création locale)
- `farm_id`
- `nom`
- `sexe`
- `espece_id`

#### Evenement
- `id` (si création locale)
- `farm_id`
- `type_evenement_id`
- `categorie`
- `date_evenement`

#### Transaction
- `id` (si création locale)
- `farm_id`
- `type_transaction`
- `montant`
- `date_transaction`
- `user_id`

#### Naissance
- `id` (si création locale)
- `farm_id`
- `mother_id`
- `date_naissance`
- `nombre_petits`

#### SanteRappel
- `id` (si création locale)
- `farm_id`
- `animal_id`
- `type_rappel`
- `date_prevue`

#### Lot
- `id` (si création locale)
- `farm_id`
- `nom`

#### Farm
- `id` (si création locale)
- `nom`

#### User
- `id` (si création locale)
- `nom`
- `email`

---

## Formats et Conventions

### Conventions de Nommage

- **Tables** : Pluriel en anglais (`animals`, `evenements`)
- **Colonnes** : snake_case (`date_naissance`, `numero_identification`)
- **JSON Keys** : snake_case (`created_at`, `sync_status`)

### Conventions de Timestamps

- **created_at** : Date de création (générée automatiquement)
- **updated_at** : Date de dernière modification (générée automatiquement)
- **deleted_at** : Date de suppression (soft delete)

### Conventions de Soft Delete

- Les enregistrements supprimés ne sont pas physiquement supprimés
- `deleted_at` est renseigné à la date de suppression
- Les endpoints `trashed` permettent de lister les enregistrements supprimés
- Les endpoints `restore` permettent de restaurer

### Conventions de Versioning

- `version` est un entier qui s'incrémente à chaque modification
- Utilisé pour la détection de conflits lors de la synchronisation
- Le serveur rejette les modifications si `version` local != `version` serveur

### Conventions de Sync Status

- `synced` : Enregistré et synchronisé
- `pending` : Créé/modifié localement, en attente de synchronisation
- `conflict` : Conflit de synchronisation détecté

### Conventions de Pagination

- Les endpoints de liste utilisent la pagination Laravel
- Paramètres :
  - `page` : Numéro de page (défaut 1)
  - `per_page` : Éléments par page (défaut 15)
- Response inclut `meta` avec :
  - `total` : Nombre total d'éléments
  - `per_page` : Éléments par page
  - `current_page` : Page courante
  - `last_page` : Dernière page

---

## Endpoints Légaux pour le Mobile

### Endpoints Sync (OBLIGATOIRES)

1. **POST /api/sync/initial** - Synchronisation initiale
2. **POST /api/sync/pull** - Récupérer les modifications
3. **POST /api/sync/push** - Envoyer les modifications
4. **POST /api/sync/verify-consistency** - Vérifier la cohérence
5. **GET /api/sync/errors** - Récupérer les erreurs

### Endpoints d'Éligibilité (ADMIN WEB ONLY)

⚠️ **IMPORTANT** : Ces endpoints sont destinés à l'admin web panel uniquement. Le mobile doit calculer l'éligibilité localement à partir des données synchronisées via `/sync/pull`.

1. **GET /api/animals/eligible/sanitaire** - Animaux éligibles sanitaires (ADMIN WEB ONLY)
2. **GET /api/animals/eligible/mouvement** - Animaux éligibles mouvements (ADMIN WEB ONLY)
3. **GET /api/animals/eligible/reproduction** - Animaux éligibles reproduction (ADMIN WEB ONLY)
4. **GET /api/reproduction/femelles-eligibles** - Femelles éligibles naissance (ADMIN WEB ONLY)
5. **GET /api/reproduction/naissances/previsions** - Prévisions mises bas (ADMIN WEB ONLY)

### Endpoints Auth (RECOMMANDÉS)

1. **POST /api/auth/login** - Connexion
2. **POST /api/auth/logout** - Déconnexion
3. **GET /api/auth/me** - Utilisateur courant

### Endpoints Fermes (RECOMMANDÉS)

1. **GET /api/farms** - Lister les fermes
2. **GET /api/farms/{farm}** - Détail ferme

### Endpoints REST (LEGACY - À ÉVITER)

⚠️ **IMPORTANT** : Les endpoints suivants sont marqués LEGACY et ne doivent PAS être utilisés par le mobile. Ils seront restreints au rôle admin-only après une période d'observation.

- `POST /api/animals` - Utiliser `/sync/push` à la place
- `PUT /api/animals/{animal}` - Utiliser `/sync/push` à la place
- `DELETE /api/animals/{animal}` - Utiliser `/sync/push` à la place
- `POST /api/sante/evenements` - Utiliser `/sync/push` à la place
- `PUT /api/sante/evenements/{evenement}` - Utiliser `/sync/push` à la place
- `DELETE /api/sante/evenements/{evenement}` - Utiliser `/sync/push` à la place
- `POST /api/reproduction/evenements` - Utiliser `/sync/push` à la place
- `PUT /api/reproduction/evenements/{evenement}` - Utiliser `/sync/push` à la place
- `DELETE /api/reproduction/evenements/{evenement}` - Utiliser `/sync/push` à la place
- `POST /api/transactions` - Utiliser `/sync/push` à la place
- `PUT /api/transactions/{transaction}` - Utiliser `/sync/push` à la place
- `DELETE /api/transactions/{transaction}` - Utiliser `/sync/push` à la place

---

## Résumé pour les Développeurs Mobile

### Flux Recommandé

1. **Login** : `POST /api/auth/login` → récupérer token et fermes
2. **Initial Sync** : `POST /api/sync/initial` → récupérer toutes les données
3. **Opérations CRUD** : Effectuer localement dans WatermelonDB
4. **Push** : `POST /api/sync/push` → envoyer les modifications
5. **Pull** : `POST /api/sync/pull` → récupérer les modifications du serveur
6. **Éligibilité** : Appeler les endpoints d'éligibilité avant de présenter les options
7. **Répéter** : Pull/Push en boucle selon les besoins

### Points Clés

- **Toujours utiliser le canal sync** pour les opérations CRUD
- **Générer les IDs localement** pour le offline-first
- **Incrémenter la version** à chaque modification locale
- **Gérer les conflits** via le champ `conflicts` dans la réponse push
- **Utiliser les endpoints d'éligibilité** pour filtrer les animaux
- **Ne pas recréer** les événements automatiques côté serveur
- **Synchroniser régulièrement** via pull pour récupérer les événements automatiques

### Gestion des Erreurs

- **VERSION_CONFLICT** : Résoudre manuellement ou forcer la version serveur
- **UUID_INVALID** : Régénérer un ID unique
- **FK_MISSING** : S'assurer que les relations existent avant l'envoi
- **VALIDATION_ERROR** : Corriger les données et réessayer
- **PERMISSION_DENIED** : Vérifier les permissions de l'utilisateur

---

**Fin de la documentation**
