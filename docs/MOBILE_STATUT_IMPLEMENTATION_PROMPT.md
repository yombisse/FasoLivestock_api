# Prompt d'Implémentation Mobile - Gestion des Statuts

## Contexte

Le backend a normalisé la gestion des statuts animaux en remplaçant `ACTIF` par `SAIN`. Le mobile doit suivre cette même logique avec une approche contextuelle par opération.

## Objectifs

1. **Remplacer ACTIF par SAIN** dans tout le code mobile (57 occurrences identifiées)
2. **Implémenter une logique contextuelle** par type d'opération
3. **Maintenir la cohérence** avec le backend
4. **Améliorer l'expérience utilisateur** avec des filtres adaptés

## État Actuel du Backend

### Statuts disponibles dans `animals.statut`
- `SAIN` : Animal sain, disponible pour toutes les opérations
- `MALADE` : Animal malade, disponible pour vente (abattage) et traitements
- `EN_TRAITEMENT` : Animal en cours de traitement, disponible pour vente
- `VENDU` : Animal vendu, indisponible
- `MORT` : Animal décédé, indisponible
- `PERDU` : Animal perdu, indisponible

### Statuts disponibles dans `evenements.statut_avant` et `evenements.statut_apres`
- `SAIN` : État sain
- `VENDU` : État vendu
- `MORT` : État mort
- `PERDU` : État perdu

## Tâches d'Implémentation

### 1. Remplacement Global ACTIF → SAIN

**Fichiers à modifier :**
- Rechercher toutes les occurrences de `'ACTIF'` dans le code mobile
- Remplacer par `'SAIN'`
- Rechercher toutes les occurrences de `'INACTIF'` dans le code mobile
- Remplacer par la logique appropriée (voir section 2)

**Commande de recherche :**
```bash
grep -r "ACTIF" --include="*.ts" --include="*.tsx" --include="*.js" --include="*.jsx"
```

### 2. Adaptation des Filtres Contextuels

#### 2.1. Pour la Reproduction (Stricte)

**Logique :** Seuls les animaux SAIN peuvent se reproduire

```typescript
// Ancien code
const femellesReproductrices = animals.filter(a => a.statut === 'ACTIF' && a.sexe === 'femelle');

// Nouveau code
const femellesReproductrices = animals.filter(a => 
  a.statut === 'SAIN' &&  // Seuls les animaux sains peuvent se reproduire
  a.sexe === 'femelle' &&
  !a.estEnGestation() // Vérifier via événements si nécessaire
);
```

#### 2.2. Pour la Vente (Flexible)

**Logique :** Peut vendre SAIN, MALADE, ou EN_TRAITEMENT (pour abattage)

```typescript
// Ancien code
const animauxVendables = animals.filter(a => a.statut === 'ACTIF');

// Nouveau code
const animauxVendables = animals.filter(a => 
  !['VENDU', 'MORT', 'PERDU'].includes(a.statut)
  // Inclut SAIN, MALADE, EN_TRAITEMENT
);
```

#### 2.3. Pour la Santé (Spécifique)

**Logique :** Filtres spécifiques par état sanitaire

```typescript
// Ancien code
const animauxSains = animals.filter(a => a.statut === 'ACTIF');

// Nouveau code
const animauxSains = animals.filter(a => a.statut === 'SAIN');
const animauxMalades = animals.filter(a => a.statut === 'MALADE');
const animauxEnTraitement = animals.filter(a => a.statut === 'EN_TRAITEMENT');
```

### 3. Création de Propriétés Calculées (Recommandé)

**Dans votre modèle Animal :**

```typescript
class Animal {
  // ... autres propriétés
  
  /**
   * Disponible pour la reproduction (stricte)
   */
  get disponiblePourReproduction(): boolean {
    return this.statut === 'SAIN';
  }
  
  /**
   * Disponible pour la vente (flexible - inclut abattage)
   */
  get disponiblePourVente(): boolean {
    return !['VENDU', 'MORT', 'PERDU'].includes(this.statut);
  }
  
  /**
   * Disponible pour les opérations sanitaires
   */
  get disponiblePourSante(): boolean {
    return ['SAIN', 'MALADE', 'EN_TRAITEMENT'].includes(this.statut);
  }
  
  /**
   * Indicateur général de disponibilité
   */
  get estActif(): boolean {
    return this.statut === 'SAIN';
  }
}
```

### 4. Mise à jour des Écrans

#### 4.1. Écran de Vente

```typescript
// Dans AnimalVenteScreen
const animauxVendables = animals.filter(a => 
  a.disponiblePourVente() // Utiliser la propriété calculée
);
```

