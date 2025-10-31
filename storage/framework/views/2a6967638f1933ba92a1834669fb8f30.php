<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تواصل معنا</title>

    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .contact-header h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .contact-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .info-card i {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .info-card h5 {
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
        }

        .info-card p {
            color: #666;
            margin: 0;
        }

        .contact-form-card {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .contact-form-card h3 {
            color: #333;
            font-weight: bold;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .map-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 2rem;
        }

        .map-placeholder {
            height: 400px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .map-placeholder i {
            font-size: 5rem;
            color: #999;
            margin-bottom: 1rem;
        }

        .map-placeholder p {
            color: #666;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .contact-header h1 {
                font-size: 2rem;
            }

            .contact-form-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container contact-container">
        <!-- Header -->
        <div class="contact-header">
            <h1>تواصل معنا</h1>
            <p>نحن هنا للإجابة على استفساراتك ومساعدتك</p>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i><?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Contact Info Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="info-card">
                    <i class='bx bx-phone-call'></i>
                    <h5>الهاتف</h5>
                    <p>+966 XX XXX XXXX</p>
                    <p class="mt-2">السبت - الخميس: 9 صباحاً - 5 مساءً</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <i class='bx bx-envelope'></i>
                    <h5>البريد الإلكتروني</h5>
                    <p>info@example.com</p>
                    <p class="mt-2">support@example.com</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <i class='bx bx-map'></i>
                    <h5>العنوان</h5>
                    <p>الرياض</p>
                    <p class="mt-2">المملكة العربية السعودية</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-card">
            <h3><i class='bx bx-message-square-dots me-2'></i>أرسل لنا رسالة</h3>

            <form action="<?php echo e(route('contact.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="name" name="name" value="<?php echo e(old('name')); ?>"
                               placeholder="أدخل اسمك الكامل" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="email" name="email" value="<?php echo e(old('email')); ?>"
                               placeholder="example@email.com" required>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">رقم الهاتف</label>
                        <input type="text" class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="phone" name="phone" value="<?php echo e(old('phone')); ?>"
                               placeholder="+966 XX XXX XXXX">
                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="subject" name="subject" value="<?php echo e(old('subject')); ?>"
                               placeholder="موضوع الرسالة" required>
                        <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-12">
                        <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                        <textarea class="form-control <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                  id="message" name="message" rows="6"
                                  placeholder="اكتب رسالتك هنا..." required><?php echo e(old('message')); ?></textarea>
                        <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-submit">
                            <i class='bx bx-send me-2'></i>إرسال الرسالة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Map Section -->
        <div class="map-container">
            <div class="map-placeholder">
                <i class='bx bx-map'></i>
                <p>يمكنك إضافة خريطة Google Maps هنا</p>
                <small class="text-muted">لعرض موقعك على الخريطة</small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/contact/public.blade.php ENDPATH**/ ?>