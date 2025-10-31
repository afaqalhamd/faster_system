<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        /* Reset Styles */
        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        img {
            border: 0;
            paddit: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        /* Main Styles */
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f0f2f5;
            padding: 40px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background-image:
                radial-gradient(circle at 20% 50%, white 1px, transparent 1px),
                radial-gradient(circle at 80% 80%, white 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .header-content {
            position: relative;
            padding: 50px 30px;
            z-index: 1;
        }

        .lock-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .email-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            margin: 0;
        }

        /* Content */
        .email-content {
            padding: 40px 35px;
            color: #333333;
            line-height: 1.8;
        }

        .greeting {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 20px 0;
        }

        .greeting-name {
            color: #f5576c;
        }

        .intro-text {
            font-size: 16px;
            color: #4a5568;
            margin: 0 0 25px 0;
        }

        /* Security Alert Box */
        .security-alert {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-right: 4px solid #f5576c;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }

        .security-alert-title {
            font-size: 16px;
            font-weight: 600;
            color: #c53030;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
        }

        .security-alert-title::before {
            content: "⚠️";
            margin-left: 10px;
            font-size: 20px;
        }

        .security-alert p {
            margin: 0;
            color: #742a2a;
            font-size: 14px;
        }

        /* CTA Button */
        .cta-section {
            text-align: center;
            margin: 35px 0;
            padding: 30px 20px;
            background: linear-gradient(135deg, #fef5f5 0%, #fff5f7 100%);
            border-radius: 12px;
        }

        .cta-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.3);
            transition: all 0.3s ease;
        }

        .cta-text {
            color: #718096;
            font-size: 13px;
            margin-top: 15px;
        }

        /* Timer Box */
        .timer-box {
            background: #fffbeb;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }

        .timer-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .timer-text {
            color: #92400e;
            font-size: 15px;
            margin: 5px 0;
        }

        .timer-highlight {
            color: #78350f;
            font-size: 24px;
            font-weight: 700;
            margin: 10px 0;
        }

        /* Info Box */
        .info-box {
            background: #f0f9ff;
            border-right: 4px solid #3b82f6;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }

        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box strong {
            color: #1e3a8a;
        }

        /* Link Box */
        .link-box {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
        }

        .link-label {
            font-size: 12px;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .link-url {
            color: #f5576c;
            font-size: 13px;
            font-family: monospace;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(to left, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }

        /* Footer */
        .email-footer {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            padding: 35px 30px;
            text-align: center;
        }

        .footer-text {
            color: #a0aec0;
            font-size: 13px;
            line-height: 1.6;
            margin: 15px 0;
        }

        .footer-links {
            margin: 15px 0;
        }

        .footer-link {
            color: #cbd5e0;
            text-decoration: none;
            font-size: 13px;
            margin: 0 10px;
        }

        .copyright {
            color: #718096;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }

            .email-content {
                padding: 30px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .greeting {
                font-size: 20px;
            }

            .cta-button {
                padding: 14px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" class="email-container" width="600" cellspacing="0" cellpadding="0" border="0" align="center">
            <!-- Header -->
            <tr>
                <td class="email-header">
                    <div class="header-pattern"></div>
                    <div class="header-content">
                        <div class="lock-icon">🔐</div>
                        <h1>إعادة تعيين كلمة المرور</h1>
                        <p>طلب إعادة تعيين كلمة المرور - موظف التوصيل</p>
                    </div>
                </td>
            </tr>

            <!-- Content -->
            <tr>
                <td class="email-content">
                    <h2 class="greeting">مرحباً <span class="greeting-name">{{ $user->username }}</span> 👋</h2>

                    <p class="intro-text">
                        لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في نظام التوصيل.
                        إذا كنت أنت من قام بهذا الطلب، يرجى النقر على الزر أدناه للمتابعة.
                    </p>

                    <!-- Security Alert -->
                    <div class="security-alert">
                        <div class="security-alert-title">تنبيه أمني</div>
                        <p>
                            إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.
                            حسابك آمن ولن يتم إجراء أي تغييرات.
                        </p>
                    </div>

                    <!-- CTA Section -->
                    <div class="cta-section">
                        <a href="{{ $resetLink }}" class="cta-button">
                            إعادة تعيين كلمة المرور 🔑
                        </a>
                        <p class="cta-text">انقر على الزر أعلاه لإعادة تعيين كلمة المرور</p>
                    </div>

                    <!-- Timer Box -->
                    <div class="timer-box">
                        <div class="timer-icon">⏰</div>
                        <p class="timer-text">هذا الرابط صالح لمدة</p>
                        <div class="timer-highlight">60 دقيقة</div>
                        <p class="timer-text">بعد ذلك سيتعين عليك طلب رابط جديد</p>
                    </div>

                    <!-- Info Box -->
                    <div class="info-box">
                        <p>
                            <strong>💡 ملاحظة مهمة:</strong><br>
                            بعد إعادة تعيين كلمة المرور، سيتم تسجيل خروجك تلقائياً من جميع الأجهزة الأخرى لضمان أمان حسابك.
                        </p>
                    </div>

                    <div class="divider"></div>

                    <!-- Alternative Link -->
                    <p style="color: #718096; font-size: 14px; margin-bottom: 10px;">
                        إذا واجهت مشكلة في النقر على الزر، يمكنك نسخ الرابط التالي ولصقه في متصفحك:
                    </p>

                    <div class="link-box">
                        <div class="link-label">🔗 رابط إعادة التعيين:</div>
                        <div class="link-url">{{ $resetLink }}</div>
                    </div>

                    <p style="color: #718096; font-size: 15px; margin-top: 30px;">
                        مع تحيات،<br>
                        <strong style="color: #2d3748;">فريق الدعم الفني</strong>
                    </p>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="email-footer">
                    <p class="footer-text">
                        هذا البريد الإلكتروني تم إرساله تلقائياً من نظام إدارة التوصيل.<br>
                        يرجى عدم الرد على هذا البريد مباشرة.
                    </p>

                    <div class="footer-links">
                        <a href="#" class="footer-link">مركز المساعدة</a>
                        <span style="color: #4a5568;">|</span>
                        <a href="#" class="footer-link">سياسة الخصوصية</a>
                        <span style="color: #4a5568;">|</span>
                        <a href="#" class="footer-link">اتصل بنا</a>
                    </div>

                    <p class="copyright">
                        🔒 محمي بتقنية التشفير المتقدم<br>
                        &copy; {{ date('Y') }} جميع الحقوق محفوظة
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
