<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>مرحباً بك في عائلتنا</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
    </style>
    <![endif]-->
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
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        table {
            border-collapse: collapse !important;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
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

        /* Header with Gradient */
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .welcome-icon {
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
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .email-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 16px;
            margin: 0;
        }

        /* Content Section */
        .email-content {
            padding: 40px 35px;
            color: #333333;
            line-height: 1.8;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 20px 0;
        }

        .greeting-name {
            color: #667eea;
        }

        .intro-text {
            font-size: 16px;
            color: #4a5568;
            margin: 0 0 30px 0;
        }

        /* Info Card */
        .info-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            border-right: 4px solid #667eea;
        }

        .info-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
        }

        .info-card-title::before {
            content: "✓";
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-left: 10px;
            font-size: 14px;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            font-size: 15px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 36px;
            height: 36px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            font-size: 18px;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            margin-left: 8px;
        }

        .info-value {
            color: #2d3748;
        }

        /* Features Section */
        .features-grid {
            display: table;
            width: 100%;
            margin: 30px 0;
        }

        .feature-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 20px 10px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 12px;
        }

        .feature-title {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin: 8px 0 4px 0;
        }

        .feature-desc {
            font-size: 12px;
            color: #718096;
            margin: 0;
        }

        /* CTA Button */
        .cta-section {
            text-align: center;
            margin: 35px 0;
        }

        .cta-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(to left, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }

        /* Support Section */
        .support-box {
            background: #fffbeb;
            border: 2px dashed #fbbf24;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }

        .support-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }

        .support-box strong {
            color: #78350f;
            font-size: 16px;
        }

        /* Footer */
        .email-footer {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            padding: 35px 30px;
            text-align: center;
        }

        .footer-logo {
            margin-bottom: 20px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin: 0 5px;
            line-height: 40px;
            color: #ffffff;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
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

        .footer-link:hover {
            color: #ffffff;
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
                font-size: 26px;
            }

            .greeting {
                font-size: 20px;
            }

            .feature-item {
                display: block;
                width: 100%;
                margin-bottom: 20px;
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
                        <div class="welcome-icon">🎉</div>
                        <h1>مرحباً بك في عائلتنا!</h1>
                        <p>نحن سعداء جداً بانضمامك إلينا</p>
                    </div>
                </td>
            </tr>

            <!-- Content -->
            <tr>
                <td class="email-content">
                    <h2 class="greeting">أهلاً <span class="greeting-name">{{ $party->full_name }}</span> 👋</h2>

                    <p class="intro-text">
                        تهانينا! تم إنشاء حسابك بنجاح. نحن متحمسون لرؤيتك تبدأ رحلتك معنا.
                        الآن يمكنك الاستمتاع بجميع مميزاتنا والبدء في تجربة تسوق رائعة.
                    </p>

                    <!-- Account Info Card -->
                    <div class="info-card">
                        <div class="info-card-title">معلومات حسابك</div>
                        <ul class="info-list">
                            <li>
                                <span class="info-icon">📧</span>
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $party->email }}</span>
                            </li>
                            <li>
                                <span class="info-icon">📱</span>
                                <span class="info-label">رقم الجوال:</span>
                                <span class="info-value">{{ $party->mobile }}</span>
                            </li>
                            <li>
                                <span class="info-icon">👤</span>
                                <span class="info-label">نوع الحساب:</span>
                                <span class="info-value">عميل</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Features Grid -->
                    <table role="presentation" class="features-grid" width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td class="feature-item">
                                <div class="feature-icon">🛍️</div>
                                <div class="feature-title">تسوق بسهولة</div>
                                <p class="feature-desc">آلاف المنتجات في انتظارك</p>
                            </td>
                            <td class="feature-item">
                                <div class="feature-icon">🚚</div>
                                <div class="feature-title">توصيل سريع</div>
                                <p class="feature-desc">نوصل طلبك في أسرع وقت</p>
                            </td>
                            <td class="feature-item">
                                <div class="feature-icon">💳</div>
                                <div class="feature-title">دفع آمن</div>
                                <p class="feature-desc">معاملات مشفرة ومحمية</p>
                            </td>
                        </tr>
                    </table>

                    <!-- CTA Button -->
                    <div class="cta-section">
                        <a href="{{ config('app.url') }}" class="cta-button">
                            ابدأ التسوق الآن 🚀
                        </a>
                    </div>

                    <div class="divider"></div>

                    <!-- Support Box -->
                    <div class="support-box">
                        <p><strong>💬 هل تحتاج إلى مساعدة؟</strong></p>
                        <p>فريق الدعم الفني متاح على مدار الساعة لمساعدتك</p>
                    </div>

                    <p style="color: #718096; font-size: 15px; margin-top: 30px;">
                        شكراً لثقتك بنا،<br>
                        <strong style="color: #2d3748;">فريق العمل</strong>
                    </p>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="email-footer">
                    <div class="footer-logo">
                        <img src="{{ url('/app/getimage/' . app('site')['colored_logo']) }}" alt="Logo" width="50" style="display: inline-block;">
                    </div>

                    <div class="social-links">
                        <a href="#" class="social-link">📘</a>
                        <a href="#" class="social-link">📷</a>
                        <a href="#" class="social-link">🐦</a>
                        <a href="#" class="social-link">📺</a>
                    </div>

                    <p class="footer-text">
                        هذا البريد الإلكتروني تم إرساله تلقائياً من نظامنا.<br>
                        يرجى عدم الرد على هذا البريد مباشرة.
                    </p>

                    <div class="footer-links">
                        <a href="#" class="footer-link">سياسة الخصوصية</a>
                        <span style="color: #4a5568;">|</span>
                        <a href="#" class="footer-link">الشروط والأحكام</a>
                        <span style="color: #4a5568;">|</span>
                        <a href="#" class="footer-link">اتصل بنا</a>
                    </div>

                    <p class="copyright">
                        &copy; {{ date('Y') }} جميع الحقوق محفوظة
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
