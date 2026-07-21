# Guide de Validation Frontend - Événements Sanitaires et Transactions

## Contexte

Le backend implémente des contraintes de validation pour la création d'événements sanitaires et de transactions financières. Ces validations sont exécutées via des FormRequests Laravel qui renvoient des erreurs HTTP 422 si les données sont invalides.

Pour éviter les rejets lors de la synchronisation, le frontend doit envoyer des données conformes aux formats attendus et implémenter les mêmes validations côté client.

---

## Événements Sanitaires

### Endpoint Général

**Création** : `POST /api/sante/evenements`

**Paramètre requis** : `current_farm_id` (dans le body ou query params)

**Champ `type` requis** : `vaccination`, `traitement`, `maladie`, `controle`

---

### 1. Vaccination

**Type** : `vaccination`

**Champs communs** :
```json
{
  "animal_id": "string (16-20 chars, required)",
  "date_evenement": "YYYY-MM-DD (required)",
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)"
}
```

**Champs spécifiques (dans `metadonnees`)** :
```json
{
  "metadonnees": {
    "nom_vaccin": "string max:255 (required)",
    "veterinaire": "string max:255 (optional)",
    "dosage": "string max:100 (optional)",
    "lot_vaccin": "string max:100 (optional)"
  }
}
```

**Exemple complet** :
```json
{
  "current_farm_id": "farm_id_here",
  "type": "vaccination",
  "animal_id": "abc1234567890xyz",
  "date_evenement": "2026-07-14",
  "description": "Vaccination contre la fièvre aphteuse",
  "cout": 25.50,
  "metadonnees": {
    "nom_vaccin": "Fièvre aphteuse",
    "veterinaire": "Dr. Kabore",
    "dosage": "2ml",
    "lot_vaccin": "LOT-2026-001"
  }
}
```

**Messages d'erreur possibles** :
```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": ["L'animal est obligatoire."],
    "metadonnees.nom_vaccin": ["Le nom du vaccin est obligatoire."]
  }
}
```

---

### 2. Traitement

**Type** : `traitement`

**Champs communs** :
```json
{
  "animal_id": "string (16-20 chars, required)",
  "date_evenement": "YYYY-MM-DD (required)",
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)"
}
```

**Champs spécifiques (dans `metadonnees`)** :
```json
{
  "metadonnees": {
    "nom_medicament": "string max:255 (required)",
    "veterinaire": "string max:255 (optional)",
    "dosage": "string max:100 (optional)",
    "duree": "string max:100 (optional)",
    "frequence": "string max:100 (optional)"
  }
}
```

**Exemple complet** :
```json
{
  "current_farm_id": "farm_id_here",
  "type": "traitement",
  "animal_id": "abc1234567890xyz",
  "date_evenement": "2026-07-14",
  "description": "Traitement antiparasitaire",
  "cout": 45.00,
  "metadonnees": {
    "nom_medicament": "Ivermectine",
    "veterinaire": "Dr. Kabore",
    "dosage": "1ml/10kg",
    "duree": "7 jours",
    "frequence": "1 fois par jour"
  }
}
```

---

### 3. Maladie

**Type** : `maladie`

**Champs communs** :
```json
{
  "animal_id": "string (16-20 chars, required)",
  "date_evenement": "YYYY-MM-DD (required)",
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)"
}
```

**Champs spécifiques (dans `metadonnees`)** :
```json
{
  "metadonnees": {
    "nom_maladie": "string max:255 (required)",
    "symptomes": "string (optional)",
    "veterinaire": "string max:255 (optional)",
    "gravite": "enum:legere|moderee|grave (optional)"
  }
}
```

**Exemple complet** :
```json
{
  "current_farm_id": "farm_id_here",
  "type": "maladie",
  "animal_id": "abc1234567890xyz",
  "date_evenement": "2026-07-14",
  "description": "Diagnostic de la maladie",
  "cout": 150.00,
  "metadonnees": {
    "nom_maladie": "Pneumonie bovine",
    "symptomes": "Toux, fièvre, difficulté respiratoire",
    "veterinaire": "Dr. Kabore",
    "gravite": "moderee"
  }
}
```

---

### 4. Contrôle

**Type** : `controle`

**Champs communs** :
```json
{
  "animal_id": "string (16-20 chars, required)",
  "date_evenement": "YYYY-MM-DD (required)",
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)"
}
```

**Champs spécifiques (dans `metadonnees`)** :
```json
{
  "metadonnees": {
    "type_controle": "string max:255 (optional)",
    "veterinaire": "string max:255 (optional)",
    "resultat": "string (optional)"
  }
}
```

