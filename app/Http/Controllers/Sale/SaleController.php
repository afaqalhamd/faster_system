<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Prefix;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\App;
use App\Enums\General;
use App\Services\PaymentTypeService;
use App\Services\GeneralDataService;
use App\Services\PaymentTransactionService;
use App\Http\Requests\SaleRequest;
use App\Services\AccountTransactionService;
use App\Services\ItemTransactionService;
use App\Models\Items\ItemSerial;
use AppModelsItems\ItemBatchTransaction;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\ItemService;
use App\Services\PartyService;
use App\Services\Communication\Email\SaleEmailNotificationService;
use App\Services\Communication\Sms\SaleSmsNotificationService;
use App\Services\SalesStatusService;
use App\Enums\ItemTransactionUniqueCode;
use App\Models\Sale\Quotation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Exception;


use Mpdf\Mpdf;

class SaleController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    private $itemService;

    private $partyService;

    public $previousHistoryOfItems;

    public $saleEmailNotificationService;

    public $saleSmsNotificationService;

    private $generalDataService;

    private $salesStatusService;

    public function __construct(PaymentTypeService           $paymentTypeService,
                                PaymentTransactionService    $paymentTransactionService,
                                AccountTransactionService    $accountTransactionService,
                                ItemTransactionService       $itemTransactionService,
                                ItemService                  $itemService,
                                PartyService                 $partyService,
                                SaleEmailNotificationService $saleEmailNotificationService,
                                SaleSmsNotificationService   $saleSmsNotificationService,
                                GeneralDataService           $generalDataService,
                                SalesStatusService           $salesStatusService
    )
    {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->paymentTypeService = $paymentTypeService;
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->partyService = $partyService;
        $this->saleEmailNotificationService = $saleEmailNotificationService;
        $this->saleSmsNotificationService = $saleSmsNotificationService;
        $this->generalDataService = $generalDataService;
        $this->salesStatusService = $salesStatusService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => $prefix->sale,
            'count_id' => ($lastCountId + 1),
        ];
        return view('sale.invoice.create', compact('data', 'selectedPaymentTypesArray'));
    }

    /**
     * Create a POS sale.
     *
     * @return \Illuminate\View\View
     */
    public function posCreate(): View
    {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => $prefix->sale,
            'count_id' => ($lastCountId + 1),
        ];
        return view('sale.invoice.pos.create', compact('data', 'selectedPaymentTypesArray'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId()
    {
        return Sale::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the orders
     *
     * @return \Illuminate\View\View
     */
    public function list(): View
    {
        return view('sale.invoice.list');
    }

    /**
     * Convert Quotation to Sale
     *
     * @return \Illuminate\Http\View | RedirectResponse
     */
    public function convertQuotationToSale($id, $convertingFrom = 'Quotation'): View|RedirectResponse
    {
        return $this->convertToSale($id, $convertingFrom);
    }

    /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function convertToSale($id, $convertingFrom = 'Sale Order'): View|RedirectResponse
    {

        if ($convertingFrom == 'Sale Order') {
            //Validate Existance of Converted Sale Orders


            $convertedBill = Sale::where('sale_order_id', $id)->first();

            if ($convertedBill) {
                session(['record' => [
                    'type' => 'success',
                    'status' => __('sale.already_converted'), //Save or update
                ]]);
                //Already Converted, Redirect it.
                return redirect()->route('sale.invoice.details', ['id' => $convertedBill->id]);
            }

            $sale = SaleOrder::with(['party',
                'itemTransaction' => [
                    'item',
                    'tax',
                    'batch.itemBatchMaster',
                    'itemSerialTransaction.itemSerialMaster'
                ]])->findOrFail($id);


        } elseif ($convertingFrom == 'Quotation') {


            $convertedQuotation = Sale::where('quotation_id', $id)->first();

            if ($convertedQuotation) {
                session(['record' => [
                    'type' => 'success',
                    'status' => __('sale.already_converted'), //Save or update
                ]]);
                //Already Converted, Redirect it.
                return redirect()->route('sale.invoice.details', ['id' => $convertedQuotation->id]);
            }

            $sale = Quotation::with(['party',
                'itemTransaction' => [
                    'item',
                    'tax',
                    'batch.itemBatchMaster',
                    'itemSerialTransaction.itemSerialMaster'
                ]])->findOrFail($id);

        }

        // Add formatted dates from ItemBatchMaster model
        $sale->itemTransaction->each(function ($transaction) {
            if (!$transaction->batch?->itemBatchMaster) {
                return;
            }
            $batchMaster = $transaction->batch->itemBatchMaster;
            $batchMaster->mfg_date = $batchMaster->getFormattedMfgDateAttribute();
            $batchMaster->exp_date = $batchMaster->getFormattedExpDateAttribute();
        });

        //Convert Code adjustment - start
        $sale->operation = 'convert';
        $sale->converting_from = $convertingFrom;
        //$sale->formatted_sale_date = $this->toSystemDateFormat($sale->order_date);
        $sale->reference_no = '';
        //Convert Code adjustment - end


        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        $sale->prefix_code = $prefix->sale;
        $sale->count_id = ($lastCountId + 1);

        $sale->formatted_sale_date = $this->toUserDateFormat(date('Y-m-d'));

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $sale->itemTransaction->map(function ($transaction) use ($allUnits) {
            $itemData = $transaction->toArray();

            // Use the getOnlySelectedUnits helper function
            $selectedUnits = getOnlySelectedUnits(
                $allUnits,
                $transaction->item->base_unit_id,
                $transaction->item->secondary_unit_id
            );

            // Add unitList to the item data
            $itemData['unitList'] = $selectedUnits->toArray();

            // Get item serial transactions with associated item serial master data
            $itemSerialTransactions = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                return $serialTransaction->itemSerialMaster->toArray();
            })->toArray();

            // Add itemSerialTransactions to the item data
            $itemData['itemSerialTransactions'] = $itemSerialTransactions;

            return $itemData;
        })->toArray();

        $itemTransactionsJson = json_encode($itemTransactions);

        //Payment Details - Fixed for conversion
        $selectedPaymentTypesArray = $this->getPaymentDataForConversion($sale, $convertingFrom);

        $taxList = CacheService::get('tax')->toJson();

        $paymentHistory = [];

        return view('sale.invoice.edit', compact('taxList', 'sale', 'itemTransactionsJson', 'selectedPaymentTypesArray', 'paymentHistory'));
    }

    /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id): View
    {
        $sale = Sale::with(['party', 'paymentTransaction',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ],
            'salesStatusHistories' => ['changedBy']
        ])->findOrFail($id);

        // Add formatted dates from ItemBatchMaster model
        $sale->itemTransaction->each(function ($transaction) {
            if (!$transaction->batch?->itemBatchMaster) {
                return;
            }
            $batchMaster = $transaction->batch->itemBatchMaster;
            $batchMaster->mfg_date = $batchMaster->getFormattedMfgDateAttribute();
            $batchMaster->exp_date = $batchMaster->getFormattedExpDateAttribute();
        });

        $sale->operation = 'update';

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $itemTransactions = $sale->itemTransaction->map(function ($transaction) use ($allUnits) {
            $itemData = $transaction->toArray();

            // Use the getOnlySelectedUnits helper function
            $selectedUnits = getOnlySelectedUnits(
                $allUnits,
                $transaction->item->base_unit_id,
                $transaction->item->secondary_unit_id
            );

            // Add unitList to the item data
            $itemData['unitList'] = $selectedUnits->toArray();

            // Get item serial transactions with associated item serial master data
            $itemSerialTransactions = $transaction->itemSerialTransaction->map(function ($serialTransaction) {
                return $serialTransaction->itemSerialMaster->toArray();
            })->toArray();

            // Add itemSerialTransactions to the item data
            $itemData['itemSerialTransactions'] = $itemSerialTransactions;

            return $itemData;
        })->toArray();

        $itemTransactionsJson = json_encode($itemTransactions);

        //Default Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());

        $paymentHistory = $this->paymentTransactionService->getPaymentRecordsArray($sale);

        $taxList = CacheService::get('tax')->toJson();

        return view('sale.invoice.edit', compact('taxList', 'sale', 'itemTransactionsJson', 'selectedPaymentTypesArray', 'paymentHistory'));
    }

    /**
     * View Sale Order details
     *
     * @param int $id , the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id): View
    {
        $sale = Sale::with(['party', 'paymentTransaction',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        return view('sale.invoice.details', compact('sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
    }

    /**
     * Print Sale
     *
     * @param int $id , the ID of the sale
     * @return \Illuminate\View\View
     */
    public function posPrint($id, $isPdf = false): View
    {

        $sale = Sale::with(['party', 'user',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ]])->findOrFail($id);
        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.invoice'),
        ];

        return view('print.sale.pos.print', compact('isPdf', 'invoiceData', 'sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));

    }

    /**
     * Print Sale
     *
     * @param int $id , the ID of the sale
     * @return \Illuminate\View\View
     */
    public function print($invoiceFormat = 'format-1', $id, $isPdf = false): View
    {

        $sale = Sale::with(['party',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ]])->findOrFail($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($sale));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.invoice'),
        ];
        if ($invoiceFormat == 'format-4') {
            //Format 4
            //A5 Print
            return view('print.sale.print-format-4', compact('isPdf', 'invoiceData', 'sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
        }
        if ($invoiceFormat == 'format-3') {
            //Format 3
            return view('print.sale.print-format-3', compact('isPdf', 'invoiceData', 'sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
        } else if ($invoiceFormat == 'format-2') {
            //Format 2
            return view('print.sale.print-format-2', compact('isPdf', 'invoiceData', 'sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
        } else {
            //Format 1
            return view('print.sale.print', compact('isPdf', 'invoiceData', 'sale', 'selectedPaymentTypesArray', 'batchTrackingRowCount'));
        }


    }


    /**
     * Generate PDF using View: print() method
     * */
    public function generatePdf($invoiceFormat = 'format-1', $id, $destination = 'D')
    {
        $random = uniqid();

        $html = $this->print(invoiceFormat: $invoiceFormat, id: $id, isPdf: true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
            'default_font' => 'dejavusans',
            //'direction' => 'rtl',
        ]);

        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML($html);
        /**
         * Display in browser
         * 'I'
         * Downloadn PDF
         * 'D'
         * Return String
         * 'S'
         * File Save
         * 'F'
         * */
        $fileName = 'Sale-Bill-' . $id . '-' . $random . '.pdf';

        $mpdf->Output($fileName, $destination);

    }

    /**
     * Store Records
     * */
    public function store(SaleRequest $request): JsonResponse
    {
        try {

        DB::beginTransaction();
        // Get the validated data from the expenseRequest
        $validatedData = $request->validated();

        // Initialize inventory status and sales status
        $validatedData['inventory_status'] = 'pending'; // Items are reserved, not deducted yet
        $validatedData['inventory_deducted_at'] = null; // Not deducted yet
        $validatedData['sales_status'] = $request->sales_status ?? 'Pending'; // Initialize sales status

        if ($request->operation == 'save' || $request->operation == 'convert') {
            // Create a new sale record using Eloquent and save it
            $newSale = Sale::create($validatedData);

            $request->request->add(['sale_id' => $newSale->id]);

            // Handle payment transfer for conversions
            if ($request->operation == 'convert') {
                $this->handleConversionPaymentTransfer($request, $newSale);
            }
        } else {
            // Get the current sale to preserve inventory status if already deducted
            $currentSale = Sale::findOrFail($validatedData['sale_id']);

            // Check if sales status is being changed
            $newSalesStatus = $request->sales_status ?? $validatedData['sales_status'];
            $currentSalesStatus = $currentSale->sales_status;

            // If sales status is changing, use the SalesStatusService to handle it properly
            if ($newSalesStatus !== $currentSalesStatus) {
                // Use the SalesStatusService to handle the status change with proper inventory management
                $salesStatusService = app(SalesStatusService::class);
                $statusChangeData = [
                    'notes' => $request->status_change_notes ?? 'Status updated during sale edit',
                ];

                // If this is a status that requires proof, we might need to handle that
                $statusesRequiringProof = $salesStatusService->getStatusesRequiringProof();
                if (in_array($newSalesStatus, $statusesRequiringProof)) {
                    // For now, we'll proceed without proof for regular updates
                    // In a real scenario, you might want to enforce proof requirements
                }

                $result = $salesStatusService->updateSalesStatus($currentSale, $newSalesStatus, $statusChangeData);

                if (!$result['success']) {
                    throw new \Exception('Failed to update sales status: ' . $result['message']);
                }
            }

            $fillableColumns = [
                'party_id' => $validatedData['party_id'],
                'sale_date' => $validatedData['sale_date'],
                'reference_no' => $validatedData['reference_no'],
                'prefix_code' => $validatedData['prefix_code'],
                'count_id' => $validatedData['count_id'],
                'sale_code' => $validatedData['sale_code'],
                'note' => $validatedData['note'],
                'round_off' => $validatedData['round_off'],
                'grand_total' => $validatedData['grand_total'],
                'state_id' => $validatedData['state_id'],
                'carrier_id' => $validatedData['carrier_id'] ?? null,
                'currency_id' => $validatedData['currency_id'],
                'exchange_rate' => $validatedData['exchange_rate'],
                // Preserve existing inventory status and deduction timestamp (will be updated by service if needed)
                'inventory_status' => $currentSale->refresh()->inventory_status,
                'inventory_deducted_at' => $currentSale->refresh()->inventory_deducted_at,
                'sales_status' => $currentSale->refresh()->sales_status,
                'shipping_charge' => $validatedData['shipping_charge'] ?? 0,
                'is_shipping_charge_distributed' => $validatedData['is_shipping_charge_distributed'] ?? 0,
            ];

            $newSale = $currentSale;
            $newSale->update($fillableColumns);

            /**
             * Before deleting ItemTransaction data take the
             * old data of the item_serial_master_id
             * to update the item_serial_quantity
             * */
            $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($newSale);

            $newSale->itemTransaction()->delete();
            //$newSale->accountTransaction()->delete();

            //Sale Account Update
            foreach ($newSale->accountTransaction as $saleAccount) {
                //get account if of model with tax accounts
                $saleAccountId = $saleAccount->account_id;

                //Delete sale and tax account
                $saleAccount->delete();

                //Update  account
                $this->accountTransactionService->calculateAccounts($saleAccountId);
            }//sale account


            // Check if paymentTransactions exist
            // $paymentTransactions = $newSale->paymentTransaction;
            // if ($paymentTransactions->isNotEmpty()) {
            //     foreach ($paymentTransactions as $paymentTransaction) {
            //         $accountTransactions = $paymentTransaction->accountTransaction;
            //         if ($accountTransactions->isNotEmpty()) {
            //             foreach ($accountTransactions as $accountTransaction) {
            //                 //Sale Account Update
            //                 $accountId = $accountTransaction->account_id;
            //                 // Do something with the individual accountTransaction
            //                 $accountTransaction->delete(); // Or any other operation

            //                 $this->accountTransactionService->calculateAccounts($accountId);
            //             }
            //         }
            //     }
            // }

            // $newSale->paymentTransaction()->delete();
        }

        $request->request->add(['modelName' => $newSale]);

        /**
         * Save Table Items in Sale Items Table
         * */
        $SaleItemsArray = $this->saveSaleItems($request);
        if (!$SaleItemsArray['status']) {
            throw new \Exception($SaleItemsArray['message']);
        }

        // Check if payments were already transferred during conversion
        $skipPaymentProcessing = false;
        if ($request->operation == 'convert') {
            $existingPayments = $newSale->refresh()->paymentTransaction;
            if ($existingPayments->isNotEmpty()) {
                $skipPaymentProcessing = true;
                Log::info('Skipping payment processing - payments already transferred', [
                    'sale_id' => $newSale->id,
                    'existing_payments_count' => $existingPayments->count()
                ]);
            }
        }

        if (!$skipPaymentProcessing) {
            /**
             * Save Expense Payment Records
             * */
            $salePaymentsArray = $this->saveSalePayments($request);
            if (!$salePaymentsArray['status']) {
                throw new \Exception($salePaymentsArray['message']);
            }
        }

        /**
         * Payment Should not be less than 0
         * */
        $paidAmount = $newSale->refresh('paymentTransaction')->paymentTransaction->sum('amount');
        if ($paidAmount < 0) {
            throw new \Exception(__('payment.paid_amount_should_not_be_less_than_zero'));
        }


        /**
         * Paid amount should not be greater than grand total
         * */
        if ($paidAmount > $newSale->grand_total) {
            throw new \Exception(__('payment.payment_should_not_be_greater_than_grand_total') . "<br>Paid Amount : " . $this->formatWithPrecision($paidAmount) . "<br>Grand Total : " . $this->formatWithPrecision($newSale->grand_total) . "<br>Difference : " . $this->formatWithPrecision($paidAmount - $newSale->grand_total));
        }

        /**
         * Update Sale Model
         * Total Paid Amunt
         * */
        if (!$this->paymentTransactionService->updateTotalPaidAmountInModel($request->modelName)) {
            throw new \Exception(__('payment.failed_to_update_paid_amount'));
        }

        /**
         * Update Account Transaction entry
         * Call Services
         * @return boolean
         * */
        // $accountTransactionStatus = $this->accountTransactionService->saleAccountTransaction($request->modelName);
        // if(!$accountTransactionStatus){
        //     throw new \Exception(__('payment.failed_to_update_account'));
        // }

        /**
         * Credit Limit Check
         * */
        if ($this->partyService->limitThePartyCreditLimit($validatedData['party_id'])) {
            //
        }

        /**
         * UPDATE HISTORY DATA
         * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
         * */
        $this->itemTransactionService->updatePreviousHistoryOfItems($request->modelName, $this->previousHistoryOfItems);

        DB::commit();

        // Regenerate the CSRF token
        //Session::regenerateToken();

        return response()->json([
            'status' => true,
            'message' => __('app.record_saved_successfully'),
            'id' => $request->sale_id,

        ]);
    }

    catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 409);
    }

    }


    public function saveSalePayments($request)
    {
        $paymentCount = $request->row_count_payments;
        $grandTotal = $request->grand_total;

        //This is only for POS Page Payments
        if ($request->is_pos_form) {
            $paymentTotal = 0;
            /**
             * Used if Payment is greater then the payment.
             * Data index start from 0
             * payment_amount[0] & payment_amount[1] because POS page has only 2 payments static code
             * */
            //#0
            $payment_0 = $request->payment_amount[0];
            //#1
            $payment_1 = $request->payment_amount[1];

            //Only if single Payment has the value
            if ($payment_1 == 0) {// #1
                if ($payment_0 > 0 && $payment_0 > $grandTotal) {
                    $request->merge([
                        'payment_amount' => array_replace($request->input('payment_amount', []), [0 => $grandTotal]) // Replace 0th index value
                    ]);
                }
            }
        }

        for ($i = 0; $i <= $paymentCount; $i++) {

            /**
             * If array record not exist then continue forloop
             * */
            if (!isset($request->payment_amount[$i])) {
                continue;
            }

            /**
             * Data index start from 0
             * */
            $amount = $request->payment_amount[$i];

            if ($amount > 0) {
                if (!isset($request->payment_type_id[$i])) {
                    return [
                        'status' => false,
                        'message' => __('payment.missed_to_select_payment_type') . "#" . $i,
                    ];
                }

                $paymentsArray = [
                    'transaction_date' => $request->sale_date,
                    'amount' => $amount,
                    'payment_type_id' => $request->payment_type_id[$i],
                    'note' => $request->payment_note[$i],
                    'payment_from_unique_code' => General::INVOICE->value,
                ];
                if (!$transaction = $this->paymentTransactionService->recordPayment($request->modelName, $paymentsArray)) {
                    throw new \Exception(__('payment.failed_to_record_payment_transactions'));
                }

            }//amount>0
        }//for end

        // Remove automatic inventory deduction based on payment completion
        // Inventory will now only be deducted when sales status is changed to POD

        return ['status' => true];
    }


    public function restrictToSellAboveMRP($itemModal, $request, $i)
    {

        //If auto update sale price is disabled then return
        if (!app('company')['restrict_to_sell_above_mrp']) {
            return;
        }

        //Validate is Restricted to sell above MRP
        if ($itemModal->mrp > 0) {
            /**
             * check is item sale price is greater than MRP
             * where, item sale price = unit_price - discount + tax
             */
            // Calculate price per unit correctly
            $pricePerUnit = $request->total[$i] / ($request->quantity[$i]);

            if ($pricePerUnit > $itemModal->mrp) {
                throw new \Exception("Restricted to sell! Item '{$itemModal->name}' has an MRP of {$this->formatWithPrecision($itemModal->mrp)}, but you are selling each unit at a price of " . $this->formatWithPrecision($pricePerUnit) . ".");
            }
        }
        return true;
    }

    public function restrictToSellBelowMSP($itemModal, $request, $i)
    {

        //If auto update sale price is disabled then return
        if (!app('company')['restrict_to_sell_below_msp']) {
            return;
        }
        //Validate is Restricted to sell below MSP
        if ($itemModal->msp > 0) {
            /**
             * check is item sale price is less than MSP
             * where, item sale price = unit_price - discount + tax
             */
            // Calculate price per unit correctly
            $pricePerUnit = $request->total[$i] / ($request->quantity[$i]);

            if ($pricePerUnit < $itemModal->msp) {
                throw new \Exception("Restricted to sell! Item '{$itemModal->name}' has an MSP of {$this->formatWithPrecision($itemModal->msp)}, but you are selling each unit at a price of " . $this->formatWithPrecision($pricePerUnit) . ".");
            }
        }
        return true;
    }

    public function updateItemMasterSalePrice($request, $isWholesaleCustomer, $i)
    {

        //If auto update sale price is disabled then return
        if (!app('company')['auto_update_sale_price']) {
            return;
        }

        $updateItemMaster = Item::find($request->item_id[$i]);
        if (!empty($request->sale_price[$i]) && $request->sale_price[$i] > 0) {
            if ($updateItemMaster->base_unit_id != $request->unit_id[$i]) {
                $salePrice = $request->sale_price[$i] * $updateItemMaster->conversion_rate;
            } else {
                $salePrice = $request->sale_price[$i];
            }

            if ($isWholesaleCustomer) {
                $updateItemMaster->wholesale_price = $salePrice;
                $updateItemMaster->is_wholesale_price_with_tax = ($request->tax_type[$i] == 'inclusive') ? 1 : 0;
            } else {
                $updateItemMaster->sale_price = $salePrice;
                $updateItemMaster->is_sale_price_with_tax = ($request->tax_type[$i] == 'inclusive') ? 1 : 0;
            }

            $updateItemMaster->save();
        }
    }


    public function saveSaleItems($request)
    {
        $itemsCount = $request->row_count;

        $isWholesaleCustomer = $request->only('is_wholesale_customer')['is_wholesale_customer'];

        for ($i = 0; $i < $itemsCount; $i++) {
            /**
             * If array record not exist then continue forloop
             * */
            if (!isset($request->item_id[$i])) {
                continue;
            }

            /**
             * Data index start from 0
             * */
            $itemDetails = Item::find($request->item_id[$i]);
            $itemName = $itemDetails->name;

            //validate input Quantity
            $itemQuantity = $request->quantity[$i];
            if (empty($itemQuantity) || $itemQuantity === 0 || $itemQuantity < 0) {
                return [
                    'status' => false,
                    'message' => ($itemQuantity < 0) ? __('item.item_qty_negative', ['item_name' => $itemName]) : __('item.please_enter_item_quantity', ['item_name' => $itemName]),
                ];
            }

            //Validate is negative stock entry allowed or not for General Item
            // Use appropriate unique_code based on inventory status:
            // - If inventory is deducted (POD status or post-delivery cancellation), use SALE (deducted)
            // - If inventory is pending, use SALE_ORDER (reserved)
            $isInventoryDeducted = in_array($request->modelName->inventory_status, ['deducted', 'deducted_delivered']);
            $uniqueCode = $isInventoryDeducted ?
                ItemTransactionUniqueCode::SALE->value :
                ItemTransactionUniqueCode::SALE_ORDER->value;

            $regularItemTransaction = $this->itemTransactionService->validateRegularItemQuantity($itemDetails, $request->warehouse_id[$i], $itemQuantity, $uniqueCode);

            if (!$regularItemTransaction) {
                throw new \Exception(__('item.failed_to_save_regular_item_record'));
            }

            // //Validate is Restricted to sell above MRP
            $this->restrictToSellAboveMRP($itemDetails, $request, $i);

            // //Validate is Restricted to sell below MSP
            $this->restrictToSellBelowMSP($itemDetails, $request, $i);

            //Auto-Update Item Master Sale Price
            $this->updateItemMasterSalePrice($request, $isWholesaleCustomer, $i);


            /**
             *
             * Item Transaction Entry
             * Use appropriate unique_code based on inventory status:
             * - If inventory is deducted (POD status or post-delivery cancellation), use SALE (deducted)
             * - If inventory is pending, use SALE_ORDER (reserved)
             * */
            $isInventoryDeducted = in_array($request->modelName->inventory_status, ['deducted', 'deducted_delivered']);
            $uniqueCode = $isInventoryDeducted ?
                ItemTransactionUniqueCode::SALE->value :
                ItemTransactionUniqueCode::SALE_ORDER->value;

            $transaction = $this->itemTransactionService->recordItemTransactionEntry($request->modelName, [
                'warehouse_id' => $request->warehouse_id[$i],
                'transaction_date' => $request->sale_date,
                'item_id' => $request->item_id[$i],
                'description' => $request->description[$i],

                'tracking_type' => $itemDetails->tracking_type,

                'input_quantity' => $itemQuantity,
                'quantity' => $itemQuantity,
                'unit_id' => $request->unit_id[$i],
                'unit_price' => $request->sale_price[$i],
                'mrp' => $request->mrp[$i] ?? 0,

                'discount' => $request->discount[$i],
                'discount_type' => $request->discount_type[$i],
                'discount_amount' => $request->discount_amount[$i],

                'charge_type' => 'shipping',
                'charge_amount' => 0,

                'tax_id' => $request->tax_id[$i],
                'tax_type' => $request->tax_type[$i],
                'tax_amount' => $request->tax_amount[$i],

                'total' => $request->total[$i],
                'unique_code' => $uniqueCode, // Use appropriate code based on inventory status

            ]);

            //return $transaction;
            if (!$transaction) {
                throw new \Exception("Failed to record Item Transaction Entry!");
            }


            /**
             * Tracking Type:
             * regular
             * batch
             * serial
             * */
            if ($itemDetails->tracking_type == 'serial') {
                //Serial validate and insert records
                if ($itemQuantity > 0) {
                    $jsonSerials = $request->serial_numbers[$i];
                    $jsonSerialsDecode = json_decode($jsonSerials);

                    /**
                     * Serial number count & Enter Quntity must be equal
                     * */
                    $countRecords = (!empty($jsonSerialsDecode)) ? count($jsonSerialsDecode) : 0;
                    if ($countRecords != $itemQuantity) {
                        throw new \Exception(__('item.opening_quantity_not_matched_with_serial_records'));
                    }

                    foreach ($jsonSerialsDecode as $serialNumber) {
                        $serialArray = [
                            'serial_code' => $serialNumber,
                        ];

                        // Use appropriate unique_code based on inventory status
                        $serialTransaction = $this->itemTransactionService->recordItemSerials($transaction->id, $serialArray, $request->item_id[$i], $request->warehouse_id[$i], $uniqueCode);

                        if (!$serialTransaction) {
                            throw new \Exception(__('item.failed_to_save_serials'));
                        }
                    }
                }
            } else if ($itemDetails->tracking_type == 'batch') {
                //Serial validate and insert records
                if ($itemQuantity > 0) {
                    /**
                     * Record Batch Entry for each batch
                     * */
                    $batchArray = [
                        'batch_no' => $request->batch_no[$i],
                        'mfg_date' => $request->mfg_date[$i] ? $this->toSystemDateFormat($request->mfg_date[$i]) : null,
                        'exp_date' => $request->exp_date[$i] ? $this->toSystemDateFormat($request->exp_date[$i]) : null,
                        'model_no' => $request->model_no[$i],
                        'mrp' => $request->mrp[$i] ?? 0,
                        'color' => $request->color[$i],
                        'size' => $request->size[$i],
                        'quantity' => $itemQuantity,
                    ];

                    // Use appropriate unique_code based on inventory status
                    $batchTransaction = $this->itemTransactionService->recordItemBatches($transaction->id, $batchArray, $request->item_id[$i], $request->warehouse_id[$i], $uniqueCode);

                    if (!$batchTransaction) {
                        throw new \Exception(__('item.failed_to_save_batch_records'));
                    }

                }
            } else {
                //Regular item transaction entry already done before if() condition


            }


        }//for end

        /**
         * Update Shipping Cost
         * */
        $this->updateShippingCost($request->modelName);

        return ['status' => true];
    }

    public function updateShippingCost($model)
    {
        $itemTransactions = $model->refresh()->itemTransaction()->with('tax')->get();
        if ($itemTransactions->isNotEmpty() && $model->shipping_charge > 0 && $model->is_shipping_charge_distributed == 1) {

            //Calculate itemTransaction unit_price * quantity, give me sum of it
            //Use foreach
            $totalItemAmount = $itemTransactions->map(function ($itemTransaction) {
                return $itemTransaction->unit_price * $itemTransaction->quantity;
            })->sum();

            //Update charge_amount in itemTransaction model for each entry
            $itemTransactions->map(function ($itemTransaction) use ($model, $totalItemAmount) {
                $itemTransaction->charge_amount = ($model->shipping_charge / $totalItemAmount) * $itemTransaction->unit_price * $itemTransaction->quantity;
                /**
                 * Calculate Charge Tax Amount
                 * get the tax value from tax model, where tax is morph with itemTransaction as well
                 * Tax model has the taxrate column
                 */
                $itemTransaction->charge_tax_amount = ($itemTransaction->charge_amount * $itemTransaction->tax->rate) / 100;

                $itemTransaction->save();
            });

        }
    }


    /**
     * Get sales analytics data for charts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            // Get date range from request or default to last 30 days
            $fromDate = $request->from_date ? $this->toSystemDateFormat($request->from_date) : now()->subDays(30)->format('Y-m-d');
            $toDate = $request->to_date ? $this->toSystemDateFormat($request->to_date) : now()->format('Y-m-d');

            // Apply same filters as the main list
            $query = Sale::query()
                ->when($request->party_id, function ($query) use ($request) {
                    return $query->where('party_id', $request->party_id);
                })
                ->when($request->user_id, function ($query) use ($request) {
                    return $query->where('created_by', $request->user_id);
                })
                ->when(!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->whereBetween('sale_date', [$fromDate, $toDate]);

            // Get sales data for bar chart (daily sales)
            $salesData = $this->getSalesChartData($query, $fromDate, $toDate);

            // Get status distribution for pie chart
            $statusData = $this->getStatusChartData($query);

            return response()->json([
                'status' => true,
                'data' => [
                    'sales_data' => $salesData,
                    'status_data' => $statusData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status chart data
     */
    private function getSalesChartData($query, $fromDate, $toDate)
    {
        // Clone query for payment analysis
        $paymentQuery = clone $query;

        // Get payment statistics
        $paymentStats = $paymentQuery
            ->selectRaw('
                SUM(grand_total) as total_amount,
                SUM(paid_amount) as paid_amount,
                COUNT(*) as total_count,
                SUM(CASE WHEN paid_amount >= grand_total THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN paid_amount < grand_total THEN 1 ELSE 0 END) as unpaid_count
            ')
            ->first();

        $totalAmount = (float) $paymentStats->total_amount;
        $paidAmount = (float) $paymentStats->paid_amount;
        $unpaidAmount = $totalAmount - $paidAmount;

        return [
            'payment_amounts' => [
                'labels' => [__('sale.paid'), __('sale.unpaid')],
                'data' => [$paidAmount, $unpaidAmount],
                'total_amount' => $totalAmount
            ],
            'payment_counts' => [
                'labels' => [__('sale.paid_invoices'), __('sale.unpaid_invoices')],
                'data' => [(int) $paymentStats->paid_count, (int) $paymentStats->unpaid_count],
                'total_count' => (int) $paymentStats->total_count
            ]
        ];
    }

    /**
     * Get status distribution chart data
     */
    private function getStatusChartData($query)
    {
        // Clone query for status data
        $statusQuery = clone $query;

        // Get status distribution
        $statusCounts = $statusQuery
            ->selectRaw('sales_status, COUNT(*) as count')
            ->groupBy('sales_status')
            ->get();

        $labels = [];
        $values = [];

        // Get status options for proper labeling
        $statusOptions = $this->generalDataService->getSaleStatus();
        $statusMap = collect($statusOptions)->keyBy('id');

        foreach ($statusCounts as $status) {
            $statusInfo = $statusMap->get($status->sales_status);
            $labels[] = $statusInfo ? $statusInfo['name'] : $status->sales_status;
            $values[] = (int) $status->count;
        }

        return [
            'labels' => $labels,
            'values' => $values
        ];
    }

    /**
     * Datatable list for sales
     * */
     public function datatableList(Request $request)
    {
        $data = Sale::with('user', 'party')
            ->when($request->party_id, function ($query) use ($request) {
                return $query->where('party_id', $request->party_id);
            })
            ->when($request->user_id, function ($query) use ($request) {
                return $query->where('created_by', $request->user_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
            })
            ->when(!auth()->user()->can('sale.invoice.can.view.other.users.sale.invoices'), function ($query) use ($request) {
                return $query->where('created_by', auth()->user()->id);
            })
            // Apply delivery user filter - restrict to delivery status sales only
            ->when($this->isDeliveryUser(), function ($query) {
                return $this->applyDeliveryUserFilter($query);
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('sale_code', 'like', "%{$searchTerm}%")
                            ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                            ->orWhere('grand_total', 'like', "%{$searchTerm}%")
                            ->orWhere('sales_status', 'like', "%{$searchTerm}%")
                            ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                                $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                $userQuery->where('username', 'like', "%{$searchTerm}%");
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('sale_date', function ($row) {
                return $row->formatted_sale_date;
            })
            ->addColumn('sale_code', function ($row) {
                return $row->sale_code;
            })
            ->addColumn('party_name', function ($row) {
                return $row->party->first_name . " " . $row->party->last_name;
            })
            ->addColumn('grand_total', function ($row) {
                return $this->formatWithPrecision($row->grand_total);
            })
            ->addColumn('balance', function ($row) {
                return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
            })
            ->addColumn('payment_status', function ($row) {
                $balance = $row->grand_total - $row->paid_amount;

                if ($balance == 0) {
                    return '<span class="badge bg-success"><i class="bx bx-check-circle me-1"></i>' . __('payment.paid') . '</span>';
                } elseif ($row->paid_amount == 0) {
                    return '<span class="badge bg-danger"><i class="bx bx-x-circle me-1"></i>' . __('payment.unpaid') . '</span>';
                } else {
                    return '<span class="badge bg-warning"><i class="bx bx-time-five me-1"></i>' . __('payment.partially_paid') . '</span>';
                }
            })
            ->addColumn('sales_status', function ($row) {
                return $row->sales_status;
            })
            ->addColumn('inventory_status', function ($row) {
                return $row->inventory_status;
            })
            ->addColumn('post_delivery_action', function ($row) {
                return $row->post_delivery_action;
            })
            ->addColumn('color', function ($row) {
                $saleStatuses = $this->generalDataService->getSaleStatus();

                // Find the status matching the given id
                return collect($saleStatuses)->firstWhere('id', $row->sales_status)['color'];

            })
            ->addColumn('action', function ($row) {
                $id = $row->id;

                $editUrl = route('sale.invoice.edit', ['id' => $id]);
                $detailsUrl = route('sale.invoice.details', ['id' => $id]);
                $printUrl = route('sale.invoice.print', ['invoiceFormat' => 'normal', 'id' => $id]);
                $pdfUrl = route('sale.invoice.pdf', ['invoiceFormat' => 'normal', 'id' => $id]);

                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $editUrl . '"><i class="bx bx-edit"></i> ' . __('app.edit') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="' . $detailsUrl . '"><i class="bx bx-show-alt"></i> ' . __('app.details') . '</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $printUrl . '"><i class="bx bx-printer"></i> ' . __('app.print') . '</a>
                                </li>
                                <li>
                                    <a target="_blank" class="dropdown-item" href="' . $pdfUrl . '"><i class="bx bxs-file-pdf"></i> ' . __('app.pdf') . '</a>
                                </li>
                                 <li>
                                    <a class="dropdown-item make-payment" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-money"></i> ' . __('payment.receive_payment') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item payment-history" data-invoice-id="' . $id . '" role="button"></i><i class="bx bx-table"></i> ' . __('payment.history') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-email" data-model="sale/invoice" data-id="' . $id . '" role="button"><i class="bx bx-envelope"></i> ' . __('app.send_email') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item notify-through-sms" data-model="sale/invoice" data-id="' . $id . '" role="button"><i class="bx bx-envelope"></i> ' . __('app.send_sms') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item status-history" data-model="statusHistoryModal" data-id="' . $id . '" role="button"><i class="bx bx-book"></i> ' . __('app.status_history') . '</a>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id="' . $id . '"><i class="bx bx-trash"></i> ' . __('app.delete') . '</button>
                                </li>
                            </ul>
                        </div>';
                return $actionBtn;
            })
            ->rawColumns(['action', 'payment_status'])
            ->make(true);
    }

    /**
     * Delete Sale Records
     * @return JsonResponse
     * */
    public function delete(Request $request): JsonResponse
    {

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Sale::find($recordId);
            if (!$record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status' => false,
                    'message' => __('app.invalid_record_id', ['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */


        try {
            // Attempt deletion (as in previous responses)
            Sale::whereIn('id', $selectedRecordIds)->chunk(100, function ($sales) {
                foreach ($sales as $sale) {
                    /**
                     * Before deleting ItemTransaction data take the
                     * old data of the item_serial_master_id
                     * to update the item_serial_quantity
                     * */
                    $this->previousHistoryOfItems = $this->itemTransactionService->getHistoryOfItems($sale);

                    //Sale Account Update
                    // foreach($sale->accountTransaction as $saleAccount){
                    //     //get account if of model with tax accounts
                    //     $saleAccountId = $saleAccount->account_id;

                    //     //Delete sale and tax account
                    //     $saleAccount->delete();

                    //     //Update  account
                    //     $this->accountTransactionService->calculateAccounts($saleAccountId);
                    // }//sale account

                    // Check if paymentTransactions exist
                    $paymentTransactions = $sale->paymentTransaction;
                    if ($paymentTransactions->isNotEmpty()) {
                        foreach ($paymentTransactions as $paymentTransaction) {
                            // $accountTransactions = $paymentTransaction->accountTransaction;
                            // if ($accountTransactions->isNotEmpty()) {
                            //     foreach ($accountTransactions as $accountTransaction) {
                            //         //Sale Account Update
                            //         $accountId = $accountTransaction->account_id;
                            //         // Do something with the individual accountTransaction
                            //         $accountTransaction->delete(); // Or any other operation

                            //         $this->accountTransactionService->calculateAccounts($accountId);
                            //     }
                            // }

                            //delete Payment now
                            $paymentTransaction->delete();
                        }
                    }//isNotEmpty

                    $itemIdArray = [];

                    //Purchasr Item delete and update the stock
                    foreach ($sale->itemTransaction as $itemTransaction) {
                        //get item id
                        $itemId = $itemTransaction->item_id;

                        //delete item Transactions
                        $itemTransaction->delete();

                        $itemIdArray[] = $itemId;
                    }//sale account


                    /**
                     * UPDATE HISTORY DATA
                     * LIKE: ITEM SERIAL NUMBER QUNATITY, BATCH NUMBER QUANTITY, GENERAL DATA QUANTITY
                     * */
                    $this->itemTransactionService->updatePreviousHistoryOfItems($sale, $this->previousHistoryOfItems);

                    //Delete Sale
                    $sale->delete();

                    //Update stock update in master
                    if (count($itemIdArray) > 0) {
                        foreach ($itemIdArray as $itemId) {
                            $this->itemService->updateItemStock($itemId);
                        }
                    }

                }//sales

            });//chunk

            //Delete Sale
            $deletedCount = Sale::whereIn('id', $selectedRecordIds)->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => __('app.cannot_delete_records'),
            ], 409);
        }
    }

    /**
     * Prepare Email Content to view
     * */
    public function getEmailContent($id)
    {
        $model = Sale::with('party')->find($id);

        $emailData = $this->saleEmailNotificationService->saleCreatedEmailNotification($id);

        $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'email' => $model->party->email,
            'subject' => $subject,
            'content' => $content,
        ];
        return $data;
    }

    /**
     * Prepare SMS Content to view
     * */
    public function getSMSContent($id)
    {
        $model = Sale::with('party')->find($id);

        $emailData = $this->saleSmsNotificationService->saleCreatedSmsNotification($id);

        $mobile = ($emailData['status']) ? $emailData['data']['mobile'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'mobile' => $mobile,
            'content' => $content,
        ];
        return $data;
    }

    /**
     *
     * Load Sold Items Data, this is used in Sale Return Page
     */
    function getSoldItemsData($partyId, $itemId = null)
    {
        try {
            $sales = Sale::with([
                'party',
                'itemTransaction' => fn($query) => $query->when($itemId, fn($q) => $q->where('item_id', $itemId)),
                'itemTransaction.item.brand',
                'itemTransaction.item.tax',
                'itemTransaction.warehouse'])
                ->where('party_id', $partyId)
                ->get();

            if ($sales->isEmpty()) {
                throw new \Exception('No Records found!!');
            }

            // Extract the first party name for display (assuming all sales belong to the same party)
            $partyName = $sales->first()->party->getFullName();

            $data = $sales->map(function ($sale) {
                return [
                    'sold_items' => $sale->itemTransaction->map(function ($transaction) use ($sale) {
                        return [
                            'id' => $transaction->id,
                            'sale_code' => $sale->sale_code,
                            'sale_date' => $this->toUserDateFormat($sale->sale_date),
                            'warehouse_name' => $transaction->warehouse->name,

                            'item_id' => $transaction->item_id,
                            'item_name' => "<span class='text-primary'>{$transaction->item->name}</span><br><i>[<b>Code: </b>{$transaction->item->item_code}]</i>",

                            'brand_name' => $transaction->brand->name ?? '',

                            'unit_price' => $this->formatWithPrecision($transaction->unit_price),
                            'quantity' => $this->formatQuantity($transaction->quantity),
                            'discount_amount' => $this->formatQuantity($transaction->discount_amount),
                            'tax_id' => $transaction->tax_id,
                            'tax_name' => $transaction->item->tax->name,
                            'tax_amount' => $this->formatQuantity($transaction->tax_amount),
                            'total' => $this->formatQuantity($transaction->total),
                        ];
                    })->toArray(),
                ];
            });

            // Include the party name in the response
            return [
                'party_name' => $partyName,
                'sold_items' => $data->flatMap(function ($sale) {
                    return $sale['sold_items'];
                })->toArray(),
            ];
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Get payment data for conversion operations
     * Handles existing payments from Sale Orders or Quotations
     */
    private function getPaymentDataForConversion($sale, $convertingFrom): string
    {
        if ($convertingFrom == 'Sale Order') {
            // Get the original Sale Order ID from the sale
            $saleOrderId = $sale->sale_order_id ?? request('sale_order_id');

            if ($saleOrderId) {
                $saleOrder = \App\Models\Sale\SaleOrder::with('paymentTransaction')->find($saleOrderId);

                // Check if SaleOrder has payments
                if ($saleOrder && $saleOrder->paymentTransaction && $saleOrder->paymentTransaction->isNotEmpty()) {
                    $existingPayments = $saleOrder->paymentTransaction->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'payment_type_id' => $payment->payment_type_id,
                            'transaction_date' => $payment->transaction_date,
                            'reference_no' => $payment->reference_no ?? '',
                            'note' => $payment->note ?? '',
                            'from_sale_order' => true,
                            'original_transaction_type' => 'Sale Order'
                        ];
                    })->toArray();

                    Log::info('Found existing payments in Sale Order', [
                        'sale_order_id' => $saleOrder->id,
                        'payments_count' => count($existingPayments),
                        'total_amount' => collect($existingPayments)->sum('amount')
                    ]);

                    return json_encode($existingPayments);
                }
            }
        } elseif ($convertingFrom == 'Quotation') {
            // Get the original Quotation ID from the sale
            $quotationId = $sale->quotation_id ?? request('quotation_id');

            if ($quotationId) {
                $quotation = \App\Models\Sale\Quotation::with('paymentTransaction')->find($quotationId);

                // Check if Quotation has payments
                if ($quotation && $quotation->paymentTransaction && $quotation->paymentTransaction->isNotEmpty()) {
                    $existingPayments = $quotation->paymentTransaction->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'payment_type_id' => $payment->payment_type_id,
                            'transaction_date' => $payment->transaction_date,
                            'reference_no' => $payment->reference_no ?? '',
                            'note' => $payment->note ?? '',
                            'from_quotation' => true,
                            'original_transaction_type' => 'Quotation'
                        ];
                    })->toArray();

                    return json_encode($existingPayments);
                }
            }
        }

        // No existing payments, return default payment types
        Log::info('No existing payments found, returning default payment types', [
            'converting_from' => $convertingFrom,
            'source_id' => $sale->id
        ]);

        return json_encode($this->paymentTypeService->selectedPaymentTypesArray());
    }

    /**
     * Handle payment transfer during conversion operations
     */
    private function handleConversionPaymentTransfer($request, $sale)
    {
        $convertingFrom = $request->converting_from;

        if ($convertingFrom == 'Sale Order' && $request->sale_order_id) {
            $this->transferPaymentsFromSaleOrder($request->sale_order_id, $sale);
        } elseif ($convertingFrom == 'Quotation' && $request->quotation_id) {
            $this->transferPaymentsFromQuotation($request->quotation_id, $sale);
        }
    }

    /**
     * Transfer payments from Sale Order to Sale
     */
    private function transferPaymentsFromSaleOrder($saleOrderId, $sale)
    {
        Log::info('Starting payment transfer from Sale Order', [
            'sale_order_id' => $saleOrderId,
            'sale_id' => $sale->id
        ]);

        $saleOrder = \App\Models\Sale\SaleOrder::with('paymentTransaction')->find($saleOrderId);

        if ($saleOrder && $saleOrder->paymentTransaction->isNotEmpty()) {
            Log::info('Found payments to transfer', [
                'payments_count' => $saleOrder->paymentTransaction->count(),
                'total_amount' => $saleOrder->paymentTransaction->sum('amount')
            ]);

            foreach ($saleOrder->paymentTransaction as $payment) {
                // Create new payment for Sale using polymorphic relationship
                $newPayment = $payment->replicate();
                $newPayment->transaction_id = $sale->id;
                $newPayment->transaction_type = \App\Models\Sale\Sale::class;
                $newPayment->save();

                Log::info('Payment transferred', [
                    'original_payment_id' => $payment->id,
                    'new_payment_id' => $newPayment->id,
                    'amount' => $payment->amount
                ]);

                // Delete original payment
                $payment->delete();
            }

            // Update amounts
            $saleOrder->update(['paid_amount' => 0]);
            $this->paymentTransactionService->updateTotalPaidAmountInModel($sale);

            Log::info('Payment transfer completed', [
                'sale_order_id' => $saleOrderId,
                'sale_id' => $sale->id,
                'new_paid_amount' => $sale->refresh()->paid_amount
            ]);
        } else {
            Log::info('No payments found to transfer', [
                'sale_order_id' => $saleOrderId
            ]);
        }
    }

    /**
     * Transfer payments from Quotation to Sale
     */
    private function transferPaymentsFromQuotation($quotationId, $sale)
    {
        Log::info('Starting payment transfer from Quotation', [
            'quotation_id' => $quotationId,
            'sale_id' => $sale->id
        ]);

        $quotation = \App\Models\Sale\Quotation::with('paymentTransaction')->find($quotationId);

        if ($quotation && $quotation->paymentTransaction->isNotEmpty()) {
            foreach ($quotation->paymentTransaction as $payment) {
                // Create new payment for Sale using polymorphic relationship
                $newPayment = $payment->replicate();
                $newPayment->transaction_id = $sale->id;
                $newPayment->transaction_type = \App\Models\Sale\Sale::class;
                $newPayment->save();

                // Delete original payment
                $payment->delete();
            }

            // Update amounts
            $quotation->update(['paid_amount' => 0]);
            $this->paymentTransactionService->updateTotalPaidAmountInModel($sale);
        }
    }

    /**
     * Process inventory deduction after payment completion
     * This method should be called when payment is fully completed
     *
     * @param Sale $sale
     * @return JsonResponse
     */
    public function processInventoryDeduction(Sale $sale): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if order is already processed
            if ($sale->inventory_status === 'deducted') {
                return response()->json([
                    'status' => false,
                    'message' => __('sale.inventory_already_deducted')
                ]);
            }

            // Check if payment is complete
            $totalPaid = $sale->paymentTransaction->sum('amount');
            if ($totalPaid < $sale->grand_total) {
                return response()->json([
                    'status' => false,
                    'message' => __('payment.payment_not_complete')
                ]);
            }

            Log::info('Starting inventory deduction for sale', [
                'sale_id' => $sale->id,
                'items_count' => $sale->itemTransaction->count()
            ]);

            // Process inventory deduction for each item
            foreach ($sale->itemTransaction as $transaction) {
                // Update the transaction unique code from SALE_ORDER to SALE
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE->value
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterPayment($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterPayment($transaction);
                }
            }

            // Update sale status
            $sale->update([
                'inventory_status' => 'deducted',
                'inventory_deducted_at' => now()
            ]);

            DB::commit();

            Log::info('Inventory deduction completed for sale', ['sale_id' => $sale->id]);

            return response()->json([
                'status' => true,
                'message' => __('sale.inventory_deducted_successfully')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Inventory deduction failed for sale', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update batch inventory after payment
     */
    private function updateBatchInventoryAfterPayment($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after payment
     */
    private function updateSerialInventoryAfterPayment($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }

    /**
     * Check and process inventory deduction after payment update
     * This should be called whenever a payment is added to a sale
     *
     * @param Sale $sale
     * @return bool
     */
    public function checkAndProcessInventoryDeduction(Sale $sale): bool
    {
        // Check if payment is now complete
        $totalPaid = $sale->paymentTransaction->sum('amount');

        // Log for debugging
        Log::info('Checking inventory deduction for sale', [
            'sale_id' => $sale->id,
            'total_paid' => $totalPaid,
            'grand_total' => $sale->grand_total,
            'inventory_status' => $sale->inventory_status
        ]);

        if ($totalPaid >= $sale->grand_total && $sale->inventory_status !== 'deducted') {
            Log::info('Processing inventory deduction for sale', ['sale_id' => $sale->id]);
            $result = $this->processInventoryDeduction($sale);
            return $result->getData()->status ?? false;
        }

        return false;
    }

    /**
     * Manual inventory deduction endpoint
     * For admin users to manually trigger inventory deduction
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function manualInventoryDeduction(Request $request, int $id): JsonResponse
    {
        $sale = Sale::with(['itemTransaction', 'paymentTransaction'])->findOrFail($id);

        // Check permissions
        if (!auth()->user()->can('sale.invoice.manual.inventory.deduction')) {
            return response()->json([
                'status' => false,
                'message' => __('auth.unauthorized')
            ], 403);
        }

        return $this->processInventoryDeduction($sale);
    }

    /**
     * Update sales status with enhanced logic
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateSalesStatus(Request $request, int $id): JsonResponse
    {
        try {
            $sale = Sale::findOrFail($id);

            // Validate required fields for specific statuses
            $statusesRequiringProof = $this->salesStatusService->getStatusesRequiringProof();

            if (in_array($request->sales_status, $statusesRequiringProof)) {
                $request->validate([
                    'sales_status' => 'required|string',
                    'notes' => 'required|string|max:1000',
                    'proof_image' => 'nullable|image|max:2048', // 2MB max
                ]);
            } else {
                $request->validate([
                    'sales_status' => 'required|string',
                    'notes' => 'nullable|string|max:1000',
                ]);
            }

            // Update status using the service
            $result = $this->salesStatusService->updateSalesStatus($sale, $request->sales_status, [
                'notes' => $request->notes,
                'proof_image' => $request->file('proof_image'),
            ]);

            if (!$result['success']) {
                return response()->json([
                    'status' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => $result['message'],
                'sales_status' => $request->sales_status,
                'inventory_updated' => $result['inventory_updated'] ?? false
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 409);
        }
    }

    /**
     * Get sales status history
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function getSalesStatusHistory($id): JsonResponse
    {
        try {
            $saleId = (int) $id;
            $sale = Sale::findOrFail($saleId);

            // Check if user has permission to view this sale
            // You might want to add authorization logic here

            $history = $this->salesStatusService->getStatusHistory($sale);

            return response()->json([
                'status' => true,
                'data' => $history,
                'message' => 'Status history retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Sale not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving sales status history', [
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while retrieving status history'
            ], 500);
        }
    }

    /**
     * Get sales status options with metadata
     *
     * @return JsonResponse
     */
    public function getSalesStatusOptions(): JsonResponse
    {
        $generalDataService = new GeneralDataService();
        $statusOptions = $generalDataService->getSaleStatus();

        // Add additional metadata for frontend use
        $salesStatusService = app(SalesStatusService::class);
        $statusesRequiringProof = $salesStatusService->getStatusesRequiringProof();

        // Enhance each status with additional information
        $enhancedOptions = array_map(function($status) use ($statusesRequiringProof) {
            return [
                'id' => $status['id'],
                'name' => $status['name'],
                'color' => $status['color'],
                'requires_proof' => in_array($status['id'], $statusesRequiringProof),
                'triggers_inventory_deduction' => $status['id'] === 'POD',
                'restores_inventory' => in_array($status['id'], ['Cancelled', 'Returned'])
            ];
        }, $statusOptions);

        return response()->json([
            'status' => true,
            'data' => $enhancedOptions,
            'statuses_requiring_proof' => $statusesRequiringProof
        ]);
    }

    /**
     * Helper method to check if current user has delivery role
     * @return bool
     */
    private function isDeliveryUser(): bool
    {
        $user = auth()->user();
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }

    /**
     * Apply delivery user filtering to query
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyDeliveryUserFilter($query)
    {
        if ($this->isDeliveryUser()) {
            // Delivery users can only see sales with 'Delivery' status
            $query->whereNotIn('sales_status', ['Pending', 'Processing']);
        }
        return $query;
    }

    /**
     * Helper method to check if current user is associated with a carrier
     * @return bool
     */
    private function isCarrierUser(): bool
    {
        $user = auth()->user();
        return $user && $user->carrier_id && $user->role && strtolower($user->role->name) === 'delivery';
    }

    /**
     * Apply carrier filtering to query for delivery users
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyCarrierFilter($query)
    {
        $user = auth()->user();
        if ($user && $user->carrier_id) {
            // Carrier users can only see sales assigned to their carrier
            $query->where('carrier_id', $user->carrier_id)
                  ->whereIn(column: 'sales_status', values: ['Delivery', 'POD', 'Completed', 'Returned','Cancelled']);
        }
        return $query;
    }

}
