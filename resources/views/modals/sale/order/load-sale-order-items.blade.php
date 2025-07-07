<div class="modal fade" id="loadSaleOrderItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('sale.order.items') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('customer.customer') }}: <span id="party-name" class="text-primary"></span></h6>
                            </div>
                            <div class="d-flex gap-2">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <select class="form-select select2-item-ajax" id="modal_item_id" name="modal_item_id" data-placeholder="Select Item">
                                        </select>
                                        <button type="button" class="btn btn-outline-primary load-sale-order-items">{{ __('app.load') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 table-responsive">
                        <table class="table mb-0 table-striped table-bordered" id="payment-history-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th scope="col">{{ __('sale.order.code') }}</th>
                                    <th scope="col">{{ __('app.date') }}</th>
                                    <th scope="col">{{ __('warehouse.warehouse') }}</th>
                                    <th scope="col">{{ __('item.item') }}</th>
                                    <th scope="col" class="text-end">{{ __('item.unit_price') }}</th>
                                    <th scope="col" class="text-end">{{ __('item.quantity') }}</th>
                                    <th scope="col" class="text-end {{ !app('company')['show_discount'] ? 'd-none':'' }}">{{ __('app.discount') }}</th>
                                    <th scope="col" class="text-end {{ app('company')['tax_type'] == 'no-tax' ? 'd-none':'' }}">{{ __('app.tax') }}</th>
                                    <th scope="col" class="text-end">{{ __('app.total') }}</th>
                                    <th scope="col" class="d-none">{{ __('app.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="11" class="text-center">{{ __('app.no_data') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('app.close') }}</button>
            </div>
        </div>
    </div>
</div>