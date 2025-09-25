# دليل التنفيذ العملي لنظام الباركود

## 1. إعداد قاعدة البيانات

### Migration للحقول الجديدة
```php
<?php
// database/migrations/2024_01_01_000000_add_barcode_fields_to_sale_orders.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBarcodeFieldsToSaleOrders extends Migration
{
    public function up()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('barcode')->unique()->nullable()->after('order_code');
            $table->timestamp('barcode_generated_at')->nullable()->after('barcode');
            $table->timestamp('delivery_confirmed_at')->nullable()->after('barcode_generated_at');
            $table->unsignedBigInteger('delivery_confirmed_by')->nullable()->after('delivery_confirmed_at');
            $table->integer('barcode_scan_count')->default(0)->after('delivery_confirmed_by');
            
            $table->foreign('delivery_confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['barcode', 'order_status']);
        });
    }

    public function down()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_confirmed_by']);
            $table->dropIndex(['barcode', 'order_status']);
            $table->dropColumn([
                'barcode', 
                'barcode_generated_at', 
                'delivery_confirmed_at', 
                'delivery_confirmed_by',
                'barcode_scan_count'
            ]);
        });
    }
}
```

### Migration لجدول تتبع المسح
```php
<?php
// database/migrations/2024_01_01_000001_create_barcode_scans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodeScansTable extends Migration
{
    public function up()
    {
        Schema::create('barcode_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_order_id');
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->timestamp('scan_timestamp')->useCurrent();
            $table->decimal('scan_location_lat', 10, 8)->nullable();
            $table->decimal('scan_location_lng', 11, 8)->nullable();
            $table->json('device_info')->nullable();
            $table->enum('status', ['success', 'failed', 'duplicate'])->default('success');
            $table->text('notes')->nullable();
            $table->string('scan_method')->default('mobile_app'); // mobile_app, web, manual
            $table->timestamps();
            
            $table->foreign('sale_order_id')->references('id')->on('sale_orders')->onDelete('cascade');
            $table->foreign('scanned_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['sale_order_id', 'scan_timestamp']);
            $table->index(['scanned_by', 'scan_timestamp']);
            $table->index(['status', 'scan_timestamp']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('barcode_scans');
    }
}
```

## 2. النماذج (Models)

### تحديث نموذج SaleOrder
```php
<?php
// app/Models/Sale/SaleOrder.php - إضافة للكود الموجود

class SaleOrder extends Model
{
    // إضافة للـ fillable الموجود
    protected $fillable = [
        // ... الحقول الموجودة
        'barcode',
        'barcode_generated_at',
        'delivery_confirmed_at',
        'delivery_confirmed_by',
        'barcode_scan_count',
    ];

    // إضافة للـ casts الموجود
    protected $casts = [
        // ... الـ casts الموجودة
        'barcode_generated_at' => 'datetime',
        'delivery_confirmed_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم الذي أكد التوصيل
     */
    public function deliveryConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_confirmed_by');
    }

    /**
     * العلاقة مع عمليات مسح الباركود
     */
    public function barcodeScans(): HasMany
    {
        return $this->hasMany(BarcodeScan::class, 'sale_order_id');
    }

    /**
     * التحقق من وجود باركود صالح
     */
    public function hasValidBarcode(): bool
    {
        return !empty($this->barcode) && !empty($this->barcode_generated_at);
    }

    /**
     * التحقق من إمكانية مسح الباركود
     */
    public function canBarcodeBeScanned(): bool
    {
        return $this->hasValidBarcode() 
            && $this->order_status === 'Delivery' 
            && empty($this->delivery_confirmed_at);
    }

    /**
     * الحصول على آخر عملية مسح ناجحة
     */
    public function getLastSuccessfulScan()
    {
        return $this->barcodeScans()
            ->where('status', 'success')
            ->latest('scan_timestamp')
            ->first();
    }
}
```

### نموذج BarcodeScan الجديد
```php
<?php
// app/Models/BarcodeScan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sale\SaleOrder;
use App\Models\User;

class BarcodeScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_order_id',
        'scanned_by',
        'scan_timestamp',
        'scan_location_lat',
        'scan_location_lng',
        'device_info',
        'status',
        'notes',
        'scan_method',
    ];

    protected $casts = [
        'scan_timestamp' => 'datetime',
        'device_info' => 'array',
        'scan_location_lat' => 'decimal:8',
        'scan_location_lng' => 'decimal:8',
    ];

    /**
     * العلاقة مع طلب البيع
     */
    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    /**
     * العلاقة مع المستخدم الذي قام بالمسح
     */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    /**
     * تحديد نطاق للمسح الناجح
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * تحديد نطاق للمسح الفاشل
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * الحصول على معلومات الموقع المنسقة
     */
    public function getFormattedLocationAttribute(): ?string
    {
        if ($this->scan_location_lat && $this->scan_location_lng) {
            return "{$this->scan_location_lat}, {$this->scan_location_lng}";
        }
        return null;
    }
}
```

