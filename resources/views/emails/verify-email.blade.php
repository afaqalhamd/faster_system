<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحقق من بريدك الإلكتروني</title>
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            background-color: #4facfe;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✉️ تحقق من بريدك الإلكتروني</h1>
        </div>
        <div class="content">
            <h2>مرحباً {{ $party->full_name }}،</h2>
            <p>شكراً لتسجيلك معنا! نحتاج فقط إلى التحقق من بريدك الإلكتروني لإكمال عملية التسجيل.</p>

            <p>اضغط على الزر أدناه للتحقق من بريدك الإلكتروني:</p>

            <center>
                <a href="{{ $verificationUrl }}" class="button">تحقق من البريد الإلكتروني</a>
            </center>

            <p>إذا لم يعمل الزر، يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
            <p style="word-break: break-all; color: #666; font-size: 14px;">{{ $verificationUrl }}</p>

            <p>إذا لم تقم بإنشاء حساب، يرجى تجاهل هذا البريد.</p>

            <p>مع أطيب التحيات،<br>فريق العمل</p>
        </div>
        <div class="footer">
            <p>هذا البريد الإلكتروني تم إرساله تلقائياً، يرجى عدم الرد عليه.</p>
            <p>&copy; {{ date('Y') }} جميع الحقوق محفوظة</p>
        </div>
    </div>
</body>
</html>
