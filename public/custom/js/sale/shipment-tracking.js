/**
 * Shipment Tracking JavaScript for Sale Order Edit Page
 */

$(document).ready(function() {
    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Fancybox for proof images
    if (typeof Fancybox !== 'undefined') {
        Fancybox.bind("[data-fancybox]", {
            // Custom options for Fancybox
        });
    }

    // Add tracking button click handler
    $('#addTrackingBtn').on('click', function() {
        // Reset form
        $('#trackingForm')[0].reset();
        $('#trackingId').val('');

        // Automatically select the carrier from the sale order
        var saleOrderCarrierId = $('#carrier_id').val();
        if (saleOrderCarrierId) {
            $('#carrierId').val(saleOrderCarrierId);
        }

        // Generate a tracking number
        var trackingNumber = generateTrackingNumber();
        $('#trackingNumber').val(trackingNumber);

        // Show modal
        $('#addTrackingModal').modal('show');
    });

    // Edit tracking button click handler
    $(document).on('click', '.edit-tracking', function() {
        var trackingId = $(this).data('tracking-id');

        // Fetch tracking data
        $.ajax({
            url: '/api/shipment-tracking/' + trackingId,
            method: 'GET',
            success: function(response) {
                if (response.status) {
                    var tracking = response.data;

                    // Populate form
                    $('#trackingId').val(tracking.id);
                    $('#carrierId').val(tracking.carrier_id);
                    $('#trackingNumber').val(tracking.tracking_number);
                    $('#trackingUrl').val(tracking.tracking_url);
                    $('#trackingStatus').val(tracking.status);
                    $('#estimatedDeliveryDate').val(tracking.estimated_delivery_date);
                    $('#trackingNotes').val(tracking.notes);

                    // Show modal
                    $('#addTrackingModal').modal('show');
                } else {
                    showErrorMessage(response.message || 'Failed to fetch tracking data');
                }
            },
            error: function() {
                showErrorMessage('Failed to fetch tracking data');
            }
        });
    });

    // Save tracking button click handler
    $('#saveTrackingBtn').on('click', function() {
        var trackingId = $('#trackingId').val();
        var formData = $('#trackingForm').serialize();
        var url = '/api/sale-orders/' + window.saleOrderId + '/tracking';
        var method = 'POST';

        if (trackingId) {
            url = '/api/shipment-tracking/' + trackingId;
            method = 'PUT';
        }

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.status) {
                    showSuccessMessage(response.message);
                    $('#addTrackingModal').modal('hide');
                    location.reload(); // Reload to show updated tracking
                } else {
                    showErrorMessage(response.message || 'Failed to save tracking');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    for (var field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    showErrorMessage(errorMessages.join('<br>'));
                } else {
                    showErrorMessage('Failed to save tracking');
                }
            }
        });
    });

    // Delete tracking button click handler
    $(document).on('click', '.delete-tracking', function() {
        var trackingId = $(this).data('tracking-id');

        // Use iziToast for confirmation instead of browser confirm
        iziToast.question({
            timeout: 20000,
            close: false,
            overlay: true,
            displayMode: 'once',
            id: 'question',
            zindex: 999,
            title: 'Confirm',
            message: 'Are you sure you want to delete this tracking?',
            position: 'center',
            buttons: [
                ['<button><b>YES</b></button>', function (instance, toast) {
                    instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');

                    // Proceed with deletion
                    $.ajax({
                        url: '/api/shipment-tracking/' + trackingId,
                        method: 'DELETE',
                        success: function(response) {
                            if (response.status) {
                                showSuccessMessage(response.message);
                                location.reload(); // Reload to show updated tracking
                            } else {
                                showErrorMessage(response.message || 'Failed to delete tracking');
                            }
                        },
                        error: function() {
                            showErrorMessage('Failed to delete tracking');
                        }
                    });
                }, true],
                ['<button>NO</button>', function (instance, toast) {
                    instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                }]
            ]
        });
    });

    // Add event button click handler
    $(document).on('click', '.add-event-btn', function() {
        var trackingId = $(this).data('tracking-id');

        // Reset form
        $('#eventForm')[0].reset();
        $('#eventTrackingId').val(trackingId);

        // Set current date and time as default
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var formattedDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        $('#eventDate').val(formattedDateTime);

        // Show modal
        $('#addEventModal').modal('show');
    });

    // Save event button click handler
    $('#saveEventBtn').on('click', function() {
        var trackingId = $('#eventTrackingId').val();
        var formData = new FormData($('#eventForm')[0]);

        $.ajax({
            url: '/api/shipment-tracking/' + trackingId + '/events',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status) {
                    showSuccessMessage(response.message);
                    $('#addEventModal').modal('hide');
                    // Reinitialize Fancybox after reload
                    location.reload(); // Reload to show updated events
                } else {
                    showErrorMessage(response.message || 'Failed to save event');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    for (var field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    showErrorMessage(errorMessages.join('<br>'));
                } else {
                    showErrorMessage('Failed to save event');
                }
            }
        });
    });

    // Delete event button click handler
    $(document).on('click', '.delete-event', function() {
        var eventId = $(this).data('event-id');

        // Use iziToast for confirmation instead of browser confirm
        iziToast.question({
            timeout: 20000,
            close: false,
            overlay: true,
            displayMode: 'once',
            id: 'question',
            zindex: 999,
            title: 'Confirm',
            message: 'Are you sure you want to delete this event?',
            position: 'center',
            buttons: [
                ['<button><b>YES</b></button>', function (instance, toast) {
                    instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');

                    // Proceed with deletion
                    $.ajax({
                        url: '/api/shipment-events/' + eventId,
                        method: 'DELETE',
                        success: function(response) {
                            if (response.status) {
                                showSuccessMessage(response.message);
                                location.reload(); // Reload to show updated events
                            } else {
                                showErrorMessage(response.message || 'Failed to delete event');
                            }
                        },
                        error: function() {
                            showErrorMessage('Failed to delete event');
                        }
                    });
                }, true],
                ['<button>NO</button>', function (instance, toast) {
                    instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                }]
            ]
        });
    });

    // Generate tracking number function
    function generateTrackingNumber() {
        // Generate a tracking number in the format FAT + timestamp + random digits
        var prefix = 'FAT';
        var now = new Date();
        var year = String(now.getFullYear()).slice(-2); // Last 2 digits of year
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var random = Math.floor(Math.random() * 1000000).toString().padStart(6, '0');

        return prefix + year + month + day + random;
    }

    // Show success message with iziToast
    function showSuccessMessage(message) {
        iziToast.success({
            title: 'Success',
            message: message,
            position: 'topRight',
            layout: 2
        });
    }

    // Show error message with iziToast
    function showErrorMessage(message) {
        iziToast.error({
            title: 'Error',
            message: message,
            position: 'topRight',
            layout: 2
        });
    }
});
