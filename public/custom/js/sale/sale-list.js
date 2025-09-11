$(function() {
	"use strict";

    const tableId = $('#datatable');

    const datatableForm = $("#datatableForm");

    /**
     *Server Side Datatable Records
    */
    window.loadDatatables = function() {
        //Delete previous data
        tableId.DataTable().destroy();

        var exportColumns = [2,3,4,5,6,7,8,9,10];//Index Starts from 0

        var table = tableId.DataTable({
            processing: true,
            serverSide: true,
            method:'get',
            ajax: {
                    url: baseURL+'/sale/invoice/datatable-list',
                    data:{
                            party_id : $('#party_id').val(),
                            user_id : $('#user_id').val(),
                            from_date : $('input[name="from_date"]').val(),
                            to_date : $('input[name="to_date"]').val(),
                            reference_no : $('#reference_no').val(), // Add reference_no parameter
                        },
                },
            // Enable regex search
            search: {
                regex: true,
                smart: false
            },
            columns: [
                {targets: 0, data:'id', orderable:true, visible:false},
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        return '<input type="checkbox" class="form-check-input row-select" name="record_ids[]" value="' + data + '">';
                      }
                },

                {
                    data: null, // Combine sale_code and status in this column
                    name: 'sale_code',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        let orderCode = data.sale_code || ''; // Default if sale_code is null
                        let statusBadge = '';

                        // Check if status is an object and extract data
                        let statusText = data.status?.text || ''; // Get text from status object
                        let statusCode = data.status?.code || ''; // Get sale_order or quotation code
                        let statusUrl = data.status?.url || '';

                        if (statusText === 'Converted from Sale Order') {
                            statusBadge = `<div class="badge text-primary bg-light-primary p-2 text-uppercase px-3">
                                            ${statusText} (<a href="${statusUrl}" target="_blank" data-bs-toggle="tooltip"
                                                              data-bs-placement="top" title="View Sale Order Details">
                                                              ${statusCode} <i class="fadeIn animated bx bx-link-external bx-tada-hover"></i>

                                                          </a>)
                                        </div>`;
                        } else if (statusText === 'Converted from Quotation') {
                            statusBadge = `<div class="badge bg-light-success text-success p-2 text-uppercase px-3">
                                            ${statusText} (<a href="${statusUrl}" target="_blank" data-bs-toggle="tooltip"
                                                              data-bs-placement="top" title="View Quotation Details">
                                                              ${statusCode} <i class="fadeIn animated bx bx-link-external bx-tada-hover"></i>

                                                          </a>)
                                        </div>`;
                        }

                        if (data.is_return_raised?.status === 'Return Raised') {
                            let returnLinks = data.is_return_raised.urls.map((url, index) =>
                                `<a href="${url}" target="_blank" data-bs-toggle="tooltip"
                                                              data-bs-placement="top" title="View Sale Return Details">
                                                              ${data.is_return_raised.codes.split(', ')[index]}
                                                              <i class="fadeIn animated bx bx-link-external bx-tada-hover"></i></a>`
                                                            ).join(', ');

                            statusBadge += `<div class="badge text-danger bg-light-danger text-uppercase">
                                                ${data.is_return_raised.status} (${returnLinks})
                                            </div>`;
                        }



                        // Combine order code and status badge
                        return `<div>
                                    <strong>${orderCode}</strong><br>
                                    ${statusBadge}
                                </div>`;
                    }
                },
                {
                    data: 'reference_no',
                    name: 'reference_no',
                    orderable: false,
                    className: 'text-center',
                    searchable: true
                },
                {data: 'sale_date', name: 'sale_date'},

                {data: 'party_name', name: 'party_name'},
                {data: 'grand_total', name: 'grand_total', className: 'text-end'},
                {data: 'balance', name: 'balance', className: 'text-end'},
                {
                    data: 'inventory_status',
                    name: 'inventory_status',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        // If data already contains HTML, return it as is
                        if (typeof data === 'string' && data.includes('<div class="badge')) {
                            return data;
                        }

                        // Otherwise, create the badge HTML based on inventory status
                        const statusMap = {
                            'deducted': { class: 'bg-light-success text-success', text: 'Inventory Deducted' },
                            'pending': { class: 'bg-light-warning text-warning', text: 'Pending' },
                            'ready_for_deduction': { class: 'bg-light-primary text-primary', text: 'Ready for Deduction' },
                            'Deducted': { class: 'bg-light-success text-success', text: 'Inventory Deducted' },
                            'Pending': { class: 'bg-light-warning text-warning', text: 'Pending' },
                            'Ready for Deduction': { class: 'bg-light-primary text-primary', text: 'Ready for Deduction' }
                            // Removed 'deducted_delivered' entry as it's handled dynamically below
                        };

                        // Check if we have a translation for this status
                        let displayText = data;
                        let statusClass = 'bg-light-secondary text-secondary'; // default
                        let statusIcon = ''; // default no icon

                        if (data === 'deducted') {
                            displayText = 'Inventory Deducted';
                            statusClass = 'bg-light-success text-success';
                        } else if (data === 'pending') {
                            displayText = 'Pending';
                            statusClass = 'bg-light-warning text-warning';
                        } else if (data === 'ready_for_deduction') {
                            displayText = 'Ready for Deduction';
                            statusClass = 'bg-light-primary text-primary';
                        } else if (data === 'deducted_delivered') {
                            // Check the post_delivery_action to determine the correct text
                            const postDeliveryAction = full.post_delivery_action ? full.post_delivery_action.toString().trim() : '';

                            if (postDeliveryAction === 'Cancelled') {
                                displayText = 'Post-Delivery Cancelled';
                                statusClass = 'bg-light-danger text-danger'; // Red for cancellation
                                statusIcon = 'bx-x-circle';
                            } else if (postDeliveryAction === 'Returned') {
                                displayText = 'Post-Delivery Return';
                                statusClass = 'bg-light-warning text-warning'; // Orange for return
                                statusIcon = 'bx-undo';
                            } else {
                                // Fallback - check sales_status as backup
                                const salesStatus = full.sales_status ? full.sales_status.toString().trim() : '';
                                if (salesStatus === 'Cancelled') {
                                    displayText = 'Post-Delivery Cancelled';
                                    statusClass = 'bg-light-danger text-danger'; // Red for cancellation
                                    statusIcon = 'bx-x-circle';
                                } else if (salesStatus === 'Returned') {
                                    displayText = 'Post-Delivery Return';
                                    statusClass = 'bg-light-warning text-warning'; // Orange for return
                                    statusIcon = 'bx-undo';
                                } else {
                                    // Final fallback - use a generic text
                                    displayText = 'Post-Delivery Action';
                                    statusClass = 'bg-light-secondary text-secondary';
                                    statusIcon = 'bx-help-circle';
                                }
                            }
                        }

                        const statusInfo = statusMap[data] || { class: statusClass, text: displayText };

                        // Create the badge with icon if available
                        const iconHtml = statusIcon ? `<i class="bx ${statusIcon} me-1"></i>` : '';
                        return `<div class="badge ${statusInfo.class} p-2 px-3">${iconHtml}${statusInfo.text}</div>`;
                    }
                },
                {
                    data: 'sales_status',
                    name: 'sales_status',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        // If data already contains HTML, return it as is
                        if (typeof data === 'string' && data.includes('<div class="badge')) {
                            return data;
                        }

                        // Otherwise, create the badge HTML based on getSaleStatus() method
                        const statusMap = {
                            'Pending': { class: 'bg-light-warning text-warning', text: 'Pending', icon: 'bx-time-five' },
                            'Processing': { class: 'bg-light-primary text-primary', text: 'Processing', icon: 'bx-loader-circle bx-spin' },
                            'Completed': { class: 'bg-light-success text-success', text: 'Completed', icon: 'bx-check-circle' },
                            'Delivery': { class: 'bg-light-info text-info', text: 'Delivery', icon: 'bx-package' },
                            'POD': { class: 'bg-light-success text-success', text: 'POD', icon: 'bx-receipt' },
                            'Cancelled': { class: 'bg-light-danger text-danger', text: 'Cancelled', icon: 'bx-x-circle' },
                            'Returned': { class: 'bg-light-warning text-warning', text: 'Returned', icon: 'bx-undo' },
                            'pending': { class: 'bg-light-warning text-warning', text: 'Pending', icon: 'bx-time-five' },
                            'processing': { class: 'bg-light-primary text-primary', text: 'Processing', icon: 'bx-loader-circle bx-spin' },
                            'completed': { class: 'bg-light-success text-success', text: 'Completed', icon: 'bx-check-circle' },
                            'delivery': { class: 'bg-light-info text-info', text: 'Delivery', icon: 'bx-package' },
                            'pod': { class: 'bg-light-success text-success', text: 'POD', icon: 'bx-receipt' },
                            'cancelled': { class: 'bg-light-danger text-danger', text: 'Cancelled', icon: 'bx-x-circle' },
                            'returned': { class: 'bg-light-warning text-warning', text: 'Returned', icon: 'bx-undo' }
                        };

                        // Try to get status info from the map
                        let statusInfo = statusMap[data];

                        // If not found, try to get translation from the service method
                        if (!statusInfo) {
                            // Default fallback
                            statusInfo = { class: 'bg-light-secondary text-secondary', text: data, icon: '' };
                        }

                        // Create the badge with icon if available
                        const iconHtml = statusInfo.icon ? `<i class="bx ${statusInfo.icon} me-1"></i>` : '';
                        return `<div class="badge ${statusInfo.class} p-2 px-3">${iconHtml}${statusInfo.text}</div>`;
                    }
                },
                {data: 'username', name: 'username'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],

            dom: "<'row' "+
                    "<'col-sm-12' "+
                        "<'float-start' l"+
                            /* card-body class - auto created here */
                        ">"+
                        "<'float-end' fr"+
                            /* card-body class - auto created here */
                        ">"+
                        "<'float-end ms-2'"+
                            "<'card-body ' B >"+
                        ">"+
                    ">"+
                  ">"+
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

            buttons: [
                {
                    className: 'btn btn-outline-danger buttons-copy buttons-html5 multi_delete',
                    text: 'Delete',
                    action: function ( e, dt, node, config ) {
                        //Confirm user then trigger submit event
                       requestDeleteRecords();
                    }
                },
                // Apply exportOptions only to Copy button
                {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to Excel button
                {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to CSV button
                {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                // Apply exportOptions only to PDF button
                {
                    extend: 'pdfHtml5',
                    orientation: 'portrait',//or "landscape"
                    exportOptions: {
                        columns: exportColumns,
                    },
                },

            ],

            select: {
                style: 'os',
                selector: 'td:first-child'
            },
            order: [[0, 'desc']],
            drawCallback: function() {
                /**
                 * Initialize Tooltip
                 * */
                setTooltip();
            },

            // Add initComplete for custom search functionality
            initComplete: function() {
                var api = this.api();

                // Custom search function for reference_no and sale_code
                $('#datatable_filter input').off('keyup search input').on('keyup search input', function() {
                    var searchTerms = $(this).val().trim();

                    if (searchTerms) {
                        // Split search terms by newline or space
                        var terms = searchTerms.split(/[\n\r\s]+/).filter(Boolean);

                        if (terms.length > 1) {
                            // Check if terms look like sale codes (starting with SL/)
                            if (terms.some(term => term.startsWith('SL/'))) {
                                // Create a server-side request with the sale_codes parameter
                                api.ajax.url(baseURL+'/sale/invoice/datatable-list?sale_codes=' +
                                    encodeURIComponent(JSON.stringify(terms))).load();
                            } else {
                                // Use reference_nos for other types of searches
                                api.ajax.url(baseURL+'/sale/invoice/datatable-list?reference_nos=' +
                                    encodeURIComponent(JSON.stringify(terms))).load();
                            }
                        } else {
                            api.search(searchTerms).draw();
                        }
                    } else {
                        api.search('').draw();
                        // Reset to original URL if search is cleared
                        api.ajax.url(baseURL+'/sale/invoice/datatable-list').load();
                    }
                });
            }
        });

        table.on('click', '.deleteRequest', function () {
              let deleteId = $(this).attr('data-delete-id');

              deleteRequest(deleteId);

        });

        //Adding Space on top & bottom of the table attributes
        $('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate').wrap("<div class='card-body py-3'>");
    }

    // Handle header checkbox click event
    tableId.find('thead').on('click', '.row-select', function() {
        var isChecked = $(this).prop('checked');
        tableId.find('tbody .row-select').prop('checked', isChecked);
    });

    /**
     * @return count
     * How many checkbox are checked
    */
   function countCheckedCheckbox(){
        var checkedCount = $('input[name="record_ids[]"]:checked').length;
        return checkedCount;
   }

   /**
    * Validate checkbox are checked
    */
   async function validateCheckedCheckbox(){
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (!confirmed) {
            return false;
        }
        if(countCheckedCheckbox() == 0){
            iziToast.error({title: 'Warning', layout: 2, message: "Please select at least one record to delete"});
            return false;
        }
        return true;
   }
    /**
     * Caller:
     * Function to single delete request
     * Call Delete Request
    */
    async function deleteRequest(id) {
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (confirmed) {
            deleteRecord(id);
        }
    }

    /**
     * Create Ajax Request:
     * Multiple Data Delete
    */
   async function requestDeleteRecords(){
        //validate checkbox count
        const confirmed = await confirmAction();//Defined in ./common/common.js
        if (confirmed) {
            //Submit delete records
            datatableForm.trigger('submit');
        }
   }
    datatableForm.on("submit", function(e) {
        e.preventDefault();

            //Form posting Functionality
            const form = $(this);
            const formArray = {
                formId: form.attr("id"),
                csrf: form.find('input[name="_token"]').val(),
                _method: form.find('input[name="_method"]').val(),
                url: form.closest('form').attr('action'),
                formObject : form,
                formData : new FormData(document.getElementById(form.attr("id"))),
            };
            ajaxRequest(formArray); //Defined in ./common/common.js

    });

    /**
     * Create AjaxRequest:
     * Single Data Delete
    */
    function deleteRecord(id){
        const form = datatableForm;
        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            _method: form.find('input[name="_method"]').val(),
            url: form.closest('form').attr('action'),
            formObject : form,
            formData: new FormData() // Create a new FormData object
        };
        // Append the 'id' to the FormData object
        formArray.formData.append('record_ids[]', id);
        ajaxRequest(formArray); //Defined in ./common/common.js
    }

    /**
    * Ajax Request
    */
    function ajaxRequest(formArray){
        var jqxhr = $.ajax({
            type: formArray._method,
            url: formArray.url,
            data: formArray.formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': formArray.csrf
            },
            beforeSend: function() {
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    // Action Before Proceeding request
                }
            },
        });
        jqxhr.done(function(data) {

            iziToast.success({title: 'Success', layout: 2, message: data.message});
        });
        jqxhr.fail(function(response) {
                var message = response.responseJSON.message;
                iziToast.error({title: 'Error', layout: 2, message: message});
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    function afterCallAjaxResponse(formObject){
        loadDatatables();
    }

    $(document).ready(function() {
        //Load Datatable
        loadDatatables();

        /**
         * Modal payment type, reinitiate initSelect2PaymentType() for modal
         * Call because modal won't support ajax search input box cursor.
         * by this code it works
         * */
        initSelect2PaymentType({ dropdownParent: $('#invoicePaymentModal') });
	} );

    $(document).on("change", '#party_id, #user_id, #reference_no, input[name="from_date"], input[name="to_date"]', function function_name(e) {
        loadDatatables();
    });

});
