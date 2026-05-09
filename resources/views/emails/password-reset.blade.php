<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sua nova senha</title>
</head>
<body style="margin:0;padding:0;background:#f5f0e8;font-family:Arial,Helvetica,sans-serif;color:#181511;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#fffdf8;border:1px solid rgba(24,21,17,.12);border-radius:18px;padding:28px;">
            <p style="margin:0 0 12px;color:#1f6f5f;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">
                Check Planilha
            </p>
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.2;">Sua nova senha foi gerada</h1>
            <p style="margin:0 0 16px;line-height:1.6;">
                Olá, {{ $recipientName }}. O sistema gerou uma nova senha temporária para o seu acesso.
            </p>
            <div style="padding:16px;border-radius:12px;background:rgba(31,111,95,.08);font-size:18px;font-weight:700;letter-spacing:.08em;">
                {{ $temporaryPassword }}
            </div>
            <p style="margin:16px 0 0;line-height:1.6;">
                Entre no sistema com essa senha e troque por uma nova assim que entrar.
            </p>
        </div>
    </div>
</body>
</html>
