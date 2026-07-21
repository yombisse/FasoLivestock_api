# Migration Mobile : GESTATION_CONFIRMEE → GESTATION

## Contexte

Le backend a été normalisé pour utiliser un seul événement `GESTATION` au lieu de `GESTATION_CONFIRMEE`. Pour maintenir la cohérence backend-mobile, toutes les références côté mobile doivent être mises à jour.

## Changements Backend

**ID constant inchangé :** `HkGB81kowtR76VdLZqZO`

**Nouveau nom :** `GESTATION` (au lieu de `GESTATION_CONFIRMEE`)

**Nouvelle description :** `Gestation de l'animal femelle`

## Actions Requises Côté Mobile

### 1. Constantes TypeScript

**Fichier :** `src/constants/typeEvenements.ts`

```typescript
// ❌ AVANT
export const TypeEvenementIds = {
  // ...
  GESTATION_CONFIRMEE: 'HkGB81kowtR76VdLZqZO',
  // ...
} as const;

// ✅ APRÈS
export const TypeEvenementIds = {
  // ...
  GESTATION: 'HkGB81kowtR76VdLZqZO',
  // ...
} as const;
```

### 2. Screens et Composants

Rechercher et remplacer toutes les occurrences de :

```typescript
// ❌ À REMPLACER
TypeEvenementIds.GESTATION_CONFIRMEE
'GESTATION_CONFIRMEE'
'Gestation confirmée'
'gestation confirmée'

// ✅ PAR
TypeEvenementIds.GESTATION
'GESTATION'
'Gestation'
'gestation'
```

**Fichiers à vérifier :**
- `src/screens/reproduction/*`
- `src/screens/animals/*`
- `src/components/EventTypeSelector*`
- `src/services/evenementService.ts`
- Tout fichier contenant des références aux types d'événements

### 3. Données Existantes dans WatermelonDB

Si des enregistrements existent déjà avec l'ancien nom, ils doivent être migrés :

```typescript
// Script de migration (à exécuter une fois)
import database from '../database';
import { TypeEvenementIds } from '../constants/typeEvenements';

async function migrateGestationConfirmee() {
  await database.write(async () => {
    const evenements = await database
      .get('evenements')
      .query(Q.where('type_evenement_id', TypeEvenementIds.GESTATION))
      .fetch();

    for (const evenement of evenements) {
      await evenement.update(ev => {
        // Les données sont déjà correctes (ID inchangé)
        // Juste vérifier que les métadonnées n'ont pas de référence à l'ancien nom
        if (ev.metadonnees?.typeEvenementNom === 'Gestation confirmée') {
          ev.metadonnees.typeEvenementNom = 'Gestation';
        }
      });
    }
  });
}
```

### 4. Labels et Textes d'Affichage

Rechercher dans les fichiers de traduction et les composants UI :

```typescript
// ❌ À REMPLACER
<GestationConfirmeeScreen />
<GestationConfirmeeIcon />
label="Gestion de la gestation confirmée"
title="Confirmation de gestation"

// ✅ PAR
<GestationScreen />
<GestationIcon />
label="Gestion de la gestation"
title="Gestation"
```

### 5. Tests et Validation

Après migration :

1. **Sync test** : Synchroniser avec le backend pour vérifier que les événements de gestation sont correctement reçus
2. **Création test** : Créer un événement de gestation depuis le mobile et vérifier qu'il apparaît correctement
3. **Affichage test** : Vérifier que tous les écrans affichent "Gestation" et non "Gestation confirmée"

## Checklist de Migration

- [ ] Mettre à jour `src/constants/typeEvenements.ts`
- [ ] Rechercher/remplacer `GESTATION_CONFIRMEE` → `GESTATION` dans tout le codebase
- [ ] Mettre à jour les labels et textes d'affichage
- [ ] Mettre à jour les fichiers de traduction (si applicable)
- [ ] Exécuter le script de migration des données existantes
- [ ] Tester la synchronisation avec le backend
- [ ] Tester la création d'événements de gestation
- [ ] Vérifier l'affichage dans tous les écrans concernés

## Notes Importantes

- **L'ID reste le même** : `HkGB81kowtR76VdLZqZO` - aucune modification de la logique de sync nécessaire
- **Seul le nom change** : de "Gestation confirmée" à "Gestation"
- **La logique métier est inchangée** : l'événement reste dans la catégorie REPRODUCTION
- **La déduplication fonctionne** : business key = `animal_id + type_evenement_id + date_evenement`

## Support

En cas de problème lors de la migration, vérifier :
1. Que l'ID constant `HkGB81kowtR76VdLZqZO` est bien utilisé
2. Que le backend renvoie bien le nom "Gestation" dans les réponses API
3. Que le cache WatermelonDB a été vidé si nécessaire
