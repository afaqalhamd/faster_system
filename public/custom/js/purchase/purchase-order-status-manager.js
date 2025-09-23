/**
 * Purchase Order Status Management JavaScript Module
 * Handles status updates with image upload and notes for ROG, Cancelled, and Returned statuses
 */

class PurchaseOrderStatusManager {
    constructor() {
        this.statusesRequiringProof = ['ROG', 'Cancelled', 'Returned'];
        this.initializeEventListeners();
        // Initialize collapse state on page load
        this.initializeCollapseState();
        // Hide specific statuses for Delivery users
        this.hideDeliveryStatuses();
        // Automatically load status history on page load
        // Status history is loaded on demand when the user clicks the history button
    }

    /**
     * Initialize event listeners for status management
     */
    initializeEventListeners() {
        // Status change event listener
        $(document).on('change', '.purchase-order-status-select', (e) => {
            this.handleStatusChange(e.target);
        });

        // Status update form submission
        $(document).on('submit', '.status-update-form', (e) => {
            e.preventDefault();
            this.submitStatusUpdate(e.target);
        });

        // Status history modal trigger
        $(document).on('click', '.view-status-history', (e) => {
            e.preventDefault();
            this.showStatusHistory();
        });

        // Handle collapse events for status history section
        $('#statusHistoryCollapse').on('hide.bs.collapse', function () {
            $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-up').addClass('bx-chevron-down');
        });

        $('#statusHistoryCollapse').on('show.bs.collapse', function () {
            $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
        });
    }

    /**
     * Initialize collapse state
     */
    initializeCollapseState() {
        // Check if we're on the purchase order edit page
        if ($('#statusHistoryContent').length && $('.view-status-history').length) {
            // The section is already shown by default, so we just need to update the icon
            $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
        }
    }

    /**
     * Hide specific statuses for Delivery users
     */
    hideDeliveryStatuses() {
        // Check if we're on the purchase order edit page and user role is available
        if ($('#order_status').length && typeof window.userRole !== 'undefined') {
            // If user role is Delivery, hide Pending, Processing, and Completed options
            if (window.userRole === 'Delivery') {
                $('#order_status option[value="Pending"]').remove();
                $('#order_status option[value="Processing"]').remove();
                $('#order_status option[value="Completed"]').remove();
            }
        }
    }

    /**
     * Handle status change and show modal if proof is required
     */
    handleStatusChange(selectElement) {
        const selectedStatus = selectElement.value;
        const orderId = $('#purchase_order_id').val();

        if (this.statusesRequiringProof.includes(selectedStatus)) {
            this.showStatusUpdateModal(orderId, selectedStatus);
        } else {
            this.updateStatusDirectly(orderId, selectedStatus);
        }
    }

    /**
     * Validate if the purchase order is fully paid before allowing ROG status
     */
    validatePaymentForROG() {
        // Get grand total and paid amount from the form
        // Remove any non-numeric characters (commas, currency symbols, etc.)
        const grandTotalStr = $('.grand_total').val() || '0';
        const paidAmountStr = $('.paid_amount').val() || '0';

        // Parse the values, handling formatted numbers
        const grandTotal = parseFloat(grandTotalStr.replace(/[^0-9.-]+/g, '')) || 0;
        const paidAmount = parseFloat(paidAmountStr.replace(/[^0-9.-]+/g, '')) || 0;

        // Allow a small tolerance for floating point comparison
        const tolerance = 0.01;

        // Check if paid amount is equal to or greater than grand total (within tolerance)
        return (paidAmount + tolerance) >= grandTotal;
    }

