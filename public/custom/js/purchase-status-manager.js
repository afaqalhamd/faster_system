/**
 * Purchase Status Management JavaScript Module
 * Handles status updates with image upload and notes for ROG, Cancelled, and Returned statuses
 */

class PurchaseStatusManager {
    constructor() {
        this.statusesRequiringProof = ['ROG', 'Cancelled', 'Returned'];
        this.initializeEventListeners();
    }

    /**
     * Initialize event listeners for status management
     */
    initializeEventListeners() {
        // Status change event listener
        $(document).on('change', '.purchase-status-select', (e) => {
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
            const purchaseId = $(e.target).data('purchase-id');
            this.showStatusHistory(purchaseId);
        });
    }

    /**
     * Handle status change and show modal if proof is required
     */
    handleStatusChange(selectElement) {
        const selectedStatus = selectElement.value;
        const purchaseId = $(selectElement).data('purchase-id');

        if (!selectedStatus) return;

        // For new purchases (create form), show warning about ROG status
        if (purchaseId === 'new' && this.statusesRequiringProof.includes(selectedStatus)) {
            this.showCreateFormWarning(selectedStatus, selectElement);
            return;
        }

        // For existing purchases (edit form)
        if (purchaseId && purchaseId !== 'new' && this.statusesRequiringProof.includes(selectedStatus)) {
            // Check if user is trying to select ROG status
            if (selectedStatus === 'ROG') {
                // Validate payment before allowing ROG status
                if (!this.validatePaymentForROG()) {
                    // Show error message and reset to previous status
                    iziToast.error({
                        title: 'Error',
                        message: 'Cannot select ROG status. Please ensure the purchase is fully paid before changing to ROG status.',
                        position: 'topRight'
                    });
                    // Reset the select to the current status
                    const currentStatus = $('#current_purchase_status').val() || 'Pending';
                    $(selectElement).val(currentStatus);
                    return;
                }
            }
            this.showStatusUpdateModal(purchaseId, selectedStatus);
        } else if (purchaseId && purchaseId !== 'new') {
            // Check if user is trying to select ROG status even for non-proof statuses (shouldn't happen but just in case)
            if (selectedStatus === 'ROG') {
                if (!this.validatePaymentForROG()) {
                    iziToast.error({
                        title: 'Error',
                        message: 'Cannot select ROG status. Please ensure the purchase is fully paid before changing to ROG status.',
                        position: 'topRight'
                    });
                    const currentStatus = $('#current_purchase_status').val() || 'Pending';
                    $(selectElement).val(currentStatus);
                    return;
                }
            }
            this.updateStatusDirectly(purchaseId, selectedStatus);
        }
    }

    /**
     * Validate if the purchase is fully paid before allowing ROG status
     */
    validatePaymentForROG() {
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
    showStatusUpdateModal(purchaseId, status) {
        // Additional validation for ROG status
        if (status === 'ROG' && !this.validatePaymentForROG()) {
            iziToast.error({
                title: 'Error',
                message: 'Cannot select ROG status. Please ensure the purchase is fully paid before changing to ROG status.',
                position: 'topRight'
            });
            const currentStatus = $('#current_purchase_status').val() || 'Pending';
            $(`.purchase-status-select[data-purchase-id="${purchaseId}"]`).val(currentStatus);
            return;
        }

        const modal = `
            <div class="modal fade" id="statusUpdateModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Status to ${status}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form class="status-update-form" data-purchase-id="${purchaseId}" data-status="${status}">
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
    }

    /**
     * Show warning for create form when selecting ROG status
     */
    showCreateFormWarning(status, selectElement) {
        const modal = `
            <div class="modal fade" id="createFormWarningModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title text-dark">
                                <i class="bx bx-warning"></i> ${status} Status Selected
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <h6><i class="bx bx-info-circle"></i> Important Information:</h6>
                                <p>You have selected <strong>${status}</strong> status which requires:</p>
                                <ul>
                                    <li><strong>Notes:</strong> Mandatory description of the status change</li>
                                    ${status === 'ROG' ? '<li><strong>Proof Image:</strong> Required image evidence of receipt of goods</li>' : '<li><strong>Proof Image:</strong> Optional supporting image</li>'}
                                    ${status === 'ROG' ? '<li><strong>Inventory Addition:</strong> Will automatically add inventory</li>' : ''}
                                </ul>
                                <p class="mb-0">You can create the purchase with this status, but you'll need to provide proof and notes after creation through the edit form.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="$('#purchase_status').val('Pending')">Change to Pending</button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continue with ${status}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#createFormWarningModal').remove();

        // Add modal to body and show
        $('body').append(modal);
        $('#createFormWarningModal').modal('show');
    }

    /**
     * Update status directly without proof requirements
     */
    updateStatusDirectly(purchaseId, status) {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!csrfToken) {
            this.showAlert('danger', 'CSRF token not found. Please refresh the page and try again.');
            return;
        }

        const formData = new FormData();
        formData.append('purchase_status', status);
        formData.append('_token', csrfToken);

        this.submitStatusUpdateRequest(purchaseId, formData);
    }

