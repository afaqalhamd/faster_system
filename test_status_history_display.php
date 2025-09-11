<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;

echo "Testing Sale Order Status History Display\n";
echo "========================================\n\n";

// Get a sale order with status histories
$saleOrder = SaleOrder::with(['saleOrderStatusHistories' => function($query) {
    $query->with('changedBy:id,first_name,last_name,email');
}])->whereHas('saleOrderStatusHistories')->first();

if (!$saleOrder) {
    echo "❌ No sale orders with status histories found\n";
    exit(1);
}

echo "Found sale order with status histories (ID: {$saleOrder->id})\n";
echo "Number of status history records: " . $saleOrder->saleOrderStatusHistories->count() . "\n";

// Render the status history section like in the Blade template
echo "\nRendering status history section:\n";
echo "================================\n";

$statusCount = $saleOrder->saleOrderStatusHistories->count();
echo "<div class=\"card-header px-4 py-3\">\n";
echo "    <div class=\"d-flex align-items-center justify-content-between\">\n";
echo "        <h5 class=\"mb-0\">Status Change History</h5>\n";
echo "        <div class=\"d-flex align-items-center\">\n";
echo "            <span class=\"badge bg-light text-muted me-2 small\" id=\"statusHistoryCount\">\n";
echo "                {$statusCount} changes\n";
echo "            </span>\n";
echo "            <button class=\"btn btn-sm btn-outline-secondary\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#statusHistoryCollapse\" aria-expanded=\"true\" aria-controls=\"statusHistoryCollapse\">\n";
echo "                <i class=\"bx bx-chevron-up fs-6\"></i>\n";
echo "            </button>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";
echo "<div class=\"collapse show\" id=\"statusHistoryCollapse\">\n";
echo "    <div class=\"card-body p-4 row g-3\">\n";
echo "        <div class=\"col-md-12\" id=\"statusHistoryContent\">\n";

