<div id="spinner-overlay">
        <div id="spinner-content">
            <div id="lottie-container"></div>
            <div id="loading-message">{{ __('app.loading') }}</div>
        </div>
    </div>

    <!-- Lottie Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

    <script>
        // Initialize Lottie animation
        const animation = lottie.loadAnimation({
            container: document.getElementById('lottie-container'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            // يمكنك تغيير هذا الرابط إلى أيقونة Lottie أخرى تفضلها
            path: 'https://assets2.lottiefiles.com/packages/lf20_usmfx6bp.json' // Shopping cart loading animation
        });

        // Set container dimensions
        document.getElementById('lottie-container').style.width = '120px';
        document.getElementById('lottie-container').style.height = '120px';
        document.getElementById('lottie-container').style.margin = '0 auto';
    </script>
