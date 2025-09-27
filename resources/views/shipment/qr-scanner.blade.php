@extends('layouts.app')

@section('title', 'QR Code Scanner')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="['shipment.tracking', 'QR Code Scanner']"/>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('shipment.qr_code_scanner') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="scanner-container">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <video id="qr-video" class="w-100 rounded border" style="max-height: 400px; background-color: #000;"></video>

                                    <div id="scanning-indicator" class="text-center mt-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">{{ __('app.loading') }}</span>
                                        </div>
                                        <p class="mt-2">{{ __('shipment.point_camera_at_qr_code') }}</p>
                                    </div>

                                    <div id="scan-result" class="mt-3 d-none">
                                        <div class="alert alert-success">
                                            <h6>{{ __('shipment.scan_successful') }}!</h6>
                                            <p id="scanned-data"></p>
                                            <div id="shipment-details"></div>
                                        </div>
                                    </div>

                                    <div id="scan-error" class="mt-3 d-none">
                                        <div class="alert alert-danger">
                                            <h6>{{ __('app.error') }}!</h6>
                                            <p id="error-message"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <button id="start-scanner" class="btn btn-primary">
                                <i class="bx bx-camera me-1"></i>{{ __('shipment.start_scanner') }}
                            </button>
                            <button id="stop-scanner" class="btn btn-secondary" disabled>
                                <i class="bx bx-stop-circle me-1"></i>{{ __('shipment.stop_scanner') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="{{ asset('custom/js/qr-scanner.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let isScanning = false;

    document.getElementById('start-scanner').addEventListener('click', function() {
        if (isScanning) return;

        isScanning = true;
        this.disabled = true;
        document.getElementById('stop-scanner').disabled = false;

        // Show scanning indicator
        document.getElementById('scanning-indicator').classList.remove('d-none');
        document.getElementById('scan-result').classList.add('d-none');
        document.getElementById('scan-error').classList.add('d-none');

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

    function displayShipmentDetails(data) {
        const detailsContainer = document.getElementById('shipment-details');
        let html = '<div class="mt-3">';

        if (data.tracking) {
            html += '<div class="card border-primary">';
            html += '<div class="card-body">';
            html += '<h6>{{ __('shipment.tracking_information') }}</h6>';
            html += '<p><strong>{{ __('shipment.waybill_number') }}:</strong> ' + (data.tracking.waybill_number || 'N/A') + '</p>';
            html += '<p><strong>{{ __('shipment.tracking_number') }}:</strong> ' + (data.tracking.tracking_number || 'N/A') + '</p>';
            html += '<p><strong>{{ __('shipment.status') }}:</strong> ' + (data.tracking.status || 'N/A') + '</p>';

            if (data.carrier) {
                html += '<p><strong>{{ __('carrier.carrier') }}:</strong> ' + data.carrier.name + '</p>';
            }

            html += '</div>';
            html += '</div>';
        }

        if (data.sale_order) {
            html += '<div class="card border-success mt-2">';
            html += '<div class="card-body">';
            html += '<h6>{{ __('sale.order.order') }}</h6>';
            html += '<p><strong>{{ __('sale.order.code') }}:</strong> ' + data.sale_order.order_code + '</p>';
            html += '<p><strong>{{ __('app.date') }}:</strong> ' + data.sale_order.formatted_order_date + '</p>';

            if (data.customer) {
                html += '<p><strong>{{ __('customer.customer') }}:</strong> ' + data.customer.first_name + ' ' + data.customer.last_name + '</p>';
            }

            html += '<div class="mt-2">';
            html += '<a href="/sale-orders/' + data.sale_order.id + '/edit" class="btn btn-sm btn-primary">';
            html += '<i class="bx bx-edit me-1"></i>{{ __('app.edit') }}';
            html += '</a>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        html += '</div>';
        detailsContainer.innerHTML = html;
    }
});
</script>
@endsection
