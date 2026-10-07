<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} · Divas Cuyo</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;color:#242424;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $preheader }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f5f7;">
        <tr><td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;">
                <tr><td align="center" style="padding:0 0 24px;">
                    <a href="{{ config('app.url') }}" style="color:#d71920;font-size:30px;line-height:38px;letter-spacing:2px;text-decoration:none;"><strong>DIVAS</strong> CUYO</a>
                </td></tr>
                <tr><td style="padding:32px 24px;background-color:#ffffff;border-top:5px solid #d71920;border-radius:12px;">
                    <p style="margin:0 0 12px;color:#a51b20;font-size:12px;letter-spacing:2px;font-weight:bold;">TU CUENTA · DIVAS CUYO</p>
                    <h1 style="margin:0 0 24px;color:#202124;font-size:28px;line-height:36px;">{{ $heading }}</h1>
                    <p style="margin:0 0 16px;font-size:17px;line-height:26px;font-weight:bold;">{{ $greeting }}</p>
                    @foreach ($introLines as $line)
                        <p style="margin:0 0 20px;color:#4b5563;font-size:16px;line-height:26px;">{{ $line }}</p>
                    @endforeach
                    <table role="presentation" cellspacing="0" cellpadding="0" style="margin:28px 0;">
                        <tr><td bgcolor="#d71920" style="border-radius:8px;text-align:center;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:15px 24px;border:1px solid #d71920;border-radius:8px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">{{ $actionText }}</a>
                        </td></tr>
                    </table>
                    @foreach ($outroLines as $line)
                        <p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:23px;">{{ $line }}</p>
                    @endforeach
                    <p style="margin:24px 0;color:#242424;font-size:15px;line-height:24px;">Saludos,<br><strong>Equipo Divas Cuyo</strong></p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                        <tr><td style="border-top:1px solid #e5e7eb;padding-top:20px;">
                            <p style="margin:0 0 10px;color:#606773;font-size:12px;line-height:20px;">Si el botón no funciona, copiá y pegá este enlace en tu navegador:</p>
                            <a href="{{ $actionUrl }}" style="color:#a51b20;font-size:12px;line-height:20px;word-break:break-all;overflow-wrap:anywhere;">{{ $actionUrl }}</a>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td align="center" style="padding:24px 12px;color:#606773;font-size:12px;line-height:20px;">
                    © {{ date('Y') }} Divas Cuyo<br>Este mensaje está relacionado con la seguridad y el acceso a tu cuenta.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
