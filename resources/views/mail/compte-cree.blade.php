<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte Klymap</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: #0f766e; padding: 32px 40px; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; font-weight: 700; letter-spacing: -0.3px; }
        .header p { color: #99f6e4; font-size: 13px; margin: 4px 0 0; }
        .body { padding: 32px 40px; }
        .body p { color: #475569; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .creds { background: #f1f5f9; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .creds .row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        .creds .row:last-child { border-bottom: none; }
        .creds .label { font-size: 13px; color: #94a3b8; font-weight: 500; }
        .creds .value { font-size: 14px; color: #0f172a; font-weight: 600; font-family: 'Courier New', monospace; }
        .btn { display: inline-block; background: #0f766e; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; margin-top: 8px; }
        .footer { padding: 20px 40px; border-top: 1px solid #f1f5f9; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Klymap</h1>
            <p>Cartographie des îlots de chaleur urbains</p>
        </div>
        <div class="body">
            <p>Bonjour <strong>{{ $prenom }}</strong>,</p>
            <p>Un compte a été créé pour vous sur la plateforme Klymap. Voici vos identifiants de connexion :</p>
            <div class="creds">
                <div class="row">
                    <span class="label">Adresse e-mail</span>
                    <span class="value">{{ $email }}</span>
                </div>
                <div class="row">
                    <span class="label">Mot de passe</span>
                    <span class="value">{{ $motDePasse }}</span>
                </div>
            </div>
            <p>Nous vous recommandons de modifier votre mot de passe après votre première connexion.</p>
            <a href="{{ config('app.url') }}/connexion" class="btn">Se connecter</a>
        </div>
        <div class="footer">
            <p>Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