**Exemple complet** :
```json
{
  "current_farm_id": "farm_id_here",
  "type": "controle",
  "animal_id": "abc1234567890xyz",
  "date_evenement": "2026-07-14",
  "description": "Contrôle de routine",
  "cout": 30.00,
  "metadonnees": {
    "type_controle": "Contrôle sanitaire",
    "veterinaire": "Dr. Kabore",
    "resultat": "Animal en bonne santé"
  }
}
```

---

## Transactions Financières

### Endpoint

**Création** : `POST /api/finance/transactions`

**Champs requis** :
```json
{
  "type_transaction": "enum:ENTREE|SORTIE|TRANSFERT|AJUSTEMENT (required)",
  "montant": "numeric >= 0 (required)",
  "date_transaction": "YYYY-MM-DD (required)"
}
```

**Champs optionnels** :
```json
{
  "categorie_id": "string (16-20 chars, optional)",
  "description": "string (optional)",
  "evenement_id": "string (16-20 chars, optional)",
  "version": "integer >= 1 (optional)"
}
```

**Exemple complet** :
```json
{
  "type_transaction": "SORTIE",
  "montant": 5000.00,
  "date_transaction": "2026-07-14",
  "categorie_id": "cat_id_here",
  "description": "Achat d'aliments pour le bétail",
  "evenement_id": null,
  "version": 1
}
```

**Messages d'erreur possibles** :
```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "type_transaction": ["Le type de transaction doit être ENTREE, SORTIE, TRANSFERT ou AJUSTEMENT."],
    "montant": ["Le montant est obligatoire."],
    "date_transaction": ["La date de transaction est obligatoire."]
  }
}
```

**Contraintes importantes** :
- Les transactions liées aux mouvements de cheptel (`animal_id !== null`) sont **immuables** (ne peuvent pas être modifiées ou supprimées)
- Le montant doit être >= 0
- `categorie_id` et `evenement_id` doivent exister dans la base si fournis

---

## Mouvements d'Animaux (Vente, Achat, Transfert)

### Endpoint

**Création** : Les mouvements sont créés via le canal sync push/pull. L'API REST legacy n'expose pas de méthode `store()` directe.

**Champs requis** (pour sync) :
```json
{
  "animal_id": "string (16-20 chars, required)",
  "type_evenement_id": "string (16-20 chars, required)",
  "date_evenement": "YYYY-MM-DD (required)"
}
```

**Champs optionnels** :
```json
{
  "description": "string (optional)",
  "cout": "numeric >= 0 (optional)",
  "farm_destination_id": "string (16-20 chars, optional)",
  "transaction_id": "string (16-20 chars, optional)",
  "statut_avant": "enum:ACTIF|VENDU|MORT|PERDU (optional)",
  "statut_apres": "enum:ACTIF|VENDU|MORT|PERDU (optional)"
}
```

**Validation conditionnelle pour TRANSFERT** :
- Si `type_evenement_id` correspond à "TRANSFERT", alors `farm_destination_id` devient **requis**

**Exemple complet (Vente)** :
```json
{
  "animal_id": "abc1234567890xyz",
  "type_evenement_id": "type_vente_id",
  "date_evenement": "2026-07-14",
  "description": "Vente au marché",
  "cout": 0,
  "farm_destination_id": null,
  "transaction_id": "transaction_id_here",
  "statut_avant": "ACTIF",
  "statut_apres": "VENDU"
}
```

**Exemple complet (Transfert)** :
```json
{
  "animal_id": "abc1234567890xyz",
  "type_evenement_id": "type_transfert_id",
  "date_evenement": "2026-07-14",
  "description": "Transfert vers ferme B",
  "cout": 0,
  "farm_destination_id": "destination_farm_id",
  "transaction_id": null,
  "statut_avant": "ACTIF",
  "statut_apres": "ACTIF"
}
```

**Messages d'erreur possibles** :
```json
{
  "success": false,
  "message": "Données invalides.",
  "errors": {
    "animal_id": ["L'animal est obligatoire."],
    "type_evenement_id": ["Le type d'événement est obligatoire."],
    "farm_destination_id": ["La ferme de destination est obligatoire pour un transfert."]
  }
}
```

---

## Données de Référence Requises

### 1. Liste des Types d'Événements Sanitaires

**Endpoint** : `GET /api/type-evenements`

**Filtre** : `categorie=SANITAIRE`