#### 4.2. Écran de Reproduction

```typescript
// Dans ReproductionScreen
const femellesDisponibles = animals.filter(a => 
  a.sexe === 'femelle' && 
  a.disponiblePourReproduction() // Utiliser la propriété calculée
);
```

#### 4.3. Écran de Santé

```typescript
// Dans SanteScreen
const animauxSains = animals.filter(a => a.statut === 'SAIN');
const animauxMalades = animals.filter(a => a.statut === 'MALADE');
const animauxEnTraitement = animals.filter(a => a.statut === 'EN_TRAITEMENT');
```

### 5. Normalisation Sync Mobile

**Dans le code de synchronisation :**

```typescript
// Avant d'envoyer les données au backend
function normalizeStatutsForSync(data: any, table: string): any {
  const normalized = { ...data };
  
  // Pour la table animals
  if (table === 'animals' && normalized.statut) {
    if (normalized.statut === 'ACTIF' || normalized.statut === 'INACTIF') {
      normalized.statut = 'SAIN';
    }
  }
  
  // Pour la table evenements
  if (table === 'evenements') {
    if (normalized.statut_avant === 'ACTIF' || normalized.statut_avant === 'INACTIF') {
      normalized.statut_avant = 'SAIN';
    }
    if (normalized.statut_apres === 'ACTIF' || normalized.statut_apres === 'INACTIF') {
      normalized.statut_apres = 'SAIN';
    }
  }
  
  return normalized;
}
```

### 6. Mise à jour des Enums TypeScript

**Dans vos fichiers de définition :**

```typescript
// Ancien enum
enum AnimalStatut {
  ACTIF = 'ACTIF',
  INACTIF = 'INACTIF',
  MALADE = 'MALADE',
  // ...
}

// Nouvel enum
enum AnimalStatut {
  SAIN = 'SAIN',
  MALADE = 'MALADE',
  EN_TRAITEMENT = 'EN_TRAITEMENT',
  VENDU = 'VENDU',
  MORT = 'MORT',
  PERDU = 'PERDU',
}
```

### 7. Tests et Validation

#### 7.1. Tests Unitaires

```typescript
describe('Animal statut logic', () => {
  it('devrait identifier les animaux disponibles pour reproduction', () => {
    const animalSain = new Animal({ statut: 'SAIN', sexe: 'femelle' });
    const animalMalade = new Animal({ statut: 'MALADE', sexe: 'femelle' });
    
    expect(animalSain.disponiblePourReproduction()).toBe(true);
    expect(animalMalade.disponiblePourReproduction()).toBe(false);
  });
  
  it('devrait identifier les animaux disponibles pour vente', () => {
    const animalSain = new Animal({ statut: 'SAIN' });
    const animalMalade = new Animal({ statut: 'MALADE' });
    const animalVendu = new Animal({ statut: 'VENDU' });
    
    expect(animalSain.disponiblePourVente()).toBe(true);
    expect(animalMalade.disponiblePourVente()).toBe(true);
    expect(animalVendu.disponiblePourVente()).toBe(false);
  });
});
```

#### 7.2. Tests d'Intégration

- Tester la synchronisation avec le backend
- Vérifier que les statuts sont correctement normalisés
- Tester les filtres dans chaque écran

### 8. Mise à jour de la Documentation

**Mettre à jour les commentaires dans le code :**
- Remplacer les références à ACTIF par SAIN
- Documenter la logique contextuelle
- Ajouter des exemples d'utilisation

## Checklist de Validation

- [ ] Toutes les occurrences de ACTIF remplacées par SAIN
- [ ] Filtres de reproduction adaptés (stricte)
- [ ] Filtres de vente adaptés (flexible)
- [ ] Filtres de santé adaptés (spécifique)
- [ ] Propriétés calculées créées
- [ ] Sync normalisé
- [ ] Enums TypeScript mises à jour
- [ ] Tests unitaires passants
- [ ] Tests d'intégration passants
- [ ] Documentation mise à jour

## Notes Importantes

1. **Ne pas modifier la structure de données** - Garder un seul champ `statut`
2. **Contextualiser la logique** - Adapter les filtres par opération
3. **Maintenir la cohérence** - Sync avec le backend
4. **Tester les cas limites** - Vente d'animaux malades, reproduction, etc.

## Support

En cas de problème, consulter :
- `docs/MOBILE_ETAT_SANTE_STATUT.md` pour les règles métier
- `docs/MOBILE_MIGRATION.md` pour les guidelines de migration
- Le backend `SyncController.php` pour la logique de normalisation
