$(function() {
    "use strict";

    // تهيئة Select2 للمستودعات
    initSelect2WarehouseList($('body'));

    // تهيئة Select2 للعناصر
    initSelect2ItemList($('body'));

    // تهيئة Select2 للعلامات التجارية
    initSelect2BrandList($('body'));

    // تهيئة جداول البيانات
    let incomingTable = $('#incoming_items_table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="fadeIn animated bx bx-copy"></i> نسخ',
                titleAttr: 'نسخ'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fadeIn animated bx bx-file"></i> إكسل',
                titleAttr: 'إكسل'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fadeIn animated bx bx-file"></i> CSV',
                titleAttr: 'CSV'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fadeIn animated bx bx-file-pdf"></i> PDF',
                titleAttr: 'PDF'
            },
            {
                extend: 'print',
                text: '<i class="fadeIn animated bx bx-printer"></i> طباعة',
                titleAttr: 'طباعة'
            }
        ],
        language: {
            url: baseURL + '/app/datatable-lang'
        }
    });

    let outgoingTable = $('#outgoing_items_table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="fadeIn animated bx bx-copy"></i> نسخ',
                titleAttr: 'نسخ'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fadeIn animated bx bx-file"></i> إكسل',
                titleAttr: 'إكسل'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fadeIn animated bx bx-file"></i> CSV',
                titleAttr: 'CSV'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fadeIn animated bx bx-file-pdf"></i> PDF',
                titleAttr: 'PDF'
            },
            {
                extend: 'print',
                text: '<i class="fadeIn animated bx bx-printer"></i> طباعة',
                titleAttr: 'طباعة'
            }
        ],
        language: {
            url: baseURL + '/app/datatable-lang'
        }
    });

    // تحميل البيانات عند النقر على زر التحميل
    $('#load_data').on('click', function() {
        loadIncomingItems();
        loadOutgoingItems();
    });

    // تحميل العناصر الداخلة
    function loadIncomingItems() {
        showSpinner();

        let warehouseId = $('#warehouse_id').val();
        let itemId = $('#item_id').val();
        let brandId = $('#brand_id').val();

        $.ajax({
            url: baseURL + '/reports/stock-movement/incoming-items',
            type: 'GET',
            data: {
                warehouse_id: warehouseId,
                item_id: itemId,
                brand_id: brandId
            },
            success: function(response) {
                if (response.status) {
                    // مسح البيانات الحالية
                    incomingTable.clear();

                    // إضافة البيانات الجديدة
                    $.each(response.data, function(index, item) {
                        incomingTable.row.add([
                            item.transaction_date,
                            item.transaction_time,
                            item.transaction_type,
                            item.invoice_or_bill_code,
                            item.party_name,
                            item.warehouse,
                            item.item_name,
                            item.brand_name,
                            item.quantity
                        ]);
                    });

                    // تحديث الجدول
                    incomingTable.draw();

                    iziToast.success({
                        title: 'نجاح',
                        message: response.message,
                        position: 'topRight'
                    });
                }
            },
            error: function(xhr) {
                let response = xhr.responseJSON;
                iziToast.error({
                    title: 'خطأ',
                    message: response.message,
                    position: 'topRight'
                });

                // مسح البيانات الحالية
                incomingTable.clear().draw();
            },
            complete: function() {
                hideSpinner();
            }
        });
    }

    // تحميل العناصر الخارجة
    function loadOutgoingItems() {
        showSpinner();

        let warehouseId = $('#warehouse_id').val();
        let itemId = $('#item_id').val();
        let brandId = $('#brand_id').val();

        $.ajax({
            url: baseURL + '/reports/stock-movement/outgoing-items',
            type: 'GET',
            data: {
                warehouse_id: warehouseId,
                item_id: itemId,
                brand_id: brandId
            },
            success: function(response) {
                if (response.status) {
                    // مسح البيانات الحالية
                    outgoingTable.clear();

                    // إضافة البيانات الجديدة
                    $.each(response.data, function(index, item) {
                        outgoingTable.row.add([
                            item.transaction_date,
                            item.transaction_time,
                            item.transaction_type,
                            item.invoice_or_bill_code,
                            item.party_name,
                            item.warehouse,
                            item.item_name,
                            item.brand_name,
                            item.quantity
                        ]);
                    });

                    // تحديث الجدول
                    outgoingTable.draw();

                    iziToast.success({
                        title: 'نجاح',
                        message: response.message,
                        position: 'topRight'
                    });
                }
            },
            error: function(xhr) {
                let response = xhr.responseJSON;
                iziToast.error({
                    title: 'خطأ',
                    message: response.message,
                    position: 'topRight'
                });

                // مسح البيانات الحالية
                outgoingTable.clear().draw();
            },
            complete: function() {
                hideSpinner();
            }
        });
    }

    // إظهار مؤشر التحميل
    function showSpinner() {
        if ($('.loading-container').length === 0) {
            $('body').append('<div class="loading-container"><div class="loading-spinner"></div></div>');
        }
        $('.loading-container').show();
    }

    // إخفاء مؤشر التحميل
    function hideSpinner() {
        $('.loading-container').hide();
    }
});