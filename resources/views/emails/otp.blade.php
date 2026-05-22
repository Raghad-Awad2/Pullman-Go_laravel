<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PULLMAN GO - رمز التحقق</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    
                    <tr>
                        <td align="center" style="background-color: #2E7D32; padding: 30px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; letter-spacing: 2px;">PULLMAN GO</h1>
                            <p style="color: #a5d6a7; margin: 5px 0 0 0; font-size: 14px;">رفيق سفرك الدائم</p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 40px 30px;">
                            <h2 style="color: #333333; margin-top: 0; margin-bottom: 20px; font-size: 22px;">
                                مرحباً بك يا صديقنا!
                            </h2>
                            
                            <p style="color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                {{ $bodyText }}
                            </p>

                            <div style="background-color: #f8f9fa; border: 2px dashed #2E7D32; border-radius: 12px; padding: 20px; margin-bottom: 30px; display: inline-block;">
                                <span style="font-size: 36px; font-weight: bold; color: #2E7D32; letter-spacing: 15px; margin-left: -15px;">
                                    {{ $otp }}
                                </span>
                            </div>

                            <p style="color: #888888; font-size: 14px; margin-bottom: 0;">
                                هذا الرمز صالح لمدة <strong style="color: #e53935;">10 دقائق</strong> فقط.
                                <br>يرجى عدم مشاركة هذا الرمز مع أي شخص.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 20px; border-top: 1px solid #eeeeee;">
                            <p style="color: #aaaaaa; font-size: 12px; margin: 0; line-height: 1.5;">
                                إذا لم تقم بهذا الطلب، يرجى تجاهل هذه الرسالة أو التواصل مع الدعم الفني فوراً.
                                <br><br>
                                &copy; {{ date('Y') }} تطبيق PULLMAN GO. جميع الحقوق محفوظة.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>