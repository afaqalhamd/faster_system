@extends('layouts.guest')
@section('title', 'إعادة تعيين كلمة المرور - موظف التوصيل')

@section('container')

	<!--wrapper-->
	<div class="wrapper delivery-reset-wrapper" dir="rtl">
		<div class="section-authentication-cover">
			<div class="">
				<div class="row g-0">
					<!-- Left Side - Illustration -->
					<div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex delivery-cover-bg">
                        <div class="card shadow-none bg-transparent rounded-0 mb-0">
							<div class="card-body text-center p-5">
								<div class="delivery-illustration">
									<i class='bx bxs-truck delivery-icon'></i>
									<h2 class="text-white mt-4 mb-3">مرحباً بك في نظام التوصيل</h2>
									<p class="text-white-50 fs-5">قم بإعادة تعيين كلمة المرور الخاصة بك بأمان</p>
									<div class="security-badges mt-5">
										<div class="badge-item">
											<i class='bx bxs-lock-alt'></i>
											<span>آمن ومشفر</span>
										</div>
										<div class="badge-item">
											<i class='bx bxs-time'></i>
											<span>صالح لمدة 60 دقيقة</span>
										</div>
										<div class="badge-item">
											<i class='bx bxs-shield-alt-2'></i>
											<span>حماية متقدمة</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Right Side - Form -->
					<div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
						<div class="card rounded-3 m-3 shadow-lg border-0 mb-0 delivery-form-card">
							<div class="card-body p-4 p-sm-5">

								<!-- Logo Section -->
								<div class="text-center mb-4">
									<div class="logo-container">
										<img src="{{ url('/app/getimage/' . app('site')['colored_logo']) }}" width="70" alt="Logo" class="mb-3">
									</div>
									<h3 class="fw-bold text-primary mb-2">إعادة تعيين كلمة المرور</h3>
									<p class="text-muted small">
										<i class='bx bxs-user-badge text-primary'></i>
										موظف التوصيل
									</p>
								</div>

								<!-- Alert Messages -->
								@include('layouts.session')

								<!-- Reset Form -->
								<form method="POST" action="{{ route('delivery.password.update') }}" class="needs-validation" novalidate>
									@csrf
									@method('POST')

									<!-- Password Reset Token -->
									<input type="hidden" name="token" value="{{ request('token') }}" />

									<!-- Email Field -->
									<div class="mb-4">
										<label for="email" class="form-label fw-semibold">
											<i class='bx bx-envelope text-primary'></i>
											البريد الإلكتروني
										</label>
										<div class="input-group">
											<input type="email"
												   class="form-control form-control-lg @error('email') is-invalid @enderror"
												   id="email"
												   name="email"
												   value="{{ old('email', request('email')) }}"
												   placeholder="example@domain.com"
												   required
												   readonly>
											<span class="input-group-text bg-light">
												<i class='bx bx-lock-alt text-muted'></i>
											</span>
											@error('email')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>

									<!-- Password Field -->
									<div class="mb-4">
										<label for="password" class="form-label fw-semibold">
											<i class='bx bx-key text-primary'></i>
											كلمة المرور الجديدة
										</label>
										<div class="input-group">
											<input type="password"
												   class="form-control form-control-lg @error('password') is-invalid @enderror"
												   id="password"
												   name="password"
												   placeholder="••••••••"
												   required
												   minlength="6">
											<button class="input-group-text bg-light border-0" type="button" id="togglePassword">
												<i class='bx bx-hide text-muted' id="toggleIcon"></i>
											</button>
											@error('password')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
										<div class="password-strength mt-2">
											<div class="progress" style="height: 4px;">
												<div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
											</div>
											<small class="text-muted d-block mt-1">
												<i class='bx bx-info-circle'></i>
												يجب أن تحتوي على 6 أحرف على الأقل
											</small>
										</div>
									</div>

									<!-- Password Confirmation Field -->
									<div class="mb-4">
										<label for="password_confirmation" class="form-label fw-semibold">
											<i class='bx bx-check-shield text-primary'></i>
											تأكيد كلمة المرور
										</label>
										<div class="input-group">
											<input type="password"
												   class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
												   id="password_confirmation"
												   name="password_confirmation"
												   placeholder="••••••••"
												   required
												   minlength="6">
											<button class="input-group-text bg-light border-0" type="button" id="togglePasswordConfirm">
												<i class='bx bx-hide text-muted' id="toggleIconConfirm"></i>
											</button>
											@error('password_confirmation')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
										<small class="text-success d-none mt-1" id="passwordMatch">
											<i class='bx bx-check-circle'></i>
											كلمات المرور متطابقة
										</small>
										<small class="text-danger d-none mt-1" id="passwordMismatch">
											<i class='bx bx-x-circle'></i>
											كلمات المرور غير متطابقة
										</small>
									</div>

									<!-- Security Notice -->
									<div class="alert alert-info alert-dismissible fade show" role="alert">
										<i class='bx bx-info-circle me-2'></i>
										<strong>ملاحظة أمنية:</strong> سيتم تسجيل خروجك من جميع الأجهزة الأخرى بعد إعادة تعيين كلمة المرور.
										<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
									</div>

									<!-- Submit Button -->
									<div class="d-grid gap-2 mb-3">
										<button type="submit" class="btn btn-primary btn-lg shadow-sm" id="submitBtn">
											<i class='bx bx-check-circle me-2'></i>
											إعادة تعيين كلمة المرور
										</button>
									</div>

									<!-- Back to Login -->
									<div class="text-center">
										<a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg w-100">
											<i class='bx bx-arrow-back me-2'></i>
											العودة لتسجيل الدخول
										</a>
									</div>
								</form>

								<!-- Footer -->
								<div class="text-center mt-4 pt-3 border-top">
									<small class="text-muted">
										<i class='bx bx-shield-quarter'></i>
										محمي بتقنية التشفير المتقدم
									</small>
								</div>
							</div>
						</div>
					</div>

				</div>
				<!--end row-->
			</div>
		</div>
	</div>
	<!--end wrapper-->

