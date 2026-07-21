# Documentation API - Approche Offline-First Hybride
## FasoLivestock - Validation Stricte pour Opérations Financières

---

## Table des Matières

1. [Vue d'ensemble de l'architecture](#vue-densemble-de-larchitecture)
2. [Endpoints pour opérations financières](#endpoints-pour-opérations-financières)
3. [Formats de données et validation](#formats-de-données-et-validation)
4. [Gestion des conflits](#gestion-des-conflits)
5. [Codes d'erreur et résolution](#codes-derreur-et-résolution)
6. [Exemples d'implémentation mobile](#exemples-dimplémentation-mobile)

---

## Vue d'ensemble de l'architecture

### Approche Hybride

**Opérations Standard (Optimiste Simple)**
- Création d'animaux
- Événements sanitaires
- Naissances
- Modifications standard

**Opérations Critiques (Optimiste avec Validation Stricte)**
- Ventes d'animaux
- Achats d'animaux
- Décès
- Pertes
- Abattages
- Transferts

### Flux de Données

```
[Mobile - Opération Critique]
    ↓
1. Validation Locale Stricte
    ↓
2. Création Optimiste Locale (WatermelonDB)
    ↓
3. Marquage sync_status: 'pending_critical'
    ↓
4. Ajout Queue Prioritaire
    ↓
5. Sync Push (Priorité Haute)
    ↓
6. Validation Backend Renforcée
    ↓
7. Réponse avec détails de validation
    ↓
8. Mise à jour Locale + Notification
```

---

## Endpoints pour Opérations Financières

### 1. Achat d'Animal

**Endpoint:** `POST /api/animals/purchase`

**Description:** Crée un animal, un événement d'achat et une transaction financière automatiquement.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "nom": "Vache Achetée",
  "numero_identification": "BO-2024-006",
  "sexe": "femelle",
  "race": "Zébu",
  "date_naissance": "2022-04-01",
  "poids": 400.0,
  "espece_id": "{espece_id}",
  "lot_id": "{lot_id}",
  "prix_achat": 250000,
  "date_achat": "2024-03-15",
  "provenance": "Marché de Bobo",
  "vendeur": "Éleveur Sanou",
  "farm_source_id": "{farm_source_id_optional}",
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- `numero_identification` unique pour la ferme
- `prix_achat` > 0
- `date_achat` <= date actuelle
- `espece_id` existe et appartient à la ferme
- `lot_id` existe et appartient à la ferme
- `user_id` a les permissions nécessaires

**Response Success (201):**
```json
{
  "success": true,
  "message": "Animal acheté avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Vache Achetée",
      "numero_identification": "BO-2024-006",
      "statut": "ACTIF",
      "origine": "achat",
      "sync_status": "synced",
      "version": 1,
      "created_at": "2024-03-15T10:30:00Z"
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_achat_id}",
      "date_evenement": "2024-03-15",
      "description": "Marché de Bobo",
      "cout": 250000,
      "statut_avant": null,
      "statut_apres": "ACTIF",
      "transaction_id": "{transaction_id}"
    },
    "transaction": {
      "id": "{transaction_id}",
      "type_transaction": "SORTIE",
      "montant": 250000,
      "date_transaction": "2024-03-15",
      "categorie_id": "{categorie_achat_id}",
      "evenement_id": "{evenement_id}"
    }
  }
}
```

**Response Error (422 - Validation):**
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "numero_identification": ["Ce numéro d'identification existe déjà pour cette ferme"],
    "prix_achat": ["Le prix d'achat doit être supérieur à 0"]
  }
}
```

**Response Error (409 - Conflit):**
```json
{
  "success": false,
  "message": "Conflit de version",
  "error": "CONFLICT_VERSION",
  "details": {
    "local_version": 1,
    "server_version": 2,
    "conflict_type": "animal_already_exists"
  }
}
```

---

### 2. Vente d'Animal

**Endpoint:** `POST /api/animals/{id}/sell`

**Description:** Met à jour le statut de l'animal à VENDU, crée un événement de vente et une transaction financière.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "prix_vente": 300000,
  "date_vente": "2024-06-15",
  "acheteur": "Éleveur Ouattara",
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- Animal existe et appartient à la ferme
- Animal a le statut 'ACTIF' (pas déjà vendu/mort/perdu)
- `prix_vente` >= 0
- `date_vente` <= date actuelle
- `user_id` a les permissions nécessaires

**Response Success (200):**
```json
{
  "success": true,
  "message": "Animal vendu avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Vache Bétel",
      "statut": "VENDU",
      "sync_status": "synced",
      "version": 2
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_vente_id}",
      "date_evenement": "2024-06-15",
      "description": "Éleveur Ouattara",
      "cout": 300000,
      "statut_avant": "ACTIF",
      "statut_apres": "VENDU",
      "transaction_id": "{transaction_id}"
    },
    "transaction": {
      "id": "{transaction_id}",
      "type_transaction": "ENTREE",
      "montant": 300000,
      "date_transaction": "2024-06-15",
      "categorie_id": "{categorie_vente_id}",
      "evenement_id": "{evenement_id}"
    }
  }
}
```

**Response Error (422 - Validation):**
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "animal": ["Cet animal n'est pas dans un état permettant la vente (statut actuel: VENDU)"]
  }
}
```

---

### 3. Déclaration de Décès

**Endpoint:** `POST /api/animals/{id}/declare-death`

**Description:** Met à jour le statut de l'animal à MORT et crée un événement de décès.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date_deces": "2024-05-20",
  "cause": "Maladie",
  "observation": "Symptômes depuis 3 jours",
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- Animal existe et appartient à la ferme
- Animal a le statut 'ACTIF' (pas déjà vendu/mort/perdu)
- `date_deces` <= date actuelle
- `date_deces` >= date_naissance de l'animal
- `user_id` a les permissions nécessaires

**Response Success (200):**
```json
{
  "success": true,
  "message": "Décès déclaré avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Vache Jasmine",
      "statut": "MORT",
      "sync_status": "synced",
      "version": 2
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_deces_id}",
      "date_evenement": "2024-05-20",
      "description": "Maladie Symptômes depuis 3 jours",
      "statut_avant": "ACTIF",
      "statut_apres": "MORT"
    }
  }
}
```

**Note:** Le décès ne crée PAS de transaction financière automatiquement.

---

### 4. Déclaration de Perte

**Endpoint:** `POST /api/animals/{id}/declare-loss`

**Description:** Met à jour le statut de l'animal à PERDU et crée un événement de perte.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date_perte": "2024-04-10",
  "motif": "Fuite",
  "observation": "Clôture endommagée",
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- Animal existe et appartient à la ferme
- Animal a le statut 'ACTIF' (pas déjà vendu/mort/perdu)
- `date_perte` <= date actuelle
- `user_id` a les permissions nécessaires

**Response Success (200):**
```json
{
  "success": true,
  "message": "Perte déclarée avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Taureau Hercule",
      "statut": "PERDU",
      "sync_status": "synced",
      "version": 2
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_perte_id}",
      "date_evenement": "2024-04-10",
      "description": "Fuite Clôture endommagée",
      "statut_avant": "ACTIF",
      "statut_apres": "PERDU"
    }
  }
}
```

**Note:** La perte ne crée PAS de transaction financière automatiquement.

---

### 5. Abattage

**Endpoint:** `POST /api/animals/{id}/slaughter`

**Description:** Met à jour le statut de l'animal à MORT (ABATTU) et crée un événement d'abattage.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date_abattage": "2024-07-01",
  "motif": "Réforme",
  "poids_carcasse": 250.5,
  "valeur_carcasse": 150000,
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- Animal existe et appartient à la ferme
- Animal a le statut 'ACTIF' (pas déjà vendu/mort/perdu)
- `date_abattage` <= date actuelle
- `poids_carcasse` > 0
- `valeur_carcasse` >= 0
- `user_id` a les permissions nécessaires

**Response Success (200):**
```json
{
  "success": true,
  "message": "Abattage enregistré avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Vache Rose",
      "statut": "MORT",
      "sync_status": "synced",
      "version": 2
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_abattage_id}",
      "date_evenement": "2024-07-01",
      "description": "Réforme poids: 250.5",
      "cout": 150000,
      "statut_avant": "ACTIF",
      "statut_apres": "MORT"
    }
  }
}
```

**Note:** L'abattage ne crée PAS de transaction financière automatiquement (la valeur_carcasse est stockée dans l'événement).

