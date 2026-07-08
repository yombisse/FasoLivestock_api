# FasoLivestock API - Documentation MVP pour Mobile

## Table des matières

1. [Authentification](#authentification)
2. [Gestion du cheptel](#gestion-du-cheptel)
3. [Traçabilité](#traçabilité)
4. [Reproduction](#reproduction)
5. [Finances](#finances)
6. [Multi-fermes](#multi-fermes)
7. [Utilisateurs & Permissions](#utilisateurs--permissions)
8. [Synchronisation](#synchronisation)

---

## Informations générales

### Base URL
```
http://votre-domaine.com/api
```

### Authentification
Toutes les routes (sauf authentification) nécessitent un token Bearer dans le header :
```
Authorization: Bearer {token}
```

### Contexte de ferme
Pour les routes business, le header `X-Farm-Id` est requis :
``
```
X-Farm-Id: {farm_uuid}
```

### Format de réponse
```json
{
  "success": true,
  "message": "Message de succès",
  "data": { ... }
}
```

### Format d'erreur
```json
{
  "success": false,
  "message": "Message d'erreur",
  "errors": { ... }
}
```

---

## 1. Authentification

### Enregistrement
```http
POST /api/auth/register
```

**Body :**
```json
{
  "name": "Jean Dupont",
  "email": "jean@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+22670000000"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Compte créé avec succès",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

### Connexion
```http
POST /api/auth/login
```

**Body :**
```json
{
  "email": "jean@example.com",
  "password": "password123"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

### Déconnexion
```http
POST /api/auth/logout
```

**Headers :**
```
Authorization: Bearer {token}
```

### Profil utilisateur
```http
GET /api/auth/me
```

**Headers :**
```
Authorization: Bearer {token}
```

### Réinitialisation mot de passe
```http
POST /api/auth/forgot-password
```

**Body :**
```json
{
  "email": "jean@example.com"
}
```

---

## 2. Gestion du cheptel

### Animaux

#### Liste des animaux
```http
GET /api/animals
```

**Headers :**
```
Authorization: Bearer {token}
X-Farm-Id: {farm_uuid}
```

**Query params (optionnels) :**
- `page` : Numéro de page
- `per_page` : Éléments par page (défaut: 15)
- `espece_id` : Filtrer par espèce
- `lot_id` : Filtrer par lot
- `sexe` : Filtrer par sexe (MALE/FEMELLE)
- `statut` : Filtrer par statut (ACTIF/INACTIF/DECED)
- `search` : Recherche textuelle (identification, nom)

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "identification": "TAG123",
      "nom": "Bovin #1",
      "sexe": "MALE",
      "date_naissance": "2024-01-01",
      "espece_id": "uuid",
      "lot_id": "uuid",
      "statut": "ACTIF",
      "poids": 450.5,
      "photo": "url_photo",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

#### Créer un animal
```http
POST /api/animals
```

**Body :**
```json
{
  "identification": "TAG123",
  "nom": "Bovin #1",
  "sexe": "MALE",
  "date_naissance": "2024-01-01",
  "espece_id": "uuid",
  "lot_id": "uuid",
  "poids": 450.5,
  "statut": "ACTIF"
}
```

#### Détail d'un animal
```http
GET /api/animals/{animal_id}
```

#### Modifier un animal
```http
PUT /api/animals/{animal_id}
```

**Body :** Même format que création

#### Supprimer un animal (soft delete)
```http
DELETE /api/animals/{animal_id}
```

#### Import batch d'animaux
```http
POST /api/animals/import
```

**Body :**
```json
{
  "animals": [
    {
      "identification": "TAG123",
      "nom": "Bovin #1",
      "sexe": "MALE",
      "date_naissance": "2024-01-01",
      "espece_id": "uuid"
    }
  ]
}
```

#### Actions métier sur animaux

##### Achat d'un animal
```http
POST /api/animals/purchase
```

**Body :**
```json
{
  "identification": "TAG123",
  "espece_id": "uuid",
  "date_achat": "2024-01-01",
  "prix": 500000,
  "vendeur": "Nom du vendeur",
  "lot_id": "uuid"
}
```

##### Naissance d'un animal
```http
POST /api/animals/birth
```

**Body :**
```json
{
  "mere_id": "uuid",
  "pere_id": "uuid",
  "espece_id": "uuid",
  "date_naissance": "2024-01-01",
  "sexe": "MALE",
  "poids": 25.5,
  "lot_id": "uuid"
}
```

##### Vente d'un animal
```http
POST /api/animals/{animal_id}/sell
```

**Body :**
```json
{
  "date_vente": "2024-01-01",
  "prix": 600000,
  "acheteur": "Nom de l'acheteur"
}
```

##### Transfert d'un animal
```http
POST /api/animals/{animal_id}/transfer
```

**Body :**
```json
{
  "farm_destination_id": "uuid",
  "date_transfert": "2024-01-01",
  "motif": "Transfert vers ferme secondaire"
}
```

##### Déclaration de décès
```http
POST /api/animals/{animal_id}/declare-death
```

**Body :**
```json
{
  "date_deces": "2024-01-01",
  "cause": "Maladie",
  "veterinaire": "Dr. X"
}
```

##### Déclaration de perte
```http
POST /api/animals/{animal_id}/declare-loss
```

**Body :**
```json
{
  "date_perte": "2024-01-01",
  "cause": "Fuite",
  "details": "Animal échappé du parc"
}
```

##### Abattage
```http
POST /api/animals/{animal_id}/slaughter
```

**Body :**
```json
{
  "date_abattage": "2024-01-01",
  "raison": "Consommation",
  "abattoir": "Nom de l'abattoir"
}
```

### Espèces

#### Liste des espèces
```http
GET /api/especes
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nom_espece": "Bovin",
      "description": "Bovins de race locale",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Créer une espèce
```http
POST /api/especes
```

**Body :**
```json
{
  "nom_espece": "Ovin",
  "description": "Ovins de race locale"
}
```

#### Paramètres biologiques d'une espèce
```http
GET /api/especes/{espece_id}/parametres
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "duree_gestation": 285,
    "age_premiere_saillie": 18,
    "intervalle_mise_bas": 365,
    "taux_mortalite": 0.05
  }
}
```

#### Modifier les paramètres biologiques
```http
PUT /api/especes/{espece_id}/parametres
```

### Lots

#### Liste des lots
```http
GET /api/lots
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nom_lot": "Lot Bovins 2024",
      "description": "Bovins nés en 2024",
      "espece_id": "uuid",
      "nombre_animaux": 25,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Créer un lot
```http
POST /api/lots
```

**Body :**
```json
{
  "nom_lot": "Lot Bovins 2024",
  "description": "Bovins nés en 2024",
  "espece_id": "uuid"
}
```

#### Animaux d'un lot
```http
GET /api/lots/{lot_id}/animals
```

#### Affecter des animaux à un lot
```http
POST /api/lots/{lot_id}/animals
```

**Body :**
```json
{
  "animal_ids": ["uuid1", "uuid2", "uuid3"]
}
```

#### Retirer un animal d'un lot
```http
DELETE /api/lots/{lot_id}/animals/{animal_id}
```

#### Statistiques d'un lot
```http
GET /api/lots/{lot_id}/stats
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_animaux": 25,
    "males": 15,
    "femelles": 10,
    "poids_moyen": 450.5,
    "age_moyen": 18
  }
}
```

---

## 3. Traçabilité

### Mouvements (Entrées/Sorties)

#### Liste des mouvements
```http
GET /api/mouvements
```

**Query params (optionnels) :**
- `type` : Filtrer par type (ACHAT, VENTE, TRANSFERT, DECES, PERTE, ABATTAGE)
- `date_debut` : Date de début (YYYY-MM-DD)
- `date_fin` : Date de fin (YYYY-MM-DD)
- `animal_id` : Filtrer par animal

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type_evenement": {
        "id": "uuid",
        "nom_type": "VENTE",
        "categorie": "MOUVEMENT"
      },
      "animal": {
        "id": "uuid",
        "identification": "TAG123",
        "nom": "Bovin #1"
      },
      "date_evenement": "2024-01-01",
      "statut_avant": "ACTIF",
      "statut_apres": "INACTIF",
      "transaction_id": "uuid",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Détail d'un mouvement
```http
GET /api/mouvements/{mouvement_id}
```

#### Historique des mouvements d'un animal
```http
GET /api/animals/{animal_id}/mouvements
```

#### Traçabilité complète d'un animal
```http
GET /api/mouvements/trace/{animal_id}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "animal": { ... },
    "historique": [
      {
        "date": "2024-01-01",
        "evenement": "ACHAT",
        "details": "Acheté chez vendeur X"
      },
      {
        "date": "2024-06-01",
        "evenement": "VENTE",
        "details": "Vendu à acheteur Y"
      }
    ]
  }
}
```

#### Statistiques des mouvements
```http
GET /api/mouvements/statistiques
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_achats": 10,
    "total_ventes": 5,
    "total_transferts": 2,
    "total_deces": 1,
    "total_pertes": 0,
    "total_abattages": 3
  }
}
```

### Types d'événements

#### Liste des types d'événements
```http
GET /api/type-evenements
```

**Query params (optionnels) :**
- `categorie` : Filtrer par catégorie (MOUVEMENT, REPRODUCTION, SANITAIRE)

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nom_type": "ACHAT",
      "categorie": "MOUVEMENT",
      "description": "Achat d'un animal"
    },
    {
      "id": "uuid",
      "nom_type": "CHALEUR",
      "categorie": "REPRODUCTION",
      "description": "Détection de chaleur"
    }
  ]
}
```

---

## 4. Reproduction

### Événements reproductifs

#### Liste des événements reproductifs
```http
GET /api/reproduction/evenements
```

**Query params (optionnels) :**
- `type` : Filtrer par type (CHALEUR, SAILLIE, GESTATION_CONFIRMEE)
- `animal_id` : Filtrer par animal
- `date_debut` : Date de début
- `date_fin` : Date de fin

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type_evenement": {
        "nom_type": "CHALEUR",
        "categorie": "REPRODUCTION"
      },
      "animal": {
        "id": "uuid",
        "identification": "TAG123"
      },
      "date_evenement": "2024-01-01",
      "details": {
        "intensite": "FORTE",
        "observation": "Chaleur détectée le matin"
      }
    }
  ]
}
```

#### Créer un événement reproductif
```http
POST /api/reproduction/evenements
```

**Body :**
```json
{
  "type_evenement_id": "uuid",
  "animal_id": "uuid",
  "date_evenement": "2024-01-01",
  "details": {
    "pere_id": "uuid",
    "intensite": "FORTE",
    "observation": "Chaleur détectée"
  }
}
```

#### Détail d'un événement reproductif
```http
GET /api/reproduction/evenements/{evenement_id}
```

#### Modifier un événement reproductif
```http
PUT /api/reproduction/evenements/{evenement_id}
```

#### Supprimer un événement reproductif
```http
DELETE /api/reproduction/evenements/{evenement_id}
```

### Naissances

#### Liste des naissances
```http
GET /api/reproduction/naissances
```

**Query params (optionnels) :**
- `mere_id` : Filtrer par mère
- `date_debut` : Date de début
- `date_fin` : Date de fin

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "mere": {
        "id": "uuid",
        "identification": "TAG123"
      },
      "pere": {
        "id": "uuid",
        "identification": "TAG456"
      },
      "date_naissance": "2024-01-01",
      "nombre_veaux": 1,
      "veaux": [
        {
          "id": "uuid",
          "identification": "TAG789",
          "sexe": "MALE",
          "poids": 25.5
        }
      ]
    }
  ]
}
```

#### Créer une naissance
```http
POST /api/reproduction/naissances
```

**Body :**
```json
{
  "mere_id": "uuid",
  "pere_id": "uuid",
  "date_naissance": "2024-01-01",
  "veaux": [
    {
      "identification": "TAG789",
      "sexe": "MALE",
      "poids": 25.5,
      "espece_id": "uuid"
    }
  ]
}
```

#### Prévisions de mises bas
```http
GET /api/reproduction/naissances/previsions
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "mere_id": "uuid",
      "identification": "TAG123",
      "date_saillie": "2024-01-01",
      "date_prevue": "2024-10-01",
      "jours_restants": 30
    }
  ]
}
```

#### Femelles éligibles à une déclaration de naissance
```http
GET /api/reproduction/femelles-eligibles
```

### Dashboard reproduction

#### Statistiques globales de reproduction
```http
GET /api/reproduction/dashboard
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_naissances": 50,
    "taux_reussite": 0.95,
    "femelles_gestantes": 15,
    "femelles_en_chaleur": 5
  }
}
```

#### Prévisions de reproduction
```http
GET /api/reproduction/forecast
```

#### Historique reproductif d'un animal
```http
GET /api/reproduction/animals/{animal_id}/historique
```

#### Statistiques reproductives d'un animal
```http
GET /api/reproduction/animals/{animal_id}/stats
```

---

## 5. Finances

### Transactions

#### Liste des transactions
```http
GET /api/transactions
```

**Query params (optionnels) :**
- `type` : Filtrer par type (REVENU, DEPENSE)
- `categorie_id` : Filtrer par catégorie
- `date_debut` : Date de début
- `date_fin` : Date de fin

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "REVENU",
      "montant": 600000,
      "date_transaction": "2024-01-01",
      "description": "Vente de bovin",
      "categorie": {
        "id": "uuid",
        "nom_categorie": "Vente d'animaux",
        "type": "REVENU"
      },
      "animal_id": "uuid",
      "tiers": "Acheteur X"
    }
  ]
}
```

