/**
 * Sales Status Management JavaScript Module
 * Handles status updates with image upload and notes for POD, Cancelled, and Returned statuses
 */

class SalesStatusManager {
    constructor() {
        this.statusesRequiringProof = ['POD', 'Cancelled', 'Returned'];
        this.initializeEventListeners();
    }

    /**
     * Initialize event listeners for status management
     */
    initializeEventListeners() {
        // Status change event listener
        $(document).on('change', '.sales-status-select', (e) => {
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
            const saleId = $(e.target).data('sale-id');
            this.showStatusHistory(saleId);
        });
    }

    /**
     * Handle status change and show modal if proof is required
     */
    handleStatusChange(selectElement) {
        const selectedStatus = selectElement.value;
        const saleId = $(selectElement).data('sale-id');

        if (this.statusesRequiringProof.includes(selectedStatus)) {
            this.showStatusUpdateModal(saleId, selectedStatus);
        } else {
            this.updateStatusDirectly(saleId, selectedStatus);
        }
    }

    /**
     * Show modal for status update with proof requirements
     */
    showStatusUpdateModal(saleId, status) {
        const modal = `
            <div class="modal fade" id="statusUpdateModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Status to ${status}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form class="status-update-form" data-sale-id="${saleId}" data-status="${status}">
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
     * Update status directly without proof requirements
     */
    updateStatusDirectly(saleId, status) {
        const formData = new FormData();
        formData.append('sales_status', status);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        this.submitStatusUpdateRequest(saleId, formData);
    }

    /**
     * Submit status update form with proof
     */
    submitStatusUpdate(form) {
        const saleId = $(form).data('sale-id');
        const status = $(form).data('status');
        const formData = new FormData(form);

        formData.append('sales_status', status);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        this.submitStatusUpdateRequest(saleId, formData);
    }

    /**
     * Submit the actual status update request
     */
    submitStatusUpdateRequest(saleId, formData) {
        // Show loading state
        this.showLoading(true);

        $.ajax({
            url: `/sale/invoice/update-sales-status/${saleId}`,
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
     * Handle successful status update
     */
    handleSuccessResponse(response) {
        // Update UI to reflect new status
        $('.current-status').text(response.sales_status);

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
    showStatusHistory(saleId) {
        $.ajax({
            url: `/sale/invoice/get-sales-status-history/${saleId}`,
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

        history.forEach((item, index) => {
            historyHtml += `
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6>${item.previous_status || 'Initial'} → ${item.new_status}</h6>
                        <p class="text-muted mb-1">
                            <small>
                                ${new Date(item.changed_at).toLocaleString()}
                                by ${item.changed_by.name}
                            </small>
                        </p>
                        ${item.notes ? `<p class="mb-2">${item.notes}</p>` : ''}
                        ${item.proof_image ? `
                            <div class="mb-2">
                                <img src="/storage/${item.proof_image}"
                                     class="img-thumbnail"
                                     style="max-width: 200px;"
                                     onclick="window.open('/storage/${item.proof_image}', '_blank')">
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        historyHtml += '</div>';

        const modal = `
            <div class="modal fade" id="statusHistoryModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Sales Status History</h5>
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
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('.alert').remove(); // Remove existing alerts
        $('.container-fluid').prepend(alert);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
}

// Initialize when document is ready
$(document).ready(() => {
    new SalesStatusManager();
});

// CSS for timeline (add this to your CSS file)
const timelineCSS = `
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -37px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline-content h6 {
    color: #495057;
    margin-bottom: 8px;
}

.img-thumbnail {
    cursor: pointer;
    transition: transform 0.2s;
}

.img-thumbnail:hover {
    transform: scale(1.05);
}
</style>
`;

// Add CSS to head
$('head').append(timelineCSS);
