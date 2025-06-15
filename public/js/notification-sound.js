document.addEventListener('DOMContentLoaded', function() {
    // Function to play notification sound
    window.playNotificationSound = function() {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.7; // زيادة مستوى الصوت إلى 70%

            // تشغيل الصوت
            const playPromise = audio.play();

            // معالجة أي أخطاء محتملة
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.error("فشل تشغيل الصوت:", error);
                });
            }
        } catch (error) {
            console.error("خطأ في تشغيل صوت الإشعار:", error);
        }
    }

    // التحقق من وجود إشعارات غير مقروءة عند تحميل الصفحة
    const alertCountElement = document.querySelector('.alert-count');
    if (alertCountElement && parseInt(alertCountElement.textContent) > 0) {
        // تشغيل الصوت للإشعارات الموجودة
        setTimeout(function() {
            window.playNotificationSound();
        }, 1000); // تأخير لضمان تحميل الصفحة بالكامل
    }
});