---

### 6. Transfert

**Endpoint:** `POST /api/animals/{id}/transfer`

**Description:** Met à jour le statut de l'animal à TRANSFERE et crée un événement de transfert.

**Headers:**
```
Content-Type: application/json
X-Farm-ID: {farm_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date_transfert": "2024-08-15",
  "farm_destination_id": "{destination_farm_id}",
  "motif": "Réorganisation du cheptel",
  "user_id": "{user_id}"
}
```

**Validation Stricte Backend:**
- Animal existe et appartient à la ferme source
- Animal a le statut 'ACTIF' (pas déjà vendu/mort/perdu)
- Ferme destination existe et est accessible
- `date_transfert` <= date actuelle
- `user_id` a les permissions sur les deux fermes
- Pas de transfert vers la même ferme

**Response Success (200):**
```json
{
  "success": true,
  "message": "Animal transféré avec succès.",
  "data": {
    "animal": {
      "id": "{animal_id}",
      "nom": "Vache Bétel",
      "statut": "TRANSFERE",
      "sync_status": "synced",
      "version": 2
    },
    "evenement": {
      "id": "{evenement_id}",
      "type_evenement_id": "{type_transfert_id}",
      "date_evenement": "2024-08-15",
      "description": "Réorganisation du cheptel",
      "statut_avant": "ACTIF",
      "statut_apres": "TRANSFERE",
      "farm_destination_id": "{destination_farm_id}"
    }
  }
}
```

