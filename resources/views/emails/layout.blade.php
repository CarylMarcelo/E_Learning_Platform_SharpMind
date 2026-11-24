<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title') · SharpMind</title>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f4effa;color:#21152d;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">@yield('preheader', $summary)</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#f4effa;">
        <tr><td align="center" style="padding:28px 12px;">
            <!--[if mso]><table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
                <tr><td bgcolor="#642c9b" style="padding:26px 28px;background-color:#642c9b;border-radius:18px 18px 0 0;">
                    <p style="margin:0;color:#ffffff;font-size:30px;font-weight:bold;letter-spacing:-1px;line-height:1.2;">Sharp<span style="font-weight:normal;">Mind</span></p>
                    <p style="margin:9px 0 0;color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:1.5px;line-height:1.6;">LEARN. GROW. BELONG.</p>
                </td></tr>
                <tr><td style="padding:28px 24px;background-color:#ffffff;border-right:1px solid #e4d6f1;border-left:1px solid #e4d6f1;">
                    @yield('status')
                    <h1 style="margin:18px 0;color:#21152d;font-size:28px;font-weight:bold;letter-spacing:-0.7px;line-height:1.3;">{{ $heading }}</h1>
                    <p style="margin:0 0 14px;color:#21152d;font-size:16px;line-height:1.7;overflow-wrap:anywhere;">Hello {{ $memberName }},</p>
                    <p style="margin:0 0 20px;color:#21152d;font-size:16px;line-height:1.7;">{{ $summary }}</p>
                    @yield('content')
                    <p style="margin:24px 0 0;color:#642c9b;font-size:16px;font-weight:bold;line-height:1.7;">The SharpMind team</p>
                </td></tr>
                <tr><td style="padding:20px 24px;background-color:#faf7fe;border:1px solid #e4d6f1;border-radius:0 0 18px 18px;">
                    <p style="margin:0;color:#5b2b82;font-size:14px;line-height:1.7;"><strong>Need help with your membership?</strong><br>Contact your membership administrator and share your membership number.</p>
                </td></tr>
                <tr><td align="center" style="padding:20px 12px 0;">
                    <p style="margin:0;color:#5b2b82;font-size:13px;line-height:1.7;">This email is about your SharpMind membership.<br>© {{ date('Y') }} SharpMind. All rights reserved.</p>
                </td></tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td></tr>
    </table>
</body>
</html>