#### Créer une transaction
```http
POST /api/transactions
```

**Body :**
```json
{
  "type": "REVENU",
  "montant": 600000,
  "date_transaction": "2024-01-01",
  "description": "Vente de bovin",
  "categorie_id": "uuid",
  "animal_id": "uuid",
  "tiers": "Acheteur X"
}
```

#### Détail d'une transaction
```http
GET /api/transactions/{transaction_id}
```

#### Modifier une transaction
```http
PUT /api/transactions/{transaction_id}
```

#### Supprimer une transaction
```http
DELETE /api/transactions/{transaction_id}
```

#### Bilan financier
```http
GET /api/transactions/bilan
```

**Query params (optionnels) :**
- `date_debut` : Date de début
- `date_fin` : Date de fin

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_revenus": 5000000,
    "total_depenses": 3000000,
    "benefice": 2000000,
    "marge": 0.4,
    "par_categorie": {
      "Vente d'animaux": 4000000,
      "Vente de lait": 1000000
    }
  }
}
```

### Catégories

#### Liste des catégories
```http
GET /api/categories
```

**Query params (optionnels) :**
- `type` : Filtrer par type (REVENU, DEPENSE)
- `farm_id` : Filtrer par ferme

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nom_categorie": "Vente d'animaux",
      "type": "REVENU",
      "description": "Revenus provenant de la vente d'animaux",
      "farm_id": null
    },
    {
      "id": "uuid",
      "nom_categorie": "Santé (vétérinaire)",
      "type": "DEPENSE",
      "description": "Frais vétérinaires, vaccins et traitements",
      "farm_id": null
    }
  ]
}
```