## 3. الخدمات (Services)

### خدمة الباركود
```php
<?php
// app/Services/BarcodeService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Carbon\Carbon;

class BarcodeService
{
    private const BARCODE_PREFIX = 'FSO'; // Faster System Order
    private const ENCRYPTION_METHOD = 'AES-256-CBC';
    
    private BarcodeGeneratorPNG $barcodeGenerator;

    public function __construct()
    {
        $this->barcodeGenerator = new BarcodeGeneratorPNG();
    }

    /**
     * إنشاء باركود مشفر لطلب البيع
     */
    public function generateBarcode(int $orderId): string
    {
        try {
            $data = [
                'order_id' => $orderId,
                'timestamp' => Carbon::now()->timestamp,
                'prefix' => self::BARCODE_PREFIX,
                'checksum' => $this->generateChecksum($orderId)
            ];

            $jsonData = json_encode($data);
            $encrypted = Crypt::encrypt($jsonData);
            
            // تحويل إلى base64 وإزالة الرموز الخاصة لسهولة المسح
            $barcode = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
            
            Log::info("Barcode generated for order {$orderId}", ['barcode_length' => strlen($barcode)]);
            
            return $barcode;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate barcode for order {$orderId}: " . $e->getMessage());
            throw new \Exception("فشل في إنشاء الباركود: " . $e->getMessage());
        }
    }

    /**
     * فك تشفير الباركود والحصول على معرف الطلب
     */
    public function decryptBarcode(string $barcode): ?int
    {
        try {
            // استعادة الرموز الخاصة
            $base64 = str_replace(['-', '_'], ['+', '/'], $barcode);
            
            // إضافة padding إذا لزم الأمر
            $base64 = str_pad($base64, strlen($base64) + (4 - strlen($base64) % 4) % 4, '=');
            
            $encrypted = base64_decode($base64);
            $decrypted = Crypt::decrypt($encrypted);
            $data = json_decode($decrypted, true);

            if (!$this->validateBarcodeData($data)) {
                Log::warning("Invalid barcode data structure", ['barcode' => substr($barcode, 0, 20) . '...']);
                return null;
            }

            if (!$this->validateChecksum($data['order_id'], $data['checksum'])) {
                Log::warning("Barcode checksum validation failed", ['order_id' => $data['order_id']]);
                return null;
            }

            // التحقق من عمر الباركود (اختياري - يمكن تعطيله)
            if ($this->isBarcodeExpired($data['timestamp'])) {
                Log::warning("Barcode expired", ['order_id' => $data['order_id'], 'timestamp' => $data['timestamp']]);
                // يمكن إرجاع null هنا إذا كنت تريد انتهاء صلاحية الباركود
            }

            Log::info("Barcode successfully decrypted", ['order_id' => $data['order_id']]);
            
            return (int) $data['order_id'];
            
        } catch (\Exception $e) {
            Log::error("Failed to decrypt barcode: " . $e->getMessage(), ['barcode' => substr($barcode, 0, 20) . '...']);
            return null;
        }
    }

    /**
     * إنشاء صورة الباركود للطباعة
     */
    public function generateBarcodeImage(string $barcode, string $type = 'CODE128'): string
    {
        try {
            $barcodeImage = $this->barcodeGenerator->getBarcode($barcode, $this->barcodeGenerator::TYPE_CODE_128);
            return base64_encode($barcodeImage);
        } catch (\Exception $e) {
            Log::error("Failed to generate barcode image: " . $e->getMessage());
            throw new \Exception("فشل في إنشاء صورة الباركود");
        }
    }

    /**
     * إنشاء QR Code كبديل للباركود
     */
    public function generateQRCode(string $barcode): string
    {
        try {
            $qrCode = new QrCode($barcode);
            $qrCode->setSize(200);
            $qrCode->setMargin(10);
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            return base64_encode($result->getString());
        } catch (\Exception $e) {
            Log::error("Failed to generate QR code: " . $e->getMessage());
            throw new \Exception("فشل في إنشاء رمز QR");
        }
    }

    /**
     * التحقق من صحة الباركود
     */
    public function validateBarcode(string $barcode): bool
    {
        $orderId = $this->decryptBarcode($barcode);
        return $orderId !== null;
    }

    /**
     * إنشاء checksum للتحقق من سلامة البيانات
     */
    private function generateChecksum(int $orderId): string
    {
        return hash('sha256', $orderId . config('app.key') . self::BARCODE_PREFIX);
    }

    /**
     * التحقق من صحة checksum
     */
    private function validateChecksum(int $orderId, string $checksum): bool
    {
        $expectedChecksum = $this->generateChecksum($orderId);
        return hash_equals($expectedChecksum, $checksum);
    }

    /**
     * التحقق من صحة بنية بيانات الباركود
     */
    private function validateBarcodeData(array $data): bool
    {
        return isset($data['order_id']) 
            && isset($data['timestamp']) 
            && isset($data['prefix']) 
            && isset($data['checksum'])
            && $data['prefix'] === self::BARCODE_PREFIX;
    }

    /**
     * التحقق من انتهاء صلاحية الباركود
     */
    private function isBarcodeExpired(int $timestamp): bool
    {
        // الباركود صالح لمدة 30 يوم (يمكن تعديلها حسب الحاجة)
        $expirationDays = config('barcode.expiration_days', 30);
        $expirationTimestamp = Carbon::createFromTimestamp($timestamp)->addDays($expirationDays)->timestamp;
        
        return Carbon::now()->timestamp > $expirationTimestamp;
    }
}
```

