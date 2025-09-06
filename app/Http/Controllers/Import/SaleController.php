<?php

namespace App\Http\Controllers\Import;

use App\Enums\App;
use App\Enums\ItemTransactionUniqueCode;
use App\Http\Controllers\Controller;
use App\Models\Party\Party;
use App\Services\AccountTransactionService;
use App\Services\Communication\Email\SaleOrderEmailNotificationService;
use App\Services\Communication\Sms\SaleOrderSmsNotificationService;
use App\Services\GeneralDataService;
use App\Services\ItemTransactionService;
use App\Services\PaymentTransactionService;
use App\Services\PaymentTypeService;
use App\Services\StatusHistoryService;
use App\Traits\FormatNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\SaleOrder;
use App\Models\Items\Item;

//use Maatwebsite\Excel\Facades\Excel;
//use App\Imports\SaleOrderImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    use FormatNumber;
    public $reader;
    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    public $saleOrderEmailNotificationService;

    public $saleOrderSmsNotificationService;

    public $generalDataService;

    public $statusHistoryService;

    public function __construct(
        Xlsx                 $reader,
        PaymentTypeService                $paymentTypeService,
        PaymentTransactionService         $paymentTransactionService,
        AccountTransactionService         $accountTransactionService,
        ItemTransactionService            $itemTransactionService,
        SaleOrderEmailNotificationService $saleOrderEmailNotificationService,
        SaleOrderSmsNotificationService   $saleOrderSmsNotificationService,
        GeneralDataService                $generalDataService,
        StatusHistoryService              $statusHistoryService
    ) {
        $this->reader = $reader;
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->saleOrderEmailNotificationService = $saleOrderEmailNotificationService;
        $this->saleOrderSmsNotificationService = $saleOrderSmsNotificationService;
        $this->generalDataService = $generalDataService;
        $this->statusHistoryService = $statusHistoryService;
    }

    /**
     * Display the import sale form
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('import.sale');
    }

    /**
     * Display the import purchase form
     *
     * @return \Illuminate\View\View
     */
    public function purchaseIndex()
    {
        return view('import.purchase');
    }

    public function saleReturnIndex()
    {
        return view('import.sale-return');
    }

    /**
     * Import sales from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function store(Request $request)
    // {
    //     $file = $request->file('excel_file');

    //     $spreadsheet = $this->reader->load($file->getPathname());

    //     // Get the first sheet
    //     $sheet = $spreadsheet->getSheet(0);
    //     $data_list = $sheet->toArray();

    //     try {
    //         DB::beginTransaction();

    //         if (count($data_list) <= 1) {
    //             throw new \Exception(__('app.records_not_found'));
    //         }

    //         // Skip header row
    //         array_shift($data_list);

    //         $partyCurrency = Party::with('currency')->select('currency_id')
    //             ->where('party_type', 'customer')
    //             ->first();

    //         if (!$partyCurrency) {
    //             throw new \Exception(__('app.customer_not_found'));
    //         }

    //         $currency_id = $partyCurrency->currency_id;
    //         $exchange_rate = $partyCurrency->currency->exchange_rate;

    //         $saleOrder = new SaleOrder();
    //         $saleOrder->party_id = 1; // Default customer
    //         $saleOrder->order_date = Carbon::now();
    //         $saleOrder->prefix_code = 'SO/';
    //         $saleOrder->count_id = SaleOrder::max('count_id') + 1;
    //         $saleOrder->order_code = $saleOrder->prefix_code . $saleOrder->count_id;
    //         $saleOrder->order_status = 'Pending';
    //         $saleOrder->round_off = 0;
    //         $saleOrder->grand_total = 0;
    //         $saleOrder->currency_id = $currency_id;
    //         $saleOrder->exchange_rate = $exchange_rate;
    //         $saleOrder->paid_amount = 0; // Set paid amount to 0 initially
    //         $saleOrder->save();

    //         $warehouse_id = $request->warehouse_id;

    //         /**
    //          * Record Status Update History
    //          */
    //         $this->statusHistoryService->RecordStatusHistory($saleOrder);

    //         /**
    //          * Save Table Items in Sale Order Items Table
    //          * */
    //         $saleOrderItemsArray = $this->saveSaleOrderItems($saleOrder, $data_list, $warehouse_id);
    //         if (!$saleOrderItemsArray['status']) {
    //             throw new \Exception($saleOrderItemsArray['message']);
    //         }

    //         // Update sale order grand total
    //         $saleOrder->grand_total = $saleOrderItemsArray['grand_total'];
    //         $saleOrder->save();

    //         // Removed automatic payment creation
    //         // No longer calling saveSaleOrderPayments

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => __('app.record_imported'),
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 422);
    //     }
    // }
    public function store(Request $request)
{
    $file = $request->file('excel_file');

    // التحقق من وجود الملف
    if (!$file) {
        return response()->json([
            'success' => false,
            'message' => __('app.no_file_uploaded')
        ], 422);
    }

    // التحقق من نوع الملف
    if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
        return response()->json([
            'success' => false,
            'message' => __('app.invalid_file_type')
        ], 422);
    }

    try {
        $spreadsheet = $this->reader->load($file->getPathname());
        $sheet = $spreadsheet->getSheet(0);
        $data_list = $sheet->toArray();

        DB::beginTransaction();

        if (count($data_list) <= 1) {
            throw new \Exception(__('app.records_not_found'));
        }

        // تخطي صف العناوين
        array_shift($data_list);

        // التحقق من وجود بيانات وصحة في الملف
        foreach ($data_list as $index => $row) {
            if (count($row) < 2) {
                throw new \Exception(__('app.invalid_file_format') . ' - الصف ' . ($index + 2) . ' يحتوي على بيانات ناقصة');
            }
            if (empty(trim($row[0])) || empty(trim($row[1]))) {
                throw new \Exception(__('app.invalid_file_format') . ' - الصف ' . ($index + 2) . ' يحتوي على بيانات فارغة');
            }
        }

        // الحصول على معلومات العملة من العميل الافتراضي
        $partyCurrency = Party::with('currency')
            ->where('party_type', 'customer')
            ->first();

        if (!$partyCurrency) {
            throw new \Exception(__('app.customer_not_found'));
        }

        // إنشاء أمر البيع مع ضمان عدم تكرار order_code
        $saleOrder = new SaleOrder();
        $saleOrder->party_id = 1; // استخدام معرف العميل الافتراضي
        $saleOrder->order_date = Carbon::now();
        $saleOrder->prefix_code = 'SO/';
        $saleOrder->order_status = 'Pending';
        $saleOrder->round_off = 0;
        $saleOrder->grand_total = 0;
        $saleOrder->currency_id = $partyCurrency->currency_id;
        $saleOrder->exchange_rate = $partyCurrency->currency->exchange_rate;
        $saleOrder->paid_amount = 0; // تأكد من أن المبلغ المدفوع يبدأ بـ 0

        // حفظ السجل أولاً للحصول على معرف فريد
        $saleOrder->save();

        // الآن نحدد count_id بناءً على ID الفريد
        $saleOrder->count_id = $saleOrder->id;
        $saleOrder->order_code = $saleOrder->prefix_code . $saleOrder->count_id;
        $saleOrder->save();

        $warehouse_id = $request->warehouse_id;

        // تسجيل تاريخ الحالة
        $this->statusHistoryService->RecordStatusHistory($saleOrder);

        // حفظ عناصر أمر البيع
        $saleOrderItemsArray = $this->saveSaleOrderItems($saleOrder, $data_list, $warehouse_id);
        if (!$saleOrderItemsArray['status']) {
            throw new \Exception($saleOrderItemsArray['message']);
        }

        // تحديث المبلغ الإجمالي فقط - لا تقم بإضافة مدفوعات تلقائية
        $saleOrder->grand_total = $saleOrderItemsArray['grand_total'];
        // التأكد من عدم وجود مدفوعات تلقائية
        $saleOrder->paid_amount = 0;
        $saleOrder->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => __('app.record_imported'),
            'order_code' => $saleOrder->order_code // إرجاع كود الطلب للمرجعية
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

    public function saveSaleOrderItems($order, $data_list, $warehouse_id)
    {
        $grandTotal = 0;

        foreach ($data_list as $row) {
            $sku = trim($row[0]);
            $quantity = trim($row[1]);

            // Find item by SKU
            $itemDetails = Item::where('sku', $sku)->first();
            if (!$itemDetails) {
                return [
                    'status' => false,
                    'message' => __('item.item_not_found', ['sku' => $sku])
                ];
            }

            $itemName = $itemDetails->name;

            // Improved quantity validation
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                return [
                    'status' => false,
                    'message' => __('item.please_enter_item_quantity', ['item_name' => $itemName]) . ' - الكمية: ' . $quantity,
                ];
            }

            // Calculate item total
            $unitPrice = $itemDetails->sale_price;
            $total = $quantity * $unitPrice;
            $grandTotal += $total;

            /**
             * Item Transaction Entry
             */
            $transaction = $this->itemTransactionService->recordItemTransactionEntry($order, [
                'warehouse_id' => $warehouse_id, // Default warehouse
                'transaction_date' => date('Y-m-d'),
                'item_id' => $itemDetails->id,
                'description' => '',
                'tracking_type' => $itemDetails->tracking_type,
                'input_quantity' => $quantity,
                'quantity' => $quantity, // Fix: Use actual quantity instead of 0
                'unit_id' => 3,
                'unit_price' => $unitPrice,
                'mrp' => $itemDetails->mrp ?? 0,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
                'tax_id' => $itemDetails->tax_id ?? 1,
                'tax_type' => 'exclusive',
                'tax_amount' => 0,
                'total' => $total,
            ]);

            if (!$transaction) {
                return [
                    'status' => false,
                    'message' => __('item.failed_to_record_transaction')
                ];
            }
            //return $transaction;
            if (!$transaction) {
                return [
                    'status' => false,
                    'message' => __('item.failed_to_record_transaction')
                ];
            }

            /**
             * Tracking Type:
             * regular
             * batch
             * serial
             * */
            if ($itemDetails->tracking_type == 'serial') {
                //Serial validate and insert records
                if ($quantity > 0) {
                    $serialArray = [
                        'serial_code' => "",
                    ];

                    $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $itemDetails->id, $warehouse_id, ItemTransactionUniqueCode::SALE_ORDER->value);

                    if (!$serialTransaction) {
                        throw new \Exception(__('item.failed_to_save_serials'));
                    }
                }
            } else if ($itemDetails->tracking_type == 'batch') {
                //                Serial validate and insert records
                if ($quantity > 0) {

                    $batchArray = [
                        'batch_no' => null,
                        'mfg_date' => null,
                        'exp_date' => null,
                        'model_no' => "",
                        'mrp' =>  0,
                        'color' => null,
                        'size' => null,
                        'quantity' => $quantity,
                    ];

                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $itemDetails->id, $warehouse_id, ItemTransactionUniqueCode::SALE_ORDER->value);

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            } else {
                //Regular item transaction entry already done before if() condition
            }
        } //for end

        return [
            'status' => true,
            'grand_total' => $grandTotal
        ];
    }

    public function saveSaleOrderPayments($order)
    {
        $paymentsArray = [
            'transaction_date' => date('Y-m-d'),
            'amount' => $order->grand_total,
            'payment_type_id' => 1, //cash
            'note' => "",
        ];

        if (!$transaction = $this->paymentTransactionService->recordPayment($order, $paymentsArray)) {
            throw new \Exception(__('payment.failed_to_record_payment_transactions'));
        }

        return ['status' => true];
    }

    public function savePurchaseOrderItems($order, $data_list, $warehouse_id)
    {
        $grandTotal = 0;

        foreach ($data_list as $row) {
            $sku = trim($row[0]);
            $quantity = trim($row[1]);

            // Find item by SKU
            $itemDetails = Item::where('sku', $sku)->first();
            if (!$itemDetails) {
                return [
                    'status' => false,
                    'message' => __('item.item_not_found', ['sku' => $sku])
                ];
            }

            $itemName = $itemDetails->name;

            // Validate quantity
            if (empty($quantity) || $quantity === 0 || $quantity < 0) {
                return [
                    'status' => false,
                    'message' => ($quantity < 0) ? __('item.item_qty_negative', ['item_name' => $itemName]) : __('item.please_enter_item_quantity', ['item_name' => $itemName]),
                ];
            }

            // Calculate item total
            $unitPrice = $itemDetails->purchase_price;
            $total = $quantity * $unitPrice;
            $grandTotal += $total;

            /**
             * Item Transaction Entry
             */
            $transaction = $this->itemTransactionService->recordItemTransactionEntry($order, [
                'warehouse_id' => $warehouse_id, // Default warehouse
                'transaction_date' => date('Y-m-d'),
                'item_id' => $itemDetails->id,
                'description' => '',
                'tracking_type' => $itemDetails->tracking_type,
                'input_quantity' => $quantity,
                'quantity' => $quantity, // Fix: Use actual quantity instead of 0
                'unit_id' => 3,
                'unit_price' => $unitPrice,
                'mrp' => $itemDetails->mrp ?? 0,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
                'tax_id' => $itemDetails->tax_id ?? 1,
                'tax_type' => 'exclusive',
                'tax_amount' => 0,
                'total' => $total,
            ]);

            if (!$transaction) {
                return [
                    'status' => false,
                    'message' => __('item.failed_to_record_transaction')
                ];
            }

            /**
             * Tracking Type:
             * regular
             * batch
             * serial
             * */
            if ($itemDetails->tracking_type == 'serial') {
                //Serial validate and insert records
                if ($quantity > 0) {
                    $serialArray = [
                        'serial_code' => "",
                    ];

                    $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $itemDetails->id, $warehouse_id, ItemTransactionUniqueCode::PURCHASE_ORDER->value);

                    if (!$serialTransaction) {
                        throw new \Exception(__('item.failed_to_save_serials'));
                    }
                }
            } else if ($itemDetails->tracking_type == 'batch') {
                // Batch validate and insert records
                if ($quantity > 0) {
                    $batchArray = [
                        'batch_no' => null,
                        'mfg_date' => null,
                        'exp_date' => null,
                        'model_no' => "",
                        'mrp' =>  0,
                        'color' => null,
                        'size' => null,
                        'quantity' => $quantity,
                    ];

                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $itemDetails->id, $warehouse_id, ItemTransactionUniqueCode::PURCHASE_ORDER->value);

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }
                }
            } else {
                //Regular item transaction entry already done before if() condition
            }
        } //for end

        return [
            'status' => true,
            'grand_total' => $grandTotal
        ];
    }

    public function savePurchaseOrderPayments($order)
    {
        $paymentsArray = [
            'transaction_date' => date('Y-m-d'),
            'amount' => $order->grand_total, // No payment initially for purchase orders
            'payment_type_id' => 1, //cash
            'note' => "",
        ];

        if (!$transaction = $this->paymentTransactionService->recordPayment($order, $paymentsArray)) {
            throw new \Exception(__('payment.failed_to_record_payment_transactions'));
        }

        return ['status' => true];
    }


    /**
     * Import purchases from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function purchaseStore(Request $request)
    {
        $file = $request->file('excel_file');

        $spreadsheet = $this->reader->load($file->getPathname());

        // Get the first sheet
        $sheet = $spreadsheet->getSheet(0);
        $data_list = $sheet->toArray();

        try {
            DB::beginTransaction();

            if (count($data_list) <= 1) {
                throw new \Exception(__('app.records_not_found'));
            }

            // Skip header row
            array_shift($data_list);

            $partyCurrency = Party::with('currency')->select('currency_id')
                ->where('party_type', 'supplier')
                ->first();

            if (!$partyCurrency) {
                throw new \Exception(__('app.supplier_not_found'));
            }

            $currency_id = $partyCurrency->currency_id;
            $exchange_rate = $partyCurrency->currency->exchange_rate;

            // Get the last count_id
//            $lastCountId = DB::table('purchase_orders')->max('count_id') ?? 0;
            $lastCountId = DB::table('purchase_orders')
                ->where('order_type', 'purchase')
                ->max('count_id') ?? 0;

            $purchaseOrder = new \App\Models\Purchase\PurchaseOrder();
            $purchaseOrder->party_id = 2; // Default supplier
            $purchaseOrder->order_date = Carbon::now();
            $purchaseOrder->prefix_code = 'PO/';
            $purchaseOrder->order_code = $purchaseOrder->prefix_code . $purchaseOrder->count_id;
            $purchaseOrder->order_status = 'Pending';
            $purchaseOrder->round_off = 0;
            $purchaseOrder->grand_total = 0;
            $purchaseOrder->currency_id = $currency_id;
            $purchaseOrder->exchange_rate = $exchange_rate;
            $purchaseOrder->save();
            $purchaseOrder->count_id = $purchaseOrder->id;
            $purchaseOrder->order_code = $purchaseOrder->prefix_code . $purchaseOrder->count_id;

            $warehouse_id = $request->warehouse_id;

            /**
             * Record Status Update History
             */
            $this->statusHistoryService->RecordStatusHistory($purchaseOrder);

            /**
             * Save Table Items in Purchase Order Items Table
             * */
            $purchaseOrderItemsArray = $this->savePurchaseOrderItems($purchaseOrder, $data_list, $warehouse_id);
            if (!$purchaseOrderItemsArray['status']) {
                throw new \Exception($purchaseOrderItemsArray['message']);
            }

            // Update purchase order grand total
            $purchaseOrder->grand_total = $purchaseOrderItemsArray['grand_total'];
            $purchaseOrder->paid_amount = 0; // Set paid amount to 0 initially
            $purchaseOrder->save();

            // Removed automatic payment creation
            // No longer calling savePurchaseOrderPayments

            /**
             * Update Account Transaction entry
             * */
            $accountTransactionStatus = $this->accountTransactionService->purchaseOrderAccountTransaction($purchaseOrder);
            if (!$accountTransactionStatus) {
                throw new \Exception(__('payment.failed_to_update_account'));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('purchase.import_success'),
                'id' => $purchaseOrder->id
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Import purchases from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
//     public function saleReturnStore(Request $request)
//     {
//         $file = $request->file('excel_file');

//         $spreadsheet = $this->reader->load($file->getPathname());

//         // Get the first sheet
//         $sheet = $spreadsheet->getSheet(0);
//         $data_list = $sheet->toArray();

//         try {
//             DB::beginTransaction();

//             if (count($data_list) <= 1) {
//                 throw new \Exception(__('app.records_not_found'));
//             }

//             // Skip header row
//             array_shift($data_list);

//             $partyCurrency = Party::with('currency')->select('currency_id')
//                 ->where('party_type', 'supplier')
//                 ->first();

//             if (!$partyCurrency) {
//                 throw new \Exception(__('app.supplier_not_found'));
//             }

//             $currency_id = $partyCurrency->currency_id;
//             $exchange_rate = $partyCurrency->currency->exchange_rate;

//             // Get the last count_id
// //            $lastCountId = DB::table('purchase_orders')->max('count_id') ?? 0;
//             $lastCountId = DB::table('purchase_orders')
//                 ->where('order_type', 'return')
//                 ->max('count_id') ?? 0;

//             $purchaseOrder = new \App\Models\Purchase\PurchaseOrder();
//             $purchaseOrder->party_id = 2; // Default supplier
//             $purchaseOrder->order_date = Carbon::now();
//             $purchaseOrder->prefix_code = 'SO/';
//             $purchaseOrder->count_id = $lastCountId + 1;
//             $purchaseOrder->order_code = $purchaseOrder->prefix_code . $purchaseOrder->count_id;
//             $purchaseOrder->order_status = 'Pending';
//             $purchaseOrder->round_off = 0;
//             $purchaseOrder->grand_total = 0;
//             $purchaseOrder->currency_id = $currency_id;
//             $purchaseOrder->exchange_rate = $exchange_rate;
//             $purchaseOrder->order_type = 'return';
//             $purchaseOrder->save();

//             $warehouse_id = $request->warehouse_id;

//             /**
//              * Record Status Update History
//              */
//             $this->statusHistoryService->RecordStatusHistory($purchaseOrder);

//             /**
//              * Save Table Items in Purchase Order Items Table
//              * */
//             $purchaseOrderItemsArray = $this->savePurchaseOrderItems($purchaseOrder, $data_list, $warehouse_id);
//             if (!$purchaseOrderItemsArray['status']) {
//                 throw new \Exception($purchaseOrderItemsArray['message']);
//             }

//             // Update purchase order grand total
//             $purchaseOrder->grand_total = $purchaseOrderItemsArray['grand_total'];
//             $purchaseOrder->save();

//             /**
//              * Save Purchase Order Payment Records
//              * */
//             $purchaseOrderPaymentsArray = $this->savePurchaseOrderPayments($purchaseOrder);
//             if (!$purchaseOrderPaymentsArray['status']) {
//                 throw new \Exception($purchaseOrderPaymentsArray['message']);
//             }

//             /**
//              * Payment Should not be less than 0
//              * */
//             $paidAmount = $purchaseOrder->refresh('paymentTransaction')->paymentTransaction->sum('amount');
//             if ($paidAmount < 0) {
//                 throw new \Exception(__('payment.paid_amount_should_not_be_less_than_zero'));
//             }

//             /**
//              * Paid amount should not be greater than grand total
//              * */
//             if ($paidAmount > $purchaseOrder->grand_total) {
//                 throw new \Exception(__('payment.payment_should_not_be_greater_than_grand_total') . "<br>Paid Amount : " . $this->formatWithPrecision($paidAmount) . "<br>Grand Total : " . $this->formatWithPrecision($purchaseOrder->grand_total) . "<br>Difference : " . $this->formatWithPrecision($paidAmount - $purchaseOrder->grand_total));
//             }

//             /**
//              * Update Purchase Order Model
//              * Total Paid Amount
//              * */
//             if (!$this->paymentTransactionService->updateTotalPaidAmountInModel($purchaseOrder)) {
//                 throw new \Exception(__('payment.failed_to_update_paid_amount'));
//             }

//             /**
//              * Update Account Transaction entry
//              * */
//             $accountTransactionStatus = $this->accountTransactionService->purchaseOrderAccountTransaction($purchaseOrder);
//             if (!$accountTransactionStatus) {
//                 throw new \Exception(__('payment.failed_to_update_account'));
//             }

//             DB::commit();

//             return response()->json([
//                 'status' => true,
//                 'message' => __('purchase.import_success'),
//                 'id' => $purchaseOrder->id
//             ]);
//         } catch (\Exception $e) {
//             DB::rollback();
//             return response()->json([
//                 'status' => false,
//                 'message' => $e->getMessage()
//             ], 422);
//         }
//     }
public function saleReturnStore(Request $request)
{
    $file = $request->file('excel_file');

    $spreadsheet = $this->reader->load($file->getPathname());

    // Get the first sheet
    $sheet = $spreadsheet->getSheet(0);
    $data_list = $sheet->toArray();

    try {
        DB::beginTransaction();

        if (count($data_list) <= 1) {
            throw new \Exception(__('app.records_not_found'));
        }

        // Skip header row
        array_shift($data_list);

        $partyCurrency = Party::with('currency')->select('currency_id')
            ->where('party_type', 'supplier')
            ->first();

        if (!$partyCurrency) {
            throw new \Exception(__('app.supplier_not_found'));
        }

        $currency_id = $partyCurrency->currency_id;
        $exchange_rate = $partyCurrency->currency->exchange_rate;

        $lastCountId = DB::table('purchase_orders')
            ->where('order_type', 'return')
            ->max('count_id') ?? 0;

        $purchaseOrder = new \App\Models\Purchase\PurchaseOrder();
        $purchaseOrder->party_id = 2; // Default supplier
        $purchaseOrder->order_date = Carbon::now();
        $purchaseOrder->prefix_code = 'SR/';
        $purchaseOrder->count_id = $lastCountId + 1;
        $purchaseOrder->order_code = $purchaseOrder->prefix_code . $purchaseOrder->count_id;
        $purchaseOrder->order_status = 'Pending';
        $purchaseOrder->round_off = 0;
        $purchaseOrder->grand_total = 0;
        $purchaseOrder->currency_id = $currency_id;
        $purchaseOrder->exchange_rate = $exchange_rate;
        $purchaseOrder->order_type = 'return';
        $purchaseOrder->save();

        $warehouse_id = $request->warehouse_id;

        /**
         * Record Status Update History
         */
        $this->statusHistoryService->RecordStatusHistory($purchaseOrder);

        /**
         * Save Table Items in Purchase Order Items Table
         */
        $purchaseOrderItemsArray = $this->savePurchaseOrderItems($purchaseOrder, $data_list, $warehouse_id);
        if (!$purchaseOrderItemsArray['status']) {
            throw new \Exception($purchaseOrderItemsArray['message']);
        }

        // Update purchase order grand total only
        $purchaseOrder->grand_total = $purchaseOrderItemsArray['grand_total'];
        $purchaseOrder->save();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => __('purchase.import_success'),
            'id' => $purchaseOrder->id
        ]);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

    /**
     * Download sample import file for sales
     *
     * @return StreamedResponse
     */
    public function downloadSample()
    {
        $filePath = 'public/download-sheet/sale_import_sample.xlsx';

        if (Storage::exists($filePath)) {
            return Storage::download($filePath, 'sale_import_sample.xlsx');
        }
        abort(404);
    }

    /**
     * Download sample import file for purchases
     *
     * @return StreamedResponse
     */
    public function downloadPurchaseSample()
    {
        //        purchase_import_sample
        $filePath = 'public/download-sheet/purchase_import_sample.xlsx';

        if (Storage::exists($filePath)) {
            return Storage::download($filePath, 'purchase_import_sample.xlsx');
        }
        abort(404);
    }

    /**
     * Download sample import file for purchases
     *
     * @return StreamedResponse
     */
    public function downloadSaleReturnSample()
    {
        //        purchase_import_sample
        $filePath = 'public/download-sheet/sale_return_import_sample.xlsx';

        if (Storage::exists($filePath)) {
            return Storage::download($filePath, 'sale_return_import_sample.xlsx');
        }
        abort(404);
    }
}
