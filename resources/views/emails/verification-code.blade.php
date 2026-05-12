<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f3ff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 500px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px;">
                            <!-- Logo -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <img src="{{ asset('assets/images/logo.svg') }}" alt="Prism AI" style="height: 60px; width: auto;">
                                    </td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="color: #1f2937; font-size: 24px; font-weight: 700; text-align: center; margin: 30px 0 10px;">
                                Verify Your Email
                            </h1>

                            <p style="color: #6b7280; font-size: 15px; text-align: center; margin: 0 0 30px; line-height: 1.6;">
                                Hi {{ $user->name ?? 'there' }},<br>
                                Please use the verification code below to verify your email address.
                            </p>

                            <!-- Code Box -->
                            <div style="background-color: #f5f3ff; border-radius: 12px; padding: 25px; text-align: center; margin: 0 0 30px;">
                                <p style="color: #6b7280; font-size: 13px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px;">
                                    Your verification code
                                </p>
                                <p style="color: #7C3AED; font-size: 36px; font-weight: 700; letter-spacing: 8px; margin: 0;">
                                    {{ $code }}
                                </p>
                            </div>

                            <p style="color: #9ca3af; font-size: 13px; text-align: center; margin: 0 0 20px; line-height: 1.6;">
                                This code will expire in 15 minutes.<br>
                                If you didn't request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px 30px; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">
                                &copy; {{ date('Y') }} Prism AI. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
