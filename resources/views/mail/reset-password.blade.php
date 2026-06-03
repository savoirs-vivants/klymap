<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: #0f3460; padding: 32px 40px; text-align: center; }
        .header-logo { font-family: monospace; font-size: 28px; font-weight: 700; color: #16c79a; letter-spacing: -1px; text-decoration: none; }
        .body { padding: 40px; }
        .body h1 { font-size: 20px; font-weight: 700; color: #0f3460; margin: 0 0 12px; }
        .body p { font-size: 15px; color: #4a5568; line-height: 1.6; margin: 0 0 20px; }
        .btn { display: inline-block; padding: 14px 32px; background: #16c79a; color: #0f3460; font-size: 15px; font-weight: 700; border-radius: 8px; text-decoration: none; }
        .btn-wrapper { text-align: center; margin: 32px 0; }
        .footer { padding: 20px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 13px; color: #94a3b8; text-align: center; }
        .url-fallback { word-break: break-all; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <span class="header-logo">Klymap</span>
        </div>

        <div class="body">
            <h1>Réinitialisation de votre mot de passe</h1>
            <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.</p>

            <div class="btn-wrapper">
                <a href="{{ $url }}" class="btn">Réinitialiser mon mot de passe</a>
            </div>

            <p>Ce lien expirera dans <strong>{{ $count }} minutes</strong>. Si vous n'avez pas demandé de réinitialisation, ignorez simplement cet email.</p>

            <p class="url-fallback">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>{{ $url }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Klymap — Solution for Urban Resilience
        </div>
    </div>
</body>
</html>