**Note:** Le transfert ne crée PAS de transaction financière automatiquement.

---

## Formats de Données et Validation

### Schéma Animal

```typescript
interface Animal {
  id: string;                    // UUID v4
  farm_id: string;               // ID de la ferme
  lot_id?: string;               // ID du lot (optionnel)
  espece_id: string;             // ID de l'espèce
  mother_id?: string;            // ID de la mère (optionnel)
  nom?: string;                  // Nom de l'animal (optionnel)
  numero_identification: string; // Numéro unique par ferme
  sexe: 'male' | 'femelle';      // Sexe
  race?: string;                 // Race (optionnel)
  date_naissance: string;        // ISO 8601 (YYYY-MM-DD)
  poids?: number;               // Poids en kg (optionnel)
  statut: 'ACTIF' | 'VENDU' | 'MORT' | 'PERDU' | 'TRANSFERE' | 'ABATTU';
  origine?: 'import' | 'achat' | 'naissance'; // Origine
  photo?: string;               // URL photo (optionnel)
  sync_status: 'pending' | 'synced' | 'conflict';
  version: number;               // Version pour optimist locking
  created_at: string;            // ISO 8601
  updated_at: string;            // ISO 8601
  deleted_at?: string;           // ISO 8601 (soft delete)
}
```

**Règles de Validation Locale (Mobile):**
- `numero_identification` : requis, format alphanumérique, max 50 caractères
- `sexe` : requis, valeurs autorisées uniquement
- `date_naissance` : requis, format ISO 8601, <= date actuelle
- `poids` : optionnel, nombre positif si présent
- `statut` : lecture seule (déterminé par les événements)

**Règles de Validation Backend (Stricte):**
- Toutes les règles locales +
- `numero_identification` unique pour la ferme
- `farm_id` existe et utilisateur a accès
- `espece_id` existe
- `lot_id` existe si fourni
- `mother_id` existe si fourni et est femelle

---

### Schéma Événement