#### Créer une catégorie
```http
POST /api/categories
```

**Body :**
```json
{
  "nom_categorie": "Nouvelle catégorie",
  "type": "DEPENSE",
  "description": "Description de la catégorie",
  "farm_id": "uuid"
}
```

#### Détail d'une catégorie
```http
GET /api/categories/{categorie_id}
```

#### Modifier une catégorie
```http
PUT /api/categories/{categorie_id}
```

#### Supprimer une catégorie
```http
DELETE /api/categories/{categorie_id}
```

---

## 6. Multi-fermes

### Fermes

#### Liste des fermes de l'utilisateur
```http
GET /api/farms
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nom_ferme": "Ferme Principale",
      "localisation": "Ouagadougou",
      "telephone": "+22670000000",
      "type_elevage": "BOVIN",
      "owner_id": "uuid",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Créer une ferme
```http
POST /api/farms
```

**Body :**
```json
{
  "nom_ferme": "Nouvelle Ferme",
  "localisation": "Kaya",
  "telephone": "+22670000000",
  "type_elevage": "OVIN"
}
```

#### Détail d'une ferme
```http
GET /api/farms/{farm_id}
```

#### Modifier une ferme
```http
PUT /api/farms/{farm_id}
```

#### Supprimer une ferme
```http
DELETE /api/farms/{farm_id}
```

#### Gestion des membres de ferme
```http
POST /api/farms/{farm_id}/users
```

**Body :**
```json
{
  "user_id": "uuid",
  "role": "eleveur"
}
```

#### Retirer un membre de ferme
```http
DELETE /api/farms/{farm_id}/users/{user_id}
```

---

## 7. Utilisateurs & Permissions

### Utilisateurs

#### Liste des utilisateurs
```http
GET /api/users
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Jean Dupont",
      "email": "jean@example.com",
      "phone": "+22670000000",
      "photo": "url_photo",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### Créer un utilisateur
