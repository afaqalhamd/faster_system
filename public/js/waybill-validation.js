/**
 * Waybill Validation JavaScript Functions
 *
 * This file contains JavaScript functions for real-time waybill validation
 * in the shipment tracking interface.
 */

// Waybill validation patterns
const WAYBILL_PATTERNS = {
    'DHL': /^GM\d{10}$/,
    'FedEx': /^\d{12}$|^\d{15}$/,
    'UPS': /^1Z[A-Z0-9]{18}$/,
    'USPS': /^\d{20}$/,
    'GenericAlphanumeric': /^[A-Z0-9]{10,20}$/,
    'GenericNumeric': /^\d{12,18}$/
};

/**
 * Get responsive QR code size based on screen dimensions
 * @returns {number} - The appropriate QR code size
 */
function getResponsiveQRSize() {
    const screenWidth = window.innerWidth;

    if (screenWidth <= 480) {
        return 120; // Small mobile screens
    } else if (screenWidth <= 768) {
        return 150; // Tablets and larger mobile screens
    } else {
        return 200; // Desktop and large screens
    }
}

/**
 * Generate responsive QR code for waybill
 * @param {string} elementId - The canvas element ID
 * @param {string} waybillNumber - The waybill number to encode
 */
function generateResponsiveQRCode(elementId, waybillNumber) {
    const qrSize = getResponsiveQRSize();

    try {
        bwipjs.toCanvas(elementId, {
            bcid: 'qrcode',
            text: waybillNumber,
            scale: 3,
            width: qrSize,
            height: qrSize,
            textxalign: 'center'
        });
    } catch (e) {
        console.error('QR code generation failed:', e);
    }
}

/**
 * Validate waybill format based on carrier
 * @param {string} waybillNumber - The waybill number to validate
 * @param {string|null} carrier - The carrier name (optional)
 * @returns {boolean} - Whether the waybill format is valid
 */
function validateWaybillFormat(waybillNumber, carrier = null) {
    // If carrier is specified, use carrier-specific pattern
    if (carrier && WAYBILL_PATTERNS[carrier]) {
        return WAYBILL_PATTERNS[carrier].test(waybillNumber);
    }

    // Try all patterns if no specific carrier
    for (let pattern in WAYBILL_PATTERNS) {
        if (WAYBILL_PATTERNS[pattern].test(waybillNumber)) {
            return true;
        }
    }

    return false;
}

/**
 * Validate waybill barcode format
 * @param {string} waybillNumber - The waybill number to validate
 * @returns {boolean} - Whether the barcode format is valid
 */
function validateWaybillBarcode(waybillNumber) {
    // Check for common waybill number patterns
    const patterns = [
        /^[A-Z0-9]{10,20}$/,           // Alphanumeric, 10-20 characters
        /^\d{12,18}$/,                 // Numeric, 12-18 digits
        /^[A-Z]{2}\d{9}[A-Z]{2}$/      // Two letters, 9 digits, two letters (DHL format example)
    ];

    for (let pattern of patterns) {
        if (pattern.test(waybillNumber)) {
            return true;
        }
    }

    return false;
}

/**
 * Real-time validation of waybill input
 * @param {HTMLElement} inputElement - The waybill input element
 * @param {HTMLElement} feedbackElement - The feedback element to display validation results
 * @param {string|null} carrier - The carrier name (optional)
 */
function validateWaybillInput(inputElement, feedbackElement, carrier = null) {
    const waybillNumber = inputElement.value.trim();

    // Clear previous feedback
    feedbackElement.innerHTML = '';
    inputElement.classList.remove('is-valid', 'is-invalid');

    // If empty, do nothing
    if (!waybillNumber) {
        return;
    }

    // Validate format
    const isValid = validateWaybillFormat(waybillNumber, carrier);

    if (isValid) {
        inputElement.classList.add('is-valid');
        feedbackElement.innerHTML = '<span class="text-success">✓ Valid waybill format</span>';
    } else {
        inputElement.classList.add('is-invalid');
        feedbackElement.innerHTML = '<span class="text-danger">✗ Invalid waybill format</span>';
    }
}

/**
 * Real-time validation of waybill barcode input
 * @param {HTMLElement} inputElement - The waybill input element
 * @param {HTMLElement} feedbackElement - The feedback element to display validation results
 */
function validateWaybillBarcodeInput(inputElement, feedbackElement) {
    const waybillNumber = inputElement.value.trim();

    // Clear previous feedback
    feedbackElement.innerHTML = '';
    inputElement.classList.remove('is-valid', 'is-invalid');

    // If empty, do nothing
    if (!waybillNumber) {
        return;
    }

    // Validate barcode format
    const isValid = validateWaybillBarcode(waybillNumber);

    if (isValid) {
        inputElement.classList.add('is-valid');
        feedbackElement.innerHTML = '<span class="text-success">✓ Valid barcode format</span>';
    } else {
        inputElement.classList.add('is-invalid');
        feedbackElement.innerHTML = '<span class="text-danger">✗ Invalid barcode format</span>';
    }
}