@endsection

@section('css')
<style>
	/* RTL Support */
	.delivery-reset-wrapper {
		direction: rtl;
		text-align: right;
		font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
	}

	/* Left Cover Background */
	.delivery-cover-bg {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		position: relative;
		overflow: hidden;
	}

	.delivery-cover-bg::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
		background-size: cover;
	}

	/* Delivery Icon Animation */
	.delivery-icon {
		font-size: 120px;
		color: #fff;
		animation: float 3s ease-in-out infinite;
		text-shadow: 0 10px 30px rgba(0,0,0,0.3);
	}

	@keyframes float {
		0%, 100% { transform: translateY(0px); }
		50% { transform: translateY(-20px); }
	}

	/* Security Badges */
	.security-badges {
		display: flex;
		justify-content: center;
		gap: 30px;
		flex-wrap: wrap;
	}

	.badge-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 8px;
		color: #fff;
		font-size: 14px;
	}

	.badge-item i {
		font-size: 32px;
		opacity: 0.9;
	}

	/* Form Card */
	.delivery-form-card {
		animation: slideInRight 0.5s ease-out;
		background: #fff;
	}

	@keyframes slideInRight {
		from {
			opacity: 0;
			transform: translateX(-30px);
		}
		to {
			opacity: 1;
			transform: translateX(0);
		}
	}

	/* Logo Container */
	.logo-container {
		animation: zoomIn 0.5s ease-out;
	}

	@keyframes zoomIn {
		from {
			opacity: 0;
			transform: scale(0.5);
		}
		to {
			opacity: 1;
			transform: scale(1);
		}
	}

	/* Form Controls */
	.form-control-lg {
		border-radius: 10px;
		border: 2px solid #e0e0e0;
		padding: 12px 16px;
		transition: all 0.3s ease;
		text-align: right;
	}

	.form-control-lg:focus {
		border-color: #667eea;
		box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
		transform: translateY(-2px);
	}

	.form-control-lg:read-only {
		background-color: #f8f9fa;
		cursor: not-allowed;
	}

	/* Input Group */
	.input-group-text {
		border-radius: 0 10px 10px 0;
		border: 2px solid #e0e0e0;
		border-right: none;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.input-group .form-control {
		border-left: none;
		border-radius: 10px 0 0 10px;
	}

	.input-group-text:hover {
		background-color: #e9ecef;
	}

	/* Password Strength */
	.password-strength .progress {
		border-radius: 10px;
		background-color: #e9ecef;
	}

	.password-strength .progress-bar {
		transition: all 0.3s ease;
		border-radius: 10px;
	}

	/* Buttons */
	.btn-lg {
		padding: 14px 24px;
		border-radius: 10px;
		font-weight: 600;
		transition: all 0.3s ease;
		border: 2px solid transparent;
	}

	.btn-primary {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
	}

	.btn-primary:hover {
		transform: translateY(-2px);
		box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
	}

	.btn-primary:active {
		transform: translateY(0);
	}

	.btn-outline-secondary {
		border-color: #dee2e6;
		color: #6c757d;
	}

	.btn-outline-secondary:hover {
		background-color: #f8f9fa;
		border-color: #adb5bd;
		color: #495057;
		transform: translateY(-2px);
	}

	/* Alert */
	.alert-info {
		background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
		border: none;
		border-radius: 10px;
		border-right: 4px solid #00acc1;
	}

	/* Form Labels */
	.form-label {
		font-weight: 600;
		color: #495057;
		margin-bottom: 8px;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.form-label i {
		font-size: 18px;
	}

	/* Validation Feedback */
	.invalid-feedback {
		text-align: right;
		font-size: 13px;
	}

	/* Loading State */
	.btn-loading {
		position: relative;
		pointer-events: none;
		opacity: 0.7;
	}

	.btn-loading::after {
		content: '';
		position: absolute;
		width: 16px;
		height: 16px;
		top: 50%;
		left: 50%;
		margin-left: -8px;
		margin-top: -8px;
		border: 2px solid #fff;
		border-radius: 50%;
		border-top-color: transparent;
		animation: spinner 0.6s linear infinite;
	}

	@keyframes spinner {
		to { transform: rotate(360deg); }
	}

	/* Responsive */
	@media (max-width: 768px) {
		.delivery-icon {
			font-size: 80px;
		}

		.security-badges {
			gap: 15px;
		}

		.badge-item i {
			font-size: 24px;
		}

		.btn-lg {
			padding: 12px 20px;
		}
	}

	/* Small text alignment */
	small {
		display: block;
		text-align: right;
	}

	/* Success/Error messages */
	#passwordMatch,
	#passwordMismatch {
		display: block;
		font-size: 13px;
		margin-top: 5px;
	}
</style>
@endsection

@section('js')
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Prevent form resubmission on page refresh
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}

		// Toggle Password Visibility
		const togglePassword = document.getElementById('togglePassword');
		const password = document.getElementById('password');
		const toggleIcon = document.getElementById('toggleIcon');

		if (togglePassword) {
			togglePassword.addEventListener('click', function() {
				const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
				password.setAttribute('type', type);

				if (type === 'text') {
					toggleIcon.classList.remove('bx-hide');
					toggleIcon.classList.add('bx-show');
				} else {
					toggleIcon.classList.remove('bx-show');
					toggleIcon.classList.add('bx-hide');
				}
			});
		}

		// Toggle Password Confirmation Visibility
		const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
		const passwordConfirm = document.getElementById('password_confirmation');
		const toggleIconConfirm = document.getElementById('toggleIconConfirm');

		if (togglePasswordConfirm) {
			togglePasswordConfirm.addEventListener('click', function() {
				const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
				passwordConfirm.setAttribute('type', type);

				if (type === 'text') {
					toggleIconConfirm.classList.remove('bx-hide');
					toggleIconConfirm.classList.add('bx-show');
				} else {
					toggleIconConfirm.classList.remove('bx-show');
					toggleIconConfirm.classList.add('bx-hide');
				}
			});
		}

		// Password Strength Indicator
		if (password) {
			password.addEventListener('input', function() {
				const value = this.value;
				const strengthBar = document.getElementById('passwordStrength');
				let strength = 0;
				let color = '';

				if (value.length >= 6) strength += 25;
				if (value.length >= 8) strength += 25;
				if (/[a-z]/.test(value) && /[A-Z]/.test(value)) strength += 25;
				if (/[0-9]/.test(value)) strength += 15;
				if (/[^a-zA-Z0-9]/.test(value)) strength += 10;

				if (strength <= 25) {
					color = '#dc3545';
				} else if (strength <= 50) {
					color = '#ffc107';
				} else if (strength <= 75) {
					color = '#17a2b8';
				} else {
					color = '#28a745';
				}

				strengthBar.style.width = strength + '%';
				strengthBar.style.backgroundColor = color;

				// Check password match
				checkPasswordMatch();
			});
		}

		// Password Match Checker
		function checkPasswordMatch() {
			const pass = document.getElementById('password').value;
			const passConfirm = document.getElementById('password_confirmation').value;
			const matchMsg = document.getElementById('passwordMatch');
			const mismatchMsg = document.getElementById('passwordMismatch');

			if (passConfirm.length > 0) {
				if (pass === passConfirm) {
					matchMsg.classList.remove('d-none');
					mismatchMsg.classList.add('d-none');
				} else {
					matchMsg.classList.add('d-none');
					mismatchMsg.classList.remove('d-none');
				}
			} else {
				matchMsg.classList.add('d-none');
				mismatchMsg.classList.add('d-none');
			}
		}

		if (passwordConfirm) {
			passwordConfirm.addEventListener('input', checkPasswordMatch);
		}

		// Form Submission with Loading State
		const form = document.querySelector('form');
		const submitBtn = document.getElementById('submitBtn');

		if (form) {
			form.addEventListener('submit', function(e) {
				// Check if passwords match
				const pass = document.getElementById('password').value;
				const passConfirm = document.getElementById('password_confirmation').value;

				if (pass !== passConfirm) {
					e.preventDefault();
					alert('كلمات المرور غير متطابقة');
					return false;
				}

				// Add loading state
				submitBtn.classList.add('btn-loading');
				submitBtn.innerHTML = '<span>جاري المعالجة...</span>';
			});
		}

		// Auto-dismiss alerts after 5 seconds
		const alerts = document.querySelectorAll('.alert:not(.alert-info)');
		alerts.forEach(function(alert) {
			setTimeout(function() {
				const bsAlert = new bootstrap.Alert(alert);
				bsAlert.close();
			}, 5000);
		});
	});
</script>
@endsection

