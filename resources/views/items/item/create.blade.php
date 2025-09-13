@extends('layouts.app')
@section('title', __('item.create'))

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
											'item.items',
											'item.list',
											'item.create',
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                              <h5 class="mb-0">{{ __('item.details') }}</h5>
                              <div class="btn-group">
                                <input type="radio" class="btn-check" name="item_type_radio" id="product" value="product" autocomplete="off" checked>
                                <label class="btn btn-outline-primary btn-sm" for="product">{{ __('item.product') }}</label>
                                {{-- <input type="radio" class="btn-check" name="item_type_radio" id="service" value="service" autocomplete="off" >
                                <label class="btn btn-outline-primary btn-sm" for="service">{{ __('service.service') }}</label> --}}
                              </div>
                            </div>
                            <div class="card-body p-4">
                                <form class="row g-3 needs-validation" id="itemForm" action="{{ route('item.store') }}" enctype="multipart/form-data">
                                    {{-- CSRF Protection --}}
                                    @csrf
                                    @method('POST')

                                    {{-- Units Modal --}}
                                    @include("modals.unit.create")

                                    <input type="hidden" id="operation" name="operation" value="save">
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">
                                    <input type="hidden" name="serial_number_json" value=''>
                                    <input type="hidden" name="batch_details_json" value=''>
                                    <input type="hidden" name="is_service" value='0'>

                                    @if(app('company')['show_sku'])
                                    <div class="col-md-4">
                                        <x-label for="sku" name="{{ __('item.sku') }}" />
                                        <x-input type="text" name="sku" :required="true" value=""/>
                                    </div>
                                    @endif
                                    <div class="col-md-4">
                                        <x-label for="name" name="{{ __('app.name') }}" />
                                        <x-input type="text" name="name" :required="true" value=""/>
                                    </div>
                                    @if(app('company')['show_hsn'])
                                    <div class="col-md-4">
                                        <x-label for="hsn" name="{{ __('item.hsn') }}" />
                                        <x-input type="text" name="hsn" :required="false" value=""/>
                                    </div>
                                    @endif

                                    <div class="col-md-4">
                                        <x-label for="asin" name="{{ __('item.asin') }}" />
                                        <x-input type="text" name="asin" :required="false" value=""/>
                                    </div>

                                    <div class="col-md-4">
                                        <x-label for="brand_id" name="{{ __('item.brand.brand') }}" />
                                        <div class="input-group">
                                            <x-dropdown-brand selected="" :showSelectOptionAll=true />
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#brandModal"><i class="bx bx-plus-circle me-0"></i>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <x-label for="hsn" name="{{ __('item.code') }}" />
                                        <div class="input-group mb-3">
                                            <x-input type="text" name="item_code" :required="true" value="{{ $data['count_id'] }}"/>
                                            <button class="btn btn-outline-secondary auto-generate-code" type="button" id="button-addon2">{{ __('app.auto') }}</button>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <x-label for="item_category_id" name="{{ __('item.category.category') }}" />
                                        <div class="input-group">
                                            <x-dropdown-item-category selected="" :isMultiple=false />
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemCategoryModal"><i class="bx bx-plus-circle me-0"></i>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <x-label for="description" name="{{ __('app.description') }}" />
                                        <x-textarea name="description" value=""/>
                                    </div>

                                    <div class="col-md-4 p-4 item-type-product">
                                        <button type="button" class="btn btn-light px-5 rounded-0" data-bs-toggle="modal" data-bs-target="#unitModal">{{ __('unit.select_unit') }}</button>
                                        <label class="primary unit-label"></label>
                                    </div>

<!-- New Custom Details Section -->
<div class="col-12">
    <h6 class="mb-3 text-uppercase">{{ __('item.custom_details') }}</h6>
    <div class="row">
        <div class="col-md-4">
            <x-label for="cust_num" name="{{ __('item.customer_number') }}" />
            <x-input type="number" name="cust_num" :required="false" value=""/>
        </div>
        <div class="col-md-4">
            <x-label for="cust_num_t" name="{{ __('item.customer_number_t') }}" />
            <x-input type="number" name="cust_num_t" :required="false" value=""/>
        </div>
        @if(app('company')['enable_size'])
        <div class="col-md-4">
            <x-label for="size" name="{{ __('item.size') }}" />
            <x-input type="text" name="size" :required="false" value=""/>
        </div>
        @endif
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_damaged" name="is_damaged" value="1">
                <label class="form-check-label" for="is_damaged">{{ __('item.is_damaged') }}</label>
            </div>
        </div>
    </div>

