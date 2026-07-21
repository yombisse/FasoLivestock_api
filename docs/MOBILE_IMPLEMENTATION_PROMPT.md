# Prompt d'Implémentation Mobile - Gestion du Statut et Reproduction

## Objectif

Implémenter côté mobile la gestion automatique du statut des animaux et la validation des événements de reproduction selon les spécifications du backend, en mode offline-first avec WatermelonDB.

## Contexte

Le backend met à jour automatiquement le **statut** des animaux lors de la création de certains événements. Le mobile doit reproduire cette logique localement pour une expérience utilisateur cohérente et pour éviter les conflits lors de la synchronisation.

**Note importante :** Le champ `etat_sante` a été supprimé. Tout est géré via le champ unique `statut`.

---

## Tâche 1 : Créer le service de gestion du statut animal

### Fichier à créer : `src/services/animalStatusService.ts`

Créer un service TypeScript avec les fonctionnalités suivantes :

1. **Enum StatutAnimal** avec les valeurs :
   - SAIN = 'SAIN'
   - MALADE = 'MALADE'
   - EN_TRAITEMENT = 'EN_TRAITEMENT'
   - VENDU = 'VENDU'
   - MORT = 'MORT'
   - PERDU = 'PERDU'

2. **Fonction `updateAnimalStatusOnEvent(animalId: string, typeEvenementId: string)`**
   - Met à jour automatiquement le statut de l'animal selon le type d'événement
   - Doit être appelée dans la même transaction que la création de l'événement
   - Règles de mise à jour :
     - MALADIE → statut = MALADE
     - TRAITEMENT → statut = EN_TRAITEMENT
     - DECES ou ABATTAGE → statut = MORT
     - VENTE → statut = VENDU
     - PERTE → statut = PERDU
     - ACHAT → statut = SAIN
   - Les événements reproductifs (SAILLIE, GESTATION, MISE_BAS, CHALEUR) ne mettent PAS à jour le statut de l'animal

3. **Fonction `setAnimalSain(animalId: string)`**
   - Remet l'animal à l'état SAIN (après guérison)
   - À utiliser pour les cas de guérison manuelle
   - Met à jour le champ `statut` (pas `etat_sante`)

---

## Tâche 2 : Créer les constantes TypeEvenementIds

### Fichier à créer : `src/constants/typeEvenements.ts`

Créer un objet constant avec les IDs des types d'événements (UUIDs du backend) :

```typescript
export const TypeEvenementIds = {
  // Reproduction
  CHALEUR: '1owjZUIVP0gIrAcSq8Sw',
  SAILLIE: 'JKAWN9rN0BYVE46snec1',
  GESTATION: 'HkGB81kowtR76VdLZqZO',
  MISE_BAS: 'kHe1MGrLuLxAwZdSQdrY',
  NAISSANCE: 'JEDpdHtycshklyWggT4B',

  // Sanitaire
  VACCINATION: 'u4OJvVlCnLQ7H7xcIVMc',
  TRAITEMENT: 'BfLtxah0PEx4CJn5zQ50',
  MALADIE: '5XjK3qZ8pLmN9oR2sT4v',
  CONTROLE: 'BtFDz672cHmbwNH4GaiH',
  PESSEE: 'rtpISsZNMYM6It03Xl2g',
  AUTRE: 'oNZ8XZX15yKUpDyl78AF',

  // Mouvement
  VENTE: 'Khjy61rPsSByYjRDE6EL',
  ACHAT: 'f96dSuy6ocGMWX58R6ve',
  TRANSFERT: '02amTPryEc4apSmqQkoc',
  DECES: 'vhSYzGylJQAmfiVrgbjp',
  PERTE: 'A36SgYEfEIdgwujVpt0m',
  ABATTAGE: 'P9Tczk0VV2AwG9P68Fpl',
} as const;
```

---

## Tâche 3 : Intégrer la mise à jour du statut dans evenementService

### Fichier à modifier : `src/services/evenementService.ts`

Modifier la fonction `createEvenement` pour inclure la mise à jour automatique du statut :

```typescript
import { updateAnimalStatusOnEvent } from './animalStatusService';

export async function createEvenement(evenementData: any) {
  await database.write(async () => {
    // Créer l'événement
    const evenement = await database.get('evenements').create(ev => {
      ev.animalId = evenementData.animal_id;
      ev.typeEvenementId = evenementData.type_evenement_id;
      ev.dateEvenement = evenementData.date_evenement;
      ev.description = evenementData.description;
      // ... autres champs
    });

    // Mettre à jour automatiquement le statut de l'animal
    await updateAnimalStatusOnEvent(
      evenementData.animal_id,
      evenementData.type_evenement_id
    );

    return evenement;
  });
}
```

