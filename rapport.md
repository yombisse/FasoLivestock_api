# Audit technique — Module Auth / Users / Roles & Permissions / Fermes (API + Admin)

Date: 2026-06-18  
Périmètre audité (fichiers examinés) :
- **Auth API** : `routes/api/auth.php`, `app/Http/Controllers/Api/AuthController.php`, `app/Services/AuthService.php`
- **Middleware & sécurité** :
  - `app/Http/Middleware/AdminAuth.php`
  - `app/Http/Middleware/CheckPermission.php`
  - `app/Http/Middleware/FarmContextMiddleware.php`
- **Roles/Permissions API** : `routes/api/roles.php`, `app/Http/Controllers/Api/RoleController.php`
- **Users API** : `app/Http/Controllers/Api/UserController.php`
- **Farms API** : `routes/api/farms.php`, `app/Http/Controllers/Api/FarmController.php`
- **Admin (UI) : controllers + services** :
  - `app/Http/Controllers/Admin/UserController.php`
  - `app/Http/Controllers/Admin/FarmController.php`
  - `app/Services/Admin/AdminApiService.php`
  - `app/Services/Admin/AuthApiService.php`
  - `app/Services/Admin/UserApiService.php`
  - `app/Services/Admin/RoleApiService.php`
  - `app/Services/Admin/FarmApiService.php`
- **Modèles** : `app/Models/User.php`, `app/Models/Farm.php`

---

## 1) Résumé exécutif

Globalement, votre code est **fonctionnel et structuré** autour de :
- Controllers API minces appelant des Services (pour l’auth) et/ou encapsulant la logique dans le controller (pour Users/Farms/Roles).
- Utilisation de **Laravel Sanctum** (auth:sanctum + tokens) pour l’API.
- Utilisation de **Spatie Permission** pour roles/permissions.
- Séparation UI Admin → appel interne au kernel Laravel via `AdminApiService` (sans requêtes HTTP externes).

Cependant, plusieurs sujets impactent la **cohérence**, la **sécurité** et la **maintenabilité** :
1. **Incohérence Auth Admin (session) vs Auth API (Sanctum)** : `AdminAuth` utilise une session `admin_token`, alors que `CheckPermission` / Sanctum reposent sur `request->user()` et Spatie permissions.
2. **Gaps de validation & sécurité** : certains endpoints acceptent/valident peu, et quelques erreurs “abort/ApiResponse mismatch” réduisent la cohérence de l’API.
3. **Problèmes de qualité de code** : duplication, imports inutilisés, debug `dd($response)` en production, et quelques patterns pouvant créer des bugs fonctionnels.
4. **Gestion 2FA et token** : `AuthService::login` rend un commentaire “token direct sans 2FA” alors que l’inscription crée un 2FA; logique de business à clarifier et sécuriser (flow attendu).
5. **FarmContextMiddleware** : vérification d’accès basée sur `Farm::where('id', $farmId)` avec `owner_id`/`users`, mais le controller API vérifie aussi. Redondance et risque de divergence.

---

## 2) Audit par zone

## 2.1 Auth API (register/login/forgot/reset/2FA)

### Observations positives
- `AuthController` centralise la réponse via `ApiResponse::success/error`.
- `AuthService::register` :
  - `Hash::make` correctement pour mots de passe.
  - génération 2FA avec `random_int` et stockage **hashé** (`code_hash`).
  - journalisation via `AuthLogger`.
- `resetPassword` vérifie :
  - token invalide/expiré
  - `Hash::check` contre `reset->token`
  - marque token comme utilisé (`used`, `used_at`).

### Problèmes / Risques
1. **Guard / rôle par défaut “superadmin” en production**
   - `AuthService::register` fait `assignRole('superadmin')` par défaut.
   - Risque majeur : n’importe quel nouvel inscrit devient superadmin.
2. **Flow 2FA non homogène**
   - `register` renvoie `pending_2fa=true` + `verification_id`, mais `login` émet un token direct **sans 2FA** (commentaire “token direct sans 2FA”).
   - Si vous souhaitez imposer 2FA après inscription, il faut empêcher l’accès sans vérification, ou déclencher un flow “login → pending_2fa → verify2fa”.
3. **Token/Session & invalidation**
   - `logout` supprime `currentAccessToken()` mais vous ne forcez pas la révocation sur d’autres tokens potentiellement “dangereux” (selon stratégie).
4. **Response data types**
   - `login` renvoie `user` en modèle Eloquent potentiellement non sérialisé proprement selon `ApiResponse`. (Ça dépend de `ApiResponse`.)
5. **Mail 2FA indisponible**
   - `createAndSend2FA` ne gère que channel email et lance exception si pas email.
   - Si vous acceptez téléphone, il faut un provider SMS ou fallback clair.

