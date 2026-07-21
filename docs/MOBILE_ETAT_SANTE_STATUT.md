# Gestion Automatique du Statut - Mobile

## Contexte

Le backend met à jour automatiquement le **statut** des animaux lors de la création de certains événements. Le mobile doit reproduire cette logique localement pour une expérience utilisateur cohérente et pour éviter les conflits lors de la synchronisation.

**Note :** Le champ `etat_sante` a été supprimé. Tout est géré via le champ unique `statut`.

## Règles de Mise à Jour Automatique

### Événements Sanitaires

| Type d'événement | Champs à mettre à jour | Valeurs |
|------------------|------------------------|---------|
| **MALADIE** | `statut` | `MALADE` |
| **TRAITEMENT** | `statut` | `EN_TRAITEMENT` |
| **VACCINATION** | Aucune mise à jour automatique | - |
| **CONTROLE** | Aucune mise à jour automatique | - |

### Événements de Mouvement

| Type d'événement | Champs à mettre à jour | Valeurs |
|------------------|------------------------|---------|
| **DECES** | `statut` | `MORT` |
| **VENTE** | `statut` | `VENDU` |
| **PERTE** | `statut` | `PERDU` |
| **ABATTAGE** | `statut` | `MORT` |
| **ACHAT** | `statut` | `SAIN` |
| **TRANSFERT** | `statut` | `SAIN` (si arrivée) ou inchangé (si départ) |

### Événements Reproductifs

| Type d'événement | Champs à mettre à jour | Valeurs |
|------------------|------------------------|---------|
| **SAILLIE** | Aucune mise à jour automatique | - |
| **GESTATION** | Aucune mise à jour automatique | - |
| **MISE_BAS** | Aucune mise à jour automatique | - |
| **CHALEUR** | Aucune mise à jour automatique | - |

**Note importante :** Le backend détecte l'état de reproduction (saillie, gestation, mise bas) via les **événements** et leur statut (champ `statut` de la table `evenements`), PAS via le champ `statut` de l'animal. Le champ `statut` de l'animal reste utilisé uniquement pour l'état de santé (SAIN, MALADE, EN_TRAITEMENT) et les mouvements (VENDU, MORT, PERDU).

## Implémentation Mobile (TypeScript)

### 1. Créer une fonction utilitaire de mise à jour

```typescript
// src/services/animalStatusService.ts

import database from '../database';
import { Q } from '@nozbe/watermelondb';
import { TypeEvenementIds } from '../constants/typeEvenements';

export enum StatutAnimal {
  SAIN = 'SAIN',
  MALADE = 'MALADE',
  EN_TRAITEMENT = 'EN_TRAITEMENT',
  VENDU = 'VENDU',
  MORT = 'MORT',
  PERDU = 'PERDU',
}

/**
 * Met à jour automatiquement le statut d'un animal
 * en fonction du type d'événement créé
 */
export async function updateAnimalStatusOnEvent(
  animalId: string,
  typeEvenementId: string
): Promise<void> {
  await database.write(async () => {
    const animal = await database.get('animals').find(animalId);

    // Événements sanitaires
    if (typeEvenementId === TypeEvenementIds.MALADIE) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.MALADE;
      });
    }

    if (typeEvenementId === TypeEvenementIds.TRAITEMENT) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.EN_TRAITEMENT;
      });
    }

    // Événements de mouvement
    if (typeEvenementId === TypeEvenementIds.DECES ||
        typeEvenementId === TypeEvenementIds.ABATTAGE) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.MORT;
      });
    }

    if (typeEvenementId === TypeEvenementIds.VENTE) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.VENDU;
      });
    }

    if (typeEvenementId === TypeEvenementIds.PERTE) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.PERDU;
      });
    }

    if (typeEvenementId === TypeEvenementIds.ACHAT) {
      await animal.update(animal => {
        animal.statut = StatutAnimal.SAIN;
      });
    }
  });
}
```

### 2. Intégrer dans les services d'événements

