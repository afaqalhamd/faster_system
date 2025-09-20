@extends('layouts.app')
@section('title', __('purchase.order.list'))

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection
        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                    <x-breadcrumb :langArray="[
                                            'purchase.order.order',
                                            'purchase.order.list',
                                        ]"/>

                    <div class="card">

                    <div class="card-header px-4 py-3 d-flex justify-content-between">
                        <!-- Other content on the left side -->
                        <div>
                            <h5 class="mb-0 text-uppercase">{{ __('purchase.order.list') }}</h5>
                        </div>
                        <div class="d-flex gap-2">
                            @can('purchase.order.import')
                            <a href="{{ route('import.purchase') }}" class="btn btn-outline-primary px-5"><i class="bx bx-import me-1"></i> {{ __("app.import_purch") }}</a>
                            @endcan
                            @can('purchase.order.create')
                            <!-- Button pushed to the right side -->
                           <x-anchor-tag href="{{ route('purchase.order.create') }}" text="{{ __('purchase.order.create') }}" class="btn btn-primary px-5" />
                            @endcan

                        </div>

                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <x-label for="party_id" name="{{ __('supplier.suppliers') }}" />

                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email"><i class="fadeIn animated bx bx-info-circle"></i></a>

                                <select class="party-ajax form-select" data-party-type='supplier' data-placeholder="Select Supplier" id="party_id" name="party_id"></select>
                            </div>
                            <div class="col-md-3">
                                <x-label for="user_id" name="{{ __('user.user') }}" />
                                <x-dropdown-user selected="" :showOnlyUsername='true' :canViewAllUsers="auth()->user()->can('purchase.order.can.view.other.users.purchase.orders')" />
                            </div>
                            <div class="col-md-3">
                                <x-label for="from_date" name="{{ __('app.from_date') }}" />
                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Order Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                                <div class="input-group mb-3">
                                    <x-input type="text" additionalClasses="datepicker-edit" name="from_date" :required="true" value=""/>
                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <x-label for="to_date" name="{{ __('app.to_date') }}" />
                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Order Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                                <div class="input-group mb-3">
                                    <x-input type="text" additionalClasses="datepicker-edit" name="to_date" :required="true" value=""/>
                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                </div>
                            </div>
                        </div>
                        <form class="row g-3 needs-validation" id="datatableForm" action="{{ route('purchase.order.delete') }}" enctype="multipart/form-data">
                            {{-- CSRF Protection --}}
                            @csrf
                            @method('POST')
                            <input type="hidden" id="base_url" value="{{ url('/') }}">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered border w-100" id="datatable">
                                    <thead>
                                        <tr>
                                            <th class="d-none"><!-- Which Stores ID & it is used for sorting --></th>
                                            <th><input class="form-check-input row-select" type="checkbox"></th>
                                            <th>{{ __('purchase.order.code') }}</th>
                                            <th>{{ __('app.date') }}</th>
                                            <th>{{ __('app.due_date') }}</th>
                                            <th>{{ __('supplier.supplier') }}</th>
                                            <th>{{ __('app.total') }}</th>
                                            <th>{{ __('payment.balance') }}</th>
                                            <th>{{ __('payment.payment_status') }}</th>
                                            <th>{{ __('purchase.stock_status') }}</th>
                                            <th>{{ __('purchase.purchase_status') }}</th>
                                            <th>{{ __('carrier.carrier') }}</th>
                                            <th>{{ __('app.created_by') }}</th>
                                            <th>{{ __('app.created_at') }}</th>
                                            <th>{{ __('app.action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
                    </div>
                </div>
                <!--end row-->
            </div>
        </div>

        @include("modals.email.send")
        @include("modals.sms.send")
        @include("modals.status-history.view", ['history_of' => 'purchase-order' ])

        @endsection
@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
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
        'purchase.returned': "{{ __('purchase.returned') }}",
        'purchase.inventory_pending': "{{ __('purchase.inventory_pending') }}",
        'purchase.inventory_added': "{{ __('purchase.inventory_added') }}",
        'purchase.inventory_removed': "{{ __('purchase.inventory_removed') }}",
        'purchase.post_receipt_return': "{{ __('purchase.post_receipt_return') }}",
        'purchase.post_receipt_cancel': "{{ __('purchase.post_receipt_cancel') }}",
        'purchase.inventory_ready_for_addition': "{{ __('purchase.inventory_ready_for_addition') }}",
        'purchase.inventory_post_receipt_action': "{{ __('purchase.inventory_post_receipt_action') }}"
    });
</script>
<script src="{{ versionedAsset('custom/js/purchase/purchase-order-list.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/email/send.js') }}"></script>
<script src="{{ versionedAsset('custom/js/sms/sms.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/status-history/status-history.js') }}"></script>
@endsection
