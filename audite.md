# Audit profond & état actuel — Module d’authentification (FasoLivestock API)

> Document destiné à servir de **base de connaissance** pour une IA externe : décrit l’implémentation actuelle (contrôleurs, services, requêtes, modèle(s), migrations, routes) et les points d’attention.

---

## 1) Vue d’ensemble technique

- Framework : **Laravel (API REST)**
- Authentification finale : **Laravel Sanctum** (tokens via `createToken(...)->plainTextToken`)
- Authentification “à deux étapes” :
  1. **login/register** : vérification du mot de passe (au login) puis génération d’un **challenge 2FA**
  2. **verify-2fa** : validation d’un code 6 chiffres stocké **hashé** → création du **token Sanctum**
- Réinitialisation de mot de passe : tokens stockés **hashés** dans `password_reset_tokens`
- Rôles/permissions : **Spatie Laravel Permission** (`HasRoles`) ; rôle par défaut `gerant` au register
- Architecture :
  - `AuthController` (controller HTTP)
  - `AuthService` (logique métier)
  - `FormRequest` (validation & erreurs JSON)
  - `TwoFactorVerification` + `PasswordResetToken` (modèles)
  - `TwoFactorEmailVerification` (mail 2FA)

---

## 2) Endpoints et parcours d’authentification

### 2.1 Routes (fichier `routes/api.php`)

Préfixe : `auth`

- `POST /auth/register`
  - throttle: `5,1` (route)
  - handler: `AuthController@register`
- `POST /auth/login`
  - throttle: `5,1`
  - handler: `AuthController@login`
- `POST /auth/forgot-password`
  - throttle: `3,1`
  - handler: `AuthController@forgotPassword`
- `POST /auth/reset-password`
  - throttle: `5,1`
  - handler: `AuthController@resetPassword`
- `POST /auth/verify-2fa`
  - throttle: `10,1`
  - handler: `AuthController@verify2fa`

Routes protégées :
- middleware `auth:sanctum`
  - `POST /auth/logout` → `AuthController@logout`
  - `GET /auth/me` → `AuthController@me`

Routes Sync (non-authentification mais dépend du mécanisme token) :
- middleware `auth:sanctum` + `farm.context`
  - `POST /sync/push`
  - `GET /sync/pull`

---

## 3) Contrôleur : `app/Http/Controllers/Api/AuthController.php`

### 3.1 Injection
- Injecte `AuthService` via constructeur.

### 3.2 Méthodes

#### `register(RegisterRequest $request)`
- Appelle `AuthService::register($request->validated())`
- Retourne `ApiResponse::success($result, 'Utilisateur créé avec succès')`
- Gestion exceptions :
  - `UserNotFoundException` → 404
  - `
Exception` → 400

#### `login(LoginRequest $request)`
- Appelle `AuthService::login(...)`
- Gestion exceptions :
  - `UserNotFoundException` → 404
  - `AuthenticationException` → 401
  - `
Exception` → 400

#### `forgotPassword(ForgotPasswordRequest $request)`
- Appelle `AuthService::forgotPassword(...)`
- Gestion exceptions :
  - `UserNotFoundException` → 404
  - `
Exception` → 400

#### `resetPassword(ResetPasswordRequest $request)`
- Appelle `AuthService::resetPassword(...)`
- Gestion exceptions :
  - `InvalidTokenException` → 422
  - `UserNotFoundException` → 404
  - `
Exception` → 400

#### `verify2fa(Verify2FARequest $request)`
- Appelle `AuthService::verify2fa(...)`
- Gestion exceptions :
  - `InvalidTokenException` → 422
  - `
Exception` → 400

#### `me(Request $request)`
- Appelle `AuthService::me($request->user())`
- Réponse : profil + rôles + permissions

#### `logout(Request $request)`
- Appelle `AuthService::logout($request->user())`

---

## 4) Service métier : `app/Services/AuthService.php`

### 4.1 `register(array $data): array`

Flux :
1. Création `User` :
   - `id` = UUID string
   - `name`, `email`, `telephone`
   - `password` hashé
   - `last_sync_at` = `now()`
2. Attribution rôle par défaut : `assignRole('gerant')`
3. Log via `AuthLogger::userRegistered(...)`
4. Choix du channel :
   - si `email` non vide → `channel=email` et `identifier=email`
   - sinon `channel=phone` et `identifier=telephone`
