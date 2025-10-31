<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سياسة الخصوصية - تطبيق التوصيل السريع</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            line-height: 2;
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            font-weight: 400;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 15px;
            font-weight: 900;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.3em;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        .content {
            padding: 50px 40px;
        }

        .section {
            margin-bottom: 45px;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .section h2 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 4px solid #667eea;
            font-weight: 700;
            position: relative;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: -4px;
            right: 0;
            width: 100px;
            height: 4px;
            background: #764ba2;
        }

        .section h3 {
            color: #764ba2;
            font-size: 1.5em;
            margin: 25px 0 15px 0;
            font-weight: 700;
        }

        .section p {
            margin-bottom: 18px;
            text-align: justify;
            font-size: 1.1em;
            line-height: 2.2;
            color: #4a5568;
        }

        .section ul {
            margin: 20px 0 20px 35px;
        }

        .section li {
            margin-bottom: 15px;
            line-height: 2;
            font-size: 1.05em;
            color: #4a5568;
            position: relative;
            padding-right: 10px;
        }

        .section li::before {
            content: '◀';
            position: absolute;
            right: -25px;
            color: #667eea;
            font-size: 0.8em;
        }

        .highlight {
            background: linear-gradient(135deg, #f0f4ff 0%, #e9f0ff 100%);
            padding: 25px 30px;
            border-right: 5px solid #667eea;
            margin: 25px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
            font-size: 1.05em;
        }

        .highlight p {
            margin: 0;
            font-weight: 600;
        }

        .contact-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 35px;
            border-radius: 15px;
            margin-top: 40px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .contact-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .contact-box h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.8em;
            font-weight: 700;
            position: relative;
        }

        .contact-box p {
            position: relative;
            font-size: 1.1em;
            line-height: 2.2;
        }

        .contact-box a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            border-bottom: 2px solid rgba(255,255,255,0.5);
            transition: all 0.3s ease;
            padding-bottom: 2px;
        }

        .contact-box a:hover {
            border-bottom-color: white;
            padding-bottom: 4px;
        }

        .footer {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px;
            text-align: center;
            color: #718096;
            font-size: 1em;
            font-weight: 600;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #764ba2;
        }

        strong {
            color: #2d3748;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 35px 25px;
            }

            .header h1 {
                font-size: 2em;
            }

            .header p {
                font-size: 1.1em;
            }

            .content {
                padding: 30px 25px;
            }

            .section h2 {
                font-size: 1.6em;
            }

            .section h3 {
                font-size: 1.3em;
            }

            .section p,
            .section li {
                font-size: 1em;
            }

            .section ul {
                margin-right: 25px;
            }

            .contact-box {
                padding: 25px;
            }

            .contact-box h3 {
                font-size: 1.5em;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.6em;
            }

            .section h2 {
                font-size: 1.4em;
            }

            .section ul {
                margin-right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 سياسة الخصوصية</h1>
            <p>تطبيق التوصيل السريع (Quick Delivery)</p>
            <p style="font-size: 0.9em; margin-top: 10px;">آخر تحديث: <?php echo e(date('Y-m-d')); ?></p>
        </div>

        <div class="content">
            <div class="section">
                <h2>مقدمة</h2>
                <p>
                    نحن في تطبيق التوصيل السريع نلتزم بحماية خصوصيتك وأمان بياناتك الشخصية. توضح سياسة الخصوصية هذه كيفية جمع واستخدام وحماية ومشاركة معلوماتك الشخصية عند استخدامك لتطبيقنا.
                </p>
                <p>
                    باستخدامك لتطبيق التوصيل السريع، فإنك توافق على جمع واستخدام المعلومات وفقاً لهذه السياسة.
                </p>
            </div>

            <div class="section">
                <h2>1. المعلومات التي نجمعها</h2>

                <h3>1.1 المعلومات الشخصية</h3>
                <p>عند التسجيل في التطبيق، قد نطلب منك تقديم المعلومات التالية:</p>
                <ul>
                    <li><strong>الاسم الكامل:</strong> لتحديد هويتك في النظام</li>
                    <li><strong>رقم الهاتف:</strong> للتواصل معك وإرسال الإشعارات</li>
                    <li><strong>البريد الإلكتروني:</strong> لإرسال التحديثات والإشعارات المهمة</li>
                    <li><strong>العنوان:</strong> لتسليم الطلبات بدقة</li>
                    <li><strong>صورة الملف الشخصي:</strong> (اختياري) لتخصيص حسابك</li>
                </ul>

                <h3>1.2 معلومات الموقع</h3>
                <p>
                    نجمع معلومات الموقع الجغرافي لتوفير خدمات التوصيل بدقة. يتم استخدام الموقع لـ:
                </p>
                <ul>
                    <li>تحديد موقع التسليم الدقيق</li>
                    <li>تتبع حالة الطلب في الوقت الفعلي</li>
                    <li>تحسين خدمات التوصيل</li>
                    <li>حساب المسافات وتكاليف التوصيل</li>
                </ul>

                <h3>1.3 معلومات الجهاز</h3>
                <ul>
                    <li>نوع الجهاز ونظام التشغيل</li>
                    <li>معرف الجهاز الفريد</li>
                    <li>عنوان IP</li>
                    <li>معلومات الشبكة</li>
                </ul>

                <h3>1.4 معلومات الاستخدام</h3>
                <ul>
                    <li>سجل الطلبات والمعاملات</li>
                    <li>تفاعلاتك مع التطبيق</li>
                    <li>تفضيلات الاستخدام</li>
                    <li>الوقت المستغرق في التطبيق</li>
                </ul>
            </div>

            <div class="section">
                <h2>2. كيفية استخدام المعلومات</h2>
                <p>نستخدم المعلومات التي نجمعها للأغراض التالية:</p>
                <ul>
                    <li><strong>تقديم الخدمة:</strong> معالجة وتنفيذ طلبات التوصيل</li>
                    <li><strong>التواصل:</strong> إرسال إشعارات حول حالة الطلبات والتحديثات</li>
                    <li><strong>تحسين الخدمة:</strong> تحليل استخدام التطبيق لتحسين الأداء</li>
                    <li><strong>الأمان:</strong> حماية حسابك ومنع الاحتيال</li>
                    <li><strong>الدعم الفني:</strong> الرد على استفساراتك وحل المشاكل</li>
                    <li><strong>التسويق:</strong> إرسال عروض وتحديثات (يمكنك إلغاء الاشتراك)</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. مشاركة المعلومات</h2>
                <p>نحن لا نبيع معلوماتك الشخصية لأطراف ثالثة. قد نشارك معلوماتك في الحالات التالية:</p>

                <h3>3.1 مع مقدمي الخدمة</h3>
                <ul>
                    <li><strong>سائقي التوصيل:</strong> مشاركة معلومات التسليم الضرورية فقط</li>
                    <li><strong>معالجات الدفع:</strong> لمعالجة المدفوعات بشكل آمن</li>
                    <li><strong>خدمات الاستضافة:</strong> لتخزين البيانات بشكل آمن</li>
                </ul>

                <h3>3.2 الامتثال القانوني</h3>
                <p>قد نكشف عن معلوماتك إذا كان ذلك مطلوباً بموجب القانون أو لحماية حقوقنا.</p>

                <h3>3.3 نقل الأعمال</h3>
                <p>في حالة الاندماج أو الاستحواذ، قد يتم نقل معلوماتك إلى الكيان الجديد.</p>
            </div>

            <div class="section">
                <h2>4. حماية المعلومات</h2>
                <p>نتخذ إجراءات أمنية صارمة لحماية معلوماتك:</p>
                <ul>
                    <li><strong>التشفير:</strong> جميع البيانات الحساسة مشفرة أثناء النقل والتخزين</li>
                    <li><strong>المصادقة الآمنة:</strong> استخدام كلمات مرور قوية ومصادقة ثنائية</li>
                    <li><strong>الوصول المحدود:</strong> فقط الموظفون المصرح لهم يمكنهم الوصول للبيانات</li>
                    <li><strong>المراقبة المستمرة:</strong> مراقبة الأنظمة للكشف عن أي نشاط مشبوه</li>
                    <li><strong>النسخ الاحتياطي:</strong> نسخ احتياطية منتظمة لحماية البيانات</li>
                </ul>
            </div>

            <div class="section">
                <h2>5. الإشعارات والرسائل</h2>
                <p>نستخدم Firebase Cloud Messaging (FCM) لإرسال الإشعارات:</p>
                <ul>
                    <li>إشعارات حالة الطلب</li>
                    <li>رسائل الدردشة</li>
                    <li>تحديثات التطبيق</li>
                    <li>العروض الترويجية (يمكنك إلغاء الاشتراك)</li>
                </ul>
                <p>يمكنك التحكم في الإشعارات من إعدادات التطبيق أو إعدادات جهازك.</p>
            </div>

            <div class="section">
                <h2>6. حقوقك</h2>
                <p>لديك الحقوق التالية فيما يتعلق بمعلوماتك الشخصية:</p>
                <ul>
                    <li><strong>الوصول:</strong> طلب نسخة من معلوماتك الشخصية</li>
                    <li><strong>التصحيح:</strong> تحديث أو تصحيح معلوماتك</li>
                    <li><strong>الحذف:</strong> طلب حذف حسابك ومعلوماتك</li>
                    <li><strong>الاعتراض:</strong> الاعتراض على معالجة معلوماتك</li>
                    <li><strong>نقل البيانات:</strong> الحصول على نسخة من بياناتك بصيغة قابلة للنقل</li>
                    <li><strong>سحب الموافقة:</strong> سحب موافقتك على معالجة البيانات في أي وقت</li>
                </ul>
            </div>

            <div class="section">
                <h2>7. الاحتفاظ بالبيانات</h2>
                <p>
                    نحتفظ بمعلوماتك الشخصية طالما كان حسابك نشطاً أو حسب الحاجة لتقديم الخدمات. بعد حذف حسابك، سنحتفظ ببعض المعلومات للأغراض التالية:
                </p>
                <ul>
                    <li>الامتثال للالتزامات القانونية</li>
                    <li>حل النزاعات</li>
                    <li>إنفاذ اتفاقياتنا</li>
                    <li>منع الاحتيال</li>
                </ul>
                <p>عادةً ما يتم حذف البيانات بالكامل بعد 90 يوماً من حذف الحساب.</p>
            </div>

            <div class="section">
                <h2>8. خصوصية الأطفال</h2>
                <div class="highlight">
                    <p>
                        <strong>⚠️ تنبيه مهم:</strong> تطبيقنا غير موجه للأطفال دون سن 13 عاماً. نحن لا نجمع عن قصد معلومات شخصية من الأطفال. إذا اكتشفنا أننا جمعنا معلومات من طفل دون سن 13 عاماً، سنحذفها فوراً.
                    </p>
                </div>
            </div>

            <div class="section">
                <h2>9. ملفات تعريف الارتباط (Cookies)</h2>
                <p>نستخدم ملفات تعريف الارتباط وتقنيات مشابهة لـ:</p>
                <ul>
                    <li>تذكر تفضيلاتك وإعداداتك</li>
                    <li>تحليل استخدام التطبيق</li>
                    <li>تحسين تجربة المستخدم</li>
                    <li>الحفاظ على أمان حسابك</li>
                </ul>
            </div>

            <div class="section">
                <h2>10. الروابط الخارجية</h2>
                <p>
                    قد يحتوي تطبيقنا على روابط لمواقع أو خدمات خارجية. نحن لسنا مسؤولين عن ممارسات الخصوصية لهذه المواقع. ننصحك بمراجعة سياسات الخصوصية الخاصة بها.
                </p>
            </div>

            <div class="section">
                <h2>11. التحديثات على السياسة</h2>
                <p>
                    قد نقوم بتحديث سياسة الخصوصية من وقت لآخر. سنخطرك بأي تغييرات جوهرية عبر:
                </p>
                <ul>
                    <li>إشعار داخل التطبيق</li>
                    <li>البريد الإلكتروني</li>
                    <li>تحديث تاريخ "آخر تحديث" في أعلى هذه الصفحة</li>
                </ul>
                <p>استمرارك في استخدام التطبيق بعد التحديثات يعني موافقتك على السياسة المحدثة.</p>
            </div>

            <div class="section">
                <h2>12. الامتثال للقوانين</h2>
                <p>نلتزم بالقوانين واللوائح المتعلقة بحماية البيانات، بما في ذلك:</p>
                <ul>
                    <li>اللائحة العامة لحماية البيانات (GDPR) - الاتحاد الأوروبي</li>
                    <li>قانون خصوصية المستهلك في كاليفورنيا (CCPA)</li>
                    <li>القوانين المحلية لحماية البيانات</li>
                </ul>
            </div>

            <div class="contact-box">
                <h3>📞 اتصل بنا</h3>
                <p>إذا كان لديك أي أسئلة أو استفسارات حول سياسة الخصوصية هذه، يرجى التواصل معنا:</p>
                <p style="margin-top: 15px;">
                    <strong>البريد الإلكتروني:</strong> <a href="mailto:privacy@quickdelivery.com">privacy@quickdelivery.com</a><br>
                    <strong>الهاتف:</strong> <a href="tel:+966500000000">+966 50 000 0000</a><br>
                    <strong>العنوان:</strong> المملكة العربية السعودية
                </p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?php echo e(date('Y')); ?> تطبيق التوصيل السريع. جميع الحقوق محفوظة.</p>
            <p style="margin-top: 10px;">
                <a href="<?php echo e(url('/')); ?>" style="color: #667eea; text-decoration: none;">الصفحة الرئيسية</a> |
                <a href="<?php echo e(url('/terms')); ?>" style="color: #667eea; text-decoration: none;">الشروط والأحكام</a>
            </p>
        </div>
    </div>
</body>
</html>

<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/privacy-policy.blade.php ENDPATH**/ ?>