**Réponse** :
```json
{
  "data": [
    {
      "id": "type_id_1",
      "nom_type": "Vaccination",
      "categorie": "SANITAIRE",
      "is_system": true
    },
    {
      "id": "type_id_2",
      "nom_type": "Traitement",
      "categorie": "SANITAIRE",
      "is_system": true
    },
    {
      "id": "type_id_3",
      "nom_type": "Contrôle",
      "categorie": "SANITAIRE",
      "is_system": true
    }
  ]
}
```

### 2. Liste des Catégories de Transactions

**Endpoint** : `GET /api/categories`

**Réponse** :
```json
{
  "data": [
    {
      "id": "cat_id_1",
      "nom_categorie": "Vente d'animaux",
      "type": "REVENU",
      "description": "Revenus générés par la vente d'animaux"
    },
    {
      "id": "cat_id_2",
      "nom_categorie": "Achat d'animaux",
      "type": "DEPENSE",
      "description": "Dépenses pour l'achat d'animaux"
    },
    {
      "id": "cat_id_3",
      "nom_categorie": "Santé (vétérinaire)",
      "type": "DEPENSE",
      "description": "Frais vétérinaires"
    }
  ]
}
```

### 3. Liste des Types de Mouvements

**Endpoint** : `GET /api/type-evenements`

**Filtre** : `categorie=MOUVEMENT`

**Réponse** :
```json
{
  "data": [
    {
      "id": "type_id_1",
      "nom_type": "Vente",
      "categorie": "MOUVEMENT",
      "is_system": true
    },
    {
      "id": "type_id_2",
      "nom_type": "Achat",
      "categorie": "MOUVEMENT",
      "is_system": true
    },
    {
      "id": "type_id_3",
      "nom_type": "Transfert",
      "categorie": "MOUVEMENT",
      "is_system": true
    },
    {
      "id": "type_id_4",
      "nom_type": "Décès",
      "categorie": "MOUVEMENT",
      "is_system": true
    }
  ]
}
```

---

## Stratégie de Validation Frontend

### 1. Validation Côté Client (Recommandée)

**Pour les événements sanitaires** :
```javascript
function validerEvenementSanitaire(donnees) {
  const erreurs = {};

  // Validation commune
  if (!donnees.animal_id || donnees.animal_id.length < 16 || donnees.animal_id.length > 20) {
    erreurs.animal_id = "L'ID de l'animal doit être une chaîne de 16 à 20 caractères.";
  }

  if (!donnees.date_evenement || !isValidDate(donnees.date_evenement)) {
    erreurs.date_evenement = "La date de l'événement est obligatoire et doit être valide.";
  }

  if (donnees.cout !== undefined && (isNaN(donnees.cout) || donnees.cout < 0)) {
    erreurs.cout = "Le coût doit être un nombre positif ou nul.";
  }

  // Validation spécifique selon le type
  switch (donnees.type) {
    case 'vaccination':
      if (!donnees.metadonnees?.nom_vaccin) {
        erreurs['metadonnees.nom_vaccin'] = "Le nom du vaccin est obligatoire.";
      }
      break;
    case 'traitement':
      if (!donnees.metadonnees?.nom_medicament) {
        erreurs['metadonnees.nom_medicament'] = "Le nom du médicament est obligatoire.";
      }
      break;
    case 'maladie':
      if (!donnees.metadonnees?.nom_maladie) {
        erreurs['metadonnees.nom_maladie'] = "Le nom de la maladie est obligatoire.";
      }
      if (donnees.metadonnees?.gravite && !['legere', 'moderee', 'grave'].includes(donnees.metadonnees.gravite)) {
        erreurs['metadonnees.gravite'] = "La gravité doit être légère, modérée ou grave.";
      }
      break;
  }

  return Object.keys(erreurs).length === 0 ? { valide: true } : { valide: false, erreurs };
}
```

**Pour les transactions** :
```javascript
function validerTransaction(donnees) {
  const erreurs = {};
  const typesValides = ['ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT'];

  if (!donnees.type_transaction || !typesValides.includes(donnees.type_transaction)) {
    erreurs.type_transaction = "Le type de transaction doit être ENTREE, SORTIE, TRANSFERT ou AJUSTEMENT.";
  }

  if (donnees.montant === undefined || isNaN(donnees.montant) || donnees.montant < 0) {
    erreurs.montant = "Le montant est obligatoire et doit être un nombre positif.";
  }

  if (!donnees.date_transaction || !isValidDate(donnees.date_transaction)) {
    erreurs.date_transaction = "La date de transaction est obligatoire et doit être valide.";
  }

  return Object.keys(erreurs).length === 0 ? { valide: true } : { valide: false, erreurs };
}
```

