<!doctype html>
<html class="{{ $themeMode }}" lang="en" dir="{{ $appDirection }}">

@include('layouts.head')

<body>
	<!-- Page Loader -->
	@include('layouts.page-loader')

	<!--wrapper-->
	<div class="wrapper">
		@include('layouts.navigation')

        @include('layouts.header')

		@yield('content')

		@include('layouts.footer')
	</div>
	<!--end wrapper-->


	{{-- @include('layouts.search') --}}

	{{-- @include('layouts.switcher') --}}

	@include('layouts.script')

    <script>
        window.userId = {{ auth()->id() }};

        // Function to play notification sound
        window.playNotificationSound = function() {
            try {
                // Create audio element with direct URL
                const audio = new Audio('/sounds/notification.mp3');
                audio.volume = 1.0; // Maximum volume

                // Try to play immediately on user interaction
                document.addEventListener('click', function playOnce() {
                    audio.play();
                    document.removeEventListener('click', playOnce);
                }, { once: true });

                // Also try normal playback
                const playPromise = audio.play();

                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.error("Sound playback failed:", error);
                    });
                }
            } catch (error) {
                console.error("Error playing notification sound:", error);
            }
        }

        // Check for notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            const alertCountElement = document.querySelector('.alert-count');
            if (alertCountElement && parseInt(alertCountElement.textContent) > 0) {
                setTimeout(window.playNotificationSound, 1000);
            }
        });
    </script>
</body>
</html>