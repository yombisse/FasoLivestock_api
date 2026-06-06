<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2FA</title>
</head>
<body>
    <p>Bonjour,</p>

    <p>Voici votre code de vérification (2FA) : <strong style="font-size:20px">{{ $code }}</strong></p>

    <p>Ce code expire dans {{ $expiresMinutes }} minutes.</p>

    <p>Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email.</p>

    <p>Cordialement,<br>{{ config('app.name', 'FasoLivestock') }}</p>
</body>
</html>