    /**
     * Submit status update form with proof
     */
    submitStatusUpdate(form) {
        const purchaseId = $(form).data('purchase-id');
        const status = $(form).data('status');
        const formData = new FormData(form);

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!csrfToken) {
            this.showAlert('danger', 'CSRF token not found. Please refresh the page and try again.');
            return;
        }

        formData.append('purchase_status', status);
        formData.append('_token', csrfToken);

        this.submitStatusUpdateRequest(purchaseId, formData);
    }

    /**
     * Submit the actual status update request
     */
    submitStatusUpdateRequest(purchaseId, formData) {
        // Show loading state
        this.showLoading(true);

        // Debug: Check if CSRF token is available
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        console.log('CSRF Token:', csrfToken);

        if (!csrfToken) {
            this.showAlert('danger', 'CSRF token not found. Please refresh the page and try again.');
            this.showLoading(false);
            return;
        }

        // Make sure the token is in FormData
        if (!formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        $.ajax({
            url: `/purchase/bill/update-purchase-status/${purchaseId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: (response) => {
                this.handleSuccessResponse(response);
                $('#statusUpdateModal').modal('hide');
            },
            error: (xhr) => {
                console.log('AJAX Error:', xhr);
                this.handleErrorResponse(xhr);
            },
            complete: () => {
                this.showLoading(false);
            }
        });
    }

    /**
     * Handle successful status update
     */
    handleSuccessResponse(response) {
        // Update UI to reflect new status
        $('.current-status').text(response.purchase_status);
        $('#purchase_status option').prop('selected', false);
        $('#purchase_status option[value="' + response.purchase_status + '"]').prop('selected', true);

        // Show success message
        this.showAlert('success', response.message +
            (response.inventory_updated ? ' Inventory has been updated.' : ''));

        // Refresh the page or update specific elements
        if (response.inventory_updated) {
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
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

        this.showAlert('danger', errorMessage);
    }

    /**
     * Show status history modal
     */
    showStatusHistory(purchaseId) {
        $.ajax({
            url: `/purchase/bill/get-purchase-status-history/${purchaseId}`,
            method: 'GET',
            success: (response) => {
                this.displayStatusHistory(response.data);
            },
            error: (xhr) => {
                this.handleErrorResponse(xhr);
            }
        });
    }

    /**
     * Display status history in modal
     */
    displayStatusHistory(history) {
        let historyHtml = '<div class="timeline">';

        if (history.length === 0) {
            historyHtml += '<p class="text-muted">No status history available.</p>';
        } else {
            history.forEach((item, index) => {
                historyHtml += `
                    <div class="timeline-item ${index === 0 ? 'timeline-item-current' : ''}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${item.previous_status || 'Initial'} → ${item.new_status}</strong>
                                    <p class="mb-1">${item.notes || 'No notes provided'}</p>
                                    <small class="text-muted">
                                        Changed by ${item.changed_by?.name || 'Unknown'} on ${new Date(item.changed_at).toLocaleString()}
                                    </small>
                                </div>
                                ${item.proof_image ? `
                                    <a href="/storage/${item.proof_image}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-image"></i> View Proof
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        historyHtml += '</div>';

        const modal = `
            <div class="modal fade" id="statusHistoryModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Purchase Status History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${historyHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#statusHistoryModal').remove();

        // Add modal to body and show
        $('body').append(modal);
        $('#statusHistoryModal').modal('show');
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

    /**
     * Show alert message
     */
    showAlert(type, message) {
        // Map Bootstrap alert types to iziToast types
        const iziToastType = type === 'danger' ? 'error' : type;

        // Configure iziToast based on type
        const config = {
            title: type === 'success' ? 'Success' : type === 'danger' || type === 'error' ? 'Error' : 'Info',
            message: message,
            position: 'topRight',
            timeout: 5000,
            close: true,
            pauseOnHover: true
        };

        // Show the appropriate iziToast notification
        switch(iziToastType) {
            case 'success':
                iziToast.success(config);
                break;
            case 'error':
                iziToast.error(config);
                break;
            case 'warning':
                iziToast.warning(config);
                break;
            case 'info':
            default:
                iziToast.info(config);
                break;
        }
    }
}

// Initialize when document is ready
$(document).ready(() => {
    window.purchaseStatusManager = new PurchaseStatusManager();
});

// CSS for timeline (add this to your CSS file or include inline)
$('head').append(`
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    border-left: 2px solid #e9ecef;
    margin-left: 10px;
    padding-left: 20px;
}

.timeline-item:last-child {
    border-left: none;
    padding-bottom: 0;
}

.timeline-item-current {
    border-left-color: #0d6efd;
}

.timeline-marker {
    position: absolute;
    left: -6px;
    top: 5px;
    width: 10px;
    height: 10px;
    background: #6c757d;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-item-current .timeline-marker {
    background: #0d6efd;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
    border: 1px solid #e9ecef;
}

.timeline-item-current .timeline-content {
    background: #e7f1ff;
    border-color: #0d6efd;
}
</style>
`);