```typescript
// src/services/evenementService.ts

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

### 3. Intégrer dans les screens de création d'événements

```typescript
// src/screens/sante/MaladieScreen.tsx

import { updateAnimalStatusOnEvent } from '../../services/animalStatusService';

const handleCreateMaladie = async () => {
  try {
    const evenementData = {
      animal_id: selectedAnimal.id,
      type_evenement_id: TypeEvenementIds.MALADIE,
      date_evenement: new Date().toISOString(),
      description: description,
      metadonnees: {
        nom_maladie: nomMaladie,
        symptomes: symptomes,
        gravite: gravite,
      },
    };

    // Créer l'événement (qui inclut la mise à jour du statut)
    await createEvenement(evenementData);

    // Ou appeler explicitement si création directe
    await updateAnimalStatusOnEvent(
      selectedAnimal.id,
      TypeEvenementIds.MALADIE
    );

    Alert.alert('Succès', 'Maladie déclarée avec succès');
    navigation.goBack();
  } catch (error) {
    Alert.alert('Erreur', 'Impossible de déclarer la maladie');
  }
};
```

### 4. Cas particuliers : Guérison

Pour remettre un animal à l'état sain après une maladie :

```typescript
// src/services/animalStatusService.ts

/**
 * Remettre un animal à l'état sain (après guérison)
 */
export async function setAnimalSain(animalId: string): Promise<void> {
  await database.write(async () => {
    const animal = await database.get('animals').find(animalId);
    await animal.update(animal => {
      animal.statut = StatutAnimal.SAIN;
    });
  });
}
```

## Constantes TypeScript à Ajouter

```typescript
// src/constants/typeEvenements.ts

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

## Synchronisation et Conflits

### Règle de priorité

**Backend prioritaire** pour les changements d'état de santé et de statut lors du sync.

Si le mobile a mis à jour l'état localement et que le backend a aussi une mise à jour différente, le backend l'emportera.

### Conflits potentiels

1. **Animal déclaré malade localement, mais guéri sur le backend**
   - Le sync pull écrasera l'état local avec l'état backend
   - Afficher une notification à l'utilisateur : "L'état de santé de X a été mis à jour par le serveur"

2. **Animal vendu localement, mais marqué mort sur le backend**
   - Le statut backend (MORT) l'emportera
   - Afficher une notification : "Le statut de X a été modifié par le serveur"

## Reproduction et Mode Offline-First

### Comportement du Backend lors du Sync

Le backend utilise **skipValidation = true** pour les événements de reproduction lors du sync push. Cela signifie que :

- Le backend **ne valide pas** la chaîne reproductif (saillie → gestation → mise bas) lors du sync
- Le backend **fait confiance** au mobile qui a déjà validé localement
- Le backend accepte les événements de reproduction sans vérifier les événements précédents

**Pourquoi ?** En mode offline-first, le mobile peut créer une saillie localement, puis immédiatement créer une gestation. Lors du sync push, le backend ne voit pas la saillie (pas encore syncée) et refuserait la gestation si la validation était stricte.

### Responsabilité du Mobile

Le mobile **DOIT** implémenter la validation locale stricte avant de créer des événements de reproduction :

