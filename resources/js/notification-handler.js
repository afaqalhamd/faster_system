window.playNotificationSound = function() {
    const audio = new Audio('/sounds/notification.mp3');
    const playPromise = audio.play();

    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.error("Audio playback failed:", error);
        });
    }
}

window.handleNewNotification = function(notification) {
    // Play sound if notification has sound property
    if (notification.sound) {
        window.playNotificationSound();
    }

    // Update notification count
    const countElement = document.querySelector('.alert-count');
    if (countElement) {
        const currentCount = parseInt(countElement.textContent);
        countElement.textContent = currentCount + 1;
    }

    // Add notification to list without page reload
    const notificationList = document.querySelector('.header-notifications-list');
    if (notificationList) {
        const newNotification = document.createElement('a');
        newNotification.className = 'dropdown-item';
        newNotification.href = notification.url;

        newNotification.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="notify bg-light-primary text-primary">
                    <i class="bx ${notification.icon || 'bx-bell'}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="msg-name">${notification.title}</h6>
                    <p class="msg-info">${notification.message}</p>
                </div>
            </div>
        `;

        notificationList.prepend(newNotification);
    }
}

// Set up Echo to listen for notifications
document.addEventListener('DOMContentLoaded', function() {
    if (window.Echo && window.userId) {
        window.Echo.private(`App.Models.User.${window.userId}`)
            .notification((notification) => {
                window.handleNewNotification(notification);
            });
    }
});