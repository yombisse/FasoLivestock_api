<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation de mot de passe</title>
</head>
<body>
    <p>Bonjour,</p>

    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>

    <p>Voici votre token de réinitialisation : <strong style="font-size:20px">{{ $token }}</strong></p>

    <p>Ce token expire dans {{ $expiresMinutes }} minutes.</p>

    <p>Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email.</p>

    <p>Cordialement,<br>{{ config('app.name', 'FasoLivestock') }}</p>
</body>
</html>