### Spécification de refactoring recommandée
- Ajouter une configuration “default_role_on_register” (env/setting), par défaut plutôt un rôle non-privilégié.
- Clarifier le flow :
  - Option A : **2FA obligatoire** → `login` retourne pending_2fa + verification_id, puis `verify2fa` émet le token.
  - Option B : **2FA conditionnel** → seulement pour utilisateurs configurés/opt-in.
- Introduire un “AuthPolicy” ou “LoginStrategy” (service) pour unifier les comportements.

---

## 2.2 Roles/Permissions (Spatie Permission)

### Observations positives
- `RoleController` utilise Spatie `Role` & `Permission`.
- Permissions groupées par module via parsing de `permission.name` (ex: `module.action`).
- Protection du rôle système : `superadmin` ne peut pas être modifié/supprimé.

### Problèmes / Risques
1. **Validation/autorisation incomplète dans certains endpoints**
   - Les routes API ont `middleware('permission:roles.*')`, mais dans le controller vous faites parfois des contrôles “superadmin”.
   - Globalement ok, mais à vérifier : `attachUser/detachUser` utilisent `User::find` sans scoper guard/guard_name, ni vérifier que `$role->guard_name` correspond.
2. **Formatage et champs**
   - `formatRole` retourne `permissions` via `$role->permissions->pluck('name')`.
   - Si relation non chargée dans certains cas, risque (selon chargement `baseQuery()`).

### Refactoring recommandé
- Centraliser la logique Spatie/guard_name dans un “RoleService”.
- Uniformiser la réponse (toujours `ApiResponse` + structure stable).
- Ajouter les tests pour :
  - attach/detach guard mismatch
  - roles/{id}/users pagination / performance si volumétrie

---

## 2.3 Users API

### Observations positives
- Endpoints CRUD complets + `trashed` & `restore`.
- Pagination + filtres via query builder.
- `UserController` utilise `StoreUserRequest/UpdateUserRequest` (bonne pratique), au moins sur création.

### Problèmes / Risques
1. **Erreur potentielle / incohérence lors du listing**
   - Dans `formatUser`, vous appelez `farms()->count()` si `farms_count` absent; ok.
2. **Gestion des roles en update**
   - `syncRoles($this->resolveRoles($request->roles))`
   - Si `$request->roles` est vide ou non présent, c’est conditionnel : ok.
   - Mais `resolveRoles` renvoie uniquement les rôles guard api. Si le front envoie des roles d’un autre guard → silencieux.
3. **Delete/destroy**
   - `destroy` fait :
     - `$user->update(['is_active' => false]);`
     - `$user->delete();`
   - Risque : confusion entre logique “archiver” et “soft delete”.
   - (Vous semblez viser soft delete + flag is_active.)
4. **Contrôle de superadmin dépend de `auth()->user()`**
   - Dans `update`, vous testez : “si la cible est superadmin et l’utilisateur courant n’est pas superadmin → 403”.
   - OK mais vérifier pour les autres endpoints (toggleActive/destroy).

### Refactoring recommandé
- Déplacer la logique CRUD + autorisations vers un `UserService` (comme Auth).
- Standardiser les erreurs : au lieu de `abort(403)` dans Farm ou patterns mixtes, toujours `ApiResponse::error` + status code.

---

## 2.4 Farms API

### Observations positives
- Accès conditionnel à `index` (superadmin → tout, sinon owner ou relation farm_user).
- `manageUsers` et `syncFarmUsers` encapsulent la gestion pivot `farm_user.role`.
- `authorizeAccess` protège avec `ownerOnly` et vérifie existence d’accès.

### Problèmes / Risques
1. **Redondance d’autorisation**
   - Vous avez `FarmContextMiddleware` qui vérifie l’accès via `X-Farm-ID`.
   - Et en parallèle `FarmController` applique `authorizeAccess`.
   - Cela crée un risque de divergence si un jour la logique change.
2. **Incohérence d’API error handling**
   - `authorizeAccess` utilise `abort(403, ...)`.
   - Alors que le reste utilise `ApiResponse::error`.
   - L’API ne renverra pas toujours une structure uniforme.
3. **Validation de `manageUsers`**
   - Vous validez via `$request->validate(...)`, mais les validate rules utilisent un format :
     - `users.*.id` = `required|uuid|exists:users,id`
     - `users.*.role` in `owner,manager,vet,worker`
   - Sur `storeFarmUsers`, vous ignorez potentiellement `pivot role` existant, mais c’est voulu (sync).
4. **Synchronisation pivot**
   - `syncWithoutDetaching` est utilisé, ce qui peut garder des anciens rôles si vous n’envoyez pas l’utilisateur.
   - Mais votre `manageUsers` appelle `syncFarmUsers($users, excludeId: owner)` donc ça ne “détache” pas d’utilisateurs non fournis → il est possible d’avoir une liste cumulée.
   - Ce comportement doit être explicite : “sync exact” ou “ajout/maintien”.

