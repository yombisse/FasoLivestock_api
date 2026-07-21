# Guide de Validation Frontend - Événements Reproductifs

## Contexte

Le backend implémente désormais des contraintes de validation strictes pour la création d'événements reproductifs (Saillie, Gestation confirmée, Mise bas). Ces validations sont exécutées **AVANT** toute écriture en base et sont appliquées via une transaction atomique.

Si le frontend envoie des données invalides, la requête sera rejetée avec un code HTTP 422 et les messages d'erreur appropriés. Il est donc **critique** que le frontend implémente les mêmes validations côté client pour éviter les rejets lors de la synchronisation.

---

## Contraintes par Type d'Événement

### 1. Saillie

**Endpoint** : `POST /api/reproduction/evenements`

**Contraintes** :
- ✅ L'animal doit être une femelle (`sexe === 'femelle'`)
- ✅ Si `date_naissance` est renseignée, l'animal doit être en âge de reproduire selon son espèce
- ✅ L'animal ne doit pas avoir déjà une gestation confirmée EN_COURS

**Message d'erreur possible** :
```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Une saillie ne peut être enregistrée que sur un animal femelle."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Âge de reproduction non atteint (24 mois requis pour l'espèce Bovin, animal actuel : 12 mois)."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Impossible de créer une saillie : cet animal a déjà une gestation confirmée en cours."
  }
}
```

