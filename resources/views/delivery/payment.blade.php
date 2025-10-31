@extends('layouts.app')

@section('title', __('delivery.collect_payment'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('delivery.collect_payment') }}</h5>
                    </div>
                    <div class="card-body">
                        <p>{{ __('delivery.collect_payment') }} {{ __('app.page_under_construction') }}</p>
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