### خدمة تأكيد التوصيل
```php
<?php
// app/Services/DeliveryConfirmationService.php

namespace App\Services;

use App\Models\Sale\SaleOrder;
use App\Models\BarcodeScan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class DeliveryConfirmationService
{
    private BarcodeService $barcodeService;
    private SaleOrderStatusService $statusService;

    public function __construct(
        BarcodeService $barcodeService,
        SaleOrderStatusService $statusService
    ) {
        $this->barcodeService = $barcodeService;
        $this->statusService = $statusService;
    }

    /**
     * تأكيد التوصيل عبر مسح الباركود
     */
    public function confirmDelivery(string $barcode, array $data): array
    {
        try {
            DB::beginTransaction();

            // فك تشفير الباركود
            $orderId = $this->barcodeService->decryptBarcode($barcode);
            
            if (!$orderId) {
                return $this->failureResponse('باركود غير صحيح أو تالف');
            }

            // البحث عن الطلب
            $saleOrder = SaleOrder::with(['party', 'carrier'])->find($orderId);
            
            if (!$saleOrder) {
                return $this->failureResponse('الطلب غير موجود');
            }

            // التحقق من صحة العملية
            $validationResult = $this->validateDeliveryConfirmation($saleOrder, $data);
            if (!$validationResult['valid']) {
                return $this->failureResponse($validationResult['message']);
            }

            // تسجيل عملية المسح
            $scanRecord = $this->recordBarcodeScan($saleOrder, $barcode, $data);

            // تحديث حالة الطلب
            $statusUpdateResult = $this->statusService->updateSaleOrderStatus(
                $saleOrder,
                'POD',
                [
                    'notes' => $data['notes'] ?? 'تم التأكيد عبر مسح الباركود',
                    'proof_image' => $data['proof_image'] ?? null,
                    'delivery_method' => 'barcode_scan',
                    'scan_location' => $this->formatLocation($data),
                    'barcode_scan_id' => $scanRecord->id
                ]
            );

            if (!$statusUpdateResult['success']) {
                throw new \Exception($statusUpdateResult['message']);
            }

            // تحديث معلومات التوصيل في الطلب
            $saleOrder->update([
                'delivery_confirmed_at' => now(),
                'delivery_confirmed_by' => auth()->id(),
            ]);

            // تحديث عداد المسح
            $saleOrder->increment('barcode_scan_count');

            DB::commit();

            Log::info("Delivery confirmed via barcode scan", [
                'order_id' => $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'confirmed_by' => auth()->id(),
                'scan_id' => $scanRecord->id
            ]);

            return $this->successResponse([
                'order_id' => $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'customer_name' => $saleOrder->party->first_name . ' ' . $saleOrder->party->last_name,
                'total_amount' => $saleOrder->grand_total,
                'confirmed_at' => $saleOrder->delivery_confirmed_at->format('Y-m-d H:i:s'),
                'scan_id' => $scanRecord->id
            ], 'تم تأكيد التوصيل بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Delivery confirmation failed: " . $e->getMessage(), [
                'barcode' => substr($barcode, 0, 20) . '...',
                'user_id' => auth()->id()
            ]);
            
            return $this->failureResponse('فشل في تأكيد التوصيل: ' . $e->getMessage());
        }
    }

    /**
     * التحقق من صحة عملية تأكيد التوصيل
     */
    private function validateDeliveryConfirmation(SaleOrder $saleOrder, array $data): array
    {
        // التحقق من حالة الطلب
        if ($saleOrder->order_status === 'POD') {
            return [
                'valid' => false,
                'message' => 'تم تأكيد استلام هذا الطلب مسبقاً في ' . $saleOrder->delivery_confirmed_at?->format('Y-m-d H:i:s')
            ];
        }

        if ($saleOrder->order_status !== 'Delivery') {
            return [
                'valid' => false,
                'message' => "الطلب في حالة '{$saleOrder->order_status}' وليس جاهز للتوصيل"
            ];
        }

        // التحقق من صلاحيات المستخدم
        if (!$this->validateDeliveryUser(auth()->user(), $saleOrder)) {
            return [
                'valid' => false,
                'message' => 'ليس لديك صلاحية لتأكيد هذا الطلب'
            ];
        }

        // التحقق من عدم وجود مسح مسبق ناجح
        $existingScan = $saleOrder->barcodeScans()->successful()->first();
        if ($existingScan) {
            return [
                'valid' => false,
                'message' => 'تم مسح باركود هذا الطلب مسبقاً'
            ];
        }

        return ['valid' => true];
    }

    /**
     * التحقق من صلاحيات مستخدم التوصيل
     */
    private function validateDeliveryUser(?User $user, SaleOrder $saleOrder): bool
    {
        if (!$user) {
            return false;
        }

        // التحقق من دور المستخدم
        if (!$user->role || !in_array(strtolower($user->role->name), ['delivery', 'admin'])) {
            return false;
        }

        // إذا كان المستخدم مرتبط بناقل، تحقق من تطابق الناقل
        if ($user->carrier_id && $saleOrder->carrier_id) {
            return $user->carrier_id === $saleOrder->carrier_id;
        }

        return true;
    }

    /**
     * تسجيل عملية مسح الباركود
     */
    private function recordBarcodeScan(SaleOrder $saleOrder, string $barcode, array $data): BarcodeScan
    {
        return BarcodeScan::create([
            'sale_order_id' => $saleOrder->id,
            'scanned_by' => auth()->id(),
            'scan_timestamp' => now(),
            'scan_location_lat' => $data['location_lat'] ?? null,
            'scan_location_lng' => $data['location_lng'] ?? null,
            'device_info' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'app_version' => $data['app_version'] ?? null,
                'device_model' => $data['device_model'] ?? null,
            ],
            'status' => 'success',
            'notes' => $data['notes'] ?? null,
            'scan_method' => $data['scan_method'] ?? 'mobile_app'
        ]);
    }

    /**
     * تنسيق معلومات الموقع
     */
    private function formatLocation(array $data): ?string
    {
        if (isset($data['location_lat']) && isset($data['location_lng'])) {
            return "{$data['location_lat']}, {$data['location_lng']}";
        }
        return null;
    }

    /**
     * استجابة النجاح
     */
    private function successResponse(array $data, string $message): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * استجابة الفشل
     */
    private function failureResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null
        ];
    }

    /**
     * الحصول على إحصائيات المسح للطلب
     */
    public function getScanStatistics(int $orderId): array
    {
        $saleOrder = SaleOrder::with('barcodeScans')->find($orderId);
        
        if (!$saleOrder) {
            return [];
        }

        $scans = $saleOrder->barcodeScans;
        
        return [
            'total_scans' => $scans->count(),
            'successful_scans' => $scans->where('status', 'success')->count(),
            'failed_scans' => $scans->where('status', 'failed')->count(),
            'last_scan' => $scans->sortByDesc('scan_timestamp')->first()?->scan_timestamp,
            'first_scan' => $scans->sortBy('scan_timestamp')->first()?->scan_timestamp,
        ];
    }
}
```