</div>

                                    <div class="col-md-4 d-none">
                                        <x-label for="status" name="{{ __('app.status') }}" />
                                        <x-dropdown-status selected="" dropdownName='status'/>
                                    </div>

                                    <div class="col-md-12 mb-3 item-type-product">
                                            <div class="d-flex align-items-center gap-3">

                                                <x-radio-block id="regular_tracking" boxName="tracking_type" text="{{ __('item.regular') }}" value="regular" boxType="radio" parentDivClass="fw-bold" :checked=true />

                                                @if(app('company')['enable_batch_tracking'])
                                                <x-radio-block id="batch_tracking" boxName="tracking_type" text="{{ __('item.batch_tracking') }}" value="batch" boxType="radio" parentDivClass="fw-bold"/>
                                                @endif

                                                @if(app('company')['enable_serial_tracking'])
                                                <x-radio-block id="serial_tracking" boxName="tracking_type" text="{{ __('item.serial_no_tracking') }}" value="serial" boxType="radio" parentDivClass="fw-bold"/>
                                                @endif

                                            </div>
                                    </div>

                                    <ul class="nav nav-tabs nav-success" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#successhome" role="tab" aria-selected="true">
                                                <div class="d-flex align-items-center">
                                                    <div class="tab-icon"><i class='bx bx-dollar font-18 me-1'></i>
                                                    </div>
                                                    <div class="tab-title">{{ __('item.pricing') }}</div>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="nav-item item-type-product" role="presentation">
                                            <a class="nav-link" data-bs-toggle="tab" href="#successprofile" role="tab" aria-selected="false">
                                                <div class="d-flex align-items-center">
                                                    <div class="tab-icon"><i class='bx bx-box font-18 me-1'></i>
                                                    </div>
                                                    <div class="tab-title">{{ __('item.stock') }}</div>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" data-bs-toggle="tab" href="#successcontact" role="tab" aria-selected="false">
                                                <div class="d-flex align-items-center">
                                                    <div class="tab-icon"><i class='bx bx-image-add font-18 me-1'></i>
                                                    </div>
                                                    <div class="tab-title">{{ __('app.image') }}</div>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content py-3">
                                        <div class="tab-pane fade show active" id="successhome" role="tabpanel">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <x-label for="purchase_price" name="{{ __('item.purchase_price') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" name="purchase_price" :required="false" additionalClasses='cu_numeric' value=""/>
                                                        <x-dropdown-general optionNaming="withOrWithoutTax" selected="" dropdownName='is_purchase_price_with_tax'/>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="tax_id" name="{{ __('tax.tax') }}" />
                                                    <div class="input-group">
                                                        <x-drop-down-taxes selected="" />
                                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#taxModal"><i class="bx bx-plus-circle me-0"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="purchase_price" name="{{ __('item.sale_profit_margin') }} (%)" />
                                                    <x-input type="text" name="profit_margin" :required="false" additionalClasses='cu_numeric' value=""/>
                                                </div>

                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <x-label for="sale_price" name="{{ __('item.sale_price') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" name="sale_price" :required="true" additionalClasses='cu_numeric' value=""/>
                                                        <x-dropdown-general optionNaming="withOrWithoutTax" selected="" dropdownName='is_sale_price_with_tax'/>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="wholesale_price" name="{{ __('item.wholesale_price') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" name="wholesale_price" :required="true" additionalClasses='cu_numeric' value="0"/>
                                                        <x-dropdown-general optionNaming="withOrWithoutTax" selected="" dropdownName='is_wholesale_price_with_tax'/>
                                                    </div>
                                                </div>
                                                {{-- Id company is enabled with discount then only show this else hide it --}}
                                                <div class="col-md-4 {{ app('company')['show_discount'] ? '' : 'd-none' }}">
                                                    <x-label for="discount_on_sale" name="{{ __('item.discount_on_sale') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" name="sale_price_discount" :required="false" additionalClasses='cu_numeric' value=""/>
                                                        <x-dropdown-general optionNaming="amountOrPercentage" selected="" dropdownName='sale_price_discount_type'/>
                                                    </div>
                                                </div>
                                                @if(app('company')['show_mrp'])
                                                <div class="col-md-2">
                                                    @php
                                                        $mrpToolTip = '<span data-bs-toggle="tooltip" data-bs-placement="top" title="' . __('item.maximum_retail_price') . '">
                                                                        <i class="fadeIn animated bx bx-info-circle text-primary"></i>
                                                                    </span>';
                                                    @endphp
                                                    <x-label for="mrp" name="{{ __('item.mrp') }} {!! $mrpToolTip !!}" />
                                                    <x-input type="text" name="mrp" :required="false" additionalClasses='cu_numeric' value=""/>
                                                </div>
                                                @endif
                                                <div class="col-md-2">
                                                    @php
                                                    $mspToolTip = '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                                title="' . __('item.minimum_selling_price') . ': <br>' . __('item.msp_message') . '">
                                                                <i class="fadeIn animated bx bx-info-circle text-primary"></i>
                                                            </span>';


                                                    @endphp
                                                    <x-label for="msp" name="{{ __('item.msp') }} {!! $mspToolTip !!}" />
                                                    <x-input type="text" name="msp" :required="false" additionalClasses='cu_numeric' value=""/>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="cargo_fee" name="{{ __('item.cargo_fee') }}" />
                                                    <x-input type="text" name="cargo_fee" :required="false" additionalClasses='cu_numeric' value="0.0"/>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="weight" name="{{ __('item.weight') }}" />
                                                    <x-input type="text" name="weight" :required="false" additionalClasses='cu_numeric' value="0.0"/>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="volume" name="{{ __('item.volume') }}" />
                                                    <x-input type="text" name="volume" :required="false" additionalClasses='cu_numeric' value="0.0"/>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="tab-pane fade" id="successprofile" role="tabpanel">

                                           <div class="row">
                                                <div class="col-md-4">
                                                    <x-label for="warehouse_id" name="{{ __('warehouse.warehouse') }}" />
                                                    <x-dropdown-warehouse selected="" dropdownName='warehouse_id'/>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="transaction_date" name="{{ __('app.as_of_date') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="datepicker" name="transaction_date" :required="true" value=""/>
                                                        <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                    </div>
                                                </div>

                                           </div>
                                           <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <x-label for="opening_quantity" name="{{ __('item.opening_quantity') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="cu_numeric" name="opening_quantity" :required="false" value=""/>

                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="at_price" name="{{ __('item.at_price') }}" />
                                                    <x-input type="text" additionalClasses="cu_numeric" name="at_price" :required="false" value=""/>
                                                </div>

                                           </div>


                                           <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <x-label for="min_stock" name="{{ __('item.min_stock') }}" />
                                                    <x-input type="text" additionalClasses="cu_numeric" name="min_stock" :required="false" value=""/>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="location" name="{{ __('item.item_location') }}" />
                                                    <x-input type="text" name="item_location" :required="false" value=""/>
                                                </div>
                                           </div>

                                        </div>
                                        <div class="tab-pane fade" id="successcontact" role="tabpanel">
                                            <!-- Single Main Image -->
                                            <div class="col-md-12 mb-4">
                                                <x-label for="picture" name="{{ __('app.image') }} ({{ __('app.main') }})" />
                                                <x-browse-image
                                                                src="{{ url('/noimage/') }}"
                                                                name='image'
                                                                imageid='uploaded-image-1'
                                                                inputBoxClass='input-box-class-1'
                                                                imageResetClass='image-reset-class-1'
                                                                :enableDragDrop="true"
                                                                />
                                            </div>

                                            <!-- Multiple Stock Images -->
                                            <div class="col-md-12 mb-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">{{ __('item.stock_images') }}</h6>
                                                        <small class="text-muted">{{ __('item.stock_images_desc') }}</small>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="stock-images-upload-area">
                                                            <input type="file"
                                                                   id="stock_images"
                                                                   name="stock_images[]"
                                                                   multiple
                                                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                                                   class="d-none">

                                                            <div class="upload-dropzone" onclick="document.getElementById('stock_images').click()">
                                                                <div class="text-center p-4">
                                                                    <i class="bx bx-cloud-upload display-4 text-primary"></i>
                                                                    <h6 class="mt-2">{{ __('item.click_to_upload_images') }}</h6>
                                                                    <p class="text-muted mb-0">{{ __('item.or_drag_drop_images') }}</p>
                                                                    <small class="text-muted">{{ __('item.supported_formats') }}: JPG, PNG, GIF, WEBP</small>
                                                                </div>
                                                            </div>

                                                            <div id="stock-images-preview" class="row mt-3" style="display: none;">
                                                                <!-- Preview images will be inserted here -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Image URL -->
                                            <div class="col-md-12 mt-3">
                                                <x-label for="image_url" name="{{ __('item.image_url') }}" />
                                                <x-input type="text" name="image_url" :required="false" value=""/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="d-md-flex d-grid align-items-center gap-3">
                                            <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                            <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                        </div>
                                    </div>
                                </form>

                            </div>

                        </div>
					</div>
				</div>
				<!--end row-->
			</div>
		</div>
        <!-- Import Modals -->
        @include("modals.tax.create")
        @include("modals.item.brand.create")
        @include("modals.item.category.create")
        @include("modals.item.serial-tracking")
        @include("modals.item.batch-tracking")

		@endsection

