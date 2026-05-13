<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Подтвердите email — BetterLK</title>
</head>
<body style="margin:0;padding:0;background-color:#0c0c12;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0c0c12;padding:48px 0 64px;">
    <tr><td align="center" style="padding:0 20px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">

                <!-- LOGO -->
                <tr><td align="center" style="padding-bottom:36px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                <td style="width:38px;height:38px;background-color:#7C5CFC;border-radius:10px;text-align:center;vertical-align:middle;line-height:38px;">
                                    <span style="color:#ffffff;font-size:18px;font-weight:800;font-family:monospace;">B</span>
                                </td>
                                <td style="padding-left:10px;vertical-align:middle;">
                                    <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.4px;">BetterLK</span>
                                </td>
                            </tr></table>
                    </td></tr>

                <!-- CARD -->
                <tr><td style="background-color:#16161f;border:1px solid #24242f;border-radius:18px;">

                        <!-- Accent bar -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="height:4px;border-radius:18px 18px 0 0;background-color:#7C5CFC;font-size:0;line-height:0;">&nbsp;</td></tr>
                        </table>

                        <!-- Body -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="padding:40px 44px 44px;">

                                    <!-- Icon -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td align="center" style="padding-bottom:24px;">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                    <tr><td style="width:62px;height:62px;background-color:#1e1538;border-radius:50%;text-align:center;vertical-align:middle;line-height:62px;">
                                                            <span style="font-size:26px;">✉️</span>
                                                        </td></tr>
                                                </table>
                                            </td></tr>
                                    </table>

                                    <!-- Heading -->
                                    <p style="font-size:24px;font-weight:700;color:#ffffff;text-align:center;letter-spacing:-0.5px;line-height:1.25;margin:0 0 10px 0;">
                                        Подтвердите ваш email
                                    </p>

                                    <!-- Subheading -->
                                    <p style="font-size:15px;color:#8888a0;text-align:center;line-height:1.7;margin:0 0 32px 0;">
                                        Осталось сделать один шаг — нажмите кнопку ниже<br>и ваш аккаунт будет активирован
                                    </p>

                                    <!-- Text -->
                                    <p style="font-size:15px;color:#c0c0cc;line-height:1.75;margin:0 0 10px 0;">
                                        Здравствуйте, <strong style="color:#ffffff;font-weight:600;">{{ $name }}</strong>!
                                    </p>
                                    <p style="font-size:15px;color:#c0c0cc;line-height:1.75;margin:0 0 8px 0;">
                                        Вы зарегистрировались в системе BetterLK. Чтобы завершить регистрацию и получить доступ к личному кабинету, подтвердите ваш адрес электронной почты.
                                    </p>

                                    <!-- CTA BUTTON -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr><td align="center" style="padding:32px 0 28px;">
                                                <!--[if mso]>
              <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                href="{{ $url }}" style="height:52px;v-text-anchor:middle;width:260px;"
                arcsize="12%" fillcolor="#7C5CFC" stroke="f">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:sans-serif;font-size:16px;font-weight:700;">
                  ✓ &nbsp;Подтвердить email
                </center>
              </v:roundrect>
              <![endif]-->
                                                <!--[if !mso]><!-->
                                                <a href="{{ $url }}"
                                                   style="display:inline-block;background-color:#7C5CFC;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:16px 48px;border-radius:12px;letter-spacing:0.01em;line-height:1.2;">
                                                    ✓&nbsp;&nbsp;Подтвердить email
                                                </a>
                                                <!--<![endif]-->
                                            </td></tr>
                                    </table>

                                    <!-- Divider -->
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr><td style="height:1px;background-color:#24242f;font-size:0;line-height:0;">&nbsp;</td></tr>
                                    </table>

                                    <!-- Fallback link -->
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:18px;">
                                        <tr><td style="background-color:#141422;border:1px solid #2a2a44;border-radius:10px;padding:16px 20px;">
                                                <p style="font-size:13px;color:#8888a0;line-height:1.65;margin:0;">
                                                    <strong style="color:#9898b8;">Если кнопка не работает</strong>, скопируйте и вставьте эту ссылку в браузер:<br>
                                                    <a href="{{ $url }}" style="color:#7C5CFC;word-break:break-all;">{{ $url }}</a>
                                                </p>
                                            </td></tr>
                                    </table>

                                    <!-- Warning -->
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;">
                                        <tr><td style="background-color:#1f0a0a;border:1px solid #5a1a1a;border-radius:10px;padding:14px 18px;">
                                                <p style="font-size:13px;color:#f87171;line-height:1.65;margin:0;">
                                                    Если вы не регистрировались в BetterLK — просто проигнорируйте это письмо. Ничего не произойдёт.
                                                </p>
                                            </td></tr>
                                    </table>

                                </td></tr>
                        </table>
                    </td></tr>

                <!-- FOOTER -->
                <tr><td align="center" style="padding-top:28px;">
                        <p style="font-size:12px;color:#35354a;text-align:center;line-height:1.75;margin:0;">
                            Это автоматическое письмо — отвечать на него не нужно.<br>
                            &copy; {{ date('Y') }} BetterLK &middot;
                            <a href="https://betterlk.ru" style="color:#44445a;text-decoration:underline;">betterlk.ru</a>
                        </p>
                    </td></tr>

            </table>
        </td></tr>
</table>
</body>
</html>