```typescript
// src/services/reproductionValidationService.ts

import database from '../database';
import { Q } from '@nozbe/watermelondb';
import { TypeEvenementIds } from '../constants/typeEvenements';

export async function validerSaillie(animalId: string): Promise<boolean> {
  const animal = await database.get('animals').find(animalId);

  // Vérifier que l'animal est femelle
  if (animal.sexe !== 'femelle') {
    throw new Error('Une saillie ne peut être enregistrée que sur un animal femelle.');
  }

  // Vérifier qu'aucune gestation EN_COURS n'existe (incluant les événements locaux non syncés)
  const gestations = await database.get('evenements').query(
    Q.where('animal_id', animalId),
    Q.where('type_evenement_id', TypeEvenementIds.GESTATION),
    Q.where('statut', 'EN_COURS')
  ).fetch();

  if (gestations.length > 0) {
    throw new Error('Impossible de créer une saillie : cet animal a déjà une gestation en cours.');
  }

  return true;
}

export async function validerGestation(animalId: string): Promise<boolean> {
  const animal = await database.get('animals').find(animalId);

  // Vérifier que l'animal est femelle
  if (animal.sexe !== 'femelle') {
    throw new Error('Une gestation ne peut être enregistrée que sur un animal femelle.');
  }

  // Vérifier qu'une saillie EN_COURS existe (incluant les événements locaux non syncés)
  const saillies = await database.get('evenements').query(
    Q.where('animal_id', animalId),
    Q.where('type_evenement_id', TypeEvenementIds.SAILLIE),
    Q.where('statut', 'EN_COURS')
  ).fetch();

  if (saillies.length === 0) {
    throw new Error('Impossible de créer une gestation : aucune saillie en cours trouvée.');
  }

  // Vérifier qu'aucune gestation EN_COURS n'existe déjà
  const gestations = await database.get('evenements').query(
    Q.where('animal_id', animalId),
    Q.where('type_evenement_id', TypeEvenementIds.GESTATION),
    Q.where('statut', 'EN_COURS')
  ).fetch();

  if (gestations.length > 0) {
    throw new Error('Impossible de créer une gestation : une gestation est déjà en cours.');
  }

  return true;
}

export async function validerMiseBas(animalId: string): Promise<boolean> {
  // Vérifier qu'une gestation EN_COURS existe (incluant les événements locaux non syncés)
  const gestations = await database.get('evenements').query(
    Q.where('animal_id', animalId),
    Q.where('type_evenement_id', TypeEvenementIds.GESTATION),
    Q.where('statut', 'EN_COURS')
  ).fetch();

  if (gestations.length === 0) {
    throw new Error('Impossible de créer une mise bas : aucune gestation en cours trouvée.');
  }

  return true;
}
```

### Intégration dans les Screens

```typescript
// src/screens/reproduction/SaillieScreen.tsx

import { validerSaillie } from '../../services/reproductionValidationService';

const handleCreateSaillie = async () => {
  try {
    // Valider AVANT création
    await validerSaillie(selectedAnimal.id);

    // Créer l'événement
    await createEvenement({
      animal_id: selectedAnimal.id,
      type_evenement_id: TypeEvenementIds.SAILLIE,
      date_evenement: new Date().toISOString(),
      statut: 'EN_COURS',
      // ... autres champs
    });

    Alert.alert('Succès', 'Saillie enregistrée avec succès');
  } catch (error) {
    Alert.alert('Erreur', error.message);
  }
};
```

### Points Clés

1. **Validation locale obligatoire** : Le mobile DOIT valider avant création
2. **Inclure les événements non syncés** : La validation doit chercher dans WatermelonDB, pas seulement sur le serveur
3. **Backend fait confiance** : Le backend utilise skipValidation lors du sync
4. **Chaîne reproductif** : Saillie (EN_COURS) → Gestation (EN_COURS) → Mise Bas (TERMINE)

## Checklist d'Implémentation Mobile

- [ ] Créer `src/services/animalStatusService.ts`
- [ ] Ajouter les constantes `TypeEvenementIds` avec MALADIE
- [ ] Intégrer `updateAnimalStatusOnEvent` dans `evenementService.ts`
- [ ] Intégrer dans tous les screens de création d'événements
- [ ] Ajouter la fonction `setAnimalSain` pour la guérison
- [ ] Tester la création d'une maladie (vérifier statut = MALADE)
- [ ] Tester un décès (vérifier statut = MORT)
- [ ] Tester une vente (vérifier statut = VENDU)
- [ ] Tester une perte (vérifier statut = PERDU)
- [ ] Tester la synchronisation avec le backend
- [ ] Ajouter des notifications pour les conflits de statut

## Notes Importantes

- **Toujours mettre à jour l'animal dans la même transaction** que la création de l'événement
- **Utiliser les IDs constants** pour éviter les erreurs de sync
- **Le backend applique les mêmes règles** : cohérence garantie
- **Les états de santé et statut sont synchronisés** via le canal sync standard