---

## Tâche 4 : Créer le service de validation reproduction

### Fichier à créer : `src/services/reproductionValidationService.ts`

Créer un service TypeScript avec les fonctions de validation stricte pour les événements de reproduction :

1. **`validerSaillie(animalId: string)`**
   - Vérifie que l'animal est femelle
   - Vérifie qu'aucune gestation EN_COURS n'existe (incluant les événements locaux non syncés)
   - Cherche dans WatermelonDB avec Q.where('statut', 'EN_COURS')
   - Lance une erreur si validation échoue

2. **`validerGestation(animalId: string)`**
   - Vérifie que l'animal est femelle
   - Vérifie qu'une saillie EN_COURS existe (incluant les événements locaux non syncés)
   - Vérifie qu'aucune gestation EN_COURS n'existe déjà
   - Lance une erreur si validation échoue

3. **`validerMiseBas(animalId: string)`**
   - Vérifie qu'une gestation EN_COURS existe (incluant les événements locaux non syncés)
   - Lance une erreur si validation échoue

**Point critique :** Ces validations doivent chercher dans WatermelonDB locale, incluant les événements non syncés, car le backend utilise skipValidation lors du sync et fait confiance au mobile.

---

## Tâche 5 : Intégrer la validation dans les screens reproduction

### Screens à modifier :

1. **`src/screens/reproduction/SaillieScreen.tsx`**
   - Appeler `validerSaillie(animalId)` AVANT création
   - Créer l'événement avec statut = 'EN_COURS'
   - Gérer les erreurs de validation avec Alert

2. **`src/screens/reproduction/GestationScreen.tsx`**
   - Appeler `validerGestation(animalId)` AVANT création
   - Créer l'événement avec statut = 'EN_COURS'
   - Gérer les erreurs de validation avec Alert

3. **`src/screens/reproduction/MiseBasScreen.tsx`**
   - Appeler `validerMiseBas(animalId)` AVANT création
   - Créer l'événement avec statut = 'TERMINE'
   - Gérer les erreurs de validation avec Alert

---

## Tâche 6 : Intégrer la mise à jour du statut dans les screens sanitaires

### Screens à modifier :

1. **`src/screens/sante/MaladieScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à MALADE

2. **`src/screens/sante/TraitementScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à EN_TRAITEMENT

3. **`src/screens/sante/GuerisonScreen.tsx`** (si existe)
   - Utiliser `setAnimalSain` pour remettre à SAIN

---

## Tâche 7 : Intégrer la mise à jour du statut dans les screens mouvement

### Screens à modifier :

1. **`src/screens/mouvement/DecesScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à MORT

2. **`src/screens/mouvement/VenteScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à VENDU

3. **`src/screens/mouvement/PerteScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à PERDU

4. **`src/screens/mouvement/AchatScreen.tsx`**
   - Utiliser `updateAnimalStatusOnEvent` après création
   - Vérifier que le statut passe à SAIN

---

## Points Critiques à Respecter

1. **Transaction atomique** : La mise à jour du statut doit être dans la même transaction que la création de l'événement
2. **Validation locale** : Les validations reproduction doivent chercher dans WatermelonDB locale, incluant les événements non syncés
3. **Backend fait confiance** : Le backend utilise skipValidation lors du sync, donc le mobile doit être strict
4. **IDs constants** : Utiliser les UUIDs constants du backend pour éviter les erreurs de sync
5. **Pas de etat_sante** : Ne plus utiliser le champ etat_sante, tout est dans statut

---

## Tests à Effectuer

1. Créer une maladie → vérifier statut = MALADE
2. Créer un traitement → vérifier statut = EN_TRAITEMENT
3. Créer un décès → vérifier statut = MORT
4. Créer une vente → vérifier statut = VENDU
5. Créer une perte → vérifier statut = PERDU
6. Créer un achat → vérifier statut = SAIN
7. Créer une saillie → vérifier que statut de l'animal ne change PAS
8. Créer une gestation sans saillie → doit échouer avec erreur
9. Créer une gestation après saillie → doit réussir
10. Créer une mise bas sans gestation → doit échouer avec erreur
11. Synchroniser avec le backend → vérifier cohérence

---

## Documentation de référence

Voir `/docs/MOBILE_ETAT_SANTE_STATUT.md` pour les règles détaillées et exemples de code.