```typescript
interface Evenement {
  id: string;                    // UUID v4
  farm_id: string;               // ID de la ferme
  type_evenement_id: string;     // ID du type d'événement
  animal_id: string;             // ID de l'animal
  date_evenement: string;        // ISO 8601 (YYYY-MM-DD)
  description?: string;           // Description (optionnel)
  cout?: number;                 // Coût (optionnel)
  metadonnees?: object;          // Métadonnées JSON (optionnel)
  statut?: 'EN_COURS' | 'TERMINE' | 'ANNULE' | null; // Pour événements reproductifs
  date_fin?: string;             // Date de fin (optionnel)
  statut_avant?: string;         // Statut avant (mouvements)
  statut_apres?: string;         // Statut après (mouvements)
  farm_destination_id?: string;  // Ferme destination (transferts)
  transaction_id?: string;       // Transaction liée (optionnel)
  sync_status: 'pending' | 'synced' | 'conflict';
  version: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}
```

**Règles de Validation Locale (Mobile):**
- `type_evenement_id` : requis, doit exister localement
- `animal_id` : requis, doit exister localement
- `date_evenement` : requis, format ISO 8601
- `cout` : optionnel, nombre positif si présent
- Pour mouvements : `statut_avant` et `statut_apres` requis

**Règles de Validation Backend (Stricte):**
- Toutes les règles locales +
- `type_evenement_id` existe et correspond au type attendu
- `animal_id` existe et appartient à la ferme
- Cohérence des statuts avant/après
- `farm_destination_id` existe si fourni (transferts)

---

### Schéma Transaction

```typescript
interface Transaction {
  id: string;                    // UUID v4
  farm_id: string;               // ID de la ferme
  categorie_id: string;          // ID de la catégorie
  type_transaction: 'ENTREE' | 'SORTIE' | 'TRANSFERT' | 'AJUSTEMENT';
  montant: number;                // Montant (décimal, >= 0)
  date_transaction: string;      // ISO 8601 (YYYY-MM-DD)
  user_id: string;               // ID de l'utilisateur
  animal_id?: string;            // ID de l'animal (optionnel)
  evenement_id?: string;         // ID de l'événement lié (optionnel)
  description?: string;          // Description (optionnel)
  numero_transaction?: string;    // Numéro de transaction (optionnel)
  tiers?: string;                // Tiers (fournisseur/client) (optionnel)
  sync_status: 'pending' | 'synced' | 'conflict';
  version: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}
```

**Règles de Validation Locale (Mobile):**
- `categorie_id` : requis, doit exister localement
- `type_transaction` : requis, valeurs autorisées uniquement
- `montant` : requis, nombre >= 0
- `date_transaction` : requis, format ISO 8601
- `user_id` : requis

**Règles de Validation Backend (Stricte):**
- Toutes les règles locales +
- `categorie_id` existe
- `user_id` existe et a les permissions
- `animal_id` existe si fourni
- `evenement_id` existe si fourni
- Cohérence du montant avec l'événement lié

---

## Gestion des Conflits

### Types de Conflits

**1. Conflit de Version (409 Conflict)**
```json
{
  "error": "CONFLICT_VERSION",
  "message": "Conflit de version",
  "details": {
    "resource_type": "animal",
    "resource_id": "{id}",
    "local_version": 1,
    "server_version": 2,
    "conflict_type": "version_mismatch"
  }
}
```

**Stratégie de Résolution:**
- **Opérations standard** : Last-write-wins avec notification
- **Opérations critiques** : Rejet systématique, demande de résolution manuelle

**2. Conflit de Validation (422 Unprocessable Entity)**
```json
{
  "error": "VALIDATION_ERROR",
  "message": "Erreur de validation",
  "details": {
    "field": "numero_identification",
    "reason": "duplicate",
    "conflict_type": "duplicate_key"
  }
}
```

**Stratégie de Résolution:**
- Notification explicite à l'utilisateur
- Proposition de correction (ex: modifier le numéro)
- Option de forcer (si permissions)

