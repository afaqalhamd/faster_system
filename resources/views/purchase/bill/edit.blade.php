@extends('layouts.app')
@section('title', __('purchase.bill'))

@section('css')
<link href="{{ asset('custom/css/purchase-status-icons.css') }}" rel="stylesheet">
@endsection

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'purchase.purchase',
                                            'purchase.bills',
                                            'purchase.update',
                                        ]"/>
                <div class="row">
                    <form class="g-3 needs-validation" id="invoiceForm" action="{{ ($purchase->operation == 'convert') ?  route('purchase-order.to.purchase.save') : route('purchase.bill.update') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf

                        {{-- Holds PUT method if Edit operation, else holds POST if Purchase Order converting to Purchase --}}
                        @if($purchase->operation == 'convert')
                            @method('POST')
                        @else
                            @method('PUT')
                        @endif

                        {{-- Hold Purchase Id if Edit operation, Purchase Order Id if Purchase Order converting to Purchase --}}
                        @if($purchase->operation == 'convert')
                            <input type="hidden" name="purchase_order_id" value="{{ $purchase->id }}">
                        @else
                            <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                        @endif


                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="row_count_payments" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">

                        {{-- Holds "update" method if Edit operation, else holds "convert" if Purchase Order converting to Purchase --}}
                        <input type="hidden" id="operation" name="operation" value="{{ $purchase->operation }}">

                        <input type="hidden" id="selectedPaymentTypesArray" value="{{ $selectedPaymentTypesArray }}">
                        <div class="row">
                            <div class="col-12 col-lg-12">
                                <div class="card">
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('purchase.details') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-4">
                                                <x-label for="party_id" name="{{ __('supplier.supplier') }}" />

                                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email"><i class="fadeIn animated bx bx-info-circle"></i></a>

                                                <div class="input-group">
                                                    <select class="form-select party-ajax" data-party-type='supplier' data-placeholder="Select Supplier" id="party_id" name="party_id">
                                                        <option value="{{ $purchase->party->id }}">{{ $purchase->party->first_name." ".$purchase->party->last_name }}</option>
                                                    </select>
                                                    <button type="button" class="input-group-text open-party-model" data-party-type='supplier'>
                                                        <i class='text-primary bx bx-plus-circle'></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="purchase_date" name="{{ __('app.date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker-edit" name="purchase_date" :required="true" value="{{ $purchase->formatted_purchase_date }}"/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <x-label for="purchase_code" name="{{ __('purchase.code') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" name="prefix_code" :required="true" placeholder="Prefix Code" value="{{ $purchase->prefix_code }}"/>
                                                    <span class="input-group-text">#</span>
                                                    <x-input type="text" name="count_id" :required="true" placeholder="Serial Number" value="{{ $purchase->count_id }}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="reference_no" name="{{ __('supplier.supplier_invoice_number') }}" />
                                                <x-input type="text" name="reference_no" :required="false" placeholder="(Optional)" value="{{ $purchase->reference_no }}"/>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="purchase_status" name="{{ __('Purchase Status') }}" />
                                                <div class="d-flex gap-2">
                                                    <div class="position-relative flex-grow-1">
                                                        <select class="form-select purchase-status-select" name="purchase_status" id="purchase_status" data-purchase-id="{{ $purchase->id }}">
                                                            @php
                                                                $generalDataService = new \App\Services\GeneralDataService();
                                                                $statusOptions = $generalDataService->getPurchaseStatus();
                                                            @endphp
                                                            @foreach($statusOptions as $status)
                                                                <option value="{{ $status['id'] }}"
                                                                        data-icon="{{ $status['icon'] }}"
                                                                        data-color="{{ $status['color'] }}"
                                                                        {{ $purchase->purchase_status == $status['id'] ? 'selected' : '' }}>
                                                                    {{ $status['name'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @if(isset($purchase) && $purchase->id)
                                                    <button type="button" class="btn btn-outline-info view-purchase-status-history" data-purchase-id="{{ $purchase->id }}" title="{{ __('View Status History') }}">
                                                        <i class="bx bx-history"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                <!-- Hidden input for current purchase status -->
                                                <input type="hidden" id="current_purchase_status" value="{{ $purchase->purchase_status }}">
                                                <small class="text-muted">
                                                    <i class="bx bx-info-circle"></i>
                                                    {{ __('ROG, Cancelled, and Returned statuses require proof images and notes') }}<br>
                                                    <i class="bx bx-package"></i>
                                                    {{ __('purchase.rog_required_for_inventory') }}
                                                    @if(isset($purchase) && $purchase->purchase_status === 'ROG')
                                                        <br><i class="bx bx-lock"></i>
                                                        {{ __('purchase.no_backward_after_rog') }}
                                                    @endif
                                                </small>
                                            </div>
                                            @if(app('company')['tax_type'] == 'gst')
                                            <div class="col-md-4">
                                                <x-label for="state_id" name="{{ __('app.state_of_supply') }}" />
                                                <x-dropdown-states selected="{{ $purchase->state_id }}" dropdownName='state_id'/>
                                            </div>
                                            @endif

                                            @if(app('company')['is_enable_secondary_currency'])
                                            <div class="col-md-4">
                                                <x-label for="invoice_currency_id" name="{{ __('currency.exchange_rate') }}" />
                                                <div class="input-group mb-3">
                                                    <x-dropdown-currency selected="{{ $purchase->currency_id }}" name='invoice_currency_id'/>
                                                    <x-input type="text" name="exchange_rate" :required="false" additionalClasses='cu_numeric' value="{{ $purchase->exchange_rate }}"/>
                                                </div>
                                            </div>
                                            @endif

                                            @if(app('company')['is_enable_carrier'] || $purchase->carrier_id !== null)
                                            <div class="col-md-4">
                                                <x-label for="carrier_id" name="{{ __('carrier.shipping_carrier') }} <i class='bx bx-package' ></i>" />
                                                <div class="input-group mb-3">
                                                    <x-dropdown-carrier selected="{{ $purchase->carrier_id }}" :showSelectOptionAll=true name='carrier_id' />
                                                </div>
                                            </div>
                                            @endif

                                    </div>
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('item.items') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-3 col-sm-12 col-lg-3">
                                                <x-label for="warehouse_id" name="{{ __('warehouse.warehouse') }}" />
                                                <x-dropdown-warehouse selected="" dropdownName='warehouse_id' />
                                            </div>
                                            <div class="col-md-9 col-sm-12 col-lg-7">
                                                <x-label for="search_item" name="{{ __('item.enter_item_name') }}" />
                                                <div class="input-group">
                                                    <span class="input-group-text" id="basic-addon1"><i class="fadeIn animated bx bx-barcode-reader text-primary"></i></span>
                                                    <input type="text" id="search_item" value="" class="form-control" required placeholder="Scan Barcode/Search Items">
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemModal"><i class="bx bx-plus-circle me-0"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12 col-sm-12 col-lg-2">
                                                <x-label for="show_load_items_modal" name="{{ __('purchase.purchased_items') }}" />
                                                <x-button type="button" class="btn btn-outline-secondary px-5 rounded-0 w-100" buttonId="show_load_items_modal" text="{{ __('app.load') }}" />
                                            </div>
                                            <div class="col-md-12 table-responsive">
                                                <table class="table mb-0 table-striped table-bordered" id="invoiceItemsTable">
                                                    <thead>
                                                        <tr class="text-uppercase">
                                                            <th scope="col">{{ __('app.action') }}</th>
                                                            <th scope="col">{{ __('item.item') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_serial_tracking'] ? 'd-none':'' }}">{{ __('item.serial') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_batch_tracking'] ? 'd-none':'' }}">{{ __('item.batch_no') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_mfg_date'] ? 'd-none':'' }}">{{ __('item.mfg_date') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_exp_date'] ? 'd-none':'' }}">{{ __('item.exp_date') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_model'] ? 'd-none':'' }}">{{ __('item.model_no') }}</th>
                                                            <th scope="col" class="{{ !app('company')['show_mrp'] ? 'd-none':'' }}">{{ __('item.mrp') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_color'] ? 'd-none':'' }}">{{ __('item.color') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_size'] ? 'd-none':'' }}">{{ __('item.size') }}</th>
                                                            <th scope="col">{{ __('app.qty') }}</th>
                                                            <th scope="col">{{ __('unit.unit') }}</th>
                                                            <th scope="col">{{ __('app.price_per_unit') }}</th>
                                                            <th scope="col" class="{{ !app('company')['show_discount'] ? 'd-none':'' }}">{{ __('app.discount') }}</th>
                                                            <th scope="col" class="{{ (app('company')['tax_type'] == 'no-tax') ? 'd-none':'' }}">{{ __('tax.tax') }}</th>
                                                            <th scope="col">{{ __('app.total') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="8" class="text-center fw-light fst-italic default-row">
                                                                No items are added yet!!
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="2" class="fw-bold text-end tfoot-first-td">
                                                                {{ __('app.total') }}
                                                            </td>
                                                            <td class="fw-bold sum_of_quantity">
                                                                0
                                                            </td>
                                                            <td class="fw-bold text-end" colspan="{{ 2 + (app('company')['show_discount'] ? 1 : 0) + (app('company')['tax_type'] == 'no-tax' ? 0 : 1) }}"></td>
                                                            <td class="fw-bold text-end sum_of_total">
                                                                0
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div class="col-md-8">
                                                <x-label for="note" name="{{ __('app.note') }}" />
                                                <x-textarea name='note' value='{{ $purchase->note }}'/>
                                            </div>
                                            <div class="col-md-4 mt-4">
                                                <table class="table mb-0 table-striped">
                                                   <tbody>
                                                        @if(app('company')['is_enable_carrier_charge'])
                                                       <tr>
                                                         <td>
                                                            <span class="fw-bold">{{ __('carrier.shipping_charge') }}</span>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_shipping_charge_distributed" name="is_shipping_charge_distributed" {{ $purchase->is_shipping_charge_distributed ? 'checked' : '' }}>
                                                                <label class="form-check-label small cursor-pointer" for="is_shipping_charge_distributed">{{ __('carrier.distribute_across_items') }}</label>
                                                            </div>
                                                        </td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end" name="shipping_charge" :required="true" placeholder="Shipping Charge" value="{{ $formatNumber->formatWithPrecision($purchase->shipping_charge ?? 0) }}"/>
                                                        </td>
                                                      </tr>
                                                      @endif
                                                      <tr>
                                                         <td class="w-50">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="round_off_checkbox">
                                                                <label class="form-check-label fw-bold cursor-pointer" for="round_off_checkbox">{{ __('app.round_off') }}</label>
                                                            </div>
                                                        </td>
                                                         <td class="w-50">
                                                            <x-input type="text" additionalClasses="text-end cu_numeric round_off " name="round_off" :required="false" placeholder="Round-Off" value="0"/>
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                         <td><span class="fw-bold">{{ __('app.grand_total') }}</span></td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end grand_total" readonly=true name="grand_total" :required="true" placeholder="Grand Total" value="0"/>
                                                        </td>
                                                      </tr>
                                                      @if(app('company')['is_enable_secondary_currency'])
                                                        <tr>
                                                             <td><span class="fw-bold exchange-lang" data-exchange-lang="{{ __('currency.converted_to') }}">{{ __('currency.converted_to') }}</span></td>
                                                             <td>
                                                                <x-input type="text" additionalClasses="text-end converted_amount" readonly=true :required="true" placeholder="Converted Amount" value="0"/>
                                                            </td>
                                                        </tr>
                                                      @endif
                                                      <tr>
                                                         <td><span class="fw-bold">{{ __('payment.previously_paid') }}</span></td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end paid_amount" readonly=true :required="false" placeholder="Paid Amount" value="{{ $formatNumber->formatWithPrecision($purchase->paid_amount) }}"/>
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                         <td><span class="fw-bold">{{ __('payment.balance') }}</span></td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end balance" readonly=true :required="false" placeholder="Balance" value="0"/>
                                                        </td>
                                                      </tr>
                                                   </tbody>
                                                </table>
                                            </div>
                                    </div>
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('payment.history') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3 ">
                                        <div class="col-md-12">
                                            <table class="table table-bordered" id="payments-table">
                                                <thead>
                                                    <tr class="table-secondary">
                                                        <th class="text-center">{{ __('payment.transaction_date') }}</th>
                                                        <th class="text-center">{{ __('payment.receipt_no') }}</th>
                                                        <th class="text-center">{{ __('payment.payment_type') }}</th>
                                                        <th class="text-center">{{ __('payment.amount') }}</th>
                                                        <th class="text-center">{{ __('app.action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- handle empty arrays in Blade -->
                                                    @php
                                                        $total = 0;
                                                    @endphp
                                                    @forelse ($paymentHistory as $payment)
                                                        <tr id="{{ $payment['id'] }}">
                                                            <td>{{ $payment['transaction_date'] }}</td>
                                                            <td>{{ $payment['reference_no'] }}</td>
                                                            <td>{{ $payment['type'] }}</td>
                                                            <td class="text-end">{{ $formatNumber->formatWithPrecision($payment['amount']) }}</td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-outline-danger delete-payment"><i class="bx bx-trash me-0"></i></button>
                                                            </td>
                                                        </tr>
                                                        @php
                                                            $total+=$payment['amount'];
                                                        @endphp
                                                    @empty
                                                    <tr>
                                                        <td class="text-center" colspan="5">
                                                            {{ __('payment.no_payment_history_found') }}
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                                <tfoot>
                                                    <th class="text-end" colspan="3">{{ __('app.total') }}</th>
                                                    <th class="text-end payment-total">{{ $formatNumber->formatWithPrecision($total) }}</th>
                                                    <th></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Status Change History Section --}}
                                    @if($purchase->purchaseStatusHistories->count() > 0)
                                    <div class="card-header px-4 py-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h4 class="mb-0 fw-bold">{{ __('purchase.status_change_history') }}</h4>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-light text-muted me-3" style="font-size: 13px; padding: 6px 12px;">{{ $purchase->purchaseStatusHistories->count() }} {{ __('app.changes') }}</span>
                                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#statusHistoryCollapse">
                                                    <i class="bx bx-chevron-down" style="font-size: 18px;"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="collapse show" id="statusHistoryCollapse">
                                        <div class="card-body p-5 row g-3">
                                            <div class="col-md-12">
                                            @foreach($purchase->purchaseStatusHistories->sortByDesc('changed_at') as $history)
                                                @php
                                                    $statusConfig = [
                                                        'Pending' => ['icon' => 'bx-time-five', 'color' => 'warning'],
                                                        'Processing' => ['icon' => 'bx-loader-circle', 'color' => 'primary'],
                                                        'Ordered' => ['icon' => 'bx-check-circle', 'color' => 'info'],
                                                        'Shipped' => ['icon' => 'bx-package', 'color' => 'info'],
                                                        'ROG' => ['icon' => 'bx-receipt', 'color' => 'success'],
                                                        'Cancelled' => ['icon' => 'bx-x-circle', 'color' => 'danger'],
                                                        'Returned' => ['icon' => 'bx-undo', 'color' => 'warning']
                                                    ];
                                                    $currentStatus = $statusConfig[$history->new_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary'];
                                                    $previousStatus = $history->previous_status ? ($statusConfig[$history->previous_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary']) : null;
                                                @endphp

                                                <div class="d-flex align-items-start mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }} position-relative">
                                                    <div class="me-4 position-relative">
                                                        <div class="bg-{{ $currentStatus['color'] }} text-white rounded-circle d-flex align-items-center justify-content-center timeline-status-circle" style="width: 40px; height: 40px; font-size: 16px; position: relative; z-index: 2;">
                                                            <i class="bx {{ $currentStatus['icon'] }}"></i>
                                                        </div>
                                                        @if(!$loop->last)
                                                            @php
                                                                $connectorColor = match($currentStatus['color']) {
                                                                    'warning' => '#ffc107',
                                                                    'primary' => '#0d6efd',
                                                                    'success' => '#198754',
                                                                    'info' => '#0dcaf0',
                                                                    'danger' => '#dc3545',
                                                                    default => '#6c757d'
                                                                };
                                                            @endphp
                                                            <div class="timeline-connector" style="position: absolute; top: 40px; left: 50%; transform: translateX(-50%); width: 3px; height: 50px; background: linear-gradient(180deg, {{ $connectorColor }} 0%, #e9ecef 100%); z-index: 1;"></div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                @if($history->previous_status)
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <span class="badge bg-{{ $previousStatus['color'] }} text-white" style="font-size: 13px; padding: 6px 12px;">
                                                                            <i class="bx {{ $previousStatus['icon'] }} me-2" style="font-size: 14px;"></i>{{ __('purchase.' . strtolower($history->previous_status)) }}
                                                                        </span>
                                                                        <i class="bx bx-right-arrow-alt text-muted" style="font-size: 16px;"></i>
                                                                        <span class="badge bg-{{ $currentStatus['color'] }} text-white" style="font-size: 13px; padding: 6px 12px;">
                                                                            <i class="bx {{ $currentStatus['icon'] }} me-2" style="font-size: 14px;"></i>{{ __('purchase.' . strtolower($history->new_status)) }}
                                                                        </span>
                                                                    </div>
                                                                @else
                                                                    <span class="badge bg-{{ $currentStatus['color'] }} text-white" style="font-size: 13px; padding: 6px 12px;">
                                                                        <i class="bx {{ $currentStatus['icon'] }} me-2" style="font-size: 14px;"></i>{{ __('purchase.' . strtolower($history->new_status)) }} <small>({{ __('app.initial') }})</small>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="text-primary fw-semibold" style="font-size: 14px;">{{ $history->changed_at->format('M d, H:i') }}</div>
                                                                <small class="text-muted d-block" style="font-size: 12px;">{{ $history->changed_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>

                                                        @if($history->notes)
                                                            <div class="bg-light rounded p-3 mb-3">
                                                                <div style="font-size: 14px;"><i class="bx bx-note text-primary me-2" style="font-size: 16px;"></i><strong>{{ __('purchase.notes') }}:</strong> {{ $history->notes }}</div>
                                                            </div>
                                                        @endif

                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="text-muted" style="font-size: 14px;">
                                                                <i class="bx bx-user text-danger me-2" style="font-size: 16px;"></i>
                                                                @if($history->changedBy)
                                                                    {{ trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name) }}
                                                                @elseif($history->changed_by)
                                                                    @php
                                                                        $user = \App\Models\User::find($history->changed_by);
                                                                        $userName = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Unknown User';
                                                                    @endphp
                                                                    {{ $userName }}
                                                                @else
                                                                    System Auto
                                                                @endif
                                                            </div>

                                                            @if($history->proof_image)
                                                                <button type="button" class="btn btn-outline-primary" style="font-size: 14px; padding: 8px 16px;" data-bs-toggle="modal" data-bs-target="#proofImageModal{{ $history->id }}">
                                                                    <i class="bx bx-image me-2" style="font-size: 16px;"></i>{{ __('purchase.view_proof') }}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>


                                                {{-- Enhanced Proof Image Modal --}}
                                                @if($history->proof_image)
                                                <div class="modal fade" id="proofImageModal{{ $history->id }}" tabindex="-1">
                                                    <div class="modal-dialog modal-xl">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">{{ __('purchase.' . strtolower($history->new_status)) }} {{ __('purchase.proof_image') }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-center p-4">
                                                                <img src="{{ asset('storage/' . $history->proof_image) }}"
                                                                     alt="{{ $history->new_status }} Proof"
                                                                     class="img-fluid rounded shadow" style="max-height: 500px;">

                                                                @if($history->notes)
                                                                    <div class="mt-4 p-4 bg-light rounded">
                                                                        <h6 class="fw-bold">{{ __('purchase.notes') }}:</h6>
                                                                        <p class="mb-0 fs-6">{{ $history->notes }}</p>
                                                                    </div>
                                                                @endif

                                                                <div class="mt-4 text-muted">
                                                                    <div class="fs-6">
                                                                        <strong>{{ __('purchase.changed_by') }}:</strong>
                                                                        @if($history->changedBy)
                                                                            {{ trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name) }}
                                                                        @elseif($history->changed_by)
                                                                            @php
                                                                                $user = \App\Models\User::find($history->changed_by);
                                                                                $userName = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Unknown User';
                                                                            @endphp
                                                                            {{ $userName }}
                                                                        @else
                                                                            System Auto
                                                                        @endif
                                                                    </div>
                                                                    <div class="fs-6 mt-1">{{ $history->changed_at->format('M d, Y H:i') }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <a href="{{ asset('storage/' . $history->proof_image) }}" download class="btn btn-primary">
                                                                    <i class="bx bx-download me-2"></i>{{ __('app.download') }}
                                                                </a>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('app.close') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('payment.payment') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3 ">
                                        <div class="payment-container">
                                            <div class="row payment-type-row-0 py-3 ">
                                                <div class="col-md-6">
                                                    <x-label for="amount" id="amount_lang" labelDataName="{{ __('payment.amount') }}" name="<strong>#1</strong> {{ __('payment.amount') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="cu_numeric" name="payment_amount[0]" value=""/>
                                                        <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-dollar"></i></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <x-label for="payment_type" id="payment_type_lang" name="{{ __('payment.type') }}" />
                                                    <div class="input-group">
                                                        <select class="form-select select2 payment-type-ajax" name="payment_type_id[0]" data-placeholder="Choose one thing">
                                                        </select>

                                                        <button type="button" class="input-group-text" data-bs-toggle="modal" data-bs-target="#paymentTypeModal">
                                                            <i class='text-primary bx bx-plus-circle'></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <x-label for="payment_note" id="payment_note_lang" name="{{ __('payment.note') }}" />
                                                    <x-textarea name="payment_note[0]" value=""/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <x-anchor-tag class="add_payment_type" href="javascript:;" text="<div class='d-flex align-items-center'><i class='fadeIn animated bx bx-plus font-30 text-primary'></i><div class=''>{{ __('payment.add_payment_type') }}</div></div>" />
                                        </div>
                                    </div>

                                    <div class="card-header px-4 py-3"></div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-12">
                                                <div class="d-md-flex d-grid align-items-center gap-3">
                                                    <x-button type="button" class="primary px-4" buttonId="submit_form" text="{{ __('app.submit') }}" />
                                                    <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                <!--end row-->
            </div>
        </div>
        <!-- Import Modals -->
        @include("modals.service.create")
        @include("modals.expense-category.create")
        @include("modals.payment-type.create")
        @include("modals.item.serial-tracking")
        @include("modals.party.create")
        @include("modals.item.create")
        @include("modals.purchase.order.load-purchased-items")

        @endsection

@section('js')
<script type="text/javascript">
        const itemsTableRecords = @json($itemTransactionsJson);
        const taxList = JSON.parse('{!! $taxList !!}');
</script>
<script src="{{ versionedAsset('custom/js/items/serial-tracking.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/payment-type/payment-type.js') }}"></script>
<script src="{{ versionedAsset('custom/js/payment-types/payment-type-select2-ajax.js') }}"></script>
<script src="{{ versionedAsset('custom/js/purchase/purchase-bill.js') }}"></script>
<script src="{{ versionedAsset('custom/js/purchase/purchase-status-icons.js') }}"></script>
<script>
    // Pass translations to the purchase status icons library
    window.purchaseStatusIcons.setTranslations({
        'purchase.pending': "{{ __('purchase.pending') }}",
        'purchase.processing': "{{ __('purchase.processing') }}",
        'purchase.completed': "{{ __('purchase.completed') }}",
        'purchase.shipped': "{{ __('purchase.shipped') }}",
        'purchase.rog': "{{ __('purchase.rog') }}",
        'purchase.cancelled': "{{ __('purchase.cancelled') }}",
        'purchase.returned': "{{ __('purchase.returned') }}"
    });

    // Re-initialize status icons after document is ready to ensure RTL handling
    $(document).ready(function() {
        setTimeout(function() {
            window.purchaseStatusIcons.initializeStatusSelects();
        }, 100);
    });
</script>
<script src="{{ versionedAsset('custom/js/purchase-status-manager.js') }}"></script>
<script src="{{ versionedAsset('custom/js/currency-exchange.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/party/party.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/item/item.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/purchase/order/load-purchased-items.js') }}"></script>
@endsection