### Refactoring recommandé
- Unifier autorisation avec une policy :
  - `FarmPolicy` (Laravel Policies) ou un `FarmAccessService`.
- Décider la sémantique pivot :
  - **Option 1** : sync exact → utiliser `sync` (ou `syncWithoutDetaching` mais avec `detach` explicite).
  - **Option 2** : ajout/maintien → conserver `syncWithoutDetaching`.
- Remplacer `abort()` par `ApiResponse::error()` pour la cohérence.

---

## 2.5 Admin (UI) : controllers + appel interne aux APIs

### Observations positives
- Séparation UI Admin → services `App\Services\Admin\*`.
- `AdminApiService` utilise un appel interne au kernel (`LaravelRequest::create` + `$kernel->handle($request)`), ce qui évite des HTTP externes.

### Problèmes / Risques
1. **Debug restant en production**
   - `app/Http/Controllers/Admin/UserController.php` contient `dd($response);` dans `destroy`.
   - Critique : stop serveur en runtime.
2. **Gestion d’erreurs incohérente**
   - `AdminApiService::internalRequest` renvoie `data` même quand `success=false`, mais ne remonte pas toujours le corps original.
   - `handleApiError` utilise `response['status']` mais parfois `status` n’est pas présent dans la structure renvoyée par le catch.
3. **Injection de token via session**
   - `AdminApiService` lit `session('admin_token')`.
   - Or côté API, vous utilisez `auth:sanctum`. Il faut s’assurer que l’admin token renvoyé par `/auth/login` est bien Sanctum token et accepté par `auth:sanctum`.
4. **Manque de typage/contrats**
   - Les services renvoient un tableau “success/status/data” non typé. Le front UI doit “deviner” la structure.

### Refactoring recommandé
- Supprimer `dd`.
- Introduire un DTO / Value Object “ApiResult” pour uniformiser.
- Le plus robuste : gérer les appels API via un trait/Client (ou un wrapper) qui garantit :
  - status code
  - payload error
  - message standardisé
- Documenter les endpoints utilisés par l’admin (contrat).

---

## 3) Problèmes transverses (Best practices)

### 3.1 Qualité du code
- Imports inutilisés (ex: `Log` non utilisé dans certains controllers).
- Duplications (`HasFactory` importé deux fois dans `User.php`).
- Indentation et commentaires “internes” (ex: block dans AuthController) à normaliser.

### 3.2 Cohérence d’API responses
- Mélange de :
  - `ApiResponse::error`
  - `abort(403, ...)` (ex: Farm)
- Besoin : une stratégie unique “toutes les erreurs API retournent la même forme”.

### 3.3 Sécurité & Authorization
- Superadmin assigné automatiquement au register.
- Certaines validations ne couvrent pas tout (ex: assignRole attachUser/detachUser) selon guard.

---

## 4) Plan de refactoring (spécifications techniques)

> Objectif : fiabiliser, sécuriser, homogénéiser réponses + autorisations, et améliorer la maintenabilité.

### Phase 0 — Correctifs critiques (immédiats)
1. **Supprimer `dd($response)`** dans `Admin\UserController::destroy`.
2. **Corriger la logique de rôle au register** :
   - Ne pas assigner “superadmin” par défaut en dur.
   - Utiliser une config/role par défaut “user”/“pending”.

### Phase 1 — Harmoniser l’API error handling
1. Mettre en place une stratégie uniforme :
   - Tous les endpoints renvoient via `ApiResponse`.
   - Remplacer `abort(...)` dans `FarmController::authorizeAccess` par `ApiResponse::error(...)`.
2. Centraliser l’exception handling via `app/Exceptions/Handler.php` (si non existant) :
   - mapper ValidationException, AuthenticationException, AuthorizationException vers la même structure.

### Phase 2 — Contrats (DTO) & services
1. Introduire des **contrats de réponse** (DTO / Value Objects) pour uniformiser `ApiResponse` :
   - Exemple : `ApiResult<T>` avec champs fixes `{ success, message, data, status }`.
   - Objectif : supprimer les suppositions côté Admin (ex: chemins `['data']['data']`).
2. Créer un **AuthServiceAdminAdapter** (ou client) pour harmoniser l’appel interne :
   - Aujourd’hui : token injecté via session `admin_token`.
   - Refactoring : encapsuler la logique “récupérer token, mettre Bearer, gérer codes”.
3. Déplacer la logique métier de `FarmController` (authorizeAccess + syncFarmUsers) vers :
   - un `FarmAccessService` (vérification droits)
   - et un `FarmMembershipService` (gestion pivot `farm_user`).