**3. Conflit de Statut (409 Conflict)**
```json
{
  "error": "STATUS_CONFLICT",
  "message": "Conflit de statut",
  "details": {
    "resource_type": "animal",
    "resource_id": "{id}",
    "local_status": "ACTIF",
    "server_status": "VENDU",
    "conflict_type": "status_mismatch"
  }
}
```

**Stratégie de Résolution:**
- Rejet pour opérations critiques
- Notification avec détails du conflit
- Option de rafraîchir les données locales

---

### Workflow de Résolution Mobile

```typescript
// 1. Détection du conflit lors du sync
if (response.status === 409) {
  const conflictType = response.data.details.conflict_type;
  
  // 2. Classification du conflit
  if (conflictType === 'version_mismatch') {
    if (isCriticalOperation) {
      // Rejet pour opérations critiques
      showConflictResolutionDialog({
        type: 'VERSION_CONFLICT',
        localData: localRecord,
        serverData: serverData,
        actions: ['REFRESH', 'MANUAL_MERGE']
      });
    } else {
      // Last-write-wins pour opérations standard
      await updateLocalRecord(serverData);
      showNotification('Données mises à jour depuis le serveur');
    }
  }
  
  if (conflictType === 'status_mismatch') {
    // Toujours rejet pour conflits de statut
    showConflictResolutionDialog({
      type: 'STATUS_CONFLICT',
      message: `Statut serveur: ${serverData.statut}, Statut local: ${localData.statut}`,
      actions: ['REFRESH', 'CANCEL']
    });
  }
}
```

---

## Codes d'Erreur et Résolution

### Codes d'Erreur HTTP

| Code | Type | Description | Action Mobile |
|------|------|-------------|--------------|
| 200 | Success | Opération réussie | Mettre à jour local, marquer synced |
| 201 | Created | Ressource créée | Mettre à jour local, marquer synced |
| 400 | Bad Request | Requête invalide | Corriger les données, réessayer |
| 401 | Unauthorized | Non authentifié | Reconnecter l'utilisateur |
| 403 | Forbidden | Permissions insuffisantes | Notifier l'utilisateur, annuler |
| 404 | Not Found | Ressource introuvable | Rafraîchir les données locales |
| 409 | Conflict | Conflit de données | Appliquer stratégie de résolution |
| 422 | Unprocessable Entity | Erreur de validation | Corriger selon les champs en erreur |
| 500 | Internal Server Error | Erreur serveur | Retry avec exponential backoff |
| 503 | Service Unavailable | Service indisponible | Retry ultérieurement |

### Codes d'Erreur Spécifiques

```typescript
enum ErrorCode {
  // Validation
  VALIDATION_ERROR = 'VALIDATION_ERROR',
  DUPLICATE_KEY = 'DUPLICATE_KEY',
  INVALID_STATUS = 'INVALID_STATUS',
  
  // Conflits
  CONFLICT_VERSION = 'CONFLICT_VERSION',
  STATUS_CONFLICT = 'STATUS_CONFLICT',
  DATA_CONFLICT = 'DATA_CONFLICT',
  
  // Permissions
  UNAUTHORIZED = 'UNAUTHORIZED',
  FORBIDDEN = 'FORBIDDEN',
  INSUFFICIENT_PERMISSIONS = 'INSUFFICIENT_PERMISSIONS',
  
  // Business
  ANIMAL_NOT_ACTIVE = 'ANIMAL_NOT_ACTIVE',
  FARM_NOT_ACCESSIBLE = 'FARM_NOT_ACCESSIBLE',
  INVALID_TRANSACTION_AMOUNT = 'INVALID_TRANSACTION_AMOUNT',
  
  // Sync
  SYNC_PENDING = 'SYNC_PENDING',
  SYNC_FAILED = 'SYNC_FAILED',
  SYNC_CONFLICT = 'SYNC_CONFLICT'
}
```

---

## Exemples d'Implémentation Mobile

### 0. Service de Mapping Type Événement (Recommandé)

