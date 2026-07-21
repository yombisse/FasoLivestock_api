# Guide de Validation Frontend - Rappels Sanitaires et Reproductifs

## Contexte

Les rappels sanitaires et reproductifs sont créés automatiquement par le backend lors de la création d'événements sanitaires (Vaccination, Traitement, Contrôle) et reproductifs (Gestation confirmée, Chaleur). Ils sont envoyés au frontend mobile via le sync pull et peuvent être gérés via l'API REST.

---

## Structure des Rappels

### Modèle SanteRappel

**Table** : `sante_rappels`

**Types de rappels sanitaires** :
- `VACCINATION` : Rappels de vaccination
- `TRAITEMENT` : Rappels de traitement
- `CONTROLE` : Rappels de contrôle

**Types de rappels reproductifs** :
- `MISE_BAS` : Rappels de mise bas (créés automatiquement lors d'une Gestation confirmée)
- `CHALEUR` : Rappels de chaleur (créés automatiquement lors d'une chaleur)

**Statuts** :
- `EN_ATTENTE` : Rappel en attente de réalisation
- `REALISE` : Rappel réalisé (lié à un événement)
- `EN_RETARD` : Rappel en retard (date_prevue passée, statut mis à jour par job schedulé)

**Champs** :
```json
{
  "id": "string (16-20 chars)",
  "farm_id": "string (16-20 chars)",
  "animal_id": "string (16-20 chars)",
  "type_rappel": "string (VACCINATION|TRAITEMENT|CONTROLE|MISE_BAS|CHALEUR)",
  "date_prevue": "YYYY-MM-DD",
  "date_realisee": "YYYY-MM-DD (nullable)",
  "statut": "string (EN_ATTENTE|REALISE|EN_RETARD)",
  "note": "string (nullable)",
  "evenement_id": "string (nullable)",
  "sync_status": "string (pending|synced)",
  "last_modified_by": "string (nullable)",
  "version": "integer",
  "created_at": "YYYY-MM-DD HH:mm:ss",
  "updated_at": "YYYY-MM-DD HH:mm:ss",
  "deleted_at": "YYYY-MM-DD HH:mm:ss (nullable)"
}
```

---

## Création Automatique des Rappels

### Rappels Sanitaires

Les rappels sanitaires sont créés automatiquement par l'observer `EvenementObserver` lors de la création d'événements sanitaires :

**Vaccination** :
- Crée un rappel de type `VACCINATION`
- Date prévue = date_evenement + intervalle_vaccin_jours (paramètre espèce)
- Statut initial = `EN_ATTENTE`

**Traitement** :
- Crée un rappel de type `TRAITEMENT`
- Date prévue = date_evenement + 30 jours (par défaut)
- Statut initial = `EN_ATTENTE`

**Contrôle** :
- Crée un rappel de type `CONTROLE`
- Date prévue = date_evenement + 90 jours (par défaut)
- Statut initial = `EN_ATTENTE`

### Rappels Reproductifs

Les rappels reproductifs sont créés automatiquement par l'observer `EvenementObserver` lors de la création d'événements reproductifs :

**Gestation confirmée** :
- Crée un rappel de type `MISE_BAS`
- Date prévue = date_evenement + duree_gestation_jours (paramètre espèce)
- Note = "Mise bas prévue selon durée de gestation de l'espèce"
- Statut initial = `EN_ATTENTE`

**Chaleur** (animal femelle) :
- Crée un rappel de type `CHALEUR`
- Date prévue = date_evenement + 21 jours (intervalle moyen)
- Note = "Chaleur suivante estimée (intervalle moyen 21 jours)"
- Statut initial = `EN_ATTENTE`

---

## Endpoints API

### 1. Lister les Rappels

**Endpoint** : `GET /api/sante/rappels`

**Paramètres de filtre** (query params) :
- `animal_id` : ID de l'animal (optionnel)
- `type_rappel` : Type de rappel (optionnel)
- `statut` : Statut du rappel (optionnel)
- `date_debut` : Date de début de période (optionnel)
- `date_fin` : Date de fin de période (optionnel)
- `en_retard` : true/false pour filtrer les rappels en retard (optionnel)
- `per_page` : Nombre de résultats par page (défaut: 15)

**Réponse** :
```json
{
  "success": true,
  "message": "Rappels sanitaires récupérés avec succès.",
  "data": {
    "rappels": [
      {
        "id": "rappel_id_1",
        "farm_id": "farm_id",
        "animal_id": "animal_id",
        "type_rappel": "VACCINATION",
        "date_prevue": "2026-08-15",
        "date_realisee": null,
        "statut": "EN_ATTENTE",
        "note": "Rappel vaccination",
        "evenement_id": "evenement_id",
        "animal": {
          "id": "animal_id", 
          "nom": "Vache 1",
          "espece": "Bovin"
        },
        "est_en_retard": false,
        "jours_restants": 32
      }
    ],
    "meta": {
      "total": 25,
      "per_page": 15,
      "current_page": 1,
      "last_page": 2
    }
  }
}
```

---

### 2. Créer un Rappel Manuel

**Endpoint** : `POST /api/sante/rappels`

**Permission requise** : `sante_rappels.create`

**Champs requis** :
```json
{
  "farm_id": "string (16-20 chars, required)",
  "animal_id": "string (16-20 chars, required)",
  "type_rappel": "string (VACCINATION|TRAITEMENT|CONTROLE|MISE_BAS|CHALEUR, required)",
  "date_prevue": "YYYY-MM-DD (required)"
}
```

**Champs optionnels** :
```json
{
  "note": "string (optional)",
  "evenement_id": "string (optional)"
}
```

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire créé avec succès.",
  "data": {
    "id": "rappel_id",
    "farm_id": "farm_id",
    "animal_id": "animal_id",
    "type_rappel": "VACCINATION",
    "date_prevue": "2026-08-15",
    "statut": "EN_ATTENTE",
    "created_at": "2026-07-15 10:00:00"
  }
}
```

---

### 3. Détail d'un Rappel

**Endpoint** : `GET /api/sante/rappels/{id}`

**Permission requise** : `sante_rappels.view`

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire récupéré avec succès.",
  "data": {
    "id": "rappel_id",
    "farm_id": "farm_id",
    "animal_id": "animal_id",
    "type_rappel": "VACCINATION",
    "date_prevue": "2026-08-15",
    "date_realisee": null,
    "statut": "EN_ATTENTE",
    "note": "Rappel vaccination",
    "evenement_id": null,
    "animal": {
      "id": "animal_id",
      "nom": "Vache 1",
      "sexe": "femelle",
      "date_naissance": "2024-01-15"
    },
    "farm": {
      "id": "farm_id",
      "name": "Ferme Test"
    },
    "evenement": null
  }
}
```

---

### 4. Modifier un Rappel

**Endpoint** : `PUT /api/sante/rappels/{id}`

**Permission requise** : `sante_rappels.update`

**Champs modifiables** :
```json
{
  "date_prevue": "YYYY-MM-DD (optional)",
  "note": "string (optional)",
  "statut": "EN_ATTENTE|REALISE|EN_RETARD (optional)"
}
```

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire mis à jour avec succès.",
  "data": {
    "id": "rappel_id",
    "date_prevue": "2026-08-20",
    "statut": "EN_ATTENTE",
    "updated_at": "2026-07-15 10:30:00"
  }
}
```

---

### 5. Marquer un Rappel comme Réalisé (via Événement)

**Endpoint** : `POST /api/sante/rappels/{id}/marquer-realise`

**Permission requise** : `sante_rappels.update`

**Champs requis** :
```json
{
  "type_evenement_id": "string (16-20 chars, required)"
}
```

**Champs optionnels** :
```json
{
  "date_evenement": "YYYY-MM-DD (optional, défaut: aujourd'hui)",
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)"
}
```

**Comportement** :
1. Crée un nouvel événement sanitaire avec les données fournies
2. Met à jour le rappel avec :
   - `statut` = `REALISE`
   - `date_realisee` = date_evenement
   - `evenement_id` = ID de l'événement créé

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire marqué comme réalisé avec succès.",
  "data": {
    "id": "rappel_id",
    "statut": "REALISE",
    "date_realisee": "2026-07-15",
    "evenement_id": "evenement_id",
    "evenement": {
      "id": "evenement_id",
      "type_evenement_id": "type_id",
      "date_evenement": "2026-07-15",
      "description": "Vaccination réalisée"
    }
  }
}
```

