# Rapport de Corrections - Architecture Multi-Farms + Offline-First

## 📋 Résumé des Corrections

Ce rapport détaille les corrections apportées à l'architecture existante pour la rendre conforme aux exigences multi-farms SaaS et offline-first sécurisées.

---

## 🔍 Problèmes Identifiés

### 1. **Tables Référentielles Incorrectement Configurées**
- **Problème**: Les tables `especes`, `categories`, et `type_evenements` avaient `sync_status` et `last_modified_by`
- **Impact**: Ces tables sont globales et ne doivent pas être traitées comme offline-first complets
- **Correction**: Suppression des champs de synchronisation des tables de référence

### 2. **Gestion des Conflits Basée sur `updated_at`**
- **Problème**: Le système utilisait `updated_at` pour la détection de conflits
- **Impact**: Risque de conflits non détectés en cas de modifications rapides
- **Correction**: Ajout d'un champ `version` pour une gestion robuste des conflits

### 3. **Absence de Filtrage Automatique par Farm**
- **Problème**: Aucun middleware ou scope global pour filtrer par `farm_id`
- **Impact**: Risque d'accès cross-farm (données d'une ferme accessibles par un utilisateur d'une autre)
- **Correction**: Création de `FarmContextMiddleware` et trait `HasFarmScope`

### 4. **Validation Insuffisante dans SyncController**
- **Problème**: Le push ne validait pas l'accès à la farm
- **Impact**: Possibilité de pousser des données vers une farm non autorisée
- **Correction**: Validation stricte de l'accès utilisateur ↔ farm dans les endpoints sync

---

## ✅ Corrections Appliquées

### A. Migrations (ALTER TABLE uniquement)

#### 1. Ajout du champ `version` aux tables métier
**Fichier**: `database/migrations/2026_06_06_000001_add_version_to_business_tables.php`

- Tables modifiées: `animals`, `transactions`, `evenements`, `lots`, `notifications`, `naissances`
- Champ ajouté: `version` (unsigned integer, default 1)
- Index ajouté sur `version`

**Raison**: Permettre une gestion robuste des conflits basée sur le versioning au lieu de `updated_at`

#### 2. Suppression des champs de sync des tables de référence
**Fichier**: `database/migrations/2026_06_06_000002_remove_sync_fields_from_reference_tables.php`

- Tables modifiées: `especes`, `categories`, `type_evenements`
- Champs supprimés: `sync_status`, `last_modified_by`
- Foreign key supprimée: `last_modified_by` → `users`

**Raison**: Les tables de référence sont globales et ne doivent pas être traitées comme offline-first

---

### B. Middleware de Contexte Farm

#### 1. FarmContextMiddleware
**Fichier**: `app/Http/Middleware/FarmContextMiddleware.php`

- Vérifie l'authentification de l'utilisateur
- Valide le `farm_id` (via header `X-Farm-ID` ou paramètre `farm_id`)
- Vérifie que l'utilisateur a accès à la farm via `farm_user` ou est owner
- Stocke le `farm_id` dans la requête pour usage ultérieur

**Raison**: Garantir que chaque requête est correctement contextualisée par farm

---

### C. Trait de Scope Global Farm

#### 1. HasFarmScope
**Fichier**: `app/Traits/HasFarmScope.php`

- Ajoute un scope global pour filtrer automatiquement par `farm_id`
- Utilise `current_farm_id` de la requête (défini par le middleware)
- Fournit des méthodes utilitaires: `withoutFarmScope()`, `forFarm()`

**Raison**: Empêcher les accès cross-farm automatiquement au niveau modèle

---

### D. Modèles Mis à Jour

#### Modèles Métier (avec HasFarmScope + version)
- `app/Models/Animal.php`
- `app/Models/Transaction.php`
- `app/Models/Evenement.php`
- `app/Models/Lot.php`
- `app/Models/Notification.php`
- `app/Models/Naissance.php`

**Modifications**:
- Ajout du trait `HasFarmScope`
- Ajout de `version` dans `$fillable`
- Relation `farm()` déjà présente

#### Modèles de Référence (sans sync fields)
- `app/Models/Espece.php`
- `app/Models/Categorie.php`
- `app/Models/TypeEvenement.php`

**Modifications**:
- Suppression de `sync_status` et `last_modified_by` de `$fillable`
- Suppression des casts associés

---

### E. SyncController Amélioré

**Fichier**: `app/Http/Controllers/Api/SyncController.php`

#### Modifications dans `push()`:
- Ajout de la validation de `farm_id` obligatoire
- Vérification de l'accès utilisateur ↔ farm avant traitement
- Validation que le `farm_id` dans les données correspond au `farm_id` de la requête
- Injection automatique du `farm_id` pour les tables métier

#### Modifications dans `processChange()`:
- Détection de conflit basée sur `version` au lieu de `updated_at`
- Logique: si `client_version !== server_version` → conflict
- Incrémentation automatique de `version` à chaque update
- Marquage comme `conflict` en cas de mismatch
- Distinction entre tables métier (avec version) et tables de référence (sans version)

