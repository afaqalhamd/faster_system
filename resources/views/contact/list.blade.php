@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Page Header -->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">رسائل التواصل</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">رسائل التواصل</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="contacts-table" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>الموضوع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Contact Modal -->
<div class="modal fade" id="viewContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الرسالة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>الاسم:</strong>
                        <p id="modal-name"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>البريد الإلكتروني:</strong>
                        <p id="modal-email"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>الهاتف:</strong>
                        <p id="modal-phone"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>الموضوع:</strong>
                        <p id="modal-subject"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>الرسالة:</strong>
                        <p id="modal-message" class="border p-3 rounded"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>الحالة:</strong>
                        <select id="modal-status" class="form-select">
                            <option value="pending">قيد الانتظار</option>
                            <option value="replied">تم الرد</option>
                            <option value="closed">مغلق</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="update-status">تحديث الحالة</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentContactId = null;

    // Initialize DataTable
    const table = $('#contacts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("contact.datatable.list") }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'subject', name: 'subject' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
        }
    });

    // View Contact
    $(document).on('click', '.view-contact', function() {
        const id = $(this).data('id');
        currentContactId = id;

        $.get(`/contact/show/${id}`, function(data) {
            $('#modal-name').text(data.name);
            $('#modal-email').text(data.email);
            $('#modal-phone').text(data.phone || 'غير متوفر');
            $('#modal-subject').text(data.subject);
            $('#modal-message').text(data.message);
            $('#modal-status').val(data.status);
            $('#viewContactModal').modal('show');
        });
    });

    // Update Status
    $('#update-status').click(function() {
        const status = $('#modal-status').val();

        $.post('{{ route("contact.update.status") }}', {
            _token: '{{ csrf_token() }}',
            id: currentContactId,
            status: status
        }, function(response) {
            if (response.success) {
                $('#viewContactModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.message);
            }
        });
    });

    // Delete Contact
    $(document).on('click', '.delete-contact', function() {
        const id = $(this).data('id');

        if (confirm('هل أنت متأكد من حذف هذه الرسالة؟')) {
            $.post('{{ route("contact.delete") }}', {
                _token: '{{ csrf_token() }}',
                id: id
            }, function(response) {
                if (response.success) {
                    table.ajax.reload();
                    toastr.success(response.message);
                }
            });
        }
    });
});
</script>
@endsection