---

### 6. Reprogrammer un Rappel

**Endpoint** : `POST /api/sante/rappels/{id}/reprogrammer`

**Permission requise** : `sante_rappels.update`

**Champs requis** :
```json
{
  "nouvelle_date": "YYYY-MM-DD (required)"
}
```

**Comportement** :
- Met à jour `date_prevue` avec la nouvelle date
- Si le rappel était en retard (EN_RETARD), le statut repasse à EN_ATTENTE
- Incrémente la version pour la synchronisation

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire reprogrammé avec succès.",
  "data": {
    "id": "rappel_id",
    "date_prevue": "2026-09-01",
    "statut": "EN_ATTENTE",
    "updated_at": "2026-07-15 11:00:00"
  }
}
```

---

### 7. Rappels à Venir

**Endpoint** : `GET /api/sante/rappels/a-venir`

**Permission requise** : `sante_rappels.view`

**Paramètres** :
- `jours` : Nombre de jours à venir (défaut: 7)

**Réponse** :
```json
{
  "success": true,
  "message": "Rappels sanitaires à venir récupérés avec succès.",
  "data": {
    "rappels": [
      {
        "id": "rappel_id",
        "type_rappel": "VACCINATION",
        "date_prevue": "2026-07-20",
        "jours_restants": 5,
        "animal": {
          "id": "animal_id",
          "nom": "Vache 1"
        }
      }
    ],
    "periode_jours": 7
  }
}
```

---

### 8. Rappels en Retard

**Endpoint** : `GET /api/sante/rappels/en-retard`

**Permission requise** : `sante_rappels.view`

**Réponse** :
```json
{
  "success": true,
  "message": "Rappels sanitaires en retard récupérés avec succès.",
  "data": {
    "rappels": [
      {
        "id": "rappel_id",
        "type_rappel": "VACCINATION",
        "date_prevue": "2026-07-10",
        "jours_retard": 5,
        "animal": {
          "id": "animal_id",
          "nom": "Vache 1"
        }
      }
    ]
  }
}
```

---

### 9. Supprimer un Rappel

**Endpoint** : `DELETE /api/sante/rappels/{id}`

**Permission requise** : `sante_rappels.delete`

**Comportement** : Soft delete (deleted_at est renseigné)

**Réponse** :
```json
{
  "success": true,
  "message": "Rappel sanitaire archivé avec succès.",
  "data": null
}
```

---

## Synchronisation via Sync Pull

Les rappels sont envoyés au frontend mobile via le sync pull dans la table `sante_rappels` :

**Format WatermelonDB** :
```json
{
  "changes": {
    "sante_rappels": {
      "created": [
        {
          "id": "rappel_id",
          "farm_id": "farm_id",
          "animal_id": "animal_id",
          "type_rappel": "VACCINATION",
          "date_prevue": "2026-08-15",
          "date_realisee": null,
          "statut": "EN_ATTENTE",
          "note": "Rappel vaccination",
          "evenement_id": null,
          "created_at": "2026-07-15 10:00:00",
          "updated_at": "2026-07-15 10:00:00"
        }
      ],
      "updated": [],
      "deleted": []
    }
  },
  "timestamp": "2026-07-15T10:00:00.000000Z"
}
```

---

## Stratégie Frontend

### 1. Affichage des Rappels

**Charger les rappels depuis WatermelonDB** :
```typescript
const { rappels } = useRappels(); // Hook personnalisé