**Validation Frontend** :
```javascript
// Exemple de validation côté client
function validerSaillie(animal, especeParametre) {
  if (animal.sexe !== 'femelle') {
    return { valide: false, erreur: "Une saillie ne peut être enregistrée que sur un animal femelle." };
  }

  if (animal.date_naissance && especeParametre) {
    const ageActuel = calculerAgeEnMois(animal.date_naissance);
    if (ageActuel < especeParametre.age_reproduction_mois) {
      return { 
        valide: false, 
        erreur: `Âge de reproduction non atteint (${especeParametre.age_reproduction_mois} mois requis pour l'espèce ${especeParametre.espece_nom}, animal actuel : ${ageActuel} mois).` 
      };
    }
  }

  // Vérifier si une gestation est en cours (requête API ou cache local)
  if (animal.aGestationEnCours) {
    return { valide: false, erreur: "Impossible de créer une saillie : cet animal a déjà une gestation confirmée en cours." };
  }

  return { valide: true };
}
```

---

### 2. Gestation Confirmée

**Endpoint** : `POST /api/reproduction/evenements`

**Contraintes** :
- ✅ L'animal doit être une femelle (`sexe === 'femelle'`)
- ✅ Une saillie EN_COURS ou sans statut doit exister pour cet animal
- ✅ Aucune gestation confirmée EN_COURS ne doit déjà exister
- ✅ La date de confirmation ne peut pas être antérieure à la date de saillie

**Message d'erreur possible** :
```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Une confirmation de gestation ne peut être enregistrée que sur un animal femelle."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Impossible de confirmer une gestation : aucune saillie en cours trouvée pour cet animal."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Impossible de confirmer une gestation : une gestation confirmée est déjà en cours pour cet animal."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "date_evenement": "Incohérence temporelle : la date de confirmation de gestation ne peut pas être antérieure à la date de saillie."
  }
}
```

**Validation Frontend** :
```javascript
function validerGestationConfirmee(animal, dateConfirmation, evenementsAnimal) {
  if (animal.sexe !== 'femelle') {
    return { valide: false, erreur: "Une confirmation de gestation ne peut être enregistrée que sur un animal femelle." };
  }

  // Chercher une saillie EN_COURS ou sans statut
  const saillieEnCours = evenementsAnimal.find(e => 
    e.type_nom === 'Saillie' && 
    (e.statut === 'EN_COURS' || e.statut === null || e.statut === '')
  );

  if (!saillieEnCours) {
    return { valide: false, erreur: "Impossible de confirmer une gestation : aucune saillie en cours trouvée pour cet animal." };
  }

  // Vérifier si une gestation est déjà en cours
  const gestationEnCours = evenementsAnimal.find(e => 
    e.type_nom === 'Gestation confirmée' && 
    e.statut === 'EN_COURS'
  );

  if (gestationEnCours) {
    return { valide: false, erreur: "Impossible de confirmer une gestation : une gestation confirmée est déjà en cours pour cet animal." };
  }

  // Vérifier cohérence temporelle
  if (new Date(dateConfirmation) < new Date(saillieEnCours.date_evenement)) {
    return { valide: false, erreur: "Incohérence temporelle : la date de confirmation de gestation ne peut pas être antérieure à la date de saillie." };
  }

  return { valide: true };
}
```

---

### 3. Mise Bas

**Endpoint** : `POST /api/reproduction/evenements`

**Note** : Les événements MISE BAS sont créés automatiquement lors de la déclaration de naissance. Le backend bloque la création manuelle via l'API REST.

**Contraintes** (si création via sync) :
- ✅ Une gestation confirmée EN_COURS doit exister pour cet animal
- ✅ La date de mise bas ne peut pas être antérieure à la date de confirmation de gestation

**Message d'erreur possible** :
```json
{
  "success": false,
  "message": "Les événements MISE BAS sont créés automatiquement lors de la déclaration de naissance.",
  "errors": {}
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": "Impossible de créer un événement MISE_BAS : aucune gestation confirmée en cours pour cet animal."
  }
}
```

```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "date_evenement": "Incohérence temporelle : la date de mise bas ne peut pas être antérieure à la date de confirmation de gestation."
  }
}
```

---

## Données Requises pour Validation

Pour effectuer les validations côté client, le frontend doit disposer des informations suivantes :

### 1. Paramètres de l'Espèce

**Endpoint** : `GET /api/especes/{id}/parametres`

**Réponse** :
```json
{
  "id": "xyz",
  "espece_id": "abc",
  "duree_gestation_jours": 285,
  "age_reproduction_mois": 24,
  "nombre_petits_typique": 1,
  "intervalle_vaccin_jours": 365,
  "age_sevrage_jours": 210,
  "poids_naissance_moyen_kg": 25.00,
  "poids_adulte_moyen_kg": 300.00
}
```

### 2. Événements de l'Animal

**Endpoint** : `GET /api/animals/{id}/evenements`

**Réponse** :
```json
{
  "evenements": [
    {
      "id": "evt1",
      "type_nom": "Saillie",
      "date_evenement": "2026-01-15",
      "statut": "EN_COURS",
      "date_fin": null
    },
    {
      "id": "evt2",
      "type_nom": "Gestation confirmée",
      "date_evenement": "2026-02-15",
      "statut": "EN_COURS",
      "date_fin": null
    }
  ]
}
```

---

## Stratégie de Synchronisation

### 1. Validation Côté Client (Recommandée)

Avant d'envoyer une requête de création d'événement :

1. Charger les paramètres de l'espèce de l'animal
2. Charger les événements existants de l'animal
3. Exécuter la validation correspondante
4. Si invalide, afficher l'erreur à l'utilisateur et bloquer l'envoi
5. Si valide, envoyer la requête

### 2. Gestion des Erreurs de Synchronisation

Lors de la synchronisation (sync push), si le backend renvoie une erreur 422 :

1. Afficher l'erreur à l'utilisateur
2. Marquer l'événement comme "conflict" dans le cache local
3. Permettre à l'utilisateur de corriger les données
4. Retenter la synchronisation après correction

**Exemple de gestion d'erreur** :
```javascript
async function creerEvenementReproduction(donnees) {
  try {
    const validation = validerSelonType(donnees);
    if (!validation.valide) {
      Alert.alert('Erreur de validation', validation.erreur);
      return;
    }

    const reponse = await api.post('/reproduction/evenements', donnees);
    return reponse.data;
  } catch (erreur) {
    if (erreur.response?.status === 422) {
      const messageErreur = erreur.response.data.errors?.animal_id || 
                           erreur.response.data.errors?.date_evenement ||
                           erreur.response.data.message;
      Alert.alert('Erreur de validation serveur', messageErreur);
      // Marquer pour retry après correction
      marquerEnConflit(donnees);
    } else {
      throw erreur;
    }
  }
}
```

---

## Checklist d'Intégration Frontend

- [ ] Charger les paramètres de l'espèce lors de la sélection d'un animal
- [ ] Charger les événements existants de l'animal
- [ ] Implémenter la validation de sexe pour Saillie et Gestation
- [ ] Implémenter la validation d'âge (si date_naissance connue) pour Saillie
- [ ] Implémenter la vérification de gestation en cours pour Saillie
- [ ] Implémenter la vérification de saillie en cours pour Gestation
- [ ] Implémenter la vérification de double gestation pour Gestation
- [ ] Implémenter la validation de cohérence temporelle
- [ ] Gérer les erreurs 422 avec affichage utilisateur
- [ ] Marquer les événements en conflit pour retry
- [ ] Tester tous les scénarios d'erreur

---

## Notes Importantes

1. **Transaction Atomique** : Le backend utilise des transactions. Si une validation échoue, **aucune donnée n'est écrite** en base. Le frontend peut retenter sans risque de duplication.

2. **Date de Naissance** : Si `date_naissance` est null, la contrainte d'âge est ignorée. Le frontend doit gérer ce cas.

3. **Statut des Événements** : Les événements peuvent avoir un statut null, 'EN_COURS', ou 'TERMINE'. La validation accepte les événements sans statut comme "en cours" pour la saillie.

4. **Mise Bas Manuelle** : La création manuelle d'événements MISE BAS est bloquée via l'API REST. Ils doivent être créés automatiquement via le flux de déclaration de naissance.

5. **Messages d'Erreur** : Les messages d'erreur sont en français et destinés à l'utilisateur final. Le frontend doit les afficher tels quels.
