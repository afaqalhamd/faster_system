/**
 * Sale Order Status Management JavaScript Module
 * Handles status updates with image upload and notes for POD, Cancelled, and Returned statuses
 */

class SaleOrderStatusManager {
    constructor() {
        this.statusesRequiringProof = ['POD', 'Cancelled', 'Returned'];
        this.initializeEventListeners();
        // Initialize collapse state on page load
        this.initializeCollapseState();
        // Hide specific statuses for Delivery users
        this.hideDeliveryStatuses();
    }

    /**
     * Initialize event listeners for status management
     */
    initializeEventListeners() {
        // Status change event listener
        $(document).on('change', '.sale-order-status-select', (e) => {
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
        // Check if we're on the sale order edit page
        if ($('#statusHistoryContent').length && $('.view-status-history').length) {
            // The section is already shown by default, so we just need to update the icon
            $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
        }
    }

    /**
     * Hide specific statuses for Delivery users
     */
    hideDeliveryStatuses() {
        // Check if we're on the sale order edit page and user role is available
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
        const orderId = $('#sale_order_id').val();
        const $select = $(selectElement);

        // Store the previous status before changing
        const previousStatus = $('#current_order_status').val() || 'Pending';

        // Check if user is trying to select POD status
        if (selectedStatus === 'POD') {
            // Validate payment before allowing POD status
            if (!this.validatePaymentForPOD()) {
                // Show error message and reset to previous status
                iziToast.error({
                    title: 'Error',
                    message: 'Cannot select POD status. Please ensure the sale order is fully paid before changing to POD status.',
                    position: 'topRight'
                });
                // Reset the select to the current status
                $select.val(previousStatus);
                return;
            }
        }

        if (this.statusesRequiringProof.includes(selectedStatus)) {
            this.showStatusUpdateModal(orderId, selectedStatus);
        } else {
            this.updateStatusDirectly(orderId, selectedStatus);
        }
    }

    /**
     * Validate if the sale order is fully paid before allowing POD status
     */
    validatePaymentForPOD() {
        // Get grand total and paid amount from the form
        const grandTotal = parseFloat($('.grand_total').val()) || 0;
        const paidAmount = parseFloat($('.paid_amount').val()) || 0;

        // Allow a small tolerance for floating point comparison
        const tolerance = 0.01;

        // Check if paid amount is equal to or greater than grand total (within tolerance)
        return (paidAmount + tolerance) >= grandTotal;
    }

    /**
     * Show modal for status update with proof requirements
     */
    showStatusUpdateModal(orderId, status) {
        // Additional validation for POD status
        if (status === 'POD' && !this.validatePaymentForPOD()) {
            iziToast.error({
                title: 'Error',
                message: 'Cannot select POD status. Please ensure the sale order is fully paid before changing to POD status.',
                position: 'topRight'
            });
            const currentStatus = $('#current_order_status').val() || 'Pending';
            $(`.sale-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
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
                                    <label class="form-label">Proof Image ${status === 'POD' ? '*' : ''}</label>
                                    <input type="file" name="proof_image" class="form-control"
                                        accept="image/*" ${status === 'POD' ? 'required' : ''}>
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
            $(`.sale-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
            $('#statusUpdateModal').modal('hide');
        });

        // Handle modal close (X button or clicking outside)
        $('#statusUpdateModal').on('hidden.bs.modal', () => {
            // Reset the status dropdown to the previous value
            const currentStatus = $('#current_order_status').val() || 'Pending';
            $(`.sale-order-status-select[data-order-id="${orderId}"]`).val(currentStatus);
            // Remove the modal from DOM
            $('#statusUpdateModal').remove();
        });
    }

    /**
     * Update status directly without proof requirements
     */
    updateStatusDirectly(orderId, status) {
        const formData = new FormData();

        formData.append('status', status);
        formData.append('notes', ''); // Empty notes for direct updates
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('order_id', orderId);

        this.submitStatusUpdateRequest(orderId, formData);
    }

    /**
     * Submit status update form with proof
     */
    submitStatusUpdate(form) {
        const orderId = $(form).data('order-id');
        const status = $(form).data('status');
        const formData = new FormData(form);

        formData.append('status', status);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('order_id', orderId);

        this.submitStatusUpdateRequest(orderId, formData);
    }

    /**
     * Submit the actual status update request
     */
    submitStatusUpdateRequest(orderId, formData) {
        // Show loading state
        this.showLoading(true);

        $.ajax({
            url: `/sale/order/update-status`,
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
     * Show status history - scroll to and expand the section
     */
    showStatusHistory() {
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
        new SaleOrderStatusManager();
    }, 100);
});