**Problème de la recherche par nom :**
- Sensible à la casse et aux fautes de frappe
- Les noms peuvent changer dans le futur
- Risque de doublons si validation non stricte
- Performance moindre (requête à chaque fois)

**Solution : TypeEvenementMappingService**

```typescript
// src/services/typeEvenementMappingService.ts

class TypeEvenementMappingService {
  private static mapping: Map<string, string> = new Map();
  private static initialized = false;

  static async initialize() {
    if (this.initialized) return;

    // Charger tous les types système (farm_id = null)
    const systemTypes = await getLocalTypeEvenements(); // sans farm_id
    
    // Créer le mapping nom_type → id
    systemTypes.forEach(type => {
      this.mapping.set(type.nom_type, type.id);
    });

    this.initialized = true;
  }

  static getId(nomType: string): string | undefined {
    return this.mapping.get(nomType);
  }

  // Constantes pour les types système courants
  static readonly TYPES = {
    VENTE: 'Vente',
    ACHAT: 'Achat',
    DECES: 'Décès',
    PERTE: 'Perte',
    ABBATTAGE: 'Abattage',
    TRANSFERT: 'Transfert',
    VACCINATION: 'Vaccination',
    TRAITEMENT: 'Traitement',
    MISE_BAS: 'Mise bas',
    SAILLIE: 'Saillie',
    GESTATION: 'Gestation confirmée',
  } as const;
}

// Utilisation
await TypeEvenementMappingService.initialize();
const venteId = TypeEvenementMappingService.getId(TypeEvenementMappingService.TYPES.VENTE);
```

**Avantages :**
- ✅ Performance : Mapping en mémoire, pas de requête
- ✅ Fiabilité : Initialisé une fois au démarrage
- ✅ Type-safety : Constantes TypeScript
- ✅ Maintenabilité : Centralisé dans un service
- ✅ Fallback : Gestion des erreurs gracieuse

**Intégration au démarrage de l'app :**

```typescript
// App.tsx ou index.tsx
import { TypeEvenementMappingService } from './services/typeEvenementMappingService';

const App = () => {
  useEffect(() => {
    // Initialiser le mapping au démarrage
    TypeEvenementMappingService.initialize();
  }, []);

  // ...
};
```

### 1. Validation Locale Stricte (Vente)

```typescript
// services/validationService.ts

export async function validateAnimalSale(
  animal: Animal,
  saleData: SaleData
): Promise<ValidationResult> {
  const errors: string[] = [];
  
  // Validation de l'animal
  if (animal.statut !== 'ACTIF') {
    errors.push(`Animal non disponible pour la vente (statut: ${animal.statut})`);
  }
  
  // Validation des données de vente
  if (saleData.prix_vente < 0) {
    errors.push('Le prix de vente doit être positif');
  }
  
  if (new Date(saleData.date_vente) > new Date()) {
    errors.push('La date de vente ne peut pas être dans le futur');
  }
  
  // Validation anti-doublon local
  const existingSale = await getLocalEventByAnimalAndType(
    animal.id,
    'Vente'
  );
  
  if (existingSale) {
    errors.push('Une vente existe déjà pour cet animal');
  }
  
  return {
    isValid: errors.length === 0,
    errors
  };
}
```

### 2. Création Optimiste avec Marquage Critique

