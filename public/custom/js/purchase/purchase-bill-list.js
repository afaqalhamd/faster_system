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

        var exportColumns = [2,3,4,5,6,7,8,9,10,11,12,13];//Index Starts from 0

        var table = tableId.DataTable({
            processing: true,
            serverSide: true,
            method:'get',
            ajax: {
                    url: baseURL+'/purchase/bill/datatable-list',
                    data:{
                            party_id : $('#party_id').val(),
                            user_id : $('#user_id').val(),

                            from_date : $('input[name="from_date"]').val(),
                            to_date : $('input[name="to_date"]').val(),
                        },
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
                    data: null, // Combine purchase_code and status in this column
                    name: 'purchase_code',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        let orderCode = data.purchase_code || ''; // Default if purchase_code is null
                        let statusBadge = '';

                        // Check if status is an object and extract data
                        let statusText = data.status?.text || ''; // Get text from status object
                        let statusCode = data.status?.code || ''; // Get sale_order or quotation code
                        let statusUrl = data.status?.url || '';

                        // Determine the status badge
                        if (statusText === 'Converted from Purchase Order') {
                            statusBadge = `<div class="badge text-primary bg-light-primary p-2 text-uppercase px-3">
                                            ${statusText} (<a href="${statusUrl}" target="_blank" data-bs-toggle="tooltip"
                                                              data-bs-placement="top" title="View Purchase Order Details">
                                                              ${statusCode} <i class="fadeIn animated bx bx-link-external bx-tada-hover"></i>

                                                          </a>)
                                        </div>`;
                        }

                        if (data.is_return_raised?.status === 'Return Raised') {
                            let returnLinks = data.is_return_raised.urls.map((url, index) =>
                                `<a href="${url}" target="_blank" data-bs-toggle="tooltip"
                                                              data-bs-placement="top" title="View Purchase Return Details">
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

                {data: 'reference_no', name: 'reference_no'},
                {data: 'purchase_date', name: 'purchase_date'},

                {data: 'party_name', name: 'party_name'},
                {data: 'grand_total', name: 'grand_total', className: 'text-end'},
                {data: 'balance', name: 'balance', className: 'text-end'},
                {
                    data: 'payment_status',
                    name: 'payment_status',
                    orderable: false,
                    className: 'text-center',
                    searchable: false
                },
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

                        // Create the badge HTML based on inventory status
                        const statusMap = {
                            'added': { class: 'bg-light-success text-success', text: window.purchaseStatusIcons.translations['purchase.inventory_added'] || 'Inventory Added' },
                            'pending': { class: 'bg-light-warning text-warning', text: window.purchaseStatusIcons.translations['purchase.inventory_pending'] || 'Pending' },
                            'ready_for_addition': { class: 'bg-light-primary text-primary', text: window.purchaseStatusIcons.translations['purchase.inventory_ready_for_addition'] || 'Ready for Addition' },
                            'Added': { class: 'bg-light-success text-success', text: window.purchaseStatusIcons.translations['purchase.inventory_added'] || 'Inventory Added' },
                            'Pending': { class: 'bg-light-warning text-warning', text: window.purchaseStatusIcons.translations['purchase.inventory_pending'] || 'Pending' },
                            'Ready for Addition': { class: 'bg-light-primary text-primary', text: window.purchaseStatusIcons.translations['purchase.inventory_ready_for_addition'] || 'Ready for Addition' }
                        };

                        // Check if we have a translation for this status
                        let displayText = data;
                        let statusClass = 'bg-light-secondary text-secondary'; // default
                        let statusIcon = ''; // default no icon

                        if (data === 'added') {
                            displayText = window.purchaseStatusIcons.translations['purchase.inventory_added'] || 'Inventory Added';
                            statusClass = 'bg-light-success text-success';
                        } else if (data === 'pending') {
                            displayText = window.purchaseStatusIcons.translations['purchase.inventory_pending'] || 'Pending';
                            statusClass = 'bg-light-warning text-warning';
                        } else if (data === 'ready_for_addition') {
                            displayText = window.purchaseStatusIcons.translations['purchase.inventory_ready_for_addition'] || 'Ready for Addition';
                            statusClass = 'bg-light-primary text-primary';
                        } else if (data === 'added_received') {
                            // Check the post_receipt_action to determine the correct text
                            const postReceiptAction = full.post_receipt_action ? full.post_receipt_action.toString().trim() : '';

                            if (postReceiptAction === 'Cancelled') {
                                displayText = window.purchaseStatusIcons.translations['purchase.post_receipt_cancel'] || 'Post-Receipt Cancelled';
                                statusClass = 'bg-light-danger text-danger'; // Red for cancellation
                                statusIcon = 'bx-x-circle';
                            } else if (postReceiptAction === 'Returned') {
                                displayText = window.purchaseStatusIcons.translations['purchase.post_receipt_return'] || 'Post-Receipt Return';
                                statusClass = 'bg-light-warning text-warning'; // Orange for return
                                statusIcon = 'bx-undo';
                            } else {
                                // Fallback - check purchase_status as backup
                                const purchaseStatus = full.purchase_status ? full.purchase_status.toString().trim() : '';
                                if (purchaseStatus === 'Cancelled') {
                                    displayText = window.purchaseStatusIcons.translations['purchase.post_receipt_cancel'] || 'Post-Receipt Cancelled';
                                    statusClass = 'bg-light-danger text-danger'; // Red for cancellation
                                    statusIcon = 'bx-x-circle';
                                } else if (purchaseStatus === 'Returned') {
                                    displayText = window.purchaseStatusIcons.translations['purchase.post_receipt_return'] || 'Post-Receipt Return';
                                    statusClass = 'bg-light-warning text-warning'; // Orange for return
                                    statusIcon = 'bx-undo';
                                } else {
                                    // Final fallback - use a generic text
                                    displayText = window.purchaseStatusIcons.translations['purchase.inventory_post_receipt_action'] || 'Post-Receipt Action';
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
                    data: 'purchase_status',
                    name: 'purchase_status',
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, full, meta) {
                        // If data already contains HTML, return it as is
                        if (typeof data === 'string' && data.includes('<div class="badge')) {
                            return data;
                        }

                        // Use the purchaseStatusIcons utility to create a proper status badge
                        if (window.purchaseStatusIcons && data) {
                            return window.purchaseStatusIcons.createStatusBadge(data);
                        }

                        // Fallback to simple badge if icons are not available
                        const status = data || 'Unknown';
                        return `<div class="badge bg-light-secondary text-secondary p-2 px-3">${status}</div>`;
                    }
                },
                {data: 'carrier_name', name: 'carrier_name', orderable: false, className: 'text-center'},
                {data: 'username', name: 'username'},
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

    $(document).on("change", '#party_id, #user_id, input[name="from_date"], input[name="to_date"]', function function_name(e) {
        loadDatatables();
    });

});
