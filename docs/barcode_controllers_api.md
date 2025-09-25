# Controllers و API Endpoints لنظام الباركود

## 1. تحديث SaleOrderController الحالي

### إضافة خدمة الباركود للـ Constructor
```php
// في SaleOrderController::__construct()
use App\Services\BarcodeService;

private $barcodeService;

public function __construct(
    // ... الخدمات الحالية
    BarcodeService $barcodeService
) {
    // ... التهيئة الحالية
    $this->barcodeService = $barcodeService;
}
```

### تعديل طريقة store لإنشاء الباركود
```php
// في SaleOrderController::store()
public function store(SaleOrderRequest $request): JsonResponse
{
    try {
        DB::beginTransaction();
        
        // الكود الحالي لإنشاء الطلب...
        $newSaleOrder = SaleOrder::create($fillableColumns);
        
        // إنشاء الباركود الجديد
        if ($request->operation == 'save') {
            $barcode = $this->barcodeService->generateBarcode($newSaleOrder->id);
            $newSaleOrder->update([
                'barcode' => $barcode,
                'barcode_generated_at' => now()
            ]);
        }
        
        // باقي الكود الحالي...
        
    } catch (\Exception $e) {
        // معالجة الأخطاء...
    }
}
```

### تحديث طريقة print لإضافة الباركود
```php
// في SaleOrderController::print()
public function print($id, $isPdf = false): View
{
    $order = SaleOrder::with([/* العلاقات الحالية */])->find($id);
    
    // إنشاء صورة الباركود للطباعة
    if ($order->barcode) {
        try {
            $order->barcode_image = $this->barcodeService->generateBarcodeImage($order->barcode);
            $order->qr_code_image = $this->barcodeService->generateQRCode($order->barcode);
        } catch (\Exception $e) {
            Log::warning("Failed to generate barcode images for order {$id}: " . $e->getMessage());
        }
    }
    
    // باقي الكود الحالي...
    return view('print.sale-order.print', compact(
        'isPdf', 'invoiceData', 'order', 
        'selectedPaymentTypesArray', 'batchTrackingRowCount'
    ));
}
```

## 2. API Controller للدليفري

```php
<?php
// app/Http/Controllers/Api/DeliveryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\DeliveryConfirmationService;
use App\Services\BarcodeService;
use App\Models\Sale\SaleOrder;

class DeliveryController extends Controller
{
    private DeliveryConfirmationService $deliveryService;
    private BarcodeService $barcodeService;

    public function __construct(
        DeliveryConfirmationService $deliveryService,
        BarcodeService $barcodeService
    ) {
        $this->deliveryService = $deliveryService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * تأكيد التوصيل عبر مسح الباركود
     */
    public function confirmDelivery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
            'proof_image' => 'nullable|image|max:2048',
            'app_version' => 'nullable|string',
            'device_model' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->deliveryService->confirmDelivery(
            $request->barcode,
            $request->all()
        );

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * التحقق من صحة الباركود
     */
    public function validateBarcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'باركود مطلوب'
            ], 422);
        }

        $orderId = $this->barcodeService->decryptBarcode($request->barcode);
        
        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'باركود غير صحيح'
            ]);
        }

        $order = SaleOrder::with(['party', 'carrier'])->find($orderId);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'باركود صحيح',
            'data' => [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'customer_name' => $order->party->first_name . ' ' . $order->party->last_name,
                'total_amount' => $order->grand_total,
                'order_status' => $order->order_status,
                'can_be_delivered' => $order->canBarcodeBeScanned(),
                'carrier_name' => $order->carrier?->name
            ]
        ]);
    }

    /**
     * الحصول على طلبات المستخدم الجاهزة للتوصيل
     */
    public function getDeliveryOrders(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = SaleOrder::with(['party', 'carrier'])
            ->where('order_status', 'Delivery')
            ->whereNotNull('barcode');

        // تصفية حسب الناقل إذا كان المستخدم مرتبط بناقل
        if ($user->carrier_id) {
            $query->where('carrier_id', $user->carrier_id);
        }

        $orders = $query->orderBy('order_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'total_pages' => $orders->lastPage(),
                'total_items' => $orders->total()
            ]
        ]);
    }
}
```

## 3. Routes للـ API

```php
// routes/api.php

use App\Http\Controllers\Api\DeliveryController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('delivery')->group(function () {
        Route::post('/confirm', [DeliveryController::class, 'confirmDelivery']);
        Route::post('/validate-barcode', [DeliveryController::class, 'validateBarcode']);
        Route::get('/orders', [DeliveryController::class, 'getDeliveryOrders']);
    });
});
```

## 4. تحديث قالب الطباعة

```html
<!-- resources/views/print/sale-order/print.blade.php -->
<!-- إضافة قسم الباركود -->
@if($order->barcode_image || $order->qr_code_image)
<div class="barcode-section" style="page-break-inside: avoid; margin: 20px 0; text-align: center;">
    <h4 style="margin-bottom: 15px;">رمز التوصيل</h4>
    
    @if($order->barcode_image)
    <div style="margin-bottom: 15px;">
        <img src="data:image/png;base64,{{ $order->barcode_image }}" 
             alt="Barcode" style="max-width: 300px; height: auto;">
    </div>
    @endif
    
    @if($order->qr_code_image)
    <div style="margin-bottom: 15px;">
        <img src="data:image/png;base64,{{ $order->qr_code_image }}" 
             alt="QR Code" style="max-width: 150px; height: auto;">
    </div>
    @endif
    
    <p style="font-size: 12px; margin-top: 10px; color: #666;">
        امسح هذا الرمز عند التوصيل لتأكيد الاستلام
    </p>
    
    @if($order->barcode)
    <p style="font-size: 10px; font-family: monospace; word-break: break-all; margin-top: 5px;">
        {{ substr($order->barcode, 0, 50) }}{{ strlen($order->barcode) > 50 ? '...' : '' }}
    </p>
    @endif
</div>
@endif
```

## 5. Middleware للتحقق من صلاحيات التوصيل

```php
<?php
// app/Http/Middleware/DeliveryUserMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeliveryUserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        if (!$user->role || !in_array(strtolower($user->role->name), ['delivery', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول لهذه الخدمة'
            ], 403);
        }

        return $next($request);
    }
}
```

## 6. Command لإنشاء باركود للطلبات الموجودة

```php
<?php
// app/Console/Commands/GenerateBarcodesForExistingOrders.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale\SaleOrder;
use App\Services\BarcodeService;

class GenerateBarcodesForExistingOrders extends Command
{
    protected $signature = 'barcode:generate-existing {--limit=100}';
    protected $description = 'Generate barcodes for existing orders without barcodes';

    private BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        parent::__construct();
        $this->barcodeService = $barcodeService;
    }

    public function handle()
    {
        $limit = $this->option('limit');
        
        $orders = SaleOrder::whereNull('barcode')
            ->whereIn('order_status', ['Pending', 'Processing', 'Delivery'])
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found without barcodes.');
            return;
        }

        $this->info("Generating barcodes for {$orders->count()} orders...");
        
        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            try {
                $barcode = $this->barcodeService->generateBarcode($order->id);
                $order->update([
                    'barcode' => $barcode,
                    'barcode_generated_at' => now()
                ]);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nFailed to generate barcode for order {$order->id}: " . $e->getMessage());
                $errorCount++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine();
        $this->info("Completed! Success: {$successCount}, Errors: {$errorCount}");
    }
}
```

هذا يكمل الجزء الأساسي من Controllers و API. النظام الآن جاهز للتنفيذ مع جميع المكونات المطلوبة.