```typescript
// screens/AnimalVenteScreen.tsx

async function handleSellAnimal() {
  // 1. Validation locale stricte
  const validation = await validateAnimalSale(animal, saleData);
  
  if (!validation.isValid) {
    showErrors(validation.errors);
    return;
  }
  
  try {
    // 2. Création optimiste locale
    const updatedAnimal = await updateAnimal(animal.id, {
      statut: 'VENDU',
      sync_status: 'pending_critical',
      version: animal.version + 1
    });
    
    const createdEvent = await createLocalRecord('evenements', {
      type_evenement_id: TypeEvenementMappingService.getId(TypeEvenementMappingService.TYPES.VENTE),
      animal_id: animal.id,
      date_evenement: saleData.date_vente,
      description: saleData.acheteur,
      cout: saleData.prix_vente,
      statut_avant: 'ACTIF',
      statut_apres: 'VENDU',
      sync_status: 'pending_critical',
      version: 1
    });
    
    const createdTransaction = await createLocalRecord('transactions', {
      type_transaction: 'ENTREE',
      montant: saleData.prix_vente,
      date_transaction: saleData.date_vente,
      categorie_id: await getCategorieIdByName('Vente d\'animaux'),
      animal_id: animal.id,
      evenement_id: createdEvent.id,
      description: saleData.acheteur,
      sync_status: 'pending_critical',
      version: 1
    });
    
    // 3. Ajout à la queue prioritaire
    await offlineQueueService.add({
      type: 'CRITICAL',
      operation: 'SELL_ANIMAL',
      endpoint: `/api/animals/${animal.id}/sell`,
      payload: saleData,
      localIds: {
        animal: animal.id,
        evenement: createdEvent.id,
        transaction: createdTransaction.id
      },
      retryCount: 0
    });
    
    // 4. Notification utilisateur
    showNotification('Vente enregistrée (en attente de synchronisation)', {
      type: 'warning',
      persistent: true
    });
    
    // 5. Navigation
    navigation.goBack();
    
  } catch (error) {
    // Rollback en cas d'erreur
    await rollbackAnimalSale(animal.id);
    showError('Erreur lors de l\'enregistrement de la vente');
  }
}
```

### 3. Gestion de la Queue Prioritaire

```typescript
// services/offlineQueueService.ts

class OfflineQueueService {
  private queue: QueueItem[] = [];
  private processing = false;
  
  async add(item: QueueItem): Promise<void> {
    if (item.type === 'CRITICAL') {
      // Ajouter en priorité haute
      this.queue.unshift(item);
    } else {
      // Ajouter en priorité normale
      this.queue.push(item);
    }
    
    // Tenter le sync immédiatement si connecté
    if (await isNetworkAvailable()) {
      this.processQueue();
    }
  }
  
  async processQueue(): Promise<void> {
    if (this.processing) return;
    
    this.processing = true;
    
    while (this.queue.length > 0 && await isNetworkAvailable()) {
      const item = this.queue.shift();
      
      try {
        const response = await api.post(item.endpoint, item.payload);
        
        // Succès : marquer comme synced
        await markRecordsAsSynced(item.localIds);
        
        // Notification pour opérations critiques
        if (item.type === 'CRITICAL') {
          showNotification('Synchronisation réussie', { type: 'success' });
        }
        
      } catch (error) {
        // Gestion des erreurs
        if (error.response?.status === 409) {
          // Conflit : résolution manuelle
          await handleConflict(item, error.response.data);
        } else if (error.response?.status === 422) {
          // Validation : notifier l'utilisateur
          await handleValidationError(item, error.response.data);
        } else {
          // Erreur réseau : retry avec backoff
          item.retryCount++;
          if (item.retryCount < MAX_RETRIES) {
            this.queue.push(item);
            await exponentialBackoff(item.retryCount);
          } else {
            // Échec final : notifier l'utilisateur
            await handleFinalFailure(item, error);
          }
        }
      }
    }
    
    this.processing = false;
  }
}
```

### 4. Résolution de Conflit

