<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق من البريد الإلكتروني</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .otp-container {
            background: #f8fafc;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .otp-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 10px;
        }
        .content {
            font-size: 16px;
            line-height: 1.8;
            color: #374151;
        }
        .warning {
            background: #fef3cd;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .highlight {
            color: #2563eb;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ $appName }}</div>
            <div class="title">رمز التحقق من البريد الإلكتروني</div>
        </div>

        <div class="content">
            <p>مرحباً <strong>{{ $party->first_name }} {{ $party->last_name }}</strong>،</p>

            <p>شكراً لك على التسجيل في {{ $appName }}! لإكمال عملية التحقق من بريدك الإلكتروني، يرجى استخدام الرمز التالي:</p>
        </div>

        <div class="otp-container">
            <div class="otp-code">{{ $otp }}</div>
            <div class="otp-label">رمز التحقق</div>
        </div>

        <div class="content">
            <p>يرجى إدخال هذا الرمز في التطبيق لتأكيد بريدك الإلكتروني.</p>

            <div class="warning">
                <strong>⚠️ تنبيه مهم:</strong>
                <ul style="margin: 10px 0; padding-right: 20px;">
                    <li>هذا الرمز صالح لمدة <span class="highlight">{{ $expirationMinutes }} دقائق</span> فقط</li>
                    <li>لا تشارك هذا الرمز مع أي شخص آخر</li>
                    <li>إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد</li>
                </ul>
            </div>

            <p>إذا كنت تواجه أي مشاكل، يرجى التواصل مع فريق الدعم الفني.</p>
        </div>

        <div class="footer">
            <p>مع أطيب التحيات،<br>فريق {{ $appName }}</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 20px;">
                هذا بريد إلكتروني تلقائي، يرجى عدم الرد عليه.
            </p>
        </div>
    </div>
</body>
</html>
