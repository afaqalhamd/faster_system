/**
 * QR Code Scanner JavaScript Functions
 *
 * This file contains JavaScript functions for QR code scanning
 * in the shipment tracking interface.
 */

/**
 * Initialize QR code scanner
 * @param {string} videoElementId - The ID of the video element for camera feed
 * @param {Function} callback - Callback function to handle scanned data
 */
function initQRScanner(videoElementId, callback) {
    // Check for camera support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.error('Camera API not supported');
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Camera API not supported' });
        }
        return;
    }

    const video = document.getElementById(videoElementId);

    if (!video) {
        console.error('Video element not found');
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Video element not found' });
        }
        return;
    }

    // Request camera access
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
        .then(stream => {
            video.srcObject = stream;
            video.setAttribute("playsinline", true); // required to tell iOS safari we don't want fullscreen
            video.play();

            // Start scanning for QR codes
            startQRScanning(video, callback);
        })
        .catch(err => {
            console.error("Camera access error:", err);
            if (callback && typeof callback === 'function') {
                callback({ status: false, message: 'Camera access denied: ' + err.message });
            }
        });
}

/**
 * Start QR code scanning process
 * @param {HTMLVideoElement} video - The video element
 * @param {Function} callback - Callback function to handle scanned data
 */
function startQRScanning(video, callback) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    function scanFrame() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Use a QR code scanning library (e.g., jsQR)
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

            // If jsQR is available, use it to scan
            if (typeof jsQR !== 'undefined') {
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    // QR code detected
                    if (callback && typeof callback === 'function') {
                        callback({ status: true, data: code.data });
                    }
                    return; // Stop scanning after successful detection
                }
            }
        }

        // Continue scanning
        requestAnimationFrame(scanFrame);
    }

    scanFrame();
}

/**
 * Stop QR code scanner
 * @param {string} videoElementId - The ID of the video element
 */
function stopQRScanner(videoElementId) {
    const video = document.getElementById(videoElementId);

    if (video && video.srcObject) {
        const stream = video.srcObject;
        const tracks = stream.getTracks();

        tracks.forEach(track => {
            track.stop();
        });

        video.srcObject = null;
    }
}

/**
 * Process scanned QR code data
 * @param {string} waybillNumber - The scanned waybill number
 * @param {Function} callback - Callback function to handle the response
 */
function processScannedQRCode(waybillNumber, callback) {
    // Validate input
    if (!waybillNumber || waybillNumber.trim() === '') {
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Waybill number is required' });
        }
        return;
    }

    // Make AJAX request to server
    fetch('/api/waybill/process-qr', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            waybill_number: waybillNumber.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('QR code processing error:', error);
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Processing failed: ' + error.message });
        }
    });
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initQRScanner,
        startQRScanning,
        stopQRScanner,
        processScannedQRCode
    };
}
