<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $appDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('shipment.public_tracking') }} - {{ app('company')['name'] }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .tracking-header {
            background: linear-gradient(135deg, var(--primary-color), #0b5ed7);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .tracking-card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .tracking-search-input {
            border-radius: 50px 0 0 50px;
            border-right: none;
        }

        .tracking-search-btn {
            border-radius: 0 50px 50px 0;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .timeline-container {
            position: relative;
            padding-left: 30px;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .timeline-item.completed::before {
            background: var(--success-color);
            box-shadow: 0 0 0 2px var(--success-color);
        }

        .document-card {
            transition: transform 0.2s;
        }

        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .loading-spinner {
            display: none;
        }

        .result-container {
            display: none;
        }

        .error-message {
            display: none;
        }

        .tracking-footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .tracking-header {
                padding: 1.5rem 0;
            }

            .search-container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="tracking-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1>
                        <i class="bx bx-package me-2"></i>
                        {{ __('shipment.public_tracking') }}
                    </h1>
                    <p class="lead">{{ __('shipment.track_your_shipment') }}</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Search Section -->
        <section class="mb-5">
            <div class="card tracking-card">
                <div class="card-body p-4">
                    <div class="search-container">
                        <div class="input-group input-group-lg">
                            <input type="text"
                                   class="form-control tracking-search-input"
                                   id="trackingCode"
                                   placeholder="{{ __('shipment.enter_tracking_code') }}"
                                   autocomplete="off">
                            <button class="btn btn-primary tracking-search-btn"
                                    type="button"
                                    id="searchButton">
                                <i class="bx bx-search me-1"></i>
                                {{ __('app.search') }}
                            </button>
                        </div>
                        <div class="form-text text-center mt-2">
                            {{ __('shipment.search_by_waybill_tracking_or_order') }}
                        </div>
                    </div>

                    <!-- Loading Spinner -->
                    <div class="text-center mt-4 loading-spinner" id="loadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('app.loading') }}</span>
                        </div>
                        <p class="mt-2">{{ __('shipment.searching_for_tracking') }}</p>
                    </div>

                    <!-- Error Message -->
                    <div class="alert alert-danger mt-4 error-message" id="errorMessage" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <span id="errorText"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Results Section -->
        <section class="result-container" id="resultContainer">
            <div class="row">
                <!-- Shipment Information -->
                <div class="col-lg-8 mb-4">
                    <div class="card tracking-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                {{ __('shipment.shipment_information') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p>
                                        <strong>{{ __('shipment.waybill_number') }}:</strong>
                                        <span id="waybillNumber">-</span>
                                    </p>
                                    <p>
                                        <strong>{{ __('shipment.tracking_number') }}:</strong>
                                        <span id="trackingNumber">-</span>
                                    </p>
                                    <p>
                                        <strong>{{ __('sale.order.code') }}:</strong>
                                        <span id="orderCode">-</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>{{ __('carrier.carrier') }}:</strong>
                                        <span id="carrierName">-</span>
                                    </p>
                                    <p>
                                        <strong>{{ __('shipment.status') }}:</strong>
                                        <span id="shipmentStatus" class="badge status-badge">-</span>
                                    </p>
                                    <p>
                                        <strong>{{ __('customer.customer') }}:</strong>
                                        <span id="customerName">-</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p>
                                        <strong>{{ __('shipment.estimated_delivery_date') }}:</strong>
                                        <span id="estimatedDelivery">-</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>{{ __('shipment.actual_delivery_date') }}:</strong>
                                        <span id="actualDelivery">-</span>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3" id="shipmentNotesContainer" style="display: none;">
                                <p class="mb-1">
                                    <strong>{{ __('app.notes') }}:</strong>
                                </p>
                                <p class="text-muted" id="shipmentNotes"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="card tracking-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-link-external me-2"></i>
                                {{ __('app.actions') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" id="printWaybillBtn" style="display: none;">
                                    <i class="bx bx-printer me-1"></i>
                                    {{ __('shipment.print_waybill') }}
                                </button>
                                <button class="btn btn-outline-secondary" id="shareTrackingBtn">
                                    <i class="bx bx-share-alt me-1"></i>
                                    {{ __('app.share') }}
                                </button>
                                <button class="btn btn-outline-success" id="newSearchBtn">
                                    <i class="bx bx-search me-1"></i>
                                    {{ __('shipment.new_search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card tracking-card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-time-five me-2"></i>
                                {{ __('shipment.tracking_timeline') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="trackingTimeline">
                                <!-- Timeline will be populated here -->
                                <div class="text-center py-5">
                                    <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">{{ __('shipment.no_tracking_events') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card tracking-card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="bx bx-file me-2"></i>
                                {{ __('shipment.documents') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="documentsContainer">
                                <!-- Documents will be populated here -->
                                <div class="col-12 text-center py-4">
                                    <i class="bx bx-file-blank text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">{{ __('shipment.no_documents_available') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="tracking-footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        &copy; {{ date('Y') }} {{ app('company')['name'] }}.
                        {{ __('shipment.all_rights_reserved') }}
                    </p>
                    <p class="mb-0 mt-2">
                        <small>{{ __('shipment.for_customer_support') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchButton = document.getElementById('searchButton');
            const trackingCodeInput = document.getElementById('trackingCode');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            const resultContainer = document.getElementById('resultContainer');
            const printWaybillBtn = document.getElementById('printWaybillBtn');
            const shareTrackingBtn = document.getElementById('shareTrackingBtn');
            const newSearchBtn = document.getElementById('newSearchBtn');

            // Search when button is clicked
            searchButton.addEventListener('click', performSearch);

            // Search when Enter key is pressed
            trackingCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            // New search button
            newSearchBtn.addEventListener('click', function() {
                resultContainer.style.display = 'none';
                trackingCodeInput.value = '';
                trackingCodeInput.focus();
            });

            // Share tracking button
            shareTrackingBtn.addEventListener('click', function() {
                const trackingCode = trackingCodeInput.value;
                if (trackingCode) {
                    const shareUrl = window.location.origin + '/tracking?code=' + encodeURIComponent(trackingCode);
                    if (navigator.share) {
                        navigator.share({
                            title: '{{ __('shipment.public_tracking') }}',
                            text: '{{ __('shipment.track_your_shipment') }}',
                            url: shareUrl
                        }).catch(console.error);
                    } else {
                        // Fallback for browsers that don't support Web Share API
                        copyToClipboard(shareUrl);
                        alert('{{ __('shipment.tracking_link_copied') }}');
                    }
                }
            });

            // Print waybill button
            printWaybillBtn.addEventListener('click', function() {
                const trackingCode = trackingCodeInput.value;
                if (trackingCode) {
                    // Open waybill print page in new tab
                    window.open('/sale-orders/waybill-print/' + encodeURIComponent(trackingCode), '_blank');
                }
            });

            // Check for tracking code in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const codeParam = urlParams.get('code');
            if (codeParam) {
                trackingCodeInput.value = codeParam;
                performSearch();
            }

            function performSearch() {
                const trackingCode = trackingCodeInput.value.trim();

                if (!trackingCode) {
                    showError('{{ __('shipment.please_enter_tracking_code') }}');
                    return;
                }

                // Show loading spinner
                loadingSpinner.style.display = 'block';
                errorMessage.style.display = 'none';
                resultContainer.style.display = 'none';

                // Disable search button during request
                searchButton.disabled = true;
                searchButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> {{ __('app.searching') }}';

                // Make AJAX request
                fetch('/tracking/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tracking_code: trackingCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        displayTrackingData(data.data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('{{ __('app.something_went_wrong') }}');
                })
                .finally(() => {
                    // Hide loading spinner
                    loadingSpinner.style.display = 'none';

                    // Re-enable search button
                    searchButton.disabled = false;
                    searchButton.innerHTML = '<i class="bx bx-search me-1"></i> {{ __('app.search') }}';
                });
            }

            function displayTrackingData(data) {
                // Populate shipment information
                document.getElementById('waybillNumber').textContent = data.shipment.waybill_number || '-';
                document.getElementById('trackingNumber').textContent = data.shipment.tracking_number || '-';
                document.getElementById('orderCode').textContent = data.sale_order ? data.sale_order.order_code : '-';
                document.getElementById('carrierName').textContent = data.carrier ? data.carrier.name : '-';
                document.getElementById('customerName').textContent = data.customer ? data.customer.display_name : '-';
                document.getElementById('estimatedDelivery').textContent = data.shipment.formatted_estimated_delivery_date || '-';
                document.getElementById('actualDelivery').textContent = data.shipment.formatted_actual_delivery_date || '-';

                // Set shipment status with appropriate class
                const statusElement = document.getElementById('shipmentStatus');
                statusElement.textContent = data.shipment.status_label || data.shipment.status || '-';
                statusElement.className = 'badge status-badge';

                // Add status-specific classes
                switch(data.shipment.status) {
                    case 'Delivered':
                        statusElement.classList.add('bg-success');
                        break;
                    case 'Failed':
                    case 'Returned':
                        statusElement.classList.add('bg-danger');
                        break;
                    case 'In Transit':
                    case 'Out for Delivery':
                        statusElement.classList.add('bg-warning');
                        break;
                    case 'Pending':
                        statusElement.classList.add('bg-info');
                        break;
                    default:
                        statusElement.classList.add('bg-secondary');
                }

                // Show/hide notes
                const notesContainer = document.getElementById('shipmentNotesContainer');
                const notesElement = document.getElementById('shipmentNotes');
                if (data.shipment.notes) {
                    notesElement.textContent = data.shipment.notes;
                    notesContainer.style.display = 'block';
                } else {
                    notesContainer.style.display = 'none';
                }

                // Show/hide print button
                if (data.can_print) {
                    printWaybillBtn.style.display = 'block';
                } else {
                    printWaybillBtn.style.display = 'none';
                }

                // Populate tracking timeline
                populateTimeline(data.events);

                // Populate documents
                populateDocuments(data.documents);

                // Show results
                resultContainer.style.display = 'block';

                // Scroll to results
                resultContainer.scrollIntoView({ behavior: 'smooth' });
            }

            function populateTimeline(events) {
                const timelineContainer = document.getElementById('trackingTimeline');

                if (!events || events.length === 0) {
                    timelineContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">{{ __('shipment.no_tracking_events') }}</p>
                        </div>
                    `;
                    return;
                }

                let timelineHtml = '<div class="timeline-container">';

                events.forEach((event, index) => {
                    const isCompleted = index < events.length - 1;
                    const timelineClass = isCompleted ? 'timeline-item completed' : 'timeline-item';

                    timelineHtml += `
                        <div class="${timelineClass}">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">${event.location || '{{ __('shipment.unknown_location') }}'}</h6>
                                            <p class="text-muted mb-2">${event.description || ''}</p>
                                            <small class="text-muted">
                                                <i class="bx bx-calendar me-1"></i>
                                                ${event.formatted_date}
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge bg-secondary">${event.status || ''}</span>
                                        </div>
                                    </div>
                                    ${event.has_proof_image ? `
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bx bx-image me-1"></i>
                                                {{ __('shipment.view_proof') }}
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                timelineHtml += '</div>';
                timelineContainer.innerHTML = timelineHtml;
            }

            function populateDocuments(documents) {
                const documentsContainer = document.getElementById('documentsContainer');

                if (!documents || documents.length === 0) {
                    documentsContainer.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <i class="bx bx-file-blank text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">{{ __('shipment.no_documents_available') }}</p>
                        </div>
                    `;
                    return;
                }

                let documentsHtml = '';

                documents.forEach(document => {
                    documentsHtml += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card document-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bx bx-file text-primary me-2" style="font-size: 1.5rem;"></i>
                                        <h6 class="mb-0">${document.type_label}</h6>
                                    </div>
                                    <p class="text-muted small mb-2">${document.notes || ''}</p>
                                    <small class="text-muted">${document.created_at}</small>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="/tracking/document/${document.id}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bx bx-download me-1"></i>
                                        {{ __('app.download') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });

                documentsContainer.innerHTML = documentsHtml;
            }

            function showError(message) {
                errorText.textContent = message;
                errorMessage.style.display = 'block';

                // Scroll to error message
                errorMessage.scrollIntoView({ behavior: 'smooth' });
            }

            function copyToClipboard(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        });
    </script>
</body>
</html>
