<?php
/**
 * Test Status History Display
 * Access: http://your-domain/test-status-history-display.php?sale_id=X
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Sale\Sale;

$saleId = $_GET['sale_id'] ?? 4; // Default to sale 4 which we know has history
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Status History Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 30px;
            border-left: 2px solid #e9ecef;
            margin-left: 10px;
            padding-left: 25px;
        }

        .timeline-item:last-child {
            border-left: none;
            padding-bottom: 0;
        }

        .timeline-item-current {
            border-left-color: #0d6efd;
        }

        .timeline-marker {
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }

        .timeline-item-current .timeline-marker {
            background-color: #198754;
            box-shadow: 0 0 0 2px #0d6efd;
        }

        .timeline-content {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .notes-section {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            margin: 10px 0;
        }

        .proof-image-container {
            text-align: center;
        }

        .proof-thumbnail {
            transition: transform 0.2s ease;
            border: 2px solid #dee2e6;
            cursor: pointer;
        }

        .proof-thumbnail:hover {
            transform: scale(1.05);
            border-color: #0d6efd;
        }

        .proof-image-section {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test Status History Display</h1>
        <p>Testing Sale ID: <strong><?= $saleId ?></strong></p>

        <?php
        try {
            $sale = Sale::with(['salesStatusHistories' => ['changedBy'], 'party'])->findOrFail($saleId);

            echo "<div class='alert alert-success'>";
            echo "✅ Sale found: {$sale->sale_code} - Customer: {$sale->party->getFullName()}";
            echo "</div>";

            if ($sale->salesStatusHistories->count() > 0) {
                echo "<div class='card'>";
                echo "<div class='card-header'>";
                echo "<h5 class='mb-0'><i class='bx bx-history me-2'></i>Status Change History</h5>";
                echo "</div>";
                echo "<div class='card-body'>";
                echo "<div class='timeline'>";

                foreach ($sale->salesStatusHistories->sortByDesc('changed_at') as $history) {
                    $isFirst = $loop->first ?? ($history === $sale->salesStatusHistories->sortByDesc('changed_at')->first());
                    echo "<div class='timeline-item " . ($isFirst ? 'timeline-item-current' : '') . "'>";
                    echo "<div class='timeline-marker'></div>";
                    echo "<div class='timeline-content'>";
                    echo "<div class='row'>";
                    echo "<div class='col-md-8'>";
                    echo "<div class='status-change'>";
                    echo "<h6 class='mb-2'>";

                    if ($history->previous_status) {
                        echo "<span class='badge bg-secondary'>{$history->previous_status}</span>";
                        echo "<i class='bx bx-right-arrow-alt mx-2'></i>";
                    }

                    $badgeColor = $history->new_status === 'POD' ? 'success' :
                                 ($history->new_status === 'Cancelled' ? 'danger' :
                                 ($history->new_status === 'Returned' ? 'warning' : 'primary'));

                    echo "<span class='badge bg-{$badgeColor}'>{$history->new_status}</span>";
                    echo "</h6>";

                    if ($history->notes) {
                        echo "<div class='notes-section mb-2'>";
                        echo "<strong class='text-muted'>Notes:</strong>";
                        echo "<p class='mb-0 text-wrap'>{$history->notes}</p>";
                        echo "</div>";
                    }

                    echo "<small class='text-muted d-block'>";
                    echo "<i class='bx bx-user me-1'></i>";
                    echo "Changed by: " . ($history->changedBy->name ?? 'Unknown');
                    echo "<br>";
                    echo "<i class='bx bx-time me-1'></i>";
                    echo $history->changed_at->format('M d, Y H:i');
                    echo "</small>";
                    echo "</div>";
                    echo "</div>";

                    if ($history->proof_image) {
                        echo "<div class='col-md-4'>";
                        echo "<div class='proof-image-section'>";
                        echo "<strong class='text-muted d-block mb-2'>Proof Image:</strong>";
                        echo "<div class='proof-image-container'>";
                        echo "<img src='/storage/{$history->proof_image}' alt='{$history->new_status} Proof' class='img-fluid rounded border proof-thumbnail' style='max-width: 200px; max-height: 150px;'>";
                        echo "<div class='mt-2'>";
                        echo "<a href='/storage/{$history->proof_image}' target='_blank' class='btn btn-outline-primary btn-sm'>";
                        echo "<i class='bx bx-show me-1'></i>View Full Size";
                        echo "</a>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }

                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }

                echo "</div>";
                echo "</div>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-info'>";
                echo "ℹ️ No status history found for this sale.";
                echo "</div>";
            }

        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "❌ Error: " . $e->getMessage();
            echo "</div>";
        }
        ?>

        <hr>
        <div class="d-flex gap-2">
            <a href="?sale_id=4" class="btn btn-primary">Test Sale 4</a>
            <a href="?sale_id=5" class="btn btn-primary">Test Sale 5</a>
            <a href="?sale_id=6" class="btn btn-primary">Test Sale 6</a>
            <a href="/sale/invoice/edit/<?= $saleId ?>" class="btn btn-success">View in Edit Form</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
