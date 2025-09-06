$(document).ready(function() {
    // Initialize datatable
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + '/delivery/completed/datatable-list',
            type: 'GET',
            data: function(d) {
                d.party_id = $('#party_id').val();
                d.from_date = $('#from_date').val();
                d.to_date = $('#to_date').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'sale_code', name: 'sale_code' },
            { data: 'sale_date', name: 'sale_date' },
            { data: 'party_name', name: 'party_name' },
            { data: 'grand_total', name: 'grand_total' },
            { data: 'balance', name: 'balance' },
            { data: 'sales_status', name: 'sales_status' },
            { data: 'delivered_at', name: 'delivered_at' },
            { data: 'delivered_by', name: 'delivered_by' },
            { data: 'created_at', name: 'created_at' }
        ],
        order: [[0, 'desc']],
        language: {
            url: baseUrl + "/assets/plugins/datatable/js/" + lang + ".json"
        }
    });

    // Handle filter changes
    $('#party_id, #from_date, #to_date').on('change', function() {
        table.ajax.reload();
    });

    // Initialize datepickers
    $('.datepicker-edit').datepicker({
        format: userDateFormat.toLowerCase(),
        autoclose: true,
        todayHighlight: true
    });
});