    /**
     * Show modal for status update with proof requirements
     */
    showStatusUpdateModal(orderId, status) {
        // Additional validation for ROG status
        if (status === 'ROG' && !this.validatePaymentForROG()) {
            iziToast.error({
                title: 'Error',
                message: 'Cannot select ROG status. Please ensure the purchase order is fully paid before changing to ROG status.',
                position: 'topRight'
            });
            const currentStatus = $('#current_order_status').val() || 'Pending';
            $(`.purchase-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
            return;
        }

        const modal = `
            <div class="modal fade" id="statusUpdateModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Status to ${status}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form class="status-update-form" data-order-id="${orderId}" data-status="${status}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Notes *</label>
                                    <textarea name="notes" class="form-control" rows="3" required
                                        placeholder="Please provide notes for this status change..."></textarea>
                                </div>
                                ${status !== 'Cancelled' ? `
                                <div class="mb-3">
                                    <label class="form-label">Proof Image ${status === 'ROG' ? '*' : ''}</label>
                                    <input type="file" name="proof_image" class="form-control"
                                        accept="image/*" ${status === 'ROG' ? 'required' : ''}>
                                    <small class="text-muted">Maximum size: 2MB. Supported formats: JPG, PNG, GIF</small>
                                </div>
                                ` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" id="cancelStatusUpdate">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#statusUpdateModal').remove();

        // Add modal to body and show
        $('body').append(modal);
        $('#statusUpdateModal').modal('show');

        // Handle cancel button click
        $(document).off('click', '#cancelStatusUpdate').on('click', '#cancelStatusUpdate', () => {
            // Reset the status dropdown to the previous value
            const currentStatus = $('#current_order_status').val() || 'Pending';
            $(`.purchase-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
            $('#statusUpdateModal').modal('hide');
        });

        // Handle modal close (X button or clicking outside)
        $('#statusUpdateModal').on('hidden.bs.modal', () => {
            // Reset the status dropdown to the previous value
            const currentStatus = $('#current_order_status').val() || 'Pending';
            $(`.purchase-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
            // Remove the modal from DOM
            $('#statusUpdateModal').remove();
        });
    }

    /**
     * Update status directly without proof requirements
     */
    updateStatusDirectly(orderId, status) {
        const formData = new FormData();

        formData.append('order_status', status);
        formData.append('notes', ''); // Empty notes for direct updates
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('order_id', orderId);

        this.submitStatusUpdateRequest(orderId, formData);
    }

    /**
     * Submit status update form with proof
     */
    submitStatusUpdate(form) {
        const orderId = $('#purchase_order_id').val();
        const status = $(form).data('status');
        const formData = new FormData(form);

        // Remove any existing status or token fields to avoid duplication
        formData.delete('order_status');
        formData.delete('_token');

        // Append the correct data
        formData.append('order_status', status);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        this.submitStatusUpdateRequest(orderId, formData);
    }

    /**
     * Submit the actual status update request
     */
    submitStatusUpdateRequest(orderId, formData) {
        // Show loading state
        this.showLoading(true);

        $.ajax({
            url: `/purchase/order/update-status/${orderId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                this.handleSuccessResponse(response);
                $('#statusUpdateModal').modal('hide');
            },
            error: (xhr) => {
                this.handleErrorResponse(xhr);
            },
            complete: () => {
                this.showLoading(false);
            }
        });
    }

    /**
     * Show status history - scroll to and expand the section
     */
    showStatusHistory() {
        const orderId = $('#purchase_order_id').val();
        if (orderId) {
            // Show loading indicator
            $('#statusHistoryContent').html('<div class="text-center py-5"><i class="bx bx-loader-alt bx-spin text-primary" style="font-size: 2rem;"></i><p class="mt-2">Loading status history...</p></div>');

            // Make sure the collapse is shown
            if (!$('#statusHistoryCollapse').hasClass('show')) {
                $('#statusHistoryCollapse').collapse('show');
                // Update the toggle button icon
                $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
            }

            // Scroll to the status history section
            $('html, body').animate({
                scrollTop: $('#statusHistoryCollapse').offset().top - 100
            }, 500);

            // Fetch status history via AJAX
            $.ajax({
                url: `/purchase/order/get-status-history/${orderId}`,
                method: 'GET',
                success: (response) => {
                    if (response.status) {
                        this.updateStatusHistorySection(response.data);
                    } else {
                        $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + (response.message || 'Unknown error') + '</p></div>');
                    }
                },
                error: (xhr) => {
                    let errorMessage = 'An error occurred while fetching status history.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + errorMessage + '</p></div>');
                }
            });
        }
    }

    /**
     * Update the status history section with new data
     */
    updateStatusHistorySection(history) {
        // Update the count badge
        $('#statusHistoryCount').text(history.length + ' changes');

        let historyHtml = '';

        if (history.length === 0) {
            historyHtml = `
                <div class="text-center py-5" id="noStatusHistoryMessage">
                    <i class="bx bx-history text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No status history available yet.</p>
                    <p class="text-muted small">Status changes will be recorded here.</p>
                </div>
            `;
        } else {
            // Sort history by changed_at descending (newest first)
            history.sort((a, b) => new Date(b.changed_at) - new Date(a.changed_at));

            history.forEach((item, index) => {
                // Status configuration for icons and colors
                const statusConfig = {
                    'Pending': {'icon': 'bx-time-five', 'color': 'warning'},
                    'Processing': {'icon': 'bx-loader-circle', 'color': 'primary'},
                    'Ordered': {'icon': 'bx-package', 'color': 'info'},
                    'Shipped': {'icon': 'bx-truck', 'color': 'primary'},
                    'ROG': {'icon': 'bx-receipt', 'color': 'success'},
                    'Cancelled': {'icon': 'bx-x-circle', 'color': 'danger'},
                    'Returned': {'icon': 'bx-undo', 'color': 'warning'}
                };

                const currentStatus = statusConfig[item.new_status] || {'icon': 'bx-circle', 'color': 'secondary'};
                const previousStatus = item.previous_status ? (statusConfig[item.previous_status] || {'icon': 'bx-circle', 'color': 'secondary'}) : null;

                // Determine connector color
                let connectorColor = '#6c757d'; // default gray
                if (currentStatus.color === 'warning') connectorColor = '#ffc107';
                else if (currentStatus.color === 'primary') connectorColor = '#0d6efd';
                else if (currentStatus.color === 'success') connectorColor = '#198754';
                else if (currentStatus.color === 'info') connectorColor = '#0dcaf0';
                else if (currentStatus.color === 'danger') connectorColor = '#dc3545';

                historyHtml += `
                    <div class="d-flex align-items-start mb-3 pb-3 ${index !== history.length - 1 ? 'border-bottom' : ''} position-relative">
                        <div class="me-3 position-relative">
                            <div class="bg-${currentStatus.color} text-white rounded-circle d-flex align-items-center justify-content-center timeline-status-circle" style="width: 28px; height: 28px; font-size: 12px; position: relative; z-index: 2;">
                                <i class="bx ${currentStatus.icon}"></i>
                            </div>
                            ${index !== history.length - 1 ? `
                                <div class="timeline-connector" style="position: absolute; top: 28px; left: 50%; transform: translateX(-50%); width: 2px; height: 40px; background: linear-gradient(180deg, ${connectorColor} 0%, #e9ecef 100%); z-index: 1;"></div>
                            ` : ''}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    ${item.previous_status ? `
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge bg-${previousStatus.color} text-white small">
                                                <i class="bx ${previousStatus.icon} me-1"></i>${item.previous_status}
                                            </span>
                                            <i class="bx bx-right-arrow-alt text-muted" style="font-size: 12px;"></i>
                                            <span class="badge bg-${currentStatus.color} text-white small">
                                                <i class="bx ${currentStatus.icon} me-1"></i>${item.new_status}
                                            </span>
                                        </div>
                                    ` : `
                                        <span class="badge bg-${currentStatus.color} text-white small">
                                            <i class="bx ${currentStatus.icon} me-1"></i>${item.new_status} <small>(Initial)</small>
                                        </span>
                                    `}
                                </div>
                                <div class="text-end">
                                    <small class="text-primary fw-semibold">${new Date(item.changed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${new Date(item.changed_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small>
                                    <small class="text-muted d-block">${this.timeSince(new Date(item.changed_at))}</small>
                                </div>
                            </div>

                            ${item.notes ? `
                                <div class="bg-light rounded p-2 mb-2">
                                    <small><i class="bx bx-note text-primary me-1"></i><strong>Notes:</strong> ${item.notes}</small>
                                </div>
                            ` : ''}

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bx bx-user text-danger me-1"></i>
                                    ${item.changed_by || 'Unknown User'}
                                </small>

                                ${item.proof_image_url ? `
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#proofImageModal${item.id}">
                                        <i class="bx bx-image me-1"></i>View Proof
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Proof Image Modal (only created if it doesn't already exist in the DOM) -->
                    ${item.proof_image_url && !$(`#proofImageModal${item.id}`).length ? `
                        <div class="modal fade" id="proofImageModal${item.id}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="proofImageModalLabel${item.id}" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="proofImageModalLabel${item.id}">Proof Image - ${item.new_status} Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="${item.proof_image_url}" alt="Proof for ${item.new_status} status" class="img-fluid rounded">
                                        ${item.notes ? `
                                            <div class="mt-3 p-3 bg-light rounded">
                                                <strong>Notes:</strong>
                                                <p class="mb-0">${item.notes}</p>
                                            </div>
                                        ` : ''}
                                        <div class="mt-3 text-muted">
                                            <small>
                                                Changed by: ${item.changed_by || 'Unknown User'} |
                                                ${new Date(item.changed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                                ${new Date(item.changed_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="${item.proof_image_url}" download class="btn btn-primary">
                                            <i class="bx bx-download me-1"></i>Download
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                `;
            });
        }

        $('#statusHistoryContent').html(historyHtml);

        // Re-animate timeline connectors
        setTimeout(function() {
            $('.timeline-connector').addClass('animate');
        }, 100);
    }

    /**
     * Helper function to calculate time since
     */
    timeSince(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return Math.floor(interval) + " years ago";
        }

        interval = seconds / 2592000;
        if (interval > 1) {
            return Math.floor(interval) + " months ago";
        }

        interval = seconds / 86400;
        if (interval > 1) {
            return Math.floor(interval) + " days ago";
        }

        interval = seconds / 3600;
        if (interval > 1) {
            return Math.floor(interval) + " hours ago";
        }

        interval = seconds / 60;
        if (interval > 1) {
            return Math.floor(interval) + " minutes ago";
        }

        return Math.floor(seconds) + " seconds ago";
    }

    /**
     * Handle successful status update
     */
    handleSuccessResponse(response) {
        // Show success message
        iziToast.success({
            title: 'Success',
            message: response.message,
            position: 'topRight'
        });

        // Reload page to show updated status
        setTimeout(() => {
            location.reload();
        }, 1500);
    }

    /**
     * Handle error response
     */
    handleErrorResponse(xhr) {
        let errorMessage = 'An error occurred while updating the status.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }

        if (xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = Object.values(xhr.responseJSON.errors).flat();
            errorMessage += '<br>' + errors.join('<br>');
        }

        iziToast.error({
            title: 'Error',
            message: errorMessage,
            position: 'topRight'
        });
    }

    /**
     * Show loading state
     */
    showLoading(show) {
        if (show) {
            $('.status-update-form button[type="submit"]').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Updating...'
            );
        } else {
            $('.status-update-form button[type="submit"]').prop('disabled', false).html('Update Status');
        }
    }
}

// Initialize when document is ready
$(document).ready(() => {
    // Add a small delay to ensure DOM is fully loaded
    setTimeout(() => {
        new PurchaseOrderStatusManager();
    }, 100);
});