```typescript
// services/conflictResolutionService.ts

export async function handleConflict(
  queueItem: QueueItem,
  conflictData: ConflictData
): Promise<void> {
  const { conflict_type, local_version, server_version } = conflictData.details;
  
  switch (conflict_type) {
    case 'version_mismatch':
      if (queueItem.type === 'CRITICAL') {
        // Pour opérations critiques : demande de résolution manuelle
        showConflictDialog({
          title: 'Conflit de version',
          message: `Version locale: ${local_version}, Version serveur: ${server_version}`,
          actions: [
            {
              label: 'Rafraîchir depuis le serveur',
              action: () => refreshFromServer(queueItem)
            },
            {
              label: 'Fusionner manuellement',
              action: () => openManualMerge(queueItem)
            },
            {
              label: 'Annuler',
              action: () => cancelOperation(queueItem)
            }
          ]
        });
      } else {
        // Pour opérations standard : last-write-wins
        await refreshFromServer(queueItem);
      }
      break;
      
    case 'status_mismatch':
      // Toujours demande de résolution pour conflits de statut
      showConflictDialog({
        title: 'Conflit de statut',
        message: `Statut local: ${conflictData.details.local_status}, Statut serveur: ${conflictData.details.server_status}`,
        actions: [
          {
            label: 'Rafraîchir depuis le serveur',
            action: () => refreshFromServer(queueItem)
          },
          {
            label: 'Annuler',
            action: () => cancelOperation(queueItem)
          }
        ]
      });
      break;
  }
}
```

---

## Checklist d'Implémentation Mobile

### Phase 1 : Infrastructure
- [ ] Créer `typeEvenementMappingService.ts` avec mapping en mémoire
- [ ] Créer `validationService.ts` avec validations locales
- [ ] Créer `offlineQueueService.ts` avec gestion prioritaire
- [ ] Implémenter `conflictResolutionService.ts`
- [ ] Ajouter observables de connectivité
- [ ] Créer composants d'indicateurs de sync

### Phase 2 : Opérations Standard
- [ ] Modifier `animalService.ts` pour création optimiste
- [ ] Modifier `evenementService.ts` pour création optimiste
- [ ] Modifier `naissanceService.ts` pour création optimiste
- [ ] Ajouter marquage `sync_status: 'pending'`

### Phase 3 : Opérations Critiques
- [ ] Modifier `AnimalVenteScreen.tsx` pour validation stricte
- [ ] Modifier `AnimalAchatScreen.tsx` pour validation stricte
- [ ] Modifier `AnimalDecesScreen.tsx` pour validation stricte
- [ ] Modifier `AnimalPerteScreen.tsx` pour validation stricte
- [ ] Modifier `AnimalAbattageScreen.tsx` pour validation stricte
- [ ] Ajouter marquage `sync_status: 'pending_critical'`
- [ ] Ajouter création de transactions locales

### Phase 4 : Sync et Conflits
- [ ] Modifier `watermelonSync.ts` pour priorité critique
- [ ] Implémenter retry avec exponential backoff
- [ ] Ajouter gestion des codes d'erreur spécifiques
- [ ] Créer interfaces de résolution de conflits

### Phase 5 : UX
- [ ] Ajouter indicateurs visuels de statut sync
- [ ] Implémenter notifications in-app
- [ ] Ajouter messages d'erreur explicites
- [ ] Créer écrans de résolution de conflits

---

## Notes Importantes

1. **Ordre des opérations** : Toujours créer l'animal avant l'événement, et l'événement avant la transaction
2. **Versioning** : Incrémenter la version à chaque modification locale
3. **Rollback** : En cas d'erreur, annuler toutes les créations locales
4. **Persistence** : Ne pas supprimer les mutations locales avant confirmation serveur
5. **Testing** : Tester scénarios offline, online, et reconnexion

---

## Support et Dépannage

### Problèmes Courants

**Q: La transaction n'apparaît pas après sync**
- Vérifier que `evenement_id` est correctement lié
- Vérifier que la transaction a `sync_status: 'synced'`
- Vérifier les logs de sync pour erreurs

**Q: Conflit de version répété**
- Vérifier que la version est incrémentée localement
- Vérifier que le sync push inclut la version correcte
- Vérifier que le backend renvoie la nouvelle version

**Q: Validation échoue offline**
- Vérifier que les données de référence sont synchronisées
- Vérifier que les IDs locaux correspondent aux IDs serveur
- Implémenter un fallback avec données par défaut

---

**Document Version:** 1.0  
**Dernière Mise à Jour:** 2024-07-15  
**Auteur:** Cascade AI Assistant  
**Projet:** FasoLivestock API