**Pour les mouvements** :
```javascript
function validerMouvement(donnees, typeEvenementNom) {
  const erreurs = {};

  if (!donnees.animal_id || donnees.animal_id.length < 16 || donnees.animal_id.length > 20) {
    erreurs.animal_id = "L'ID de l'animal doit être une chaîne de 16 à 20 caractères.";
  }

  if (!donnees.type_evenement_id) {
    erreurs.type_evenement_id = "Le type d'événement est obligatoire.";
  }

  if (!donnees.date_evenement || !isValidDate(donnees.date_evenement)) {
    erreurs.date_evenement = "La date de l'événement est obligatoire et doit être valide.";
  }

  // Validation conditionnelle pour TRANSFERT
  if (typeEvenementNom?.toUpperCase() === 'TRANSFERT') {
    if (!donnees.farm_destination_id) {
      erreurs.farm_destination_id = "La ferme de destination est obligatoire pour un transfert.";
    }
  }

  return Object.keys(erreurs).length === 0 ? { valide: true } : { valide: false, erreurs };
}
```

### 2. Gestion des Erreurs de Synchronisation

Lors de la synchronisation (sync push), si le backend renvoie une erreur 422 :

```javascript
async function synchroniserEvenementSanitaire(donnees) {
  try {
    const validation = validerEvenementSanitaire(donnees);
    if (!validation.valide) {
      Alert.alert('Erreur de validation', JSON.stringify(validation.erreurs));
      return;
    }

    const reponse = await api.post('/sante/evenements', donnees);
    return reponse.data;
  } catch (erreur) {
    if (erreur.response?.status === 422) {
      const messageErreur = extrairePremiereErreur(erreur.response.data.errors);
      Alert.alert('Erreur de validation serveur', messageErreur);
      marquerEnConflit(donnees);
    } else {
      throw erreur;
    }
  }
}

function extrairePremiereErreur(errors) {
  const firstKey = Object.keys(errors)[0];
  return errors[firstKey][0];
}
```

---

## Checklist d'Intégration Frontend

### Événements Sanitaires
- [ ] Charger les types d'événements sanitaires depuis l'API
- [ ] Implémenter la validation des champs communs (animal_id, date_evenement, cout)
- [ ] Implémenter la validation spécifique par type (nom_vaccin, nom_medicament, nom_maladie)
- [ ] Valider les formats de chaînes (max length)
- [ ] Valider les enums (gravite: legere|moderee|grave)
- [ ] Envoyer metadonnees comme objet JSON
- [ ] Gérer les erreurs 422 avec affichage utilisateur

### Transactions
- [ ] Charger les catégories de transactions depuis l'API
- [ ] Valider le type_transaction (ENTREE|SORTIE|TRANSFERT|AJUSTEMENT)
- [ ] Valider le montant (numeric >= 0)
- [ ] Valider la date_transaction
- [ ] Vérifier l'existence de categorie_id et evenement_id si fournis
- [ ] Gérer l'immuabilité des transactions liées aux mouvements
- [ ] Gérer les erreurs 422 avec affichage utilisateur

### Mouvements
- [ ] Charger les types de mouvements depuis l'API
- [ ] Implémenter la validation conditionnelle pour TRANSFERT (farm_destination_id requis)
- [ ] Valider les statuts_avant/statut_apres (ACTIF|VENDU|MORT|PERDU)
- [ ] Synchroniser via le canal sync push/pull (pas d'API REST directe)
- [ ] Gérer les erreurs 422 avec affichage utilisateur

---

## Notes Importantes

1. **Format des IDs** : Tous les IDs (animal_id, type_evenement_id, categorie_id, etc.) sont des chaînes de 16 à 20 caractères.

2. **Format des dates** : Toutes les dates doivent être au format `YYYY-MM-DD` (ISO 8601).

3. **Metadonnees** : Les champs spécifiques aux événements sanitaires doivent être envoyés dans un objet `metadonnees` sérialisé en JSON.

4. **Montants** : Tous les montants doivent être des nombres positifs ou nuls (>= 0).

5. **Transactions immuables** : Les transactions liées aux mouvements de cheptel (avec animal_id) ne peuvent pas être modifiées ou supprimées.

6. **Validation conditionnelle** : Pour les mouvements de type TRANSFERT, le champ `farm_destination_id` devient requis.

7. **Sync vs REST** : Les mouvements d'animaux doivent être créés via le canal sync push/pull, pas via l'API REST directe.

8. **Messages d'erreur** : Les messages d'erreur sont en français et destinés à l'utilisateur final. Le frontend doit les afficher tels quels.