#### Modifications dans `pull()`:
- Déjà sécurisé avec validation farm_id (inchangé)

---

### F. Configuration Middleware

**Fichier**: `bootstrap/app.php`

- Enregistrement de l'alias `farm.context` pour `FarmContextMiddleware`

---

### G. Routes API

**Fichier**: `routes/api.php`

- Ajout du middleware `farm.context` aux routes sync:
  - `POST /sync/push`
  - `GET /sync/pull`

---

## 🔐 Sécurité Améliorée

### 1. Isolation des Données par Farm
- **Avant**: Aucun filtrage automatique, risque d'accès cross-farm
- **Après**: Filtrage automatique via `HasFarmScope` + validation middleware

### 2. Validation d'Accès
- **Avant**: Validation seulement dans `pull()`
- **Après**: Validation dans `push()` ET `pull()` + middleware global

### 3. Gestion des Conflits
- **Avant**: Basée sur `updated_at` (risque de race conditions)
- **Après**: Basée sur `version` (robuste et prédictible)

---

## 📊 Liste des Fichiers Modifiés/Créés

### Nouveaux Fichiers (7)
1. `database/migrations/2026_06_06_000001_add_version_to_business_tables.php`
2. `database/migrations/2026_06_06_000002_remove_sync_fields_from_reference_tables.php`
3. `app/Http/Middleware/FarmContextMiddleware.php`
4. `app/Traits/HasFarmScope.php`
5. `app/Http/Controllers/Api/SyncController.php` (refactor complet)
6. `bootstrap/app.php` (modification)
7. `routes/api.php` (modification)

### Fichiers Modifiés (9)
8. `app/Models/Animal.php`
9. `app/Models/Transaction.php`
10. `app/Models/Evenement.php`
11. `app/Models/Lot.php`
12. `app/Models/Notification.php`
13. `app/Models/Naissance.php`
14. `app/Models/Espece.php`
15. `app/Models/Categorie.php`
16. `app/Models/TypeEvenement.php`

**Total: 16 fichiers**

---

## 🚀 Instructions de Déploiement

### 1. Exécuter les migrations
```bash
php artisan migrate
```

### 2. Vérifier l'enregistrement du middleware
Le middleware est déjà enregistré dans `bootstrap/app.php`

### 3. Tester l'API sync
Les endpoints sync nécessitent maintenant:
- Header `X-Farm-ID` OU paramètre `farm_id`
- Authentification via Sanctum
- Accès validé à la farm

### 4. Mettre à jour le client mobile
Le client doit:
- Envoyer `farm_id` dans chaque requête sync
- Gérer le champ `version` pour la détection de conflits
- Envoyer `version` dans les updates et deletes

---

## ⚠️ Risques Résiduels

### 1. Endpoints Non-Sync
- Les endpoints API existants (hors sync) ne sont pas encore protégés par `farm.context`
- **Recommandation**: Ajouter `farm.context` à tous les endpoints métier

### 2. Requêtes Directes Modèle
- Les requêtes Eloquent directes dans le code peuvent contourner le scope global
- **Recommandation**: Utiliser `withoutFarmScope()` uniquement quand nécessaire et documenté

### 3. Données Existantes
- Si des données existent déjà, elles n'ont pas de `version` (sera 1 par défaut)
- **Recommandation**: Si base de données non vide, exécuter un script de migration des versions

---

## 📝 Notes Techniques

### Versioning
- `version` commence à 1 pour les nouveaux enregistrements
- Chaque update incrémente `version`
- Les deletes softs incrémentent aussi `version`
- Conflit détecté si `client_version !== server_version`

### Tables de Référence
- `especes`, `categories`, `type_evenements` sont globales
- Elles sont toujours synchronisées via pull mais sans versioning
- Elles ne sont pas filtrées par farm_id

### Middleware FarmContext
- Peut recevoir `farm_id` via header `X-Farm-ID` OU paramètre `farm_id`
- Priorité: header > paramètre
- Stocke `current_farm_id` dans la requête pour usage par les scopes

---

## ✅ Validation des Corrections

### Tests Recommandés
1. **Test d'accès cross-farm**: Tenter d'accéder à une farm non autorisée → 403
2. **Test de conflit**: Modifier un enregistrement côté serveur puis côté mobile → conflict détecté
3. **Test de version**: Vérifier que `version` s'incrémente à chaque update
4. **Test de scope**: Vérifier que les requêtes Eloquent sont filtrées par farm_id
5. **Test de référence**: Vérifier que les tables de référence sont accessibles sans farm_id

---

## 🎯 Conclusion

L'architecture a été corrigée pour:
- ✅ Sécuriser l'accès multi-farms (middleware + scopes)
- ✅ Améliorer la gestion des conflits (versioning)
- ✅ Corriger le traitement des tables de référence (suppression sync fields)
- ✅ Valider l'accès dans tous les endpoints sync

Le système est maintenant conforme aux exigences SaaS multi-farms sécurisé + offline-first robuste.

**Aucune migration fresh n'a été nécessaire** - toutes les corrections sont des ALTER TABLE ou modifications de code.