## 4. ملف التكوين

```php
<?php
// config/barcode.php

return [
    /*
    |--------------------------------------------------------------------------
    | Barcode Configuration
    |--------------------------------------------------------------------------
    */

    // مدة صلاحية الباركود بالأيام (0 = بدون انتهاء صلاحية)
    'expiration_days' => env('BARCODE_EXPIRATION_DAYS', 30),

    // نوع الباركود المستخدم للطباعة
    'print_type' => env('BARCODE_PRINT_TYPE', 'CODE128'),

    // حجم QR Code
    'qr_size' => env('BARCODE_QR_SIZE', 200),

    // هامش QR Code
    'qr_margin' => env('BARCODE_QR_MARGIN', 10),

    // تفعيل تسجيل الموقع الجغرافي
    'enable_location_tracking' => env('BARCODE_ENABLE_LOCATION', true),

    // المسافة المسموحة للمسح (بالكيلومتر) - 0 = بدون قيود
    'max_scan_distance' => env('BARCODE_MAX_SCAN_DISTANCE', 0),

    // السماح بالمسح المتكرر
    'allow_multiple_scans' => env('BARCODE_ALLOW_MULTIPLE_SCANS', false),

    // تفعيل انتهاء صلاحية الباركود
    'enable_expiration' => env('BARCODE_ENABLE_EXPIRATION', false),
];
```

هذا الدليل يوفر الكود الكامل والتفصيلي لتنفيذ نظام الباركود. في الجزء التالي سأكمل بـ Controllers و API endpoints.
