<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Items\Item;
use App\Services\ItemTransactionService;
use App\Services\ItemService;
use App\Services\AccountTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

class ItemController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    protected $itemTransactionService;
    protected $itemService;
    protected $accountTransactionService;
    protected $previousHistoryOfItems;

    public function __construct(
        ItemTransactionService $itemTransactionService,
        ItemService $itemService,
        AccountTransactionService $accountTransactionService
    ) {
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->accountTransactionService = $accountTransactionService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * إضافة منتج جديد عبر API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // التحقق من البيانات
            $validator = Validator::make($request->all(), [
                'is_service' => 'required|boolean',
                'item_code' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'hsn' => 'nullable|string|max:50',
                'sku' => 'nullable|string|max:50',
                'item_category_id' => 'required|exists:item_categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'asin' => 'nullable|string|max:50',
                'weight' => 'nullable|numeric',
                'volume' => 'nullable|numeric',
                'image_url' => 'nullable|string',
                'cust_num' => 'nullable|string|max:50',
                'cust_num_t' => 'nullable|string|max:50',
                'cargo_fee' => 'nullable|numeric',
                'base_unit_id' => 'required|exists:units,id',
                'secondary_unit_id' => 'nullable|exists:units,id',
                'conversion_rate' => 'nullable|numeric|min:0',
                'sale_price' => 'required|numeric|min:0',
                'is_sale_price_with_tax' => 'required|boolean',
                'sale_price_discount' => 'nullable|numeric|min:0',
                'sale_price_discount_type' => 'nullable|in:percentage,fixed',
                'purchase_price' => 'required|numeric|min:0',
                'is_purchase_price_with_tax' => 'required|boolean',
                'tax_id' => 'nullable|exists:taxes,id',
                'wholesale_price' => 'nullable|numeric|min:0',
                'is_wholesale_price_with_tax' => 'nullable|boolean',
                'profit_margin' => 'nullable|numeric',
                'mrp' => 'nullable|numeric|min:0',
                'msp' => 'nullable|numeric|min:0',
                'tracking_type' => 'required|in:regular,batch,serial',
                'min_stock' => 'nullable|numeric|min:0',
                'item_location' => 'nullable|string|max:255',
                'status' => 'required|boolean',
                'is_damaged' => 'nullable|boolean',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'opening_quantity' => 'nullable|numeric|min:0',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'at_price' => 'nullable|numeric|min:0',
                'transaction_date' => 'nullable|date',
                'serial_number_json' => 'nullable|json',
                'batch_details_json' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filename = null;

            // معالجة الصورة إذا تم تحميلها
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $filename = $this->uploadImage($request->file('image'));
            }

            // إعداد بيانات المنتج للحفظ
            $recordsToSave = [
                'is_service' => $request->is_service,
                'item_code' => $request->item_code,
                'name' => $request->name,
                'description' => $request->description,
                'hsn' => $request->hsn,
                'sku' => $request->sku,
                'item_category_id' => $request->item_category_id,
                'brand_id' => $request->brand_id,
                'asin' => $request->asin,
                'weight' => $request->weight,
                'volume' => $request->volume,
                'image_url' => $request->image_url,
                'cust_num' => $request->cust_num,
                'cust_num_t' => $request->cust_num_t,
                'cargo_fee' => $request->cargo_fee,
                'base_unit_id' => $request->base_unit_id,
                'secondary_unit_id' => $request->secondary_unit_id,
                'conversion_rate' => ($request->base_unit_id == $request->secondary_unit_id) ? 1 : $request->conversion_rate,
                'sale_price' => $request->sale_price,
                'is_sale_price_with_tax' => $request->is_sale_price_with_tax,
                'sale_price_discount' => $request->sale_price_discount,
                'sale_price_discount_type' => $request->sale_price_discount_type,
                'purchase_price' => $request->purchase_price,
                'is_purchase_price_with_tax' => $request->is_purchase_price_with_tax,
                'tax_id' => $request->tax_id,
                'wholesale_price' => $request->wholesale_price,
                'is_wholesale_price_with_tax' => $request->is_wholesale_price_with_tax,
                'profit_margin' => $request->profit_margin,
                'mrp' => $request->mrp,
                'msp' => $request->msp,
                'tracking_type' => $request->tracking_type,
                'min_stock' => $request->min_stock,
                'item_location' => $request->item_location,
                'status' => $request->status,
                'is_damaged' => $request->has('is_damaged') ? 1 : 0,
                'count_id' => $this->getLastCountId() + 1,
                'image_path' => $filename,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            // إنشاء المنتج
            $itemModel = Item::create($recordsToSave);
            $request->request->add(['itemModel' => $itemModel]);

            // معالجة تتبع المنتج (عادي، دفعات، أرقام تسلسلية)
            if ($request->tracking_type == 'serial' && $request->opening_quantity > 0) {
                // معالجة الأرقام التسلسلية
                $jsonSerials = $request->serial_number_json;
                $jsonSerialsDecode = json_decode($jsonSerials);

                // التحقق من تطابق عدد الأرقام التسلسلية مع الكمية المدخلة
                $countRecords = (!empty($jsonSerialsDecode)) ? count($jsonSerialsDecode) : 0;
                if ($countRecords != $request->opening_quantity) {
                    throw new \Exception(__('item.opening_quantity_not_matched_with_serial_records'));
                }

                // تسجيل معاملة المنتج
                if (!$transaction = $this->recordInItemTransactionEntry($request)) {
                    throw new \Exception(__('item.failed_to_record_item_transactions'));
                }

                // تسجيل الأرقام التسلسلية
                foreach ($jsonSerialsDecode as $serialNumber) {
                    $serialArray = [
                        'serial_code' => $serialNumber,
                    ];

                    $serialTransaction = $this->itemTransactionService->recordItemSerials(
                        $transaction->id,
                        $serialArray,
                        $request->itemModel->id,
                        $request->warehouse_id,
                        ItemTransactionUniqueCode::ITEM_OPENING->value
                    );

                    if (!$serialTransaction) {
                        throw new \Exception(__('item.failed_to_save_serials'));
                    }
                }
            } elseif ($request->tracking_type == 'batch' && $request->opening_quantity > 0) {
                // معالجة الدفعات
                $jsonBatches = $request->batch_details_json;
                $jsonBatchDecode = json_decode($jsonBatches);

                // التحقق من تطابق مجموع كميات الدفعات مع الكمية المدخلة
                $totalOpeningQuantity = (!empty($jsonBatchDecode)) ? array_sum(array_column($jsonBatchDecode, 'openingQuantity')) : 0;
                if ($totalOpeningQuantity != $request->opening_quantity) {
                    throw new \Exception(__('item.opening_quantity_not_matched_with_batch_records'));
                }

                // تسجيل معاملة المنتج
                if (!$transaction = $this->recordInItemTransactionEntry($request)) {
                    throw new \Exception(__('item.failed_to_record_item_transactions'));
                }

                // تسجيل الدفعات
                foreach ($jsonBatchDecode as $batchRecord) {
                    $batchArray = [
                        'batch_no' => $batchRecord->batchNo,
                        'mfg_date' => $batchRecord->mfgDate ? $this->toSystemDateFormat($batchRecord->mfgDate) : null,
                        'exp_date' => $batchRecord->expDate ? $this->toSystemDateFormat($batchRecord->expDate) : null,
                        'model_no' => $batchRecord->modelNo,
                        'mrp' => $batchRecord->mrp ?? 0,
                        'color' => $batchRecord->color,
                        'size' => $batchRecord->size,
                        'quantity' => $batchRecord->openingQuantity,
                    ];

                    $batchTransaction = $this->itemTransactionService->recordItemBatches(
                        $transaction->id,
                        $batchArray,
                        $request->itemModel->id,
                        $request->warehouse_id,
                        ItemTransactionUniqueCode::ITEM_OPENING->value
                    );

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            } else {
                // منتج عادي
                if ($request->opening_quantity > 0) {
                    if (!$transaction = $this->recordInItemTransactionEntry($request)) {
                        throw new \Exception(__('item.failed_to_record_item_transactions'));
                    }
                }
            }

            // تحديث بيانات المخزون
            $this->itemTransactionService->updatePreviousHistoryOfItems($request->itemModel, $this->previousHistoryOfItems);

            // تحديث متوسط سعر الشراء
            $this->itemTransactionService->updateItemMasterAveragePurchasePrice([$request->itemModel->id]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'data' => [
                    'id' => $itemModel->id,
                    'name' => $itemModel->name,
                    'item_code' => $itemModel->item_code,
                    'sku' => $itemModel->sku,
                    'asin' => $itemModel->asin,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل معاملة المنتج
     *
     * @param Request $request
     * @return mixed
     */
    private function recordInItemTransactionEntry($request)
    {
        $itemModel = $request->itemModel;

        $transaction = $this->itemTransactionService->recordItemTransactionEntry($itemModel, [
            'item_id' => $itemModel->id,
            'transaction_date' => $request->transaction_date ?? now(),
            'warehouse_id' => $request->warehouse_id,
            'tracking_type' => $request->tracking_type,
            'mrp' => 0,
            'quantity' => $request->opening_quantity,
            'input_quantity' => $request->opening_quantity,
            'unit_id' => $request->base_unit_id,
            'unit_price' => $request->at_price ?? $request->purchase_price,
            'discount_type' => 'percentage',
            'tax_id' => $request->tax_id,
            'tax_type' => ($request->is_sale_price_with_tax) ? 'inclusive' : 'exclusive',
            'total' => $request->opening_quantity * ($request->at_price ?? $request->purchase_price),
        ]);

        return $transaction;
    }

    /**
     * رفع صورة المنتج
     *
     * @param $image
     * @return string
     */
    private function uploadImage($image): string
    {
        // إنشاء اسم فريد للصورة
        $random = uniqid();
        $filename = $random . '.' . $image->getClientOriginalExtension();
        $directory = 'images/items';

        // إنشاء المجلد إذا لم يكن موجودًا
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // حفظ الملف في مجلد 'items'
        Storage::disk('public')->putFileAs($directory, $image, $filename);

        // إنشاء صورة مصغرة
        $thumbnailDirectory = $directory . '/thumbnail';
        if (!Storage::disk('public')->exists($thumbnailDirectory)) {
            Storage::disk('public')->makeDirectory($thumbnailDirectory);
        }

        // تحميل الصورة
        $imagePath = Storage::disk('public')->path($directory . '/' . $filename);

        // مسار الصورة المصغرة
        $thumbnailPath = Storage::disk('public')->path($thumbnailDirectory . '/' . $filename);

        // إنشاء الصورة المصغرة
        $thumbImage = Image::load($imagePath)
            ->width(200)
            ->height(200)
            ->save($thumbnailPath);

        return $filename;
    }

    /**
     * الحصول على آخر معرف عداد
     *
     * @return int
     */
    private function getLastCountId(): int
    {
        return Item::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }
    public function index(Request $request)
    {
        // Get pagination parameters from request or use defaults
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $page = $request->input('page', 1);

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('status', 1)
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Display the specified item.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand', 'itemGeneralQuantities'])
                    ->find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found'
            ], 404);
        }

        // No longer adding image_url to item

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    /**
     * Get items by category with pagination.
     *
     * @param  int  $categoryId
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemsByCategory($categoryId, Request $request)
    {
        // Get pagination parameters from request or use defaults
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $page = $request->input('page', 1);

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit'])
                    ->where('item_category_id', $categoryId)
                    ->where('status', 1)
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Search items by name or code with pagination.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $perPage = $request->input('per_page', 20); // Default 20 items per page

        $items = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('status', 1)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('item_code', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    /**
     * Get item by SKU.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemBySKU($sku)
    {
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->where('sku', $sku)
                    ->where('status', 1)
                    ->first();

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found with this SKU'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'item_code' => 'string|max:100|unique:items,item_code,' . $id,
            'sku' => 'string|max:100|unique:items,sku,' . $id,
            'description' => 'nullable|string',
            'item_category_id' => 'exists:item_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'base_unit_id' => 'exists:units,id',
            'secondary_unit_id' => 'nullable|exists:units,id',
            'base_unit_multiplier' => 'nullable|numeric',
            'purchase_price' => 'numeric|min:0',
            'sale_price' => 'numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric',
            'alert_quantity' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'is_batch_tracking' => 'boolean',
            'is_serial_tracking' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($request->all());

        // Refresh the item with relationships
        $item = Item::with(['tax', 'baseUnit', 'secondaryUnit', 'category', 'brand'])
                    ->find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Item updated successfully',
            'data' => $item
        ]);
    }
    

}