5. Appel privé `createAndSend2FA($user, $channel, $identifier)`
6. Retourne un payload orienté front :
   - `user` (objet)
   - `pending_2fa: true`
   - `verification_id`
   - `channel`
   - `roles` = `getRoleNames()`

**Important** : le service *refuse* la 2FA téléphone (voir section 4.6).

---

### 4.2 `login(array $data): array`

Flux :
1. `login = $data['login']` ; recherche `User` par :
   - `email == login` OR `telephone == login`
2. Si absent → `UserNotFoundException`
3. Vérification password : `Hash::check($data['password'], $user->password)`
   - échec → `AuthenticationException`
4. Met à jour `last_sync_at` = now
5. Détermine le channel :
   - si `$user->email` existe et si `login === $user->email` → channel=email
   - sinon channel=phone
6. Appel `createAndSend2FA` avec `identifier` correspondant
7. Retourne payload identique à register : `pending_2fa=true`, `verification_id`, etc.

---

### 4.3 `forgotPassword(array $data): array`

- Recherche user par `login` (email OU telephone)
- Si absent → `UserNotFoundException`
- Génère token : `Str::random(64)` puis:
  - supprime anciens tokens de l’utilisateur (`PasswordResetToken::where(...)->delete()`)
  - crée un nouveau `PasswordResetToken` :
    - `token` = `Hash::make($token)`
    - `expires_at` = now + 60 minutes
    - `used=false`
- Log via `AuthLogger::passwordResetTokenGenerated(...)`
- TODO indiqué : envoi email / sms non implémenté ici.

Retourne :
- `reset_token` (token en clair)
- `message`: “Un email de réinitialisation a été envoyé.”

---

### 4.4 `resetPassword(array $data): array`

1. Trouve `User` par `login` (email OU telephone)
2. Cherche token valide : `PasswordResetToken::where('user_id', ...)->valid()->first()`
   - si absent → `InvalidTokenException('Token invalide.')`
3. Vérifie token en clair : `Hash::check($data['token'], $reset->token)`
   - échec → `InvalidTokenException('Token invalide.')`
4. Vérifie expiration : `expires_at < now()` → `InvalidTokenException('Token expiré.')`
5. Met à jour `user.password` hashé et `last_sync_at`
6. Marque token utilisé : `used=true` et `used_at=now()`
7. Log via `AuthLogger::passwordResetSuccessful(...)`
8. Retour : `message` succès

---

### 4.5 `me($user): array`

Retourne :
- `user` (objet)
- `roles` = `getRoleNames()`
- `permissions` = `getAllPermissions()->pluck('name')`

---

### 4.6 2FA : `createAndSend2FA(User $user, string $channel, string $identifier): TwoFactorVerification`

Implémentation actuelle :
- Le code indique explicitement **MVP: email uniquement**
- Si `$channel !== 'email'` → `AuthenticationException('2FA par téléphone non disponible pour le moment.')`

Sinon :
1. Génère code numérique : `random_int(100000, 999999)`
2. Hash du code : `Hash::make($code)`
3. Crée `TwoFactorVerification` (table `two_factor_verifications`) :
   - `user_id`
   - `channel`
   - `identifier`
   - `code_hash`
   - `expires_at` = now + 10 minutes
   - `used=false`
   - `attempts=0`
4. Envoi du mail :
   - `Mail::to($identifier)->send(new TwoFactorEmailVerification($code, $identifier, 10));`

Retour : l’objet `TwoFactorVerification` créé.

---

### 4.7 `verify2fa(array $data): array`

1. Charge `TwoFactorVerification` par `id` + scope `valid()`
   - scope `valid()` = `used=false` et `expires_at > now()`
2. Si absent → `InvalidTokenException('2FA invalide ou expirée.')`
3. Si `attempts >= 5` → `InvalidTokenException('Trop de tentatives.')`
4. Vérification code : `Hash::check($data['code'], $verification->code_hash)`
   - échec : `increment('attempts')` puis `InvalidTokenException('Code 2FA incorrect.')`
5. Si succès :
   - met `used=true`, `used_at=now()`
6. Récupère le `User` via relation `verification->user`
7. Crée un token Sanctum :
   - `$user->createToken('auth_token')->plainTextToken`
8. Retour :
   - `user`
   - `token` (en clair)
   - `roles`.

---

## 5) Requêtes validées (FormRequest)

### 5.1 `LoginRequest`
- `login`: required|string
- `password`: required|string|min:12
- `failedValidation` renvoie JSON `{success:false, message, errors}`

