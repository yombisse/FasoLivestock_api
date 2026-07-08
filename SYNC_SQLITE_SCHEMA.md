# FasoLivestock API - Schéma SQLite pour Synchronisation Offline-First

## Tables concernées par la synchronisation

Le système de synchronisation gère 10 tables divisées en 2 catégories :

### 1. Tables Business (avec farm_id)
Ces tables sont spécifiques à chaque ferme et nécessitent le contexte de ferme.

### 2. Tables Reference (sans farm_id)
Ces tables sont partagées entre toutes les fermes (données de référence).

---

## 1. Tables Business

### animals

```sql
CREATE TABLE animals (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT NOT NULL,
    nom TEXT,
    race TEXT,
    sexe TEXT CHECK(sexe IN ('male', 'femelle')),
    date_naissance TEXT,  -- YYYY-MM-DD
    poids REAL,
    statut TEXT CHECK(statut IN ('ACTIF', 'VENDU', 'MORT', 'PERDU')) DEFAULT 'ACTIF',
    espece_id TEXT NOT NULL,
    lot_id TEXT,
    mother_id TEXT,
    numero_identification TEXT,
    photo TEXT,
    naissance_id TEXT,
    origine TEXT CHECK(origine IN ('enregistrement', 'achat', 'naissance')) DEFAULT 'enregistrement',
    etat_sante TEXT CHECK(etat_sante IN ('SAIN', 'MALADE', 'QUARANTAINE')) DEFAULT 'SAIN',
    farm_source_id TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_animals_farm_id ON animals(farm_id);
CREATE INDEX idx_animals_espece_id ON animals(espece_id);
CREATE INDEX idx_animals_lot_id ON animals(lot_id);
CREATE INDEX idx_animals_mother_id ON animals(mother_id);
CREATE INDEX idx_animals_statut ON animals(statut);
CREATE INDEX idx_animals_sync_status ON animals(sync_status);
CREATE INDEX idx_animals_version ON animals(version);
CREATE UNIQUE INDEX idx_animals_farm_identification ON animals(farm_id, numero_identification);
```

### transactions

```sql
CREATE TABLE transactions (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT NOT NULL,
    type_transaction TEXT CHECK(type_transaction IN ('ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT')) DEFAULT 'ENTREE',
    montant REAL NOT NULL,
    date_transaction TEXT NOT NULL,  -- YYYY-MM-DD
    user_id TEXT NOT NULL,
    animal_id TEXT,
    categorie_id TEXT NOT NULL,
    description TEXT,
    evenement_id TEXT,
    tiers TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_transactions_farm_id ON transactions(farm_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_animal_id ON transactions(animal_id);
CREATE INDEX idx_transactions_categorie_id ON transactions(categorie_id);
CREATE INDEX idx_transactions_date_transaction ON transactions(date_transaction);
CREATE INDEX idx_transactions_user_date ON transactions(user_id, date_transaction);
CREATE INDEX idx_transactions_sync_status ON transactions(sync_status);
CREATE INDEX idx_transactions_version ON transactions(version);
CREATE INDEX idx_transactions_evenement_id ON transactions(evenement_id);
```

### evenements

```sql
CREATE TABLE evenements (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT NOT NULL,
    type_evenement_id TEXT NOT NULL,
    animal_id TEXT NOT NULL,
    date_evenement TEXT NOT NULL,  -- YYYY-MM-DD
    description TEXT,
    cout REAL,
    farm_destination_id TEXT,
    statut_avant TEXT CHECK(statut_avant IN ('ACTIF', 'VENDU', 'MORT', 'PERDU')),
    statut_apres TEXT CHECK(statut_apres IN ('ACTIF', 'VENDU', 'MORT', 'PERDU')),
    categorie TEXT CHECK(categorie IN ('MOUVEMENT', 'REPRODUCTION', 'SANITAIRE')),
    transaction_id TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_evenements_farm_id ON evenements(farm_id);
CREATE INDEX idx_evenements_type_evenement_id ON evenements(type_evenement_id);
CREATE INDEX idx_evenements_animal_id ON evenements(animal_id);
CREATE INDEX idx_evenements_date_evenement ON evenements(date_evenement);
CREATE INDEX idx_evenements_sync_status ON evenements(sync_status);
CREATE INDEX idx_evenements_farm_destination_id ON evenements(farm_destination_id);
CREATE INDEX idx_evenements_version ON evenements(version);
```

### lots

```sql
CREATE TABLE lots (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT NOT NULL,
    nom_lot TEXT NOT NULL,
    nombre INTEGER DEFAULT 0,
    description TEXT,
    espece_id TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_lots_farm_id ON lots(farm_id);
CREATE INDEX idx_lots_sync_status ON lots(sync_status);
CREATE INDEX idx_lots_version ON lots(version);
```

