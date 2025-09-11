@extends('layouts.app')
@section('title', __('sale.order.order'))

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'sale.sale',
                                            'sale.order.list',
                                            'sale.order.update',
                                        ]"/>
                <div class="row">
                    <form class="g-3 needs-validation" id="invoiceForm" action="{{ route('sale.order.update') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('PUT')

                        <input type="hidden" id="sale_order_id" name="sale_order_id" value="{{ $order->id }}">
                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="row_count_payments" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <input type="hidden" id="operation" name="operation" value="update">
                        <input type="hidden" id="selectedPaymentTypesArray" value="{{ $selectedPaymentTypesArray }}">
                        <div class="row">
                            <div class="col-12 col-lg-12">
                                <div class="card">
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('sale.order.details') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-4">
                                                <x-label for="party_id" name="{{ __('customer.customer') }}" />

                                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email"><i class="fadeIn animated bx bx-info-circle"></i></a>

                                                <div class="input-group">
                                                    <select class="form-select party-ajax" data-party-type='customer' data-placeholder="Select Customer" id="party_id" name="party_id">
                                                        <option value="{{ $order->party->id }}">{{ $order->party->first_name." ".$order->party->last_name }}</option>
                                                    </select>
                                                    <button type="button" class="input-group-text open-party-model" data-party-type='customer'>
                                                        <i class='text-primary bx bx-plus-circle'></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="order_date" name="{{ __('app.date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker-edit" name="order_date" :required="true" value="{{ $order->formatted_order_date }}"/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="due_date" name="{{ __('app.due_date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker-edit" name="due_date" :required="true" value="{{ $order->formatted_due_date }}"/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="order_code" name="{{ __('sale.order.code') }}" />
                                                <!--  -->
                                                <div class="input-group mb-3">
                                                    <x-input type="text" name="prefix_code" :required="true" placeholder="Prefix Code" value="{{ $order->prefix_code }}"/>
                                                    <span class="input-group-text">#</span>
                                                    <x-input type="text" name="count_id" :required="true" placeholder="Serial Number" value="{{ $order->count_id }}"/>
                                                </div>
                                            </div>
                                            @if(app('company')['tax_type'] == 'gst')
                                            <div class="col-md-4">
                                                <x-label for="state_id" name="{{ __('app.state_of_supply') }}" />
                                                <x-dropdown-states selected="{{ $order->state_id }}" dropdownName='state_id'/>
                                            </div>
                                            @endif

                                            @if(app('company')['is_enable_carrier'] || $order->carrier_id !== null)
                                            <div class="col-md-4">
                                                <x-label for="carrier_id" name="{{ __('carrier.shipping_carrier') }} <i class='bx bx-package' ></i>" />
                                                <div class="input-group mb-3">
                                                    <x-dropdown-carrier selected="{{ $order->carrier_id }}" :showSelectOptionAll=true name='carrier_id' />
                                                </div>
                                            </div>
                                            @endif

                                            <div class="col-md-4">
                                                <x-label for="order_status" name="{{ __('sale.order_status') }}" />
                                                <div class="d-flex gap-2">
                                                    <div class="position-relative flex-grow-1">
                                                        <select class="form-select sale-order-status-select" name="order_status" id="order_status" data-order-id="{{ $order->id }}">
                                                            @php
                                                                $generalDataService = new \App\Services\GeneralDataService();
                                                                $statusOptions = $generalDataService->getSaleOrderStatus();
                                                            @endphp
                                                            @foreach($statusOptions as $status)
                                                                <option value="{{ $status['id'] }}"
                                                                        data-icon="{{ $status['icon'] }}"
                                                                        data-color="{{ $status['color'] }}"
                                                                        {{ $order->order_status == $status['id'] ? 'selected' : '' }}>
                                                                    {{ $status['name'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @if(isset($order) && $order->id)
                                                    <button type="button" class="btn btn-outline-info view-status-history" data-order-id="{{ $order->id }}" title="{{ __('View Status History') }}">
                                                        <i class="bx bx-history"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bx bx-info-circle"></i>
                                                    {{ __('POD, Cancelled, and Returned statuses require proof images and notes') }}
                                                </small>
                                            </div>
                                            @if(app('company')['is_enable_secondary_currency'])
                                            <div class="col-md-4">
                                                <x-label for="invoice_currency_id" name="{{ __('currency.exchange_rate') }}" />
                                                <div class="input-group mb-3">
                                                    <x-dropdown-currency selected="{{ $order->currency_id }}" name='invoice_currency_id'/>
                                                    <x-input type="text" name="exchange_rate" :required="false" additionalClasses='cu_numeric' value="{{ $order->exchange_rate }}"/>
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
                                                <x-label for="show_load_items_modal" name="{{ __('sale.sold_items') }}" />
                                                <x-button type="button" class="btn btn-outline-secondary px-5 rounded-0 w-100" buttonId="show_load_items_modal" text="{{ __('app.load') }}" />
                                            </div>
                                            <div class="col-md-12 table-responsive">
                                                <table class="table mb-0 table-striped table-bordered" id="invoiceItemsTable">
                                                    <thead>
                                                        <tr class="text-uppercase">
                                                            <th scope="col">{{ __('app.action') }}</th>
                                                            <th scope="col">{{ __('item.item') }}</th>
                                                            <th scope="col">{{ __('item.sku') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_serial_tracking'] ? 'd-none':'' }}">{{ __('item.serial') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_batch_tracking'] ? 'd-none':'' }}">{{ __('item.batch_no') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_mfg_date'] ? 'd-none':'' }}">{{ __('item.mfg_date') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_exp_date'] ? 'd-none':'' }}">{{ __('item.exp_date') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_model'] ? 'd-none':'' }}">{{ __('item.model_no') }}</th>
                                                            <th scope="col" class="{{ !app('company')['show_mrp'] ? 'd-none':'' }}">{{ __('item.mrp') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_color'] ? 'd-none':'' }}">{{ __('item.color') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_size'] ? 'd-none':'' }}">{{ __('item.size') }}</th>
                                                            <th scope="col" class="d-none">{{ __('unit.unit') }}</th>
                                                            <th scope="col" class="d-none2">{{ __('app.price_per_unit') }}</th>
                                                            <th scope="col">{{ __('app.qty') }}</th>
                                                            <th scope="col">{{ __('app.real_qty') }}</th>
                                                            <th scope="col" class="{{ !app('company')['show_discount'] ? 'd-none':'' }}">{{ __('app.discount') }}</th>
                                                            <th scope="col" class="{{ (app('company')['tax_type'] == 'no-tax') ? 'd-none':'' }}">{{ __('tax.tax') }}</th>
                                                            <th scope="col">{{ __('app.location') }}</th>
                                                            <th scope="col">{{ __('app.status') }}</th>
                                                            <th scope="col">{{ __('app.action') }}</th>
                                                            <th scope="col" class="d-none">{{ __('app.total') }}</th>
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
                                                            <td class="fw-bold text-end" colspan="4"></td>
                                                            <td class="fw-bold text-end sum_of_total">
                                                                0
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div class="col-md-8">
                                                <x-label for="note" name="{{ __('app.note') }}" />
                                                <x-textarea name='note' value='{{ $order->note }}'/>
                                            </div>
                                            <div class="col-md-4 mt-4">
                                                <table class="table mb-0 table-striped">
                                                   <tbody>
                                                    @if(app('company')['is_enable_carrier_charge'])
                                                       <tr>
                                                         <td>
                                                            <span class="fw-bold">{{ __('carrier.shipping_charge') }}</span>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_shipping_charge_distributed" name="is_shipping_charge_distributed" {{ $order->is_shipping_charge_distributed ? 'checked' : '' }}>
                                                                <label class="form-check-label small cursor-pointer" for="is_shipping_charge_distributed">{{ __('carrier.distribute_across_items') }}</label>
                                                            </div>
                                                        </td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end" name="shipping_charge" :required="true" placeholder="Shipping Charge" value="{{ $formatNumber->formatWithPrecision($order->shipping_charge ?? 0) }}"/>
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
                                                            <x-input type="text" additionalClasses="text-end grand_total" readonly=true name="grand_total" :required="true" placeholder="Round-Off" value="0"/>
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
                                                            <x-input type="text" additionalClasses="text-end paid_amount" readonly=true :required="false" placeholder="Paid Amount" value="{{ $formatNumber->formatWithPrecision($order->paid_amount, comma:false) }}"/>
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
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('payment.payment') }}</h5>

                                        @if(isset($order->inventory_status))
                                            <div class="mt-2">
                                                @if($order->inventory_status === 'pending')
                                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                        <i class="bx bx-clock"></i>
                                                        <strong>{{ __('Inventory Status') }}:</strong> {{ __('Reserved but not deducted') }}
                                                        <br><small>{{ __('Inventory will be automatically deducted when payment is completed') }}</small>
                                                    </div>
                                                @elseif($order->inventory_status === 'deducted')
                                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                        <i class="bx bx-check-circle"></i>
                                                        <strong>{{ __('Inventory Status') }}:</strong> {{ __('Deducted') }}
                                                        @if($order->inventory_deducted_at)
                                                            <br><small>{{ __('Deducted on') }}: {{ $order->inventory_deducted_at->format('Y-m-d H:i:s') }}</small>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($order->inventory_status === 'pending' && auth()->user()->can('sale.order.manual.inventory.deduction'))
                                                    <div class="mb-3">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" id="manualInventoryDeduction" data-order-id="{{ $order->id }}">
                                                            <i class="bx bx-package"></i> {{ __('Manual Inventory Deduction') }}
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
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

                                    {{-- Status Change History Section --}}
                                    <div class="card-header px-4 py-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5 class="mb-0">Status Change History</h5>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-light text-muted me-2 small" id="statusHistoryCount">
                                                    {{ $order->saleOrderStatusHistories->count() }} changes
                                                </span>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#statusHistoryCollapse" aria-expanded="true" aria-controls="statusHistoryCollapse">
                                                    <i class="bx bx-chevron-up fs-6"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="collapse show" id="statusHistoryCollapse">
                                        <div class="card-body p-4 row g-3">
                                            <div class="col-md-12" id="statusHistoryContent">
                                                @if($order->saleOrderStatusHistories->count() > 0)
                                                    @foreach($order->saleOrderStatusHistories->sortByDesc('changed_at') as $history)
                                                        @php
                                                            $statusConfig = [
                                                                'Pending' => ['icon' => 'bx-time-five', 'color' => 'warning'],
                                                                'Processing' => ['icon' => 'bx-loader-circle', 'color' => 'primary'],
                                                                'Completed' => ['icon' => 'bx-check-circle', 'color' => 'success'],
                                                                'Delivery' => ['icon' => 'bx-package', 'color' => 'info'],
                                                                'POD' => ['icon' => 'bx-receipt', 'color' => 'success'],
                                                                'Cancelled' => ['icon' => 'bx-x-circle', 'color' => 'danger'],
                                                                'Returned' => ['icon' => 'bx-undo', 'color' => 'warning']
                                                            ];
                                                            $currentStatus = $statusConfig[$history->new_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary'];
                                                            $previousStatus = $history->previous_status ? ($statusConfig[$history->previous_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary']) : null;
                                                        @endphp

                                                        <div class="d-flex align-items-start mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }} position-relative">
                                                            <div class="me-3 position-relative">
                                                                <div class="bg-{{ $currentStatus['color'] }} text-white rounded-circle d-flex align-items-center justify-content-center timeline-status-circle" style="width: 28px; height: 28px; font-size: 12px; position: relative; z-index: 2;">
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
                                                                    <div class="timeline-connector" style="position: absolute; top: 28px; left: 50%; transform: translateX(-50%); width: 2px; height: 40px; background: linear-gradient(180deg, {{ $connectorColor }} 0%, #e9ecef 100%); z-index: 1;"></div>
                                                                @endif
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                                    <div>
                                                                        @if($history->previous_status)
                                                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                                                <span class="badge bg-{{ $previousStatus['color'] }} text-white small">
                                                                                    <i class="bx {{ $previousStatus['icon'] }} me-1"></i>{{ $history->previous_status }}
                                                                                </span>
                                                                                <i class="bx bx-right-arrow-alt text-muted" style="font-size: 12px;"></i>
                                                                                <span class="badge bg-{{ $currentStatus['color'] }} text-white small">
                                                                                    <i class="bx {{ $currentStatus['icon'] }} me-1"></i>{{ $history->new_status }}
                                                                                </span>
                                                                            </div>
                                                                        @else
                                                                            <span class="badge bg-{{ $currentStatus['color'] }} text-white small">
                                                                                <i class="bx {{ $currentStatus['icon'] }} me-1"></i>{{ $history->new_status }} <small>(Initial)</small>
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <small class="text-primary fw-semibold">{{ $history->changed_at->format('M d, H:i') }}</small>
                                                                        <small class="text-muted d-block">{{ $history->changed_at->diffForHumans() }}</small>
                                                                    </div>
                                                                </div>

                                                                @if($history->notes)
                                                                    <div class="bg-light rounded p-2 mb-2">
                                                                        <small><i class="bx bx-note text-primary me-1"></i><strong>Notes:</strong> {{ $history->notes }}</small>
                                                                    </div>
                                                                @endif

                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted">
                                                                        <i class="bx bx-user text-danger me-1"></i>
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
                                                                    </small>

                                                                    @if($history->proof_image)
                                                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#proofImageModal{{ $history->id }}">
                                                                            <i class="bx bx-image me-1"></i>View Proof
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Compact Proof Image Modal --}}
                                                        @if($history->proof_image)
                                                        <div class="modal fade" id="proofImageModal{{ $history->id }}" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h6 class="modal-title">{{ $history->new_status }} Proof Image</h6>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body text-center">
                                                                        <img src="{{ asset('storage/' . $history->proof_image) }}"
                                                                             alt="{{ $history->new_status }} Proof"
                                                                             class="img-fluid rounded">

                                                                        @if($history->notes)
                                                                            <div class="mt-3 p-3 bg-light rounded">
                                                                                <strong>Notes:</strong>
                                                                                <p class="mb-0">{{ $history->notes }}</p>
                                                                            </div>
                                                                        @endif

                                                                        <div class="mt-3 text-muted">
                                                                            <small>
                                                                                Changed by:
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
                                                                                 | {{ $history->changed_at->format('M d, Y H:i') }}
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <a href="{{ asset('storage/' . $history->proof_image) }}" download class="btn btn-primary btn-sm">
                                                                            <i class="bx bx-download me-1"></i>Download
                                                                        </a>
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <div class="text-center py-5" id="noStatusHistoryMessage">
                                                        <i class="bx bx-history text-muted" style="font-size: 3rem;"></i>
                                                        <p class="text-muted mt-3">No status history available yet.</p>
                                                        <p class="text-muted small">Status changes will be recorded here.</p>
                                                    </div>
                                                @endif
                                            </div>
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
        @include("modals.item.batch-tracking-sale")
        @include("modals.party.create")
        @include("modals.item.create")
        @include("modals.sale.order.load-sold-items")

        @endsection

@section('js')
<script type="text/javascript">
        const itemsTableRecords = @json($itemTransactionsJson);
        const taxList = JSON.parse('{!! $taxList !!}');
</script>
<script src="{{ versionedAsset('custom/js/payment-types/payment-type-select2-ajax.js') }}"></script>
<script src="{{ versionedAsset('custom/js/sale/sale-order.js') }}"></script>
<script src="{{ versionedAsset('custom/js/sale/sale-order-status-manager.js') }}"></script>
<script src="{{ versionedAsset('custom/js/currency-exchange.js') }}"></script>
<script src="{{ versionedAsset('custom/js/items/serial-tracking.js') }}"></script>
<script src="{{ versionedAsset('custom/js/items/serial-tracking-settings.js') }}"></script>
<script src="{{ versionedAsset('custom/js/items/batch-tracking-sale.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/payment-type/payment-type.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/party/party.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/item/item.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/sale/order/load-sold-items.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/sale/order/load-sold-items.js') }}"></script>

<script>
// Timeline Animation
$(document).ready(function() {
    // Animate timeline connectors on page load
    setTimeout(function() {
        $('.timeline-connector').addClass('animate');
    }, 500);

    // Add hover effects to timeline items
    $('.timeline-status-circle').hover(
        function() {
            $(this).closest('.position-relative').find('.timeline-connector').css('opacity', '1');
        },
        function() {
            $(this).closest('.position-relative').find('.timeline-connector').css('opacity', '0.6');
        }
    );

    // Automatically load status history on page load
    const orderId = $('.view-status-history').data('order-id');
    if (orderId) {
        // Show loading indicator
        $('#statusHistoryContent').html('<div class="text-center py-5"><i class="bx bx-loader-alt bx-spin text-primary" style="font-size: 2rem;"></i><p class="mt-2">Loading status history...</p></div>');

        // Fetch status history via AJAX
        $.ajax({
            url: `/sale/order/status-history/${orderId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatusHistorySection(response.data);
                } else {
                    $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + (response.message || 'Unknown error') + '</p></div>');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while fetching status history.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + errorMessage + '</p></div>');
            }
        });
    }

    // Status update functionality
    const proofRequiredStatuses = ['POD', 'Cancelled', 'Returned'];

    // Show/hide proof image section based on selected status
    $('#order_status').on('change', function() {
        const selectedStatus = $(this).val();
        if (proofRequiredStatuses.includes(selectedStatus)) {
            // For statuses requiring proof, we'll show a modal
            if (selectedStatus === 'POD' || selectedStatus === 'Cancelled' || selectedStatus === 'Returned') {
                showStatusUpdateModal($(this).data('order-id'), selectedStatus);
            }
        }
    });

    // Show status update modal for proof-required statuses
    function showStatusUpdateModal(orderId, status) {
        const modal = `
            <div class="modal fade" id="statusUpdateModal" tabindex="-1">
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

    // Status update form submission
    // $(document).on('submit', '.status-update-form', function(e) {
    //     e.preventDefault();
    //     const orderId = $(this).data('sale_order_id');
    //     const status = $(this).data('status');
    //     const formData = new FormData();
    //
    //     console.log(orderId);
    //
    //     const csrfToken = $('meta[name="csrf-token"]').attr('content');
    //     formData.append('order_id', orderId);
    //     formData.append('status', status);
    //     formData.append('_token', csrfToken);
    //
    //     // Show loading state
    //     $('.status-update-form button[type="submit"]').prop('disabled', true).html(
    //         '<span class="spinner-border spinner-border-sm me-2"></span>Updating...'
    //     );
    //
    //     $.ajax({
    //         url: '/sale/order/update-status',
    //         method: 'POST',
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    //         headers: {
    //             'X-CSRF-TOKEN': csrfToken
    //         },
    //         success: function(response) {
    //             if (response.success) {
    //                 iziToast.success({
    //                     title: 'Success',
    //                     message: response.message,
    //                     position: 'topRight'
    //                 });
    //                 $('#statusUpdateModal').modal('hide');
    //                 // Reload page to show updated status
    //                 setTimeout(() => {
    //                     location.reload();
    //                 }, 1500);
    //             } else {
    //                 iziToast.error({
    //                     title: 'Error',
    //                     message: response.message,
    //                     position: 'topRight'
    //                 });
    //             }
    //         },
    //         error: function(xhr) {
    //             let errorMessage = 'An error occurred while updating the status.';
    //             if (xhr.responseJSON && xhr.responseJSON.message) {
    //                 errorMessage = xhr.responseJSON.message;
    //             }
    //             iziToast.error({
    //                 title: 'Error',
    //                 message: errorMessage,
    //                 position: 'topRight'
    //             });
    //         },
    //         complete: function() {
    //             $('.status-update-form button[type="submit"]').prop('disabled', false).html('Update Status');
    //         }
    //     });
    // });

    // Status history button click handler (kept for backward compatibility)
    $(document).on('click', '.view-status-history', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');

        // Show loading indicator
        $('#statusHistoryContent').html('<div class="text-center py-5"><i class="bx bx-loader-alt bx-spin text-primary" style="font-size: 2rem;"></i><p class="mt-2">Loading status history...</p></div>');

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

        // Fetch status history via AJAX
        $.ajax({
            url: `/sale/order/status-history/${orderId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatusHistorySection(response.data);
                } else {
                    $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + (response.message || 'Unknown error') + '</p></div>');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while fetching status history.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#statusHistoryContent').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p>Error loading status history: ' + errorMessage + '</p></div>');
            }
        });
    });

    // Update the status history section with new data
    function updateStatusHistorySection(history) {
        // Update the count badge
        $('#statusHistoryCount').text(history.length + ' changes');

        let historyHtml = '';

        if (history.length === 0) {
            historyHtml = `
                <div class="text-center py-5" id="noStatusHistoryMessage">
                    <i class="bx bx-history text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No status history available yet.</p>
                    <p class="text-muted small">Status changes will be recorded here.</p>
                </div>
            `;
        } else {
            // Sort history by changed_at descending (newest first)
            history.sort((a, b) => new Date(b.changed_at) - new Date(a.changed_at));

            history.forEach((item, index) => {
                // Status configuration for icons and colors
                const statusConfig = {
                    'Pending': {'icon': 'bx-time-five', 'color': 'warning'},
                    'Processing': {'icon': 'bx-loader-circle', 'color': 'primary'},
                    'Completed': {'icon': 'bx-check-circle', 'color': 'success'},
                    'Delivery': {'icon': 'bx-package', 'color': 'info'},
                    'POD': {'icon': 'bx-receipt', 'color': 'success'},
                    'Cancelled': {'icon': 'bx-x-circle', 'color': 'danger'},
                    'Returned': {'icon': 'bx-undo', 'color': 'warning'}
                };

                const currentStatus = statusConfig[item.new_status] || {'icon': 'bx-circle', 'color': 'secondary'};
                const previousStatus = item.previous_status ? (statusConfig[item.previous_status] || {'icon': 'bx-circle', 'color': 'secondary'}) : null;

                // Determine connector color
                let connectorColor = '#6c757d'; // default gray
                if (currentStatus.color === 'warning') connectorColor = '#ffc107';
                else if (currentStatus.color === 'primary') connectorColor = '#0d6efd';
                else if (currentStatus.color === 'success') connectorColor = '#198754';
                else if (currentStatus.color === 'info') connectorColor = '#0dcaf0';
                else if (currentStatus.color === 'danger') connectorColor = '#dc3545';

                historyHtml += `
                    <div class="d-flex align-items-start mb-3 pb-3 ${index !== history.length - 1 ? 'border-bottom' : ''} position-relative">
                        <div class="me-3 position-relative">
                            <div class="bg-${currentStatus.color} text-white rounded-circle d-flex align-items-center justify-content-center timeline-status-circle" style="width: 28px; height: 28px; font-size: 12px; position: relative; z-index: 2;">
                                <i class="bx ${currentStatus.icon}"></i>
                            </div>
                            ${index !== history.length - 1 ? `
                                <div class="timeline-connector" style="position: absolute; top: 28px; left: 50%; transform: translateX(-50%); width: 2px; height: 40px; background: linear-gradient(180deg, ${connectorColor} 0%, #e9ecef 100%); z-index: 1;"></div>
                            ` : ''}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    ${item.previous_status ? `
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge bg-${previousStatus.color} text-white small">
                                                <i class="bx ${previousStatus.icon} me-1"></i>${item.previous_status}
                                            </span>
                                            <i class="bx bx-right-arrow-alt text-muted" style="font-size: 12px;"></i>
                                            <span class="badge bg-${currentStatus.color} text-white small">
                                                <i class="bx ${currentStatus.icon} me-1"></i>${item.new_status}
                                            </span>
                                        </div>
                                    ` : `
                                        <span class="badge bg-${currentStatus.color} text-white small">
                                            <i class="bx ${currentStatus.icon} me-1"></i>${item.new_status} <small>(Initial)</small>
                                        </span>
                                    `}
                                </div>
                                <div class="text-end">
                                    <small class="text-primary fw-semibold">${new Date(item.changed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${new Date(item.changed_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small>
                                    <small class="text-muted d-block">${timeSince(new Date(item.changed_at))}</small>
                                </div>
                            </div>

                            ${item.notes ? `
                                <div class="bg-light rounded p-2 mb-2">
                                    <small><i class="bx bx-note text-primary me-1"></i><strong>Notes:</strong> ${item.notes}</small>
                                </div>
                            ` : ''}

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bx bx-user text-danger me-1"></i>
                                    ${item.changed_by?.name || item.changed_by_name || 'Unknown User'}
                                </small>

                                ${item.proof_image ? `
                                    <a href="/storage/${item.proof_image}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-image me-1"></i>View Proof
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        $('#statusHistoryContent').html(historyHtml);

        // Re-animate timeline connectors
        setTimeout(function() {
            $('.timeline-connector').addClass('animate');
        }, 100);
    }

    // Helper function to calculate time since
    function timeSince(date) {
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

    // Handle collapse events for status history section
    $('#statusHistoryCollapse').on('hide.bs.collapse', function () {
        $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-up').addClass('bx-chevron-down');
    });

    $('#statusHistoryCollapse').on('show.bs.collapse', function () {
        $('[data-bs-target="#statusHistoryCollapse"]').find('i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
    });

    $('#manualInventoryDeduction').on('click', function() {
        const orderId = $(this).data('order-id');
        const button = $(this);

        if (confirm('{{ __('Are you sure you want to deduct inventory for this order?') }}')) {
            button.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> {{ __('Processing...') }}');

            $.ajax({
                url: `{{ route('sale.order.manual.inventory.deduction', '') }}/${orderId}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        iziToast.success({
                            title: 'Success',
                            message: response.message,
                            position: 'topRight'
                        });
                        // Reload page to show updated status
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: response.message,
                            position: 'topRight'
                        });
                        button.prop('disabled', false).html('<i class="bx bx-package"></i> {{ __('Manual Inventory Deduction') }}');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || '{{ __('An error occurred') }}';
                    iziToast.error({
                        title: 'Error',
                        message: errorMessage,
                        position: 'topRight'
                    });
                    button.prop('disabled', false).html('<i class="bx bx-package"></i> {{ __('Manual Inventory Deduction') }}');
                }
            });
        }
    });
});
</script>

<style>
/* Compact Status History Styles */
.status-history-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-history-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.timeline-item {
    transition: all 0.2s ease;
}

.timeline-item:hover {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 8px;
    margin: -8px;
}

.status-icon {
    font-size: 12px;
    flex-shrink: 0;
}

/* Timeline Connector Styles */
.timeline-connector {
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.timeline-connector:hover {
    opacity: 1;
}

/* Enhanced Timeline Connector with Animation */
.timeline-connector::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 0;
    background: inherit;
    transition: height 0.5s ease;
}

.timeline-connector.animate::before {
    height: 100%;
}

/* Status Circle Hover Effect */
.timeline-status-circle {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-status-circle:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .timeline-item {
        font-size: 14px;
    }

    .status-icon {
        width: 24px !important;
        height: 24px !important;
        font-size: 11px;
    }

    .badge {
        font-size: 10px;
    }

    .timeline-connector {
        height: 30px !important;
    }

    .timeline-status-circle {
        width: 24px !important;
        height: 24px !important;
    }
}

/* Remove old complex styles */
.enhanced-timeline-wrapper,
.timeline-progress-bar,
.timeline-progress-fill,
.enhanced-timeline-item,
.timeline-marker-container,
.timeline-marker-outer,
.timeline-marker {
    display: none !important;
}
</style>
@endsection
