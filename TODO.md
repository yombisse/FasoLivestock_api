# TODO - 2FA (email code numérique) pour Auth

- [ ] Inspecter les autres modèles/migrations liés à password_reset_tokens et config mail
- [ ] Ajouter une table `two_factor_verifications` (migration) + modèle `TwoFactorVerification`
- [ ] Ajouter un mail `TwoFactorEmailVerification` (code numérique, durée d’expiration, pas dans la réponse API)
- [ ] Mettre à jour `AuthService` :
  - [ ] register(): créer user + générer code 2FA + envoyer mail + renvoyer pending_2fa
  - [ ] login(): après vérif mdp générer code 2FA + envoyer mail + renvoyer pending_2fa
  - [ ] ajouter méthode `verify2fa()` : valider code (hash), marquer used, créer token
- [ ] Ajouter un endpoint `POST /auth/verify-2fa` dans `routes/api.php`
- [ ] Ajouter `Verify2FARequest` (validation input)
- [ ] (Optionnel) Préparer le hook téléphone (champ channel=phone mais peut rester placeholder)
- [ ] Tester manuellement via curl/postman (register/login → mail → verify-2fa)