```http
POST /api/users
```

**Body :**
```json
{
  "name": "Jean Dupont",
  "email": "jean@example.com",
  "password": "password123",
  "phone": "+22670000000"
}
```

#### Détail d'un utilisateur
```http
GET /api/users/{user_id}
```

#### Modifier un utilisateur
```http
PUT /api/users/{user_id}
```

#### Activer/Désactiver un utilisateur
```http
PATCH /api/users/{user_id}/toggle-active
```

#### Supprimer un utilisateur
```http
DELETE /api/users/{user_id}
```

### Rôles & Permissions

#### Liste des rôles
```http
GET /api/roles
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "admin",
      "description": "Administrateur système"
    },
    {
      "id": "uuid",
      "name": "eleveur",
      "description": "Éleveur de ferme"
    }
  ]
}
```

#### Liste des permissions disponibles
```http
GET /api/roles/permissions
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    "animals.view",
    "animals.create",
    "animals.update",
    "animals.delete",
    "transactions.view",
    "transactions.create",
    "transactions.update",
    "transactions.delete",
    "reproduction.view",
    "reproduction.create",
    "reproduction.update",
    "reproduction.delete",
    "mouvements.view",
    "categories.view",
    "categories.create",
    "categories.update",
    "categories.delete"
  ]
}
```

