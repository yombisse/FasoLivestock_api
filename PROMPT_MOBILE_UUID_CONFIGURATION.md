# PROMPT POUR L'ÉQUIPE MOBILE — Configuration WatermelonDB UUID

---

## CONTEXTE

Le backend Laravel utilise des UUIDs standards (36 caractères avec tirets) comme identifiants pour toutes les tables. L'application mobile React Native avec WatermelonDB génère actuellement des IDs courts (16 caractères sans tirets), ce qui cause des erreurs de synchronisation car PostgreSQL rejette ces IDs courts.

**Problème actuel:**
- Backend: UUIDs standards → `019f4275-6620-70c8-857b-c835b6cdaf0a`
- Mobile: IDs courts WatermelonDB → `MII4cAPrJXF6CYqc`
- Erreur PostgreSQL: `invalid input syntax for type uuid`

## OBJECTIF

Configurer WatermelonDB pour générer des UUIDs standards au lieu d'IDs courts, afin que la synchronisation fonctionne sans modification du backend.

## TABLES CONCERNÉES

Toutes les tables synchronisées:
- `animals`
- `transactions`
- `evenements`
- `lots`
- `notifications`
- `naissances`
- `especes`
- `categories`
- `type_evenements`
- `farms`
- `farm_user`

## TÂCHES À EFFECTUER

### 1. Modifier le schéma WatermelonDB

**Fichier:** `src/database/schema.js` (ou équivalent)

**Changement requis:**
Remplacer `autoId()` ou `@autoId` par `uuid()` ou `@uuid` pour toutes les tables.

**Exemple AVANT:**
```javascript
const animalsSchema = {
  name: 'animals',
  columns: [
    { name: 'id', type: 'string', isIndexed: true, isPrimary: true },
    // ... autres colonnes
  ]
}
```

**Exemple APRÈS:**
```javascript
const animalsSchema = {
  name: 'animals',
  columns: [
    { name: 'id', type: 'string', isIndexed: true, isPrimary: true },
    // ... autres colonnes
  ]
}
```

**Dans le code de génération d'ID:**
```javascript
// AVANT (génération d'ID court)
const id = Math.random().toString(36).substring(2, 18).toUpperCase();

// APRÈS (génération d'UUID standard)
const id = uuid.v4(); // ou équivalent
```

### 2. Mettre à jour les modèles WatermelonDB

Pour chaque modèle, s'assurer que la méthode de génération d'ID utilise des UUIDs.

**Exemple avec @nozbe/watermelondb:**
```javascript
import { Model } from '@nozbe/watermelondb';
import { field, date, children } from '@nozbe/watermelondb/decorators';
import { v4 as uuidv4 } from 'uuid';

class Animal extends Model {
  static table = 'animals';

  @field('id')
  id;

  // Surcharger la méthode de création pour générer un UUID
  static async create(record) {
    return super.create({
      ...record,
      id: record.id || uuidv4(), // Générer UUID si non fourni
    });
  }
}
```

### 3. Migration des données existantes

**IMPORTANT:** Les données existantes dans la base locale mobile ont des IDs courts. Il faut les migrer vers des UUIDs standards.

**Script de migration:**
```javascript
import { database } from './database';
import { v4 as uuidv4 } from 'uuid';

async function migrateToUUID() {
  const tables = ['animals', 'transactions', 'evenements', 'lots', 'notifications', 'naissances', 'especes', 'categories', 'type_evenements', 'farms', 'farm_user'];
  
  await database.write(async () => {
    for (const tableName of tables) {
      const collection = database.collections.get(tableName);
      const records = await collection.query().fetch();
      
      for (const record of records) {
        const oldId = record.id;
        const newId = uuidv4();
        
        // Mettre à jour l'ID
        await record.update(record => {
          record.id = newId;
        });
        
        console.log(`Migrated ${tableName}: ${oldId} -> ${newId}`);
      }
    }
  });
}
```

### 4. Mettre à jour les foreign keys locales

Après la migration des IDs, vérifier que toutes les relations utilisent les nouveaux UUIDs.

**Exemple:**
```javascript
// Vérifier que animal.espece_id pointe vers le bon UUID
// Vérifier que evenement.animal_id pointe vers le bon UUID
// etc.
```

### 5. Tester la synchronisation

**Scénario de test:**
1. Lancer la migration locale
2. Créer un nouvel animal (vérifier qu'il a un UUID standard)
3. Synchroniser avec le backend
4. Vérifier les logs backend (plus d'erreur "Invalid UUID format")
5. Vérifier que les données sont bien persistées côté backend

**Logs à vérifier:**
```bash
# Backend Laravel
tail -f storage/logs/laravel.log | grep SYNC/PUSH
```

### 6. Gérer les conflits de synchronisation

Si des données avec IDs courts existent encore côté backend (cas rare), implémenter une logique de réconciliation:

```javascript
// Dans le code de sync
if (response.errors && response.errors.some(e => e.reason === 'Invalid UUID format')) {
  // Régénérer l'ID avec un UUID et réessayer
  record.id = uuidv4();
  await syncManager.pushChanges();
}
```

## FORMAT UUID ATTENDU

**UUID standard (RFC 4122):**
- Format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- Exemple: `019f4275-6620-70c8-857b-c835b6cdaf0a`
- Longueur: 36 caractères (incluant les tirets)
- Caractères: hexadécimaux (0-9, a-f)

**Librairies recommandées:**
- `uuid` (npm): `npm install uuid`
- `react-native-uuid`: `npm install react-native-uuid`

## VALIDATION

Après implémentation, valider:

1. **Format des IDs:**
   - Tous les nouveaux IDs ont 36 caractères
   - Format avec tirets: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

2. **Synchronisation:**
   - Plus d'erreurs "Invalid UUID format" dans les logs backend
   - Les données sont bien persistées côté backend

3. **Données existantes:**
   - Toutes les données locales ont été migrées
   - Aucune donnée perdue

4. **Performance:**
   - La génération d'UUID n'impacte pas significativement les performances
   - La taille de la base locale reste acceptable

## LIVRABLES ATTENDUS

1. Code modifié du schéma WatermelonDB
2. Script de migration des données existantes
3. Rapport de test de synchronisation
4. Logs backend montrant le succès de la sync

## NOTES IMPORTANTES

- **Backup local:** Effectuer un backup de la base locale avant la migration
- **Test en staging:** Tester d'abord en environnement de staging
- **Rollback:** Prévoir un plan de rollback en cas de problème
- **Communication:** Informer les utilisateurs de la migration (si applicable)

## CONTACT

En cas de problème ou de question sur le format UUID attendu, contacter l'équipe backend.

---

**Date de création:** 14 juillet 2026
**Priorité:** HAUTE
**Impact:** Critique pour la synchronisation
