<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
            color: #333;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        h1 {
            margin: 0;
            font-size: 28px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #f5576c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 إعادة تعيين كلمة المرور</h1>
        </div>
        <div class="content">
            <h2>مرحباً {{ $party->full_name }}،</h2>
            <p>تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.</p>

            <p>اضغط على الزر أدناه لإعادة تعيين كلمة المرور:</p>

            <center>
                <a href="{{ $resetLink }}" class="button">إعادة تعيين كلمة المرور</a>
            </center>

            <div class="warning">
                <strong>⚠️ تنبيه أمني:</strong>
                <ul>
                    <li>هذا الرابط صالح لمدة 60 دقيقة فقط</li>
                    <li>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد</li>
                    <li>لا تشارك هذا الرابط مع أي شخص</li>
                </ul>
            </div>

            <p>إذا لم يعمل الزر، يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
            <p style="word-break: break-all; color: #666; font-size: 14px;">{{ $resetLink }}</p>

            <p>مع أطيب التحيات،<br>فريق العمل</p>
        </div>
        <div class="footer">
            <p>هذا البريد الإلكتروني تم إرساله تلقائياً، يرجى عدم الرد عليه.</p>
            <p>&copy; {{ date('Y') }} جميع الحقوق محفوظة</p>
        </div>
    </div>
</body>
</html>