#### Assigner un rôle à un utilisateur
```http
POST /api/roles/{role_id}/users
```

**Body :**
```json
{
  "user_id": "uuid"
}
```

#### Retirer un rôle d'un utilisateur
```http
DELETE /api/roles/{role_id}/users/{user_id}
```

---

## 8. Synchronisation

### Push (Mobile → Serveur)

```http
POST /api/sync/push
```

**Headers :**
```
Authorization: Bearer {token}
X-Farm-Id: {farm_uuid}
```

**Body :**
```json
{
  "changes": [
    {
      "table": "animals",
      "action": "create",
      "data": {
        "id": "uuid",
        "identification": "TAG123",
        "nom": "Bovin #1",
        "sexe": "MALE",
        "espece_id": "uuid",
        "farm_id": "uuid",
        "sync_status": "pending",
        "version": 1
      }
    },
    {
      "table": "transactions",
      "action": "update",
      "data": {
        "id": "uuid",
        "montant": 600000,
        "version": 2
      }
    }
  ],
  "last_sync_at": "2024-01-01T00:00:00Z",
  "farm_id": "uuid"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Synchronisation push réussie",
  "data": {
    "results": [
      {
        "table": "animals",
        "action": "create",
        "status": "created"
      },
      {
        "table": "transactions",
        "action": "update",
        "status": "updated"
      }
    ],
    "conflicts": [],
    "synced_at": "2024-01-01T12:00:00Z"
  }
}
```

### Pull (Serveur → Mobile)

```http
GET /api/sync/pull
```

**Headers :**
```
Authorization: Bearer {token}
X-Farm-Id: {farm_uuid}
```

**Query params :**
- `last_sync_at` : Date de dernière synchronisation (ISO 8601)
- `farm_id` : ID de la ferme

**Réponse :**
```json
{
  "success": true,
  "message": "Synchronisation pull réussie",
  "data": {
    "changes": {
      "animals": [
        {
          "id": "uuid",
          "identification": "TAG123",
          "nom": "Bovin #1",
          "updated_at": "2024-01-01T12:00:00Z",
          "deleted_at": null
        }
      ],
      "transactions": [
        {
          "id": "uuid",
          "montant": 600000,
          "updated_at": "2024-01-01T12:00:00Z",
          "deleted_at": null
        }
      ],
      "evenements": [],
      "lots": [],
      "especes": [],
      "categories": [],
      "type_evenements": []
    },
    "synced_at": "2024-01-01T12:00:00Z"
  }
}
```

---

## Notes importantes

### Versioning
- Les données business incluent un champ `version` pour la détection de conflits
- Le système de sync utilise ce champ pour gérer les conflits

### Soft Deletes
- La plupart des entités utilisent le soft delete
- Les données supprimées sont incluses dans le sync avec `deleted_at` non null
- Utilisez les endpoints `trashed` et `restore` pour gérer les suppressions

### Permissions
- Toutes les routes sont protégées par des permissions Spatie
- Les permissions doivent être assignées aux rôles des utilisateurs
- Vérifiez les permissions requises dans la documentation de chaque route

### Farm Context
- Les routes business nécessitent le header `X-Farm-Id`
- Ce contexte est utilisé pour filtrer les données par ferme
- Les utilisateurs peuvent avoir accès à plusieurs fermes

### Pagination
- La plupart des listes supportent la pagination
- Utilisez les paramètres `page` et `per_page`
- La réponse inclut les métadonnées de pagination

### Recherche et filtres
- De nombreuses routes supportent la recherche textuelle
- Les filtres sont documentés pour chaque route
- Utilisez les query params pour affiner les résultats

---

## Erreurs courantes

### 401 Unauthorized
- Token manquant ou invalide
- Token expiré

### 403 Forbidden
- Permission manquante
- Accès non autorisé à la ferme

### 404 Not Found
- Ressource non trouvée
- ID invalide

### 422 Validation Error
- Données invalides
- Champs manquants

### 500 Server Error
- Erreur serveur
- Contactez l'administrateur

---

## Support

Pour toute question ou problème, contactez l'équipe technique de FasoLivestock.
