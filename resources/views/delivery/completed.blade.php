@extends('layouts.app')
@section('title', __('delivery.completed_deliveries'))

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'delivery.delivery',
            'delivery.completed_deliveries',
        ]"/>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <div>
                    <h5 class="mb-0 text-uppercase">{{ __('delivery.completed_deliveries') }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('delivery.dashboard') }}" class="btn btn-outline-primary px-5">
                        <i class="bx bx-arrow-back me-1"></i> {{ __("delivery.back_to_dashboard") }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-label for="party_id" name="{{ __('customer.customer') }}" />
                        <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email">
                            <i class="fadeIn animated bx bx-info-circle"></i>
                        </a>
                        <select class="party-ajax form-select" data-party-type='customer' data-placeholder="Select Customer" id="party_id" name="party_id"></select>
                    </div>
                    <div class="col-md-3">
                        <x-label for="from_date" name="{{ __('app.from_date') }}" />
                        <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Sale Date">
                            <i class="fadeIn animated bx bx-info-circle"></i>
                        </a>
                        <div class="input-group mb-3">
                            <x-input type="text" additionalClasses="datepicker-edit" name="from_date" :required="true" value=""/>
                            <span class="input-group-text" id="input-near-focus" role="button">
                                <i class="fadeIn animated bx bx-calendar-alt"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <x-label for="to_date" name="{{ __('app.to_date') }}" />
                        <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Sale Date">
                            <i class="fadeIn animated bx bx-info-circle"></i>
                        </a>
                        <div class="input-group mb-3">
                            <x-input type="text" additionalClasses="datepicker-edit" name="to_date" :required="true" value=""/>
                            <span class="input-group-text" id="input-near-focus" role="button">
                                <i class="fadeIn animated bx bx-calendar-alt"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered border w-100" id="datatable">
                        <thead>
                            <tr>
                                <th class="d-none"><!-- Which Stores ID & it is used for sorting --></th>
                                <th>{{ __('sale.order.code') }}</th>
                                <th>{{ __('app.date') }}</th>
                                <th>{{ __('customer.customer') }}</th>
                                <th>{{ __('app.total') }}</th>
                                <th>{{ __('payment.balance') }}</th>
                                <th>{{ __('app.status') }}</th>
                                <th>{{ __('delivery.delivered_at') }}</th>
                                <th>{{ __('delivery.delivered_by') }}</th>
                                <th>{{ __('app.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/delivery/completed.js') }}"></script>
@endsection