### Phase 3 — Autorisations via Policies (sécurité + cohérence)
1. Ajouter `FarmPolicy` :
   - `view`, `create`, `update`, `delete`, `manageMembers`
   - règles basées sur :
     - superadmin
     - owner (`owner_id`)
     - appartenance via pivot `farm_user`
2. Ajouter `UserPolicy` et protéger explicitement :
   - toggleActive/destroy
   - modifications superadmin (cible/acteur)
3. Uniformiser les checks de rôles :
   - au lieu de mélanger `abort`, `ApiResponse`, middlewares : appliquer une stratégie unique (policy -> gestion exception -> ApiResponse).

### Phase 4 — 2FA & flow Sanctum (fiabiliser le login)
1. Définir le flow de login attendu :
   - **Option recommandée (2FA obligatoire pour certains comptes)** :
     - `login` : si compte nécessite 2FA -> ne pas émettre token
     - retourner `{ pending_2fa: true, verification_id }`
     - `verify2fa` émet le token Sanctum.
2. Si 2FA est obligatoire, empêcher tout accès tant que `used=true` n’est pas confirmé.
3. Ajouter une gestion “brute force”/rate-limit cohérente :
   - throttle déjà présent dans `routes/api/auth.php`
   - compléter avec contrôle sur `attempts` (vous avez déjà `>=5`)

### Phase 5 — Qualité (code health) & suppression des risques
1. Supprimer toute trace debug en prod :
   - `dd($response)` dans `Admin\UserController::destroy`
2. Nettoyer les imports inutilisés et la duplication :
   - ex `HasFactory` importé deux fois dans `User.php`
3. Uniformiser les styles/indentations et la structure des commentaires.
4. Harmoniser la sémantique “archiver” vs “soft delete” :
   - `UserController::destroy` : clarifier entre `SoftDeletes` et flag `is_active`
   - `FarmController` : Idem (SoftDeletes présent côté modèle)

### Phase 6 — Tests & validation (recommandé avant déploiement)
#### Tests critiques API (à prioriser)
1. Auth :
   - register : rôle par défaut configurable (test que ce n’est pas forcé superadmin)
   - login :
     - mauvais password -> 401
     - utilisateur désactivé -> 401/403 selon spec
     - flow 2FA -> token seulement après `verify2fa`
   - forgot/reset :
     - token expiré -> 422
     - token utilisé -> 422
2. Roles/Permissions :
   - CRUD rôles (superadmin interdit)
   - attach/detach utilisateur :
     - permission manquante -> 403
     - guard_name (api) -> cohérence
3. Users :
   - update superadmin cible -> 403
   - toggleActive/destroy self -> 422/403 attendu
4. Farms :
   - ownerOnly update/delete
   - manageUsers : validation règles + pivot role autorisé
   - accès non autorisé -> 403 (structure ApiResponse uniforme)

#### Tests Admin UI (intégration)
1. Parcours UI principal :
   - login admin
   - list users/roles/farms
   - créer/modifier/supprimer -> aucun `dd`
2. Sécurité session :
   - session expirée -> redirection login + message

### Phase 7 — Documentation & contrat d’API
1. Documenter explicitement :
   - structure de `ApiResponse`
   - endpoints utilisés par Admin (`/auth/me`, `/users`, `/roles`, `/farms`)
2. Ajouter un `OpenAPI` ou au minimum un markdown `api-contract.md`.
3. Conventions de permissions :
   - ex `module.action` => ex `farms.view`, `roles.create`, etc.

---

## 5) Liste des actions recommandées (checklist rapide)

- [ ] Supprimer `dd($response)` (Admin\UserController)
- [ ] Retirer l’assignation automatique `superadmin` au register (ou la rendre configurable)
- [ ] Remplacer `abort(403, ...)` dans `Api\FarmController` par `ApiResponse::error(...)` (ou policy + exception handler)
- [ ] Unifier la stratégie “contrats de réponse” côté Admin et API (éviter `['data']['data']` fragile)
- [ ] Clarifier le flow 2FA : empêcher token direct tant que `verify2fa` non exécuté (si 2FA requis)
- [ ] Ajouter policies `FarmPolicy` / `UserPolicy` pour remplacer les checks dupliqués
- [ ] Écrire des tests API (auth/roles/users/farms) + tests UI admin smoke

---

## 6) Conclusion

Votre architecture montre une intention claire de séparation (Controller/Service) et l’usage de standards (Sanctum, Spatie Permission, SoftDeletes). Les principaux axes d’amélioration sont :
- **cohérence sécurité & autorisation** (policies + error handling uniforme),
- **fiabilisation du flow 2FA**,
- **réduction des risques de runtime** (debug `dd`),
- **homogénéisation des contrats API** entre Admin et API.

Ces changements rendront le module plus robuste, maintenable et plus simple à faire évoluer (notamment sur les règles de permissions et la gestion des fermes).
