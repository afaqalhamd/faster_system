@extends('layouts.app')

@section('title', __('sale.delivery_payment'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">{{ __('sale.delivery') }}</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">{{ __('sale.delivery_payment') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header px-4 py-3">
                        <h5 class="mb-0">{{ __('sale.delivery_payment') }}</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ __('sale.sale_details') }}</h5>
                                        <div class="table-responsive">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th>{{ __('sale.sale_code') }}</th>
                                                    <td id="sale_code">-</td>
                                                </tr>
                                                <tr>
                                                    <th>{{ __('party.customer') }}</th>
                                                    <td id="customer_name">-</td>
                                                </tr>
                                                <tr>
                                                    <th>{{ __('sale.grand_total') }}</th>
                                                    <td id="grand_total">-</td>
                                                </tr>
                                                <tr>
                                                    <th>{{ __('payment.paid_amount') }}</th>
                                                    <td id="paid_amount">-</td>
                                                </tr>
                                                <tr>
                                                    <th>{{ __('payment.balance') }}</th>
                                                    <td id="balance">-</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ __('payment.record_payment') }}</h5>
                                        <form id="deliveryPaymentForm">
                                            @csrf
                                            <input type="hidden" id="sale_id" name="sale_id">

                                            <div class="mb-3">
                                                <label for="amount" class="form-label">{{ __('payment.amount') }}</label>
                                                <input type="text" class="form-control cu_numeric" id="amount" name="amount" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="payment_type_id" class="form-label">{{ __('payment.type') }}</label>
                                                <select class="form-select" id="payment_type_id" name="payment_type_id" required>
                                                    <option value="">{{ __('app.select') }} {{ __('payment.type') }}</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="note" class="form-label">{{ __('payment.note') }}</label>
                                                <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-primary">{{ __('payment.record_payment') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">{{ __('payment.payment_history') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="paymentHistoryTable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('app.date') }}</th>
                                                        <th>{{ __('payment.amount') }}</th>
                                                        <th>{{ __('payment.type') }}</th>
                                                        <th>{{ __('payment.note') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="paymentHistoryBody">
                                                    <!-- Payment history will be loaded here -->
                                                </tbody>
                                            </table>
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
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Get sale ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const saleId = urlParams.get('sale_id');

        if (saleId) {
            loadDeliveryPaymentDetails(saleId);
            loadPaymentTypes();
        }

        // Handle form submission
        $('#deliveryPaymentForm').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                url: `/sale/invoice/delivery-payment/${saleId}`,
                method: 'POST',
                data: formData,
                beforeSend: function() {
                    showSpinner();
                },
                success: function(response) {
                    if (response.status) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight'
                        });

                        // Reset form
                        $('#deliveryPaymentForm')[0].reset();

                        // Reload payment details
                        loadDeliveryPaymentDetails(saleId);
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: response.message,
                            position: 'topRight'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    iziToast.error({
                        title: 'Error',
                        message: errorMessage,
                        position: 'topRight'
                    });
                },
                complete: function() {
                    hideSpinner();
                }
            });
        });
    });

    function loadDeliveryPaymentDetails(saleId) {
        $.ajax({
            url: `/sale/invoice/delivery-payment-details/${saleId}`,
            method: 'GET',
            beforeSend: function() {
                showSpinner();
            },
            success: function(response) {
                if (response.status) {
                    const data = response.data;

                    // Update sale details
                    $('#sale_code').text(data.sale_code);
                    $('#customer_name').text(data.customer_name);
                    $('#grand_total').text(data.grand_total);
                    $('#paid_amount').text(data.paid_amount);
                    $('#balance').text(data.balance);
                    $('#sale_id').val(data.sale_id);

                    // Update payment history
                    updatePaymentHistory(data.payments);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message,
                        position: 'topRight'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to load payment details';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                iziToast.error({
                    title: 'Error',
                    message: errorMessage,
                    position: 'topRight'
                });
            },
            complete: function() {
                hideSpinner();
            }
        });
    }

    function loadPaymentTypes() {
        $.ajax({
            url: '/api/payment-types', // Updated to use API endpoint
            method: 'GET',
            success: function(response) {
                const paymentTypes = response.data || response || [];
                let options = '<option value="">{{ __("app.select") }} {{ __("payment.type") }}</option>';

                paymentTypes.forEach(function(type) {
                    // Ensure we're accessing the correct properties
                    const id = type.id || type.value || '';
                    const name = type.name || type.text || type.label || '';

                    if (id && name) {
                        options += `<option value="${id}">${name}</option>`;
                    }
                });

                $('#payment_type_id').html(options);
            },
            error: function() {
                iziToast.error({
                    title: 'Error',
                    message: 'Failed to load payment types',
                    position: 'topRight'
                });
            }
        });
    }

    function updatePaymentHistory(payments) {
        let html = '';

        if (payments && payments.length === 0) {
            html = '<tr><td colspan="4" class="text-center">{{ __("payment.no_payment_history") }}</td></tr>';
        } else if (payments) {
            payments.forEach(function(payment) {
                html += `
                    <tr>
                        <td>${payment.transaction_date || payment.date || '-'}</td>
                        <td>${payment.amount || '-'}</td>
                        <td>${payment.payment_type || payment.type || '-'}</td>
                        <td>${payment.note || '-'}</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center">{{ __("payment.no_payment_history") }}</td></tr>';
        }

        $('#paymentHistoryBody').html(html);
    }
</script>
@endpush
