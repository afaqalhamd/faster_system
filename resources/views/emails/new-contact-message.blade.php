<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسالة تواصل جديدة</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .info-value {
            color: #555;
        }
        .message-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #667eea;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 رسالة تواصل جديدة</h1>
        </div>

        <p>تم استلام رسالة تواصل جديدة من خلال الموقع:</p>

        <div class="info-row">
            <div class="info-label">الاسم:</div>
            <div class="info-value">{{ $contact->name }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">البريد الإلكتروني:</div>
            <div class="info-value">{{ $contact->email }}</div>
        </div>

        @if($contact->phone)
        <div class="info-row">
            <div class="info-label">رقم الهاتف:</div>
            <div class="info-value">{{ $contact->phone }}</div>
        </div>
        @endif

        <div class="info-row">
            <div class="info-label">الموضوع:</div>
            <div class="info-value">{{ $contact->subject }}</div>
        </div>

        <div class="info-label">الرسالة:</div>
        <div class="message-box">
            {{ $contact->message }}
        </div>

        <div style="text-align: center;">
            <a href="{{ url('/contact/list') }}" class="btn">
                عرض جميع الرسائل
            </a>
        </div>

        <div class="footer">
            <p>تم إرسال هذا البريد تلقائياً من نظام إدارة الموقع</p>
            <p>{{ config('app.name') }} &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