@section('js')
<script src="{{ versionedAsset('custom/js/items/item.js') }}"></script>
<script src="{{ versionedAsset('custom/js/items/serial-tracking.js') }}"></script>
<script src="{{ versionedAsset('custom/js/items/batch-tracking.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/tax/tax.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/item/brand/brand.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/item/category/category.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/unit/unit.js') }}"></script>

<script>
$(document).ready(function() {
    // Stock Images Upload Handler
    $('#stock_images').on('change', function() {
        handleStockImagesPreview(this.files);
    });

    // Drag and Drop functionality
    $('.upload-dropzone').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
    }).on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');

        const files = e.originalEvent.dataTransfer.files;
        document.getElementById('stock_images').files = files;
        handleStockImagesPreview(files);
    });

    function handleStockImagesPreview(files) {
        const previewContainer = $('#stock-images-preview');
        previewContainer.empty();

        if (files.length > 0) {
            previewContainer.show();

            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imageHtml = `
                            <div class="col-md-3 mb-3 stock-image-item" data-index="${index}">
                                <div class="card">
                                    <div class="card-body p-2">
                                        <img src="${e.target.result}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                        <div class="mt-2">
                                            <small class="text-muted">${file.name}</small>
                                            <button type="button" class="btn btn-danger btn-sm float-end remove-image" data-index="${index}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        previewContainer.append(imageHtml);
                    };
                    reader.readAsDataURL(file);
                }
            });
        } else {
            previewContainer.hide();
        }
    }

    // Remove image handler
    $(document).on('click', '.remove-image', function() {
        const index = $(this).data('index');
        const input = document.getElementById('stock_images');
        const dt = new DataTransfer();

        Array.from(input.files).forEach((file, i) => {
            if (i !== index) {
                dt.items.add(file);
            }
        });

        input.files = dt.files;
        $(this).closest('.stock-image-item').remove();

        if (input.files.length === 0) {
            $('#stock-images-preview').hide();
        }
    });
});
</script>

<style>
.upload-dropzone {
    border: 2px dashed #007bff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-dropzone:hover,
.upload-dropzone.drag-over {
    border-color: #0056b3;
    background-color: #f8f9ff;
}

.stock-image-item .card {
    transition: transform 0.2s ease;
}

.stock-image-item .card:hover {
    transform: translateY(-2px);
}
</style>
@endsection
