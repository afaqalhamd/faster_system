<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إعادة تعيين كلمة المرور - العميل</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Floating Shapes */
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .shape1 {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape2 {
            width: 60px;
            height: 60px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
        }

        .shape3 {
            width: 100px;
            height: 100px;
            top: 40%;
            left: 5%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
            }
        }

        /* Container */
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 550px;
            width: 100%;
            padding: 0;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
            overflow: hidden;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header Section */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
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
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            animation: bounce 2s infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        .header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        /* Content Section */
        .content {
            padding: 40px 35px;
        }

        /* User Info Badge */
        .user-badge {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            border-right: 4px solid #667eea;
        }

        .user-badge i {
            font-size: 24px;
            color: #667eea;
            margin-left: 12px;
        }

        .user-badge span {
            color: #2d3748;
            font-size: 14px;
            font-weight: 600;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #2d3748;
            font-weight: 600;
            font-size: 15px;
        }

        label i {
            margin-left: 8px;
            color: #667eea;
            font-size: 18px;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 14px 50px 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
            text-align: right;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .toggle-password {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #718096;
            font-size: 20px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 10px;
        }

        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 10px;
        }

        .strength-text {
            font-size: 12px;
            color: #718096;
            display: flex;
            align-items: center;
        }

        .strength-text i {
            margin-left: 5px;
            font-size: 14px;
        }

        /* Password Match Indicator */
        .match-indicator {
            display: none;
            font-size: 13px;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            align-items: center;
        }

        .match-indicator i {
            margin-left: 8px;
            font-size: 16px;
        }

        .match-indicator.match {
            display: flex;
            background: #d4edda;
            color: #155724;
        }

        .match-indicator.mismatch {
            display: flex;
            background: #f8d7da;
            color: #721c24;
        }

        /* Submit Button */
        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button[type="submit"]:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        button[type="submit"]:active:not(:disabled) {
            transform: translateY(0);
        }

        button[type="submit"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Messages */
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message i {
            font-size: 24px;
            margin-left: 12px;
        }

        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-right: 4px solid #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-right: 4px solid #dc3545;
        }

        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading p {
            color: #718096;
            font-size: 15px;
        }

        /* Security Notice */
        .security-notice {
            background: #fffbeb;
            border: 2px dashed #fbbf24;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }

        .security-notice i {
            font-size: 24px;
            color: #f59e0b;
            margin-bottom: 8px;
        }

        .security-notice p {
            color: #92400e;
            font-size: 13px;
            margin: 0;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                border-radius: 0;
                max-width: 100%;
            }

            .content {
                padding: 30px 20px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            input[type="password"],
            input[type="text"] {
                padding: 12px 45px 12px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="shape shape1"></div>
    <div class="shape shape2"></div>
    <div class="shape shape3"></div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="lock-icon">🔐</div>
            <h1>إعادة تعيين كلمة المرور</h1>
            <p>قم بإنشاء كلمة مرور جديدة وآمنة</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- User Badge -->
            <div class="user-badge">
                <i class='bx bxs-user-circle'></i>
                <span>{{ request('email') }}</span>
            </div>

            <!-- Message -->
            <div id="message" class="message"></div>

            <!-- Form -->
            <form id="resetForm">
                <input type="hidden" id="email" value="{{ request('email') }}">
                <input type="hidden" id="token" value="{{ request('token') }}">

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">
                        <i class='bx bx-key'></i>
                        كلمة المرور الجديدة
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" placeholder="أدخل كلمة المرور الجديدة" required minlength="6">
                        <i class='bx bx-hide toggle-password' id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText">
                            <i class='bx bx-info-circle'></i>
                            <span>يجب أن تحتوي على 6 أحرف على الأقل</span>
                        </div>
                    </div>
                </div>

                <!-- Password Confirmation Field -->
                <div class="form-group">
                    <label for="password_confirmation">
                        <i class='bx bx-check-shield'></i>
                        تأكيد كلمة المرور
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirmation" placeholder="أعد إدخال كلمة المرور" required>
                        <i class='bx bx-hide toggle-password' id="togglePasswordConfirm"></i>
                    </div>
                    <div class="match-indicator" id="matchIndicator"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn">
                    <i class='bx bx-check-circle'></i>
                    <span>إعادة تعيين كلمة المرور</span>
                </button>
            </form>

            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>جاري إعادة تعيين كلمة المرور...</p>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <i class='bx bx-shield-quarter'></i>
                <p><strong>ملاحظة أمنية:</strong> سيتم تسجيل خروجك من جميع الأجهزة الأخرى بعد إعادة تعيين كلمة المرور</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordConfirmInput = document.getElementById('password_confirmation');
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const matchIndicator = document.getElementById('matchIndicator');
            const form = document.getElementById('resetForm');

            // Toggle Password Visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bx-hide');
                this.classList.toggle('bx-show');
            });

            togglePasswordConfirm.addEventListener('click', function() {
                const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmInput.setAttribute('type', type);
                this.classList.toggle('bx-hide');
                this.classList.toggle('bx-show');
            });

            // Password Strength Checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let color = '';
                let text = '';

                if (password.length >= 6) strength += 25;
                if (password.length >= 8) strength += 25;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                if (/[0-9]/.test(password)) strength += 15;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

                if (strength <= 25) {
                    color = '#dc3545';
                    text = 'ضعيفة جداً';
                } else if (strength <= 50) {
                    color = '#ffc107';
                    text = 'ضعيفة';
                } else if (strength <= 75) {
                    color = '#17a2b8';
                    text = 'متوسطة';
                } else {
                    color = '#28a745';
                    text = 'قوية';
                }

                strengthFill.style.width = strength + '%';
                strengthFill.style.backgroundColor = color;
                strengthText.innerHTML = `<i class='bx bx-info-circle'></i><span>قوة كلمة المرور: ${text}</span>`;
                strengthText.style.color = color;

                checkPasswordMatch();
            });

            // Password Match Checker
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;

                if (passwordConfirm.length > 0) {
                    if (password === passwordConfirm) {
                        matchIndicator.className = 'match-indicator match';
                        matchIndicator.innerHTML = '<i class="bx bx-check-circle"></i><span>كلمات المرور متطابقة</span>';
                    } else {
                        matchIndicator.className = 'match-indicator mismatch';
                        matchIndicator.innerHTML = '<i class="bx bx-x-circle"></i><span>كلمات المرور غير متطابقة</span>';
                    }
                } else {
                    matchIndicator.style.display = 'none';
                }
            }

            passwordConfirmInput.addEventListener('input', checkPasswordMatch);

            // Form Submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const email = document.getElementById('email').value;
                const token = document.getElementById('token').value;
                const password = passwordInput.value;
                const passwordConfirmation = passwordConfirmInput.value;
                const messageDiv = document.getElementById('message');
                const submitBtn = document.getElementById('submitBtn');
                const loading = document.getElementById('loading');

                // Validate password match
                if (password !== passwordConfirmation) {
                    showMessage('<i class="bx bx-error-circle"></i> كلمات المرور غير متطابقة', 'error');
                    return;
                }

                // Validate password length
                if (password.length < 6) {
                    showMessage('<i class="bx bx-error-circle"></i> كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'error');
                    return;
                }

                // Hide previous messages
                messageDiv.style.display = 'none';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>جاري المعالجة...</span>';
                form.style.display = 'none';
                loading.style.display = 'block';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const response = await fetch('/api/customer/auth/reset-password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: JSON.stringify({
                            email: email,
                            token: token,
                            password: password,
                            password_confirmation: passwordConfirmation
                        })
                    });

                    const data = await response.json();

                    loading.style.display = 'none';

                    if (data.status) {
                        showMessage('<i class="bx bx-check-circle"></i> ' + data.message + ' يمكنك الآن إغلاق هذه الصفحة وتسجيل الدخول من التطبيق.', 'success');

                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.close();
                        }, 3000);
                    } else {
                        showMessage('<i class="bx bx-error-circle"></i> ' + data.message, 'error');
                        form.style.display = 'block';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bx bx-check-circle"></i><span>إعادة تعيين كلمة المرور</span>';
                    }
                } catch (error) {
                    loading.style.display = 'none';
                    form.style.display = 'block';
                    showMessage('<i class="bx bx-error-circle"></i> حدث خطأ أثناء إعادة تعيين كلمة المرور. يرجى المحاولة مرة أخرى.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bx bx-check-circle"></i><span>إعادة تعيين كلمة المرور</span>';
                }
            });

            function showMessage(html, type) {
                const messageDiv = document.getElementById('message');
                messageDiv.innerHTML = html;
                messageDiv.className = 'message ' + type;
                messageDiv.style.display = 'flex';

                // Scroll to message
                messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    </script>
</body>
</html>