// Filtrer par statut
const rappelsEnAttente = rappels.filter(r => r.statut === 'EN_ATTENTE');
const rappelsEnRetard = rappels.filter(r => r.statut === 'EN_RETARD');
const rappelsRealises = rappels.filter(r => r.statut === 'REALISE');

// Calculer les jours restants
const joursRestants = (datePrevue: string) => {
  const today = new Date();
  const date = new Date(datePrevue);
  return Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
};
```

**Affichage avec indicateur d'urgence** :
```typescript
const getUrgenceColor = (rappel: Rappel) => {
  const jours = joursRestants(rappel.date_prevue);
  
  if (rappel.statut === 'EN_RETARD') return 'red';
  if (jours <= 3) return 'orange';
  if (jours <= 7) return 'yellow';
  return 'green';
};
```

---

### 2. Marquer un Rappel comme Réalisé

**Option 1 : Via l'endpoint dédié (recommandé)**

Cette méthode crée automatiquement l'événement sanitaire associé :

```typescript
async function marquerRappelRealise(rappelId: string, typeEvenementId: string) {
  try {
    const response = await api.post(`/sante/rappels/${rappelId}/marquer-realise`, {
      type_evenement_id: typeEvenementId,
      date_evenement: new Date().toISOString().split('T')[0],
      description: 'Réalisation du rappel',
      cout: 0
    });

    // Mettre à jour le rappel localement
    await database.write(async () => {
      const rappel = await database.get('sante_rappels').find(rappelId);
      await rappel.update(r => {
        r.statut = 'REALISE';
        r.date_realisee = new Date().toISOString().split('T')[0];
        r.evenement_id = response.data.data.evenement.id;
      });
    });

    return response.data;
  } catch (error) {
    console.error('Erreur lors du marquage du rappel:', error);
    throw error;
  }
}
```

**Option 2 : Via création d'événement + mise à jour manuelle**

Cette méthode est plus flexible mais nécessite deux opérations :

```typescript
async function marquerRappelRealiseManuel(rappelId: string, evenementData: any) {
  try {
    // 1. Créer l'événement sanitaire
    const evenement = await creerEvenementSanitaire(evenementData);

    // 2. Mettre à jour le rappel
    await database.write(async () => {
      const rappel = await database.get('sante_rappels').find(rappelId);
      await rappel.update(r => {
        r.statut = 'REALISE';
        r.date_realisee = evenement.date_evenement;
        r.evenement_id = evenement.id;
      });
    });

    return evenement;
  } catch (error) {
    console.error('Erreur lors du marquage du rappel:', error);
    throw error;
  }
}
```

---

### 3. Reprogrammer un Rappel

```typescript
async function reprogrammerRappel(rappelId: string, nouvelleDate: string) {
  try {
    const response = await api.post(`/sante/rappels/${rappelId}/reprogrammer`, {
      nouvelle_date: nouvelleDate
    });

    // Mettre à jour le rappel localement
    await database.write(async () => {
      const rappel = await database.get('sante_rappels').find(rappelId);
      await rappel.update(r => {
        r.date_prevue = nouvelleDate;
        r.statut = 'EN_ATTENTE'; // Repasse à EN_ATTENTE si c'était EN_RETARD
      });
    });

    return response.data;
  } catch (error) {
    console.error('Erreur lors de la reprogrammation:', error);
    throw error;
  }
}
```

---

### 4. Gestion des Statuts

**Mise à jour automatique des statuts EN_RETARD** :

Le backend exécute un job schedulé quotidiennement pour mettre à jour les statuts EN_RETARD. Le frontend doit rafraîchir la liste des rappels périodiquement ou après sync pull.

**Rafraîchissement local** :
```typescript
// Après sync pull, recalculer les statuts EN_RETARD localement
async function mettreAJourStatutsRetards() {
  await database.write(async () => {
    const rappels = await database.get('sante_rappels').query().fetch();
    
    for (const rappel of rappels) {
      if (rappel.statut === 'EN_ATTENTE') {
        const datePrevue = new Date(rappel.date_prevue);
        const aujourdHui = new Date();
        
        if (datePrevue < aujourdHui) {
          await rappel.update(r => {
            r.statut = 'EN_RETARD';
          });
        }
      }
    }
  });
}
```

---

### 5. Interface Utilisateur

**Écran de liste des rappels** :
```typescript
function RappelsScreen() {
  const { rappels, loading } = useRappels();
  
  const rappelsEnAttente = rappels.filter(r => r.statut === 'EN_ATTENTE');
  const rappelsEnRetard = rappels.filter(r => r.statut === 'EN_RETARD');
  
  return (
    <View>
      <Section title="En retard">
        {rappelsEnRetard.map(rappel => (
          <RappelCard 
            key={rappel.id} 
            rappel={rappel}
            onMarquerRealise={() => marquerRappelRealise(rappel.id, typeEvenementId)}
            onReprogrammer={() => reprogrammerRappel(rappel.id, nouvelleDate)}
          />
        ))}
      </Section>
      
      <Section title="À venir">
        {rappelsEnAttente.map(rappel => (
          <RappelCard 
            key={rappel.id} 
            rappel={rappel}
            onMarquerRealise={() => marquerRappelRealise(rappel.id, typeEvenementId)}
            onReprogrammer={() => reprogrammerRappel(rappel.id, nouvelleDate)}
          />
        ))}
      </Section>
    </View>
  );
}
```

---

## Checklist d'Intégration Frontend

### Sync Pull
- [ ] S'assurer que la table `sante_rappels` est incluse dans le schéma WatermelonDB
- [ ] Implémenter la synchronisation des rappels via sync pull
- [ ] Rafraîchir la liste des rappels après sync pull

### Affichage
- [ ] Charger les rappels depuis WatermelonDB
- [ ] Filtrer par statut (EN_ATTENTE, EN_RETARD, REALISE)
- [ ] Calculer les jours restants/jours de retard
- [ ] Afficher les indicateurs d'urgence (couleurs)
- [ ] Afficher les informations de l'animal associé

### Actions
- [ ] Implémenter le marquage comme réalisé via endpoint dédié
- [ ] Implémenter la reprogrammation des rappels
- [ ] Implémenter la suppression (archivage) des rappels
- [ ] Gérer les erreurs API avec affichage utilisateur

### Statuts
- [ ] Rafraîchir les statuts EN_RETARD après sync pull
- [ ] Calculer localement les statuts EN_RETARD pour affichage immédiat
- [ ] Mettre à jour l'interface lors du changement de statut

### Notifications
- [ ] Envoyer des notifications locales pour les rappels urgents (<= 3 jours)
- [ ] Envoyer des notifications locales pour les rappels en retard
- [ ] Permettre à l'utilisateur de configurer les préférences de notification

---

## Notes Importantes

1. **Création automatique** : Les rappels sont créés automatiquement par le backend. Le frontend ne doit pas créer de rappels manuellement sauf cas exceptionnels.

2. **Marquage comme réalisé** : Utilisez l'endpoint dédié `/marquer-realise` pour créer automatiquement l'événement sanitaire associé. Cela garantit la cohérence des données.

3. **Statuts EN_RETARD** : Le backend met à jour les statuts EN_RETARD via un job schedulé. Le frontend doit rafraîchir les données après sync pull.

4. **Sync pull** : Les rappels sont envoyés dans la table `sante_rappels` du sync pull. Assurez-vous que cette table est synchronisée.

5. **Permissions** : Les endpoints nécessitent des permissions Spatie (`sante_rappels.view`, `sante_rappels.update`, etc.).

6. **Types de rappels** : Les types reproductifs (MISE_BAS, CHALEUR) sont créés automatiquement et ne doivent pas être modifiés manuellement.

7. **Versioning** : Les rappels utilisent le versioning pour la synchronisation. Incrémentez la version lors des modifications locales.