### notifications

```sql
CREATE TABLE notifications (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT,
    animal_id TEXT,
    titre TEXT,
    message TEXT NOT NULL,
    sent_at TEXT,  -- ISO 8601
    evenement_id TEXT,
    type TEXT CHECK(type IN ('VACCINATION', 'TRAITEMENT', 'NAISSANCE', 'MOUVEMENT', 'ALERTE', 'INFO')) DEFAULT 'INFO',
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_notifications_farm_id ON notifications(farm_id);
CREATE INDEX idx_notifications_animal_id ON notifications(animal_id);
CREATE INDEX idx_notifications_sent_at ON notifications(sent_at);
CREATE INDEX idx_notifications_sync_status ON notifications(sync_status);
CREATE INDEX idx_notifications_evenement_id ON notifications(evenement_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_version ON notifications(version);
```

### naissances

```sql
CREATE TABLE naissances (
    id TEXT PRIMARY KEY,  -- UUID
    farm_id TEXT NOT NULL,
    mother_id TEXT NOT NULL,
    date_naissance TEXT NOT NULL,  -- YYYY-MM-DD
    nombre_petits INTEGER DEFAULT 0,
    poids_naissance REAL,
    observation TEXT,
    evenement_id TEXT,
    date_saillie TEXT,  -- YYYY-MM-DD
    pere_id TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_naissances_farm_id ON naissances(farm_id);
CREATE INDEX idx_naissances_mother_id ON naissances(mother_id);
CREATE INDEX idx_naissances_evenement_id ON naissances(evenement_id);
CREATE INDEX idx_naissances_date_naissance ON naissances(date_naissance);
CREATE INDEX idx_naissances_sync_status ON naissances(sync_status);
CREATE INDEX idx_naissances_version ON naissances(version);
```

---

## 2. Tables Reference

### especes

```sql
CREATE TABLE especes (
    id TEXT PRIMARY KEY,  -- UUID
    nom TEXT UNIQUE NOT NULL,
    description TEXT,
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_especes_sync_status ON especes(sync_status);
```

### categories

```sql
CREATE TABLE categories (
    id TEXT PRIMARY KEY,  -- UUID
    nom_categorie TEXT UNIQUE NOT NULL,
    type TEXT CHECK(type IN ('REVENU', 'DEPENSE')),
    description TEXT,
    farm_id TEXT,  -- NULL pour catégories globales, UUID pour catégories spécifiques à une ferme
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_categories_sync_status ON categories(sync_status);
CREATE INDEX idx_categories_farm_id ON categories(farm_id);
CREATE INDEX idx_categories_version ON categories(version);
```

### type_evenements

```sql
CREATE TABLE type_evenements (
    id TEXT PRIMARY KEY,  -- UUID
    nom_type TEXT UNIQUE NOT NULL,
    description TEXT,
    categorie TEXT CHECK(categorie IN ('MOUVEMENT', 'REPRODUCTION', 'SANITAIRE')),
    farm_id TEXT,  -- NULL pour types globaux, UUID pour types spécifiques à une ferme
    
    -- Champs de synchronisation
    sync_status TEXT CHECK(sync_status IN ('pending', 'synced', 'conflict')) DEFAULT 'synced',
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TEXT,  -- ISO 8601
    updated_at TEXT,  -- ISO 8601
    deleted_at TEXT   -- ISO 8601 (soft delete)
);

-- Indexes
CREATE INDEX idx_type_evenements_sync_status ON type_evenements(sync_status);
CREATE INDEX idx_type_evenements_farm_id ON type_evenements(farm_id);
CREATE INDEX idx_type_evenements_version ON type_evenements(version);
```

---

## Tables de synchronisation (optionnelles)

### sync_queue

Table locale pour gérer la file d'attente des changements à synchroniser.

```sql
CREATE TABLE sync_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL,
    record_id TEXT NOT NULL,
    action TEXT CHECK(action IN ('create', 'update', 'delete')) NOT NULL,
    data TEXT NOT NULL,  -- JSON string
    status TEXT CHECK(status IN ('pending', 'synced', 'failed')) DEFAULT 'pending',
    error_message TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    synced_at TEXT
);

-- Indexes
CREATE INDEX idx_sync_queue_status ON sync_queue(status);
CREATE INDEX idx_sync_queue_table ON sync_queue(table_name);
```

### sync_metadata

Table locale pour stocker les métadonnées de synchronisation.

```sql
CREATE TABLE sync_metadata (
    id INTEGER PRIMARY KEY,
    last_sync_at TEXT,  -- ISO 8601
    last_push_at TEXT,  -- ISO 8601
    last_pull_at TEXT,  -- ISO 8601
    farm_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    sync_token TEXT
);

-- Insert initial record
INSERT INTO sync_metadata (id, farm_id, user_id) VALUES (1, '', '');
```

