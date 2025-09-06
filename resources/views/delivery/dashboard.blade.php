@extends('layouts.app')
@section('title', __('delivery.dashboard'))

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'delivery.delivery',
            'delivery.dashboard',
        ]"/>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">{{ __('delivery.pending_deliveries') }}</p>
                                <h4 class="my-1 text-primary" id="pendingCount">0</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto">
                                <i class='bx bx-package'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">{{ __('delivery.completed_deliveries') }}</p>
                                <h4 class="my-1 text-success" id="completedCount">0</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto">
                                <i class='bx bx-check-circle'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <div>
                    <h5 class="mb-0 text-uppercase">{{ __('delivery.pending_deliveries') }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('delivery.pending') }}" class="btn btn-outline-primary px-5">
                        <i class="bx bx-list-ul me-1"></i> {{ __("delivery.view_all") }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered border w-100" id="pendingDatatable">
                        <thead>
                            <tr>
                                <th>{{ __('sale.order.code') }}</th>
                                <th>{{ __('app.date') }}</th>
                                <th>{{ __('customer.customer') }}</th>
                                <th>{{ __('app.total') }}</th>
                                <th>{{ __('app.status') }}</th>
                                <th>{{ __('app.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <div>
                    <h5 class="mb-0 text-uppercase">{{ __('delivery.recently_completed') }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('delivery.completed') }}" class="btn btn-outline-success px-5">
                        <i class="bx bx-list-ul me-1"></i> {{ __("delivery.view_all") }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered border w-100" id="completedDatatable">
                        <thead>
                            <tr>
                                <th>{{ __('sale.order.code') }}</th>
                                <th>{{ __('app.date') }}</th>
                                <th>{{ __('customer.customer') }}</th>
                                <th>{{ __('app.total') }}</th>
                                <th>{{ __('delivery.delivered_at') }}</th>
                                <th>{{ __('delivery.delivered_by') }}</th>
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
<script src="{{ versionedAsset('custom/js/delivery/dashboard.js') }}"></script>
@endsection