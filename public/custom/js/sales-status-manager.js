/**
 * Sales Status Management JavaScript Module
 * Handles status updates with image upload and notes for POD, Cancelled, and Returned statuses
 */

class SalesStatusManager {
    constructor() {
        this.statusesRequiringProof = ['POD', 'Cancelled', 'Returned'];

        // Translations
        this.translations = {
            en: {
                pod_payment_error: 'Cannot select POD status. Please ensure the sale is fully paid before changing to POD status.',
                success: 'Success',
                error: 'Error',
                info: 'Info',
                status_history: 'Sales Status History',
                no_status_history: 'No status history available.',
                view_proof: 'View Proof',
                changed_by: 'Changed by',
                initial: 'Initial',
                no_notes: 'No notes provided',
                unknown: 'Unknown',
                close: 'Close'
            },
            ar: {
                pod_payment_error: 'لا يمكن تحديد حالة "إثبات التسليم" (POD). يرجى التأكد من أن عملية البيع مدفوعة بالكامل قبل تغيير الحالة.',
                success: 'نجاح',
                error: 'خطأ',
                info: 'معلومة',
                status_history: 'سجل حالات المبيعات',
                no_status_history: 'لا يوجد سجل للحالات متاح.',
                view_proof: 'عرض الإثبات',
                changed_by: 'تم التغيير بواسطة',
                initial: 'أولي',
                no_notes: 'لا توجد ملاحظات',
                unknown: 'غير معروف',
                close: 'إغلاق'
            }
        };

        // Detect current language (default to English)
        this.currentLang = document.documentElement.lang || 'en';

        this.initializeEventListeners();
    }

    /**
     * Get translated message
     */
    getTranslation(key) {
        return this.translations[this.currentLang] && this.translations[this.currentLang][key]
            ? this.translations[this.currentLang][key]
            : this.translations['en'][key] || key;
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

        if (!selectedStatus) return;

        // For new sales (create form), show warning about POD status
        if (saleId === 'new' && this.statusesRequiringProof.includes(selectedStatus)) {
            this.showCreateFormWarning(selectedStatus, selectElement);
            return;
        }

        // For existing sales (edit form)
        if (saleId && saleId !== 'new' && this.statusesRequiringProof.includes(selectedStatus)) {
            // Check if user is trying to select POD status
            if (selectedStatus === 'POD') {
                // Validate payment before allowing POD status
                if (!this.validatePaymentForPOD()) {
                    // Show error message and reset to previous status
                    this.showAlert('danger', this.getTranslation('pod_payment_error'));
                    // Reset the select to the current status
                    const currentStatus = $('#current_sale_status').val() || 'Pending';
                    $(selectElement).val(currentStatus);
                    return;
                }
            }
            this.showStatusUpdateModal(saleId, selectedStatus);
        } else if (saleId && saleId !== 'new') {
            // Check if user is trying to select POD status even for non-proof statuses (shouldn't happen but just in case)
            if (selectedStatus === 'POD') {
                if (!this.validatePaymentForPOD()) {
                    this.showAlert('danger', this.getTranslation('pod_payment_error'));
                    const currentStatus = $('#current_sale_status').val() || 'Pending';
                    $(selectElement).val(currentStatus);
                    return;
                }
            }
            this.updateStatusDirectly(saleId, selectedStatus);
        }
    }

    /**
     * Validate if the sale is fully paid before allowing POD status
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
    showStatusUpdateModal(saleId, status) {
        // Additional validation for POD status
        if (status === 'POD' && !this.validatePaymentForPOD()) {
            this.showAlert('danger', this.getTranslation('pod_payment_error'));
            const currentStatus = $('#current_sale_status').val() || 'Pending';
            $(`.sales-status-select[data-sale-id="${saleId}"]`).val(currentStatus);
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
     * Show warning for create form when selecting POD status
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
                                    ${status === 'POD' ? '<li><strong>Proof Image:</strong> Required image evidence of delivery</li>' : '<li><strong>Proof Image:</strong> Optional supporting image</li>'}
                                    ${status === 'POD' ? '<li><strong>Inventory Deduction:</strong> Will automatically deduct inventory</li>' : ''}
                                </ul>
                                <p class="mb-0">You can create the sale with this status, but you'll need to provide proof and notes after creation through the edit form.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="$('#sales_status').val('Pending')">Change to Pending</button>
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
    updateStatusDirectly(saleId, status) {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!csrfToken) {
            this.showAlert('danger', 'CSRF token not found. Please refresh the page and try again.');
            return;
        }

        const formData = new FormData();
        formData.append('sales_status', status);
        formData.append('_token', csrfToken);

        this.submitStatusUpdateRequest(saleId, formData);
    }

    /**
     * Submit status update form with proof
     */
    submitStatusUpdate(form) {
        const saleId = $(form).data('sale-id');
        const status = $(form).data('status');
        const formData = new FormData(form);

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!csrfToken) {
            this.showAlert('danger', 'CSRF token not found. Please refresh the page and try again.');
            return;
        }

        formData.append('sales_status', status);
        formData.append('_token', csrfToken);

        this.submitStatusUpdateRequest(saleId, formData);
    }

    /**
     * Submit the actual status update request
     */
    submitStatusUpdateRequest(saleId, formData) {
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
            url: `/sale/invoice/update-sales-status/${saleId}`,
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
        $('.current-status').text(response.sales_status);
        $('#sales_status option').prop('selected', false);
        $('#sales_status option[value="' + response.sales_status + '"]').prop('selected', true);

        // Update the hidden current status field
        $('#current_sale_status').val(response.sales_status);

        // Show success message
        this.showAlert('success', response.message +
            (response.inventory_updated ? ' Inventory has been updated.' : ''));

        // Refresh the page or update specific elements
        if (response.inventory_updated) {
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // For POD status changes, we might want to refresh the page to show updated information
            if (response.sales_status === 'POD') {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
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

        if (history.length === 0) {
            historyHtml += `<p class="text-muted">${this.getTranslation('no_status_history')}</p>`;
        } else {
            history.forEach((item, index) => {
                historyHtml += `
                    <div class="timeline-item ${index === 0 ? 'timeline-item-current' : ''}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${item.previous_status || this.getTranslation('initial')} → ${item.new_status}</strong>
                                    <p class="mb-1">${item.notes || this.getTranslation('no_notes')}</p>
                                    <small class="text-muted">
                                        ${this.getTranslation('changed_by')} ${item.changed_by?.name || this.getTranslation('unknown')} on ${new Date(item.changed_at).toLocaleString()}
                                    </small>
                                </div>
                                ${item.proof_image ? `
                                    <a href="/storage/${item.proof_image}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-image"></i> ${this.getTranslation('view_proof')}
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
                            <h5 class="modal-title">${this.getTranslation('status_history')}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${historyHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.getTranslation('close')}</button>
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

        // Get translated title based on type
        let title = this.getTranslation('info');
        if (type === 'success') {
            title = this.getTranslation('success');
        } else if (type === 'danger' || type === 'error') {
            title = this.getTranslation('error');
        }

        // Configure iziToast based on type
        const config = {
            title: title,
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
    window.salesStatusManager = new SalesStatusManager();
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