---

## Notes importantes pour l'implémentation mobile

### 1. UUID
- Utilisez des UUID v4 pour les IDs
- Stockez-les comme TEXT dans SQLite
- Générez-les côté mobile pour les créations offline

### 2. Timestamps
- Utilisez le format ISO 8601 : `YYYY-MM-DDTHH:MM:SS.sssZ`
- Stockez-les comme TEXT dans SQLite
- Convertissez en DateTime natif côté mobile

### 3. Soft Deletes
- Utilisez `deleted_at` pour marquer les suppressions
- NULL = enregistrement actif
- Non NULL = enregistrement supprimé
- Incluez les enregistrements supprimés dans le sync

### 4. Versioning
- Incrémentez `version` à chaque modification
- Envoyez la version actuelle lors du sync push
- Le serveur vérifie la version pour détecter les conflits

### 5. Sync Status
- `pending` : En attente de synchronisation
- `synced` : Synchronisé avec le serveur
- `conflict` : Conflit détecté (résolution manuelle requise)

### 6. Contraintes CHECK
- SQLite supporte les contraintes CHECK
- Utilisez-les pour valider les enums côté mobile
- Fallback : validez dans le code si CHECK non supporté

### 7. Indexes
- Créez tous les indexes listés pour optimiser les performances
- Les indexes sont particulièrement importants pour les filtres et la sync

### 8. Relations
- Les clés étrangères sont stockées comme TEXT (UUID)
- Validez les relations dans le code (SQLite FOREIGN KEY support est limité)
- Utilisez des transactions pour maintenir l'intégrité

### 9. Données de référence
- Les tables reference (especes, categories, type_evenements) sont partagées
- Sync-les complètement lors du premier pull
- Sync-les incrémentalement ensuite

### 10. Contexte de ferme
- Toutes les requêtes business doivent inclure `farm_id`
- Filtrez toutes les données business par `farm_id` côté mobile
- Le header `X-Farm-Id` est requis pour les appels API

---

## Stratégie de synchronisation recommandée

### Initial Sync (Première connexion)
1. Pull complet des tables reference
2. Pull complet des tables business pour la ferme courante
3. Stocker `last_sync_at` dans `sync_metadata`

### Sync Incrémentiel
1. Pull : Envoyer `last_sync_at` pour récupérer uniquement les modifications
2. Push : Envoyer les changements locaux depuis `sync_queue`
3. Mettre à jour `last_sync_at` après sync réussi

### Gestion des conflits
1. Détecter les conflits via le champ `version`
2. Marquer les enregistrements en conflit avec `sync_status = 'conflict'`
3. Proposer une interface de résolution à l'utilisateur
4. Forcer la version gagnante et resync

### Offline-First
1. Toutes les opérations CRUD fonctionnent offline
2. Stocker les changements dans `sync_queue`
3. Sync automatique lors de la reconnexion
4. Indicateur de sync status dans l'UI

---

## Exemple de workflow sync

```javascript
// Pull
POST /api/sync/pull
Headers: {
  Authorization: Bearer {token},
  X-Farm-Id: {farm_uuid}
}
Body: {
  last_sync_at: "2024-01-01T00:00:00Z",
  farm_id: "uuid"
}

// Push
POST /api/sync/push
Headers: {
  Authorization: Bearer {token},
  X-Farm-Id: {farm_uuid}
}
Body: {
  changes: [
    {
      table: "animals",
      action: "create",
      data: {
        id: "uuid",
        farm_id: "uuid",
        nom: "Bovin #1",
        sync_status: "pending",
        version: 1
      }
    }
  ],
  last_sync_at: "2024-01-01T00:00:00Z",
  farm_id: "uuid"
}
```

---

## Validation des données

### Avant sync push
- Valider tous les champs requis
- Vérifier les contraintes CHECK
- S'assurer que les UUID sont valides
- Vérifier que les relations existent

### Après sync pull
- Valider la structure des données reçues
- Gérer les enregistrements supprimés (deleted_at non null)
- Mettre à jour les indexes si nécessaire
- Rafraîchir l'UI avec les nouvelles données

---

## Performance

### Optimisations recommandées
1. Utiliser des transactions pour les opérations bulk
2. Préparer les statements SQL récurrents
3. Utiliser des indexes pour les filtres fréquents
4. Limiter la taille des payloads sync (pagination si nécessaire)
5. Compresser les données si payload > 1MB

### Monitoring
- Logger les temps de sync
- Surveiller la taille de la base locale
- Alertes en cas d'échec de sync répété
- Statistiques d'utilisation offline vs online
