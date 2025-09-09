{{--
    Example integration for Sales Status Management in Blade template
    Add this to your sales details or list view
--}}

@push('styles')
<style>
.status-badge {
    font-size: 0.875em;
    padding: 0.5rem 1rem;
    border-radius: 1rem;
}
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-marker {
    position: absolute;
    left: -37px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #dee2e6;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}
</style>
@endpush

{{-- Sales Status Update Section --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Sales Status Management</h5>
        <div>
            <span class="current-status status-badge badge bg-{{ $sale->sales_status === 'Pending' ? 'warning' : ($sale->sales_status === 'Completed' ? 'success' : 'primary') }}">
                {{ $sale->sales_status }}
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Update Status</label>
                    <select class="form-select sales-status-select" data-sale-id="{{ $sale->id }}">
                        <option value="">Select Status</option>
                        @foreach($this->generalDataService->getSaleStatus() as $status)
                            <option value="{{ $status['id'] }}" {{ $sale->sales_status === $status['id'] ? 'selected' : '' }}>
                                {{ $status['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Inventory Status</label>
                    <input type="text" class="form-control" value="{{ ucfirst($sale->inventory_status ?? 'pending') }}" readonly>
                    @if($sale->inventory_deducted_at)
                        <small class="text-muted">Deducted on: {{ $sale->inventory_deducted_at->format('M d, Y H:i') }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info view-status-history" data-sale-id="{{ $sale->id }}">
                <i class="bx bx-history"></i> View Status History
            </button>

            @if($sale->inventory_status === 'pending' && auth()->user()->can('sale.invoice.manual.inventory.deduction'))
                <button type="button" class="btn btn-outline-warning" onclick="manualInventoryDeduction({{ $sale->id }})">
                    <i class="bx bx-package"></i> Manual Inventory Deduction
                </button>
            @endif
        </div>
    </div>
</div>

{{-- Status Requirements Information --}}
<div class="alert alert-info">
    <h6><i class="bx bx-info-circle"></i> Status Change Requirements:</h6>
    <ul class="mb-0">
        <li><strong>POD:</strong> Requires proof image and notes. Triggers inventory deduction.</li>
        <li><strong>Cancelled/Returned:</strong> Requires notes and optional proof image. Restores inventory if previously deducted.</li>
        <li><strong>Other statuses:</strong> No additional requirements.</li>
    </ul>
</div>

@push('scripts')
<script src="{{ asset('js/sales-status-manager.js') }}"></script>
<script>
function manualInventoryDeduction(saleId) {
    if (!confirm('Are you sure you want to manually deduct inventory for this sale?')) {
        return;
    }

    $.ajax({
        url: `/sale/invoice/manual-inventory-deduction/${saleId}`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status) {
                alert('Inventory deducted successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('An error occurred: ' + (xhr.responseJSON?.message || 'Unknown error'));
        }
    });
}

// Example of how to integrate with existing sales table
$(document).ready(function() {
    // Add status column to DataTable if using DataTables
    if (typeof window.salesTable !== 'undefined') {
        window.salesTable.on('draw', function() {
            // Reinitialize status selects after table redraw
            $('.sales-status-select').off('change').on('change', function() {
                new SalesStatusManager().handleStatusChange(this);
            });
        });
    }
});
</script>
@endpush

{{-- Example of DataTable column modification for sales list --}}
{{--
Add this to your sales list DataTable columns:

{
    data: 'sales_status',
    name: 'sales_status',
    title: 'Status',
    render: function(data, type, row) {
        if (type === 'display') {
            const colors = {
                'Pending': 'warning',
                'Processing': 'primary',
                'Completed': 'success',
                'Delivery': 'info',
                'POD': 'primary',
                'Cancelled': 'danger',
                'Returned': 'secondary'
            };

            return `
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-${colors[data] || 'secondary'}">${data}</span>
                    <select class="form-select form-select-sm sales-status-select"
                            data-sale-id="${row.id}" style="width: auto;">
                        <option value="">Change...</option>
                        @foreach($this->generalDataService->getSaleStatus() as $status)
                            <option value="{{ $status['id'] }}">{{ $status['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            `;
        }
        return data;
    }
}
--}}