### 5.2 `RegisterRequest`
- `name`: required|string|max:100
- `email`: nullable|email|unique:users,email|required_without:telephone|prohibited_if:telephone,
- `telephone`: nullable|string|max:20|unique:users,telephone|required_without:email|prohibited_if:email,
- `password`: required|string|min:12|confirmed
- `failedValidation` renvoie JSON 422

### 5.3 `ForgotPasswordRequest`
- `login`: required|string

### 5.4 `ResetPasswordRequest`
- `login`: required|string
- `token`: required|string
- `password`: required|string|min:12|confirmed

### 5.5 `Verify2FARequest`
- `verification_id`: required|uuid
- `code`: required|digits:6

---

## 6) Modèles et migrations (base de données)

### 6.1 Table `users` (migration `0001_01_01_000000_create_users_table.php`)
- PK : UUID (`uuid('id')->primary()`) 
- Champs : `name`, `email unique`, `telephone nullable`, `password`, `is_active`, `last_sync_at` nullable,
  `email_verified_at` nullable, soft deletes, timestamps.

### 6.2 Table `two_factor_verifications` (migration `2026_05_23_200000_create_two_factor_verifications_table.php`)
- `id` : uuid PK
- `user_id` : uuid index + FK → `users(id)` cascade delete
- `channel` : string(20) index (`email|phone`)
- `identifier` : string(255) index (email/phone)
- `code_hash` : string(255)
- `expires_at` : timestamp
- `used` : boolean default false
- `used_at` nullable timestamp
- `attempts` : unsignedSmallInteger default 0
- created_at/updated_at (nullable dans migration)

**Modèle** : `app/Models/TwoFactorVerification.php`
- `protected $incrementing = false;`
- booted() : set `id` UUID si vide
- cast : `expires_at` datetime, `used` boolean, `attempts` integer
- relation `user()` → belongsTo(User::class)
- scope `valid()` : `used=false` + `expires_at > now()`

### 6.3 Table `password_reset_tokens`

- Création initiale dans `0001_01_01_000000_create_users_table.php` :
  - `id` uuid PK
  - `identifier`, `token`, `type` default `email`, `expires_at`, `used`

- Correction / refonte dans `2026_05_23_000001_fix_password_reset_tokens_table.php` :
  - Ajoute `user_id` uuid FK → users
  - Ajoute colonnes : `expires_at` (si absent), `used`, `used_at`, `deleted_at` (soft deletes)
  - Supprime colonnes `email`, `telephone`, `type` si présentes

**Modèle** : `app/Models/PasswordResetToken.php`
- `$fillable` : `user_id, token, expires_at, used, used_at`
- casts : datetime & boolean
- scope `valid()` : `expires_at > now()` + `used=false`

### 6.4 Table Sanctum `personal_access_tokens`

- migration `2026_05_22_124201_create_personal_access_tokens_table.php` :
  - `token` unique, `abilities`, `expires_at`, etc.

---

## 7) Envoi 2FA par email

### 7.1 Mailable : `app/Mail/TwoFactorEmailVerification.php`
- `build()` :
  - subject `Votre code de vérification (2FA)`
  - view `emails.two_factor_email_verification`
  - variables : `code`, `email`, `expiresMinutes`

### 7.2 Template : `resources/views/emails/two_factor_email_verification.blade.php`
- HTML simple ; affiche le code + durée d’expiration.

---

## 8) Journalisation (logging)

### 8.1 `app/Helpers/AuthLogger.php`
- Utilise `Log::info` / `Log::warning`
- Méthodes :
  - `userRegistered`
  - `loginFailedUserNotFound`
  - `loginFailedWrongPassword`
  - `loginSuccessful`
  - `passwordResetRequestUserNotFound`
  - `passwordResetTokenGenerated`
  - `passwordResetInvalidToken`
  - `passwordResetExpiredToken`
  - `passwordResetUserNotFound`
  - `passwordResetSuccessful`
  - `userLoggedOut`

---

## 9) Points d’attention / incohérences repérées (important pour audit)

1. **2FA téléphone non disponible**
   - Le code de login/register peut choisir `channel=phone` si `telephone` est l’identifiant, mais `createAndSend2FA` lève une exception si `channel !== 'email'`.
   - Conséquence : un utilisateur ayant un téléphone mais sans email (ou login au téléphone) ne pourra pas compléter la 2FA.

