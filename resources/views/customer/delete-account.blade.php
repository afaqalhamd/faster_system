<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>حذف الحساب - Delete Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">حذف الحساب</h1>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Delete Account</h2>
                <p class="text-gray-600 text-sm">
                    سيتم حذف جميع بياناتك بشكل نهائي ولا يمكن استرجاعها
                </p>
                <p class="text-gray-600 text-sm">
                    All your data will be permanently deleted and cannot be recovered
                </p>
            </div>

            <!-- Alert Messages -->
            <div id="alertMessage" class="hidden mb-4 p-4 rounded-lg"></div>

            <!-- Form -->
            <form id="deleteAccountForm" class="space-y-4">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        البريد الإلكتروني / Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="example@email.com"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        كلمة المرور / Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Confirmation Checkbox -->
                <div class="flex items-start">
                    <input
                        type="checkbox"
                        id="confirm"
                        required
                        class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                    >
                    <label for="confirm" class="mr-2 text-sm text-gray-700">
                        أؤكد أنني أرغب في حذف حسابي بشكل نهائي
                        <br>
                        <span class="text-gray-500">I confirm that I want to permanently delete my account</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
                >
                    <span id="btnText">حذف الحساب نهائياً / Delete Account</span>
                    <span id="btnLoader" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </form>

            <!-- Important Notice -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-xs text-blue-800">
                        <p class="font-semibold mb-2">ماذا سيحدث عند حذف الحساب؟</p>
                        <p class="mb-2">
                            <strong>إذا لم يكن لديك طلبات:</strong> سيتم حذف حسابك بالكامل
                        </p>
                        <p class="mb-3">
                            <strong>إذا كان لديك طلبات أو معاملات:</strong> سيتم حذف بياناتك الشخصية فقط (الاسم، البريد، الهاتف) والاحتفاظ بسجلات الطلبات للأغراض القانونية
                        </p>
                        <p class="font-semibold mb-2">What happens when you delete your account?</p>
                        <p class="mb-2">
                            <strong>If you have no orders:</strong> Your account will be completely deleted
                        </p>
                        <p>
                            <strong>If you have orders or transactions:</strong> Only your personal data (name, email, phone) will be deleted, order records will be kept for legal purposes
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-600 text-center">
                    هذه الصفحة مطلوبة لسياسة Google Play لحذف البيانات
                    <br>
                    This page is required for Google Play data deletion policy
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('deleteAccountForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const alertMessage = document.getElementById('alertMessage');

            // Get form data
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm').checked;

            if (!confirm) {
                showAlert('يرجى تأكيد رغبتك في حذف الحساب / Please confirm account deletion', 'error');
                return;
            }

            // Disable button and show loader
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');

            try {
                const response = await fetch('/api/customer/auth/delete-account', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.status) {
                    showAlert(data.message + ' / Account deleted successfully', 'success');
                    // Clear form
                    document.getElementById('deleteAccountForm').reset();
                    // Disable form after successful deletion
                    setTimeout(() => {
                        document.getElementById('deleteAccountForm').querySelectorAll('input, button').forEach(el => {
                            el.disabled = true;
                        });
                    }, 2000);
                } else {
                    showAlert(data.message || 'حدث خطأ / An error occurred', 'error');
                    // Re-enable button
                    submitBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoader.classList.add('hidden');
                }
            } catch (error) {
                showAlert('حدث خطأ في الاتصال / Connection error', 'error');
                // Re-enable button
                submitBtn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
            }
        });

        function showAlert(message, type) {
            const alertMessage = document.getElementById('alertMessage');
            alertMessage.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');

            if (type === 'error') {
                alertMessage.classList.add('bg-red-100', 'text-red-700');
            } else {
                alertMessage.classList.add('bg-green-100', 'text-green-700');
            }

            alertMessage.textContent = message;

            // Auto hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertMessage.classList.add('hidden');
                }, 5000);
            }
        }
    </script>
</body>
</html>

