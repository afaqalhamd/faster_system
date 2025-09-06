$(document).ready(function() {
    // Initialize datatable
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + '/delivery/pending/datatable-list',
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
            { data: 'username', name: 'username' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
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

    // Handle delivery status update
    $(document).on('click', '.update-delivery-status', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: lang === 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?',
            text: lang === 'ar' ? 'هل تريد تحديث حالة التوصيل إلى مكتمل؟' : 'Do you want to update the delivery status to completed?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: lang === 'ar' ? 'نعم، حدث الحالة' : 'Yes, update status',
            cancelButtonText: lang === 'ar' ? 'إلغاء' : 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/delivery/update-status/' + id,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire(
                                lang === 'ar' ? 'تم التحديث!' : 'Updated!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        } else {
                            Swal.fire(
                                lang === 'ar' ? 'خطأ!' : 'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            lang === 'ar' ? 'خطأ!' : 'Error!',
                            lang === 'ar' ? 'حدث خطأ أثناء تحديث الحالة' : 'An error occurred while updating the status',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Initialize datepickers
    $('.datepicker-edit').datepicker({
        format: userDateFormat.toLowerCase(),
        autoclose: true,
        todayHighlight: true
    });
});