/**
 * Send waybill validation request to server
 * @param {string} waybillNumber - The waybill number to validate
 * @param {string|null} carrier - The carrier name (optional)
 * @param {Function} callback - Callback function to handle the response
 */
function validateWaybillServer(waybillNumber, carrier = null, callback) {
    // Make AJAX request to server
    fetch('/api/waybill/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            waybill_number: waybillNumber,
            carrier: carrier
        })
    })
    .then(response => response.json())
    .then(data => {
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Waybill validation error:', error);
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Validation failed' });
        }
    });
}

/**
 * Send waybill barcode validation request to server
 * @param {string} waybillNumber - The waybill number to validate
 * @param {Function} callback - Callback function to handle the response
 */
function validateWaybillBarcodeServer(waybillNumber, callback) {
    // Make AJAX request to server
    fetch('/api/waybill/validate-barcode', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            waybill_number: waybillNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Waybill barcode validation error:', error);
        if (callback && typeof callback === 'function') {
            callback({ status: false, message: 'Validation failed' });
        }
    });
}

/**
 * Initialize waybill validation for form elements
 * @param {string} waybillInputId - The ID of the waybill input element
 * @param {string} feedbackElementId - The ID of the feedback element
 * @param {string|null} carrierSelectId - The ID of the carrier select element (optional)
 */
function initWaybillValidation(waybillInputId, feedbackElementId, carrierSelectId = null) {
    const waybillInput = document.getElementById(waybillInputId);
    const feedbackElement = document.getElementById(feedbackElementId);
    let carrierSelect = null;

    if (carrierSelectId) {
        carrierSelect = document.getElementById(carrierSelectId);
    }

    if (!waybillInput || !feedbackElement) {
        console.error('Waybill validation elements not found');
        return;
    }

    // Add input event listener for real-time validation
    waybillInput.addEventListener('input', function() {
        let carrier = null;
        if (carrierSelect) {
            carrier = carrierSelect.value;
        }
        validateWaybillInput(waybillInput, feedbackElement, carrier);
    });

    // Add blur event listener for server validation
    waybillInput.addEventListener('blur', function() {
        const waybillNumber = waybillInput.value.trim();
        if (waybillNumber) {
            let carrier = null;
            if (carrierSelect) {
                carrier = carrierSelect.value;
            }

            validateWaybillServer(waybillNumber, carrier, function(response) {
                if (response.status) {
                    if (response.valid) {
                        waybillInput.classList.remove('is-invalid');
                        waybillInput.classList.add('is-valid');
                        feedbackElement.innerHTML = '<span class="text-success">✓ ' + response.message + '</span>';
                    } else {
                        waybillInput.classList.remove('is-valid');
                        waybillInput.classList.add('is-invalid');
                        feedbackElement.innerHTML = '<span class="text-danger">✗ ' + response.message + '</span>';
                    }
                }
            });
        }
    });
}

/**
 * Initialize waybill barcode validation for form elements
 * @param {string} waybillInputId - The ID of the waybill input element
 * @param {string} feedbackElementId - The ID of the feedback element
 */
function initWaybillBarcodeValidation(waybillInputId, feedbackElementId) {
    const waybillInput = document.getElementById(waybillInputId);
    const feedbackElement = document.getElementById(feedbackElementId);

    if (!waybillInput || !feedbackElement) {
        console.error('Waybill barcode validation elements not found');
        return;
    }

    // Add input event listener for real-time validation
    waybillInput.addEventListener('input', function() {
        validateWaybillBarcodeInput(waybillInput, feedbackElement);
    });

    // Add blur event listener for server validation
    waybillInput.addEventListener('blur', function() {
        const waybillNumber = waybillInput.value.trim();
        if (waybillNumber) {
            validateWaybillBarcodeServer(waybillNumber, function(response) {
                if (response.status) {
                    if (response.valid) {
                        waybillInput.classList.remove('is-invalid');
                        waybillInput.classList.add('is-valid');
                        feedbackElement.innerHTML = '<span class="text-success">✓ ' + response.message + '</span>';
                    } else {
                        waybillInput.classList.remove('is-valid');
                        waybillInput.classList.add('is-invalid');
                        feedbackElement.innerHTML = '<span class="text-danger">✗ ' + response.message + '</span>';
                    }
                }
            });
        }
    });
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateWaybillFormat,
        validateWaybillBarcode,
        validateWaybillInput,
        validateWaybillBarcodeInput,
        validateWaybillServer,
        validateWaybillBarcodeServer,
        initWaybillValidation,
        initWaybillBarcodeValidation,
        getResponsiveQRSize,
        generateResponsiveQRCode
    };
}
