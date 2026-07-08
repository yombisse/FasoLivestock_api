# Documentation Module Santé & Rappels - Mobile Offline-First

## Tables SQLite à créer côté mobile

### 1. Table `sante_rappels` (Rappels sanitaires)

```sql
CREATE TABLE sante_rappels (
    id TEXT PRIMARY KEY,
    farm_id TEXT NOT NULL,
    animal_id TEXT NOT NULL,
    type_rappel TEXT NOT NULL, -- 'VACCINATION', 'TRAITEMENT', 'CONTROLE'
    date_prevue TEXT NOT NULL, -- YYYY-MM-DD
    date_realisee TEXT, -- YYYY-MM-DD (nullable)
    statut TEXT NOT NULL DEFAULT 'EN_ATTENTE', -- 'EN_ATTENTE', 'REALISE', 'EN_RETARD'
    note TEXT,
    evenement_id TEXT, -- Lien vers l'événement réalisé (nullable)
    sync_status TEXT DEFAULT 'synced', -- 'pending', 'synced', 'conflict'
    last_modified_by TEXT,
    version INTEGER DEFAULT 1,
    deleted_at TEXT, -- Soft delete (nullable)
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id)
);

-- Index pour les requêtes fréquentes
CREATE INDEX idx_sante_rappels_farm ON sante_rappels(farm_id);
CREATE INDEX idx_sante_rappels_animal ON sante_rappels(animal_id);
CREATE INDEX idx_sante_rappels_statut ON sante_rappels(statut);
CREATE INDEX idx_sante_rappels_date_prevue ON sante_rappels(date_prevue);
CREATE INDEX idx_sante_rappels_sync ON sante_rappels(sync_status);
```

### 2. Table `evenements` (Déjà existante - à vérifier)

La table `evenements` existe déjà côté mobile. S'assurer qu'elle contient :
- `metadonnees` (TEXT/JSON) pour stocker les données spécifiques par type
- `categorie` (TEXT) avec valeurs : 'MOUVEMENT', 'REPRODUCTION', 'SANITAIRE', 'AUTRE'

---

## Endpoints API Module Santé

### A. Endpoints CRUD (Données à synchroniser)

#### Rappels Sanitaires

| Endpoint | Méthode | Description | Sync |
|----------|---------|-------------|------|
| `/sante/rappels` | GET | Liste des rappels sanitaires | ⬇️ Download |
| `/sante/rappels` | POST | Créer un rappel sanitaire | ⬆️ Upload |
| `/sante/rappels/{rappel}` | GET | Détail d'un rappel | ⬇️ Download |
| `/sante/rappels/{rappel}` | PUT | Modifier un rappel | ⬆️ Upload |
| `/sante/rappels/{rappel}` | DELETE | Supprimer un rappel | ⬆️ Upload |
| `/sante/rappels/trashed` | GET | Liste des rappels archivés | ⬇️ Download |
| `/sante/rappels/{id}/restore` | POST | Restaurer un rappel archivé | ⬆️ Upload |
| `/sante/rappels/{rappel}/marquer-realise` | POST | Marquer un rappel comme réalisé | ⬆️ Upload |

#### Événements Sanitaires

| Endpoint | Méthode | Description | Sync |
|----------|---------|-------------|------|
| `/sante/evenements` | GET | Liste des événements sanitaires | ⬇️ Download |
| `/sante/evenements` | POST | Créer un événement sanitaire | ⬆️ Upload |
| `/sante/evenements/{evenement}` | GET | Détail d'un événement | ⬇️ Download |
| `/sante/evenements/{evenement}` | PUT | Modifier un événement | ⬆️ Upload |
| `/sante/evenements/{evenement}` | DELETE | Supprimer un événement | ⬆️ Upload |

---

### B. Endpoints Calculés (Statistiques & Filtres - Pas de sync)

#### Rappels Sanitaires - Filtres

| Endpoint | Méthode | Description | Usage Mobile |
|----------|---------|-------------|--------------|
| `/sante/rappels/a-venir` | GET | Rappels à venir (7 jours par défaut) | Affichage alertes dashboard |
| `/sante/rappels/en-retard` | GET | Rappels en retard | Affichage alertes urgentes |

#### Historique & Statistiques par Animal