if ($statusCount > 0) {
    // Sort by changed_at descending
    $sortedHistories = $saleOrder->saleOrderStatusHistories->sortByDesc('changed_at');
    
    foreach ($sortedHistories as $index => $history) {
        $statusConfig = [
            'Pending' => ['icon' => 'bx-time-five', 'color' => 'warning'],
            'Processing' => ['icon' => 'bx-loader-circle', 'color' => 'primary'],
            'Completed' => ['icon' => 'bx-check-circle', 'color' => 'success'],
            'Delivery' => ['icon' => 'bx-package', 'color' => 'info'],
            'POD' => ['icon' => 'bx-receipt', 'color' => 'success'],
            'Cancelled' => ['icon' => 'bx-x-circle', 'color' => 'danger'],
            'Returned' => ['icon' => 'bx-undo', 'color' => 'warning']
        ];
        
        $currentStatus = $statusConfig[$history->new_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary'];
        $previousStatus = $history->previous_status ? ($statusConfig[$history->previous_status] ?? ['icon' => 'bx-circle', 'color' => 'secondary']) : null;
        
        $isLast = ($index === count($sortedHistories) - 1);
        
        echo "            <div class=\"d-flex align-items-start mb-3 pb-3 " . (!$isLast ? 'border-bottom' : '') . " position-relative\">\n";
        echo "                <div class=\"me-3 position-relative\">\n";
        echo "                    <div class=\"bg-{$currentStatus['color']} text-white rounded-circle d-flex align-items-center justify-content-center timeline-status-circle\" style=\"width: 28px; height: 28px; font-size: 12px; position: relative; z-index: 2;\">\n";
        echo "                        <i class=\"bx {$currentStatus['icon']}\"></i>\n";
        echo "                    </div>\n";
        
        if (!$isLast) {
            $connectorColor = match($currentStatus['color']) {
                'warning' => '#ffc107',
                'primary' => '#0d6efd',
                'success' => '#198754',
                'info' => '#0dcaf0',
                'danger' => '#dc3545',
                default => '#6c757d'
            };
            
            echo "                    <div class=\"timeline-connector\" style=\"position: absolute; top: 28px; left: 50%; transform: translateX(-50%); width: 2px; height: 40px; background: linear-gradient(180deg, {$connectorColor} 0%, #e9ecef 100%); z-index: 1;\"></div>\n";
        }
        
        echo "                </div>\n";
        echo "                <div class=\"flex-grow-1\">\n";
        echo "                    <div class=\"d-flex justify-content-between align-items-start mb-1\">\n";
        echo "                        <div>\n";
        
        if ($history->previous_status) {
            echo "                            <div class=\"d-flex align-items-center gap-1 mb-1\">\n";
            echo "                                <span class=\"badge bg-{$previousStatus['color']} text-white small\">\n";
            echo "                                    <i class=\"bx {$previousStatus['icon']} me-1\"></i>{$history->previous_status}\n";
            echo "                                </span>\n";
            echo "                                <i class=\"bx bx-right-arrow-alt text-muted\" style=\"font-size: 12px;\"></i>\n";
            echo "                                <span class=\"badge bg-{$currentStatus['color']} text-white small\">\n";
            echo "                                    <i class=\"bx {$currentStatus['icon']} me-1\"></i>{$history->new_status}\n";
            echo "                                </span>\n";
            echo "                            </div>\n";
        } else {
            echo "                            <span class=\"badge bg-{$currentStatus['color']} text-white small\">\n";
            echo "                                <i class=\"bx {$currentStatus['icon']} me-1\"></i>{$history->new_status} <small>(Initial)</small>\n";
            echo "                            </span>\n";
        }
        
        echo "                        </div>\n";
        echo "                        <div class=\"text-end\">\n";
        echo "                            <small class=\"text-primary fw-semibold\">" . $history->changed_at->format('M d, H:i') . "</small>\n";
        echo "                            <small class=\"text-muted d-block\">" . $history->changed_at->diffForHumans() . "</small>\n";
        echo "                        </div>\n";
        echo "                    </div>\n";
        
        if ($history->notes) {
            echo "                    <div class=\"bg-light rounded p-2 mb-2\">\n";
            echo "                        <small><i class=\"bx bx-note text-primary me-1\"></i><strong>Notes:</strong> {$history->notes}</small>\n";
            echo "                    </div>\n";
        }
        
        echo "                    <div class=\"d-flex justify-content-between align-items-center\">\n";
        echo "                        <small class=\"text-muted\">\n";
        echo "                            <i class=\"bx bx-user text-danger me-1\"></i>\n";
        
        if ($history->changedBy) {
            $userName = trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name);
            echo "                            {$userName}\n";
        } elseif ($history->changed_by) {
            $user = \App\Models\User::find($history->changed_by);
            $userName = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Unknown User';
            echo "                            {$userName}\n";
        } else {
            echo "                            System Auto\n";
        }
        
        echo "                        </small>\n";
        
        if ($history->proof_image) {
            echo "                        <button type=\"button\" class=\"btn btn-outline-primary btn-sm\" data-bs-toggle=\"modal\" data-bs-target=\"#proofImageModal{$history->id}\">\n";
            echo "                            <i class=\"bx bx-image me-1\"></i>View Proof\n";
            echo "                        </button>\n";
        }
        
        echo "                    </div>\n";
        echo "                </div>\n";
        echo "            </div>\n";
    }
} else {
    echo "            <div class=\"text-center py-5\" id=\"noStatusHistoryMessage\">\n";
    echo "                <i class=\"bx bx-history text-muted\" style=\"font-size: 3rem;\"></i>\n";
    echo "                <p class=\"text-muted mt-3\">No status history available yet.</p>\n";
    echo "                <p class=\"text-muted small\">Status changes will be recorded here.</p>\n";
    echo "            </div>\n";
}

echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";

echo "\n✅ Test completed!\n";