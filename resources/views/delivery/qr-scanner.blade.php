@extends('layouts.app')

@section('title', __('delivery.qr_scanner'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('delivery.qr_scanner') }}</h5>
                    </div>
                    <div class="card-body">
                        <!-- Scanner Container -->
                        <div class="scanner-container">
                            <video id="qr-video" class="w-100" style="max-height: 400px;"></video>

                            <!-- Scanning Indicator -->
                            <div id="scanning-indicator" class="text-center mt-3 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">{{ __('delivery.scanning') }}...</span>
                                </div>
                                <p class="mt-2">{{ __('delivery.point_camera_at_qr') }}</p>
                            </div>

                            <!-- Scan Result -->
                            <div id="scan-result" class="mt-3 d-none">
                                <div class="alert alert-success">
                                    <h6>{{ __('delivery.scan_successful') }}!</h6>
                                    <p id="scanned-data"></p>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div id="scan-error" class="mt-3 d-none">
                                <div class="alert alert-danger">
                                    <h6>{{ __('delivery.scan_failed') }}</h6>
                                    <p id="error-message"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Scanner Controls -->
                        <div class="mt-3">
                            <button id="start-scanner" class="btn btn-primary">{{ __('delivery.start_scanner') }}</button>
                            <button id="stop-scanner" class="btn btn-secondary" disabled>{{ __('delivery.stop_scanner') }}</button>
                        </div>

                        <!-- Order Details Section -->
                        <div id="order-details" class="mt-4 d-none">
                            <h5>{{ __('delivery.order_details') }}</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>{{ __('customer.customer') }}</h6>
                                            <p id="customer-name"></p>
                                            <p id="customer-phone"></p>
                                            <p id="customer-address"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>{{ __('sale.order') }}</h6>
                                            <p>{{ __('sale.order.code') }}: <span id="order-code"></span></p>
                                            <p>{{ __('sale.total_amount') }}: <span id="order-total"></span></p>
                                            <p>{{ __('sale.paid_amount') }}: <span id="paid-amount"></span></p>
                                            <p>{{ __('sale.due_amount') }}: <span id="due-amount"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Form -->
                            <div id="payment-form" class="mt-3 d-none">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>{{ __('delivery.collect_payment') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="payment-collection-form">
                                            <div class="mb-3">
                                                <label for="payment-amount" class="form-label">{{ __('delivery.amount') }}</label>
                                                <input type="number" class="form-control" id="payment-amount" step="0.01" min="0">
                                            </div>
                                            <div class="mb-3">
                                                <label for="payment-method" class="form-label">{{ __('delivery.payment_method') }}</label>
                                                <select class="form-select" id="payment-method">
                                                    <option value="">{{ __('app.select') }}</option>
                                                    <!-- Payment methods will be populated dynamically -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="reference-number" class="form-label">{{ __('delivery.reference_number') }}</label>
                                                <input type="text" class="form-control" id="reference-number">
                                            </div>
                                            <div class="mb-3">
                                                <label for="payment-notes" class="form-label">{{ __('app.note') }}</label>
                                                <textarea class="form-control" id="payment-notes" rows="3"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success">{{ __('delivery.collect_payment') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- POD Form -->
                            <div id="pod-form" class="mt-3 d-none">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>{{ __('delivery.proof_of_delivery') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="pod-confirmation-form">
                                            <div class="mb-3">
                                                <label for="pod-notes" class="form-label">{{ __('app.note') }} <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="pod-notes" rows="3" required></textarea>
                                                <div class="form-text">{{ __('delivery.pod_notes_required') }}</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="signature" class="form-label">{{ __('delivery.signature') }}</label>
                                                <div id="signature-pad" class="border rounded" style="height: 150px;"></div>
                                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="clear-signature">{{ __('delivery.clear_signature') }}</button>
                                            </div>
                                            <div class="mb-3">
                                                <label for="pod-photos" class="form-label">{{ __('delivery.photos') }}</label>
                                                <input type="file" class="form-control" id="pod-photos" multiple accept="image/*">
                                            </div>
                                            <button type="submit" class="btn btn-success">{{ __('delivery.confirm_delivery') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include required libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="{{ asset('js/qr-scanner.js') }}"></script>
<script src="{{ asset('js/delivery-workflow.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delivery workflow
    const deliveryWorkflow = new DeliveryWorkflow();

    let stream = null;
    let isScanning = false;

    // Scanner control buttons
    document.getElementById('start-scanner').addEventListener('click', function() {
        this.disabled = true;
        document.getElementById('stop-scanner').disabled = false;

        // Show scanning indicator
        document.getElementById('scanning-indicator').classList.remove('d-none');

        // Initialize QR scanner
        initQRScanner('qr-video', function(result) {
            if (result.status) {
                // Hide scanning indicator
                document.getElementById('scanning-indicator').classList.add('d-none');

                // Show scan result
                document.getElementById('scanned-data').textContent = result.data;
                document.getElementById('scan-result').classList.remove('d-none');

                // Process the scanned data
                processScannedData(result.data);
            } else {
                // Show error
                document.getElementById('scanning-indicator').classList.add('d-none');
                document.getElementById('error-message').textContent = result.message;
                document.getElementById('scan-error').classList.remove('d-none');

                // Reset scanning state
                isScanning = false;
                document.getElementById('start-scanner').disabled = false;
                document.getElementById('stop-scanner').disabled = true;
            }
        });
    });

    document.getElementById('stop-scanner').addEventListener('click', function() {
        stopQRScanner('qr-video');
        isScanning = false;
        this.disabled = true;
        document.getElementById('start-scanner').disabled = false;

        // Hide scanning indicator
        document.getElementById('scanning-indicator').classList.add('d-none');
    });

    /**
     * Process scanned data
     * @param {string} data - The scanned data
     */
    function processScannedData(data) {
        // Send to server for processing
        processScannedQRCode(data, function(response) {
            if (response.status) {
                // Handle successful processing
                displayShipmentDetails(response.data);
            } else {
                // Handle error
                document.getElementById('error-message').textContent = response.message;
                document.getElementById('scan-error').classList.remove('d-none');
            }

            // Reset scanning state
            isScanning = false;
            document.getElementById('start-scanner').disabled = false;
            document.getElementById('stop-scanner').disabled = true;
        });
    }

    /**
     * Display shipment details
     * @param {Object} data - The shipment data
     */
    function displayShipmentDetails(data) {
        // Show order details section
        document.getElementById('order-details').classList.remove('d-none');

        // Populate customer information
        if (data.customer) {
            document.getElementById('customer-name').textContent = data.customer.first_name + ' ' + data.customer.last_name;
            document.getElementById('customer-phone').textContent = data.customer.mobile || '';
            document.getElementById('customer-address').textContent = data.customer.address || '';
        }

        // Populate order information
        if (data.sale_order) {
            document.getElementById('order-code').textContent = data.sale_order.order_code;
            document.getElementById('order-total').textContent = data.sale_order.grand_total;
            document.getElementById('paid-amount').textContent = data.sale_order.paid_amount;

            // Calculate due amount
            const dueAmount = data.sale_order.grand_total - data.sale_order.paid_amount;
            document.getElementById('due-amount').textContent = dueAmount;

            // Check payment status
            if (dueAmount <= 0) {
                // Payment is complete, show POD form
                document.getElementById('payment-form').classList.add('d-none');
                document.getElementById('pod-form').classList.remove('d-none');
            } else {
                // Payment is incomplete, show payment form
                document.getElementById('payment-form').classList.remove('d-none');
                document.getElementById('pod-form').classList.add('d-none');

                // Pre-fill payment amount with due amount
                document.getElementById('payment-amount').value = dueAmount;
            }
        }
    }

    // Payment form submission
    document.getElementById('payment-collection-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data
        const orderId = document.getElementById('order-code').textContent; // This should be the actual order ID
        const paymentData = {
            amount: document.getElementById('payment-amount').value,
            payment_type_id: document.getElementById('payment-method').value,
            reference_number: document.getElementById('reference-number').value,
            notes: document.getElementById('payment-notes').value
        };

        // Collect payment
        // Note: In a real implementation, you would need the actual order ID
        // deliveryWorkflow.collectPayment(orderId, paymentData)
        //     .then(response => {
        //         // Show success message
        //         // Show POD form after successful payment
        //         document.getElementById('payment-form').classList.add('d-none');
        //         document.getElementById('pod-form').classList.remove('d-none');
        //     })
        //     .catch(error => {
        //         // Show error message
        //     });
    });

    // POD form submission
    document.getElementById('pod-confirmation-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data
        const orderId = document.getElementById('order-code').textContent; // This should be the actual order ID
        const podData = {
            notes: document.getElementById('pod-notes').value,
            // signature: ..., // Signature data would be captured here
            // photos: ... // Photo data would be captured here
        };

        // Complete delivery
        // Note: In a real implementation, you would need the actual order ID
        // deliveryWorkflow.completeDelivery(orderId, podData)
        //     .then(response => {
        //         // Show success message
        //         // Redirect or show confirmation
        //     })
        //     .catch(error => {
        //         // Show error message
        //     });
    });

    // Clear signature button
    document.getElementById('clear-signature').addEventListener('click', function() {
        // Clear signature pad
        // Implementation depends on the signature pad library used
    });
});
</script>
@endsection