| Endpoint | Méthode | Description | Usage Mobile |
|----------|---------|-------------|--------------|
| `/sante/animals/{animal}/historique-medical` | GET | Historique médical complet d'un animal | Fiche animal |
| `/sante/animals/{animal}/statistiques-sanitaires` | GET | Statistiques sanitaires d'un animal | Fiche animal |
| `/sante/animals/{animal}/vaccinations` | GET | Liste des vaccinations d'un animal | Fiche animal |
| `/sante/animals/{animal}/traitements` | GET | Liste des traitements d'un animal | Fiche animal |
| `/sante/animals/{animal}/maladies` | GET | Liste des maladies d'un animal | Fiche animal |
| `/sante/animals/{animal}/consultations` | GET | Liste des consultations d'un animal | Fiche animal |

#### Statistiques Ferme

| Endpoint | Méthode | Description | Usage Mobile |
|----------|---------|-------------|--------------|
| `/sante/resume-ferme` | GET | Résumé sanitaire de la ferme courante | Dashboard santé |
| `/sante/alertes-ferme` | GET | Alertes sanitaires de la ferme | Dashboard santé |
| `/sante/statistiques-par-type` | GET | Statistiques sanitaires par type | Dashboard santé |

---

## Formats de Données

### Création d'un Rappel Sanitaire

**Endpoint** : `POST /sante/rappels`

```json
{
  "animal_id": "uuid-de-l-animal",
  "type_rappel": "VACCINATION", // ou "TRAITEMENT", "CONTROLE"
  "date_prevue": "2024-12-15",
  "note": "Rappel vaccination annuelle"
}
```

### Création d'un Événement Sanitaire

**Endpoint** : `POST /sante/evenements`

#### Vaccination
```json
{
  "animal_id": "uuid-de-l-animal",
  "type": "vaccination",
  "date_evenement": "2024-12-15",
  "description": "Vaccination contre la rage",
  "cout": 5000,
  "metadonnees": {
    "nom_vaccin": "Rage",
    "veterinaire": "Dr. Koné",
    "dosage": "5ml",
    "lot_vaccin": "LOT123"
  }
}
```

#### Traitement
```json
{
  "animal_id": "uuid-de-l-animal",
  "type": "traitement",
  "date_evenement": "2024-12-15",
  "description": "Traitement antibiotique",
  "cout": 15000,
  "metadonnees": {
    "nom_medicament": "Antibiotique X",
    "veterinaire": "Dr. Koné",
    "dosage": "10ml",
    "duree": "7 jours",
    "frequence": "2x/jour"
  }
}
```

#### Maladie
```json
{
  "animal_id": "uuid-de-l-animal",
  "type": "maladie",
  "date_evenement": "2024-12-15",
  "description": "Fièvre aphteuse",
  "cout": 20000,
  "metadonnees": {
    "nom_maladie": "Fièvre aphteuse",
    "symptomes": "Fièvre élevée, lésions buccales",
    "veterinaire": "Dr. Koné",
    "gravite": "moderee"
  }
}
```

#### Contrôle
```json
{
  "animal_id": "uuid-de-l-animal",
  "type": "controle",
  "date_evenement": "2024-12-15",
  "description": "Contrôle de routine",
  "cout": 3000,
  "metadonnees": {
    "type_controle": "Examen général",
    "veterinaire": "Dr. Koné",
    "resultat": "Animal en bonne santé"
  }
}
```

### Marquer un Rappel comme Réalisé

**Endpoint** : `POST /sante/rappels/{rappel}/marquer-realise`

```json
{
  "evenement_id": "uuid-de-l-evenement-cree"
}
```

---

## Stratégie de Synchronisation

### Tables à synchroniser (bidirectionnelle)
1. **sante_rappels** - CRUD complet
2. **evenements** (déjà existante) - CRUD complet

### Endpoints calculés (pas de sync)
- Tous les endpoints de statistiques et filtres
- Ces données sont calculées à partir des tables locales

### Workflow Offline-First

1. **Création offline** : Enregistrer dans SQLite avec `sync_status = 'pending'`
2. **Synchronisation** : Envoyer les données pending au serveur
3. **Réception** : Télécharger les données mises à jour du serveur
4. **Conflits** : Gérer via le champ `version` et `sync_status = 'conflict'`

---

## Permissions Requises

- `sante.view` : Lecture des données santé
- `sante.create` : Création de rappels/événements
- `sante.update` : Modification de rappels/événements
- `sante.delete` : Suppression de rappels/événements