2. **forgotPassword retourne un message d’email sans envoi effectif**
   - `AuthService::forgotPassword` contient un TODO : email/sms non implémenté.
   - Pourtant retourne `message: 'Un email de réinitialisation a été envoyé.'`.

3. **Schéma `password_reset_tokens` : double définition initiale et correction**
   - La migration initiale contient `type`, `identifier`.
   - La migration de correction ajoute `user_id`, `used_at` et supprime potentiellement `type/email/telephone`.
   - Le modèle `PasswordResetToken` n’utilise pas `identifier`/`type`, mais uniquement `user_id`/`token`/`expires_at`/`used`/`used_at`.
   - Risque : si la migration de fix n’a pas été appliquée correctement sur certaines bases, `resetPassword` peut casser.

4. **Double chargement de traits / doublon dans `User.php`**
   - `use HasFactory` est présent deux fois dans `User` :
     - `use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles,HasApiTokens, HasFactory;`
   - Ce n’est pas fatal dans tous les cas, mais c’est un signe de non-netteté.

5. **`verify2fa`: relation chargée implicitement**
   - `$verification->user` dépend de la relation `user()` ; ok.
   - Le service renvoie `token` (plain text) mais ne force pas l’usage d’un header standard côté client (c’est celui de Sanctum).

6. **Exposition “user” dans les réponses de register/login/verify2fa**
   - Le code retourne un objet `user` dans les réponses et `token`.
   - Selon `User::$hidden`, `password` est hidden : ok.
   - Mais le payload contient potentiellement des champs sensibles (à vérifier selon besoin projet).

---

## 10) Contrats implicites avec les clients (format JSON)

Bien que `ApiResponse` ne soit pas audité ici, on observe le pattern :
- `ApiResponse::success($result, message)`
- `ApiResponse::error($message, null, statusCode)`

Payload “métier” de register/login :
- `user`
- `pending_2fa: true`
- `verification_id`
- `channel`
- `roles`

Payload “métier” verify2fa :
- `user`
- `token`
- `roles`

Payload resetPassword :
- `{ message }`

Payload forgotPassword :
- `reset_token`
- `message`

---

## 11) Dépendances externes identifiées

- **Laravel Sanctum** (`HasApiTokens`, `auth:sanctum`) 
- **Spatie Laravel Permission** (`HasRoles`, `assignRole`, `getAllPermissions`) 
- **Laravel Mail** pour 2FA

---

## 12) Résumé exécutable pour une IA externe

- Le backend implémente un login à **2FA obligatoire** avant l’obtention du **token Sanctum**.
- Le 2FA est actuellement **implémenté uniquement par email** (pas par téléphone) alors que des routes et validations permettent un `telephone` comme identifiant.
- Après succès `verify-2fa`, le token est créé et sert à appeler `/auth/me` et `/auth/logout` et toutes les routes protégées (ex: `/sync/*`).
- La réinitialisation de mot de passe utilise un token **hashé** et expire après **60 minutes**, marqué utilisé après reset.
- La base de données inclut `two_factor_verifications` et `personal_access_tokens`; `password_reset_tokens` a subi une migration de correction (ajout `user_id`, `used_at`, suppression de colonnes initiales).

---

## 13) Fichiers clés du module d’authentification

- `app/Http/Controllers/Api/AuthController.php`
- `app/Services/AuthService.php`
- `app/Http/Requests/Auth/*` : `RegisterRequest`, `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`, `Verify2FARequest`
- `app/Models/User.php`
- `app/Models/TwoFactorVerification.php`
- `app/Models/PasswordResetToken.php`
- `app/Mail/TwoFactorEmailVerification.php`
- `resources/views/emails/two_factor_email_verification.blade.php`
- `routes/api.php`
- Migrations :
  - `0001_01_01_000000_create_users_table.php`
  - `2026_05_22_124201_create_personal_access_tokens_table.php`
  - `2026_05_23_200000_create_two_factor_verifications_table.php`
  - `2026_05_23_000001_fix_password_reset_tokens_table.php`

---

## 14) Conclusion

Le module d’authentification est cohérent du point de vue “login → 2FA → token Sanctum”. Cependant, il existe des incohérences fonctionnelles (2FA téléphone annoncé via `channel`, mais non supporté), et un flux de reset password qui indique un envoi email sans implémentation côté service.

Le fichier `audite.md` constitue maintenant une documentation autonome permettant à une IA externe de comprendre l’implémentation actuelle, la structure des tables et le chemin d’exécution HTTP.
