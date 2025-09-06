<?php

namespace App\Http\Controllers\Reports;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\User;
use App\Services\StockImpact;
use App\Enums\ItemTransactionUniqueCode;

class StockMovementReportController extends Controller
{
    use FormatsDateInputs;
    use FormatNumber;

    private $stockImpact;

    function __construct(StockImpact $stockImpact)
    {
        $this->stockImpact = $stockImpact;
    }

    /**
     * عرض صفحة تقرير حركة المخزون خلال 24 ساعة
     */
    public function index()
    {
        return view('report.stock-movement.index');
    }

    /**
     * الحصول على بيانات العناصر الداخلة للمخزون خلال 24 ساعة
     * @return JsonResponse
     */
    public function getIncomingItems(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            $itemId = $request->input('item_id');
            $brandId = $request->input('brand_id');

            // إذا لم يتم تحديد المستودع، استخدم المستودعات المتاحة للمستخدم
            $warehouseIds = $warehouseId ? [$warehouseId] : User::find(auth()->id())->getAccessibleWarehouses()->pluck('id');

            // تاريخ اليوم والأمس (24 ساعة)
            $today = Carbon::now();
            $yesterday = Carbon::now()->subHours(24);

            // الحصول على العناصر الداخلة
            $incomingItems = ItemTransaction::with('item.brand', 'warehouse', 'transaction')
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereBetween('created_at', [$yesterday, $today])
                ->where(function($query) {
                    $query->where('unique_code', ItemTransactionUniqueCode::PURCHASE->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::STOCK_RECEIVE->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::SALE_RETURN->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::PURCHASE_RETURN->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::ITEM_OPENING->value);
                })
                ->when($itemId, function ($query) use ($itemId) {
                    return $query->where('item_id', $itemId);
                })
                ->when($brandId, function ($query) use ($brandId) {
                    return $query->whereHas('item', function ($query) use ($brandId) {
                        $query->where('brand_id', $brandId);
                    });
                })
                ->get();

            if ($incomingItems->count() == 0) {
                throw new \Exception('لا توجد عناصر داخلة خلال 24 ساعة الماضية!');
            }

            $recordsArray = [];

            foreach ($incomingItems as $item) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($item->transaction_date),
                    'transaction_time' => Carbon::parse($item->created_at)->format('H:i:s'),
                    'transaction_type' => $item->transaction_type,
                    'invoice_or_bill_code' => $item->transaction ? $item->transaction->getTableCode() : '',
                    'party_name' => ($item->transaction && $item->transaction->party) ? $item->transaction->party->getFullName() : '',
                    'warehouse' => $item->warehouse->name,
                    'item_name' => $item->item->name,
                    'brand_name' => $item->item->brand->name ?? '',
                    'quantity' => $this->formatWithPrecision($item->quantity, comma: false),
                    'stock_impact' => $this->stockImpact->returnStockImpact($item->unique_code, $item->quantity)['quantity'],
                ];
            }

            return response()->json([
                'status' => true,
                'message' => "تم استرجاع البيانات بنجاح!",
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * الحصول على بيانات العناصر الخارجة من المخزون خلال 24 ساعة
     * @return JsonResponse
     */
    public function getOutgoingItems(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            $itemId = $request->input('item_id');
            $brandId = $request->input('brand_id');

            // إذا لم يتم تحديد المستودع، استخدم المستودعات المتاحة للمستخدم
            $warehouseIds = $warehouseId ? [$warehouseId] : User::find(auth()->id())->getAccessibleWarehouses()->pluck('id');

            // تاريخ اليوم والأمس (24 ساعة)
            $today = Carbon::now();
            $yesterday = Carbon::now()->subHours(24);

            // الحصول على العناصر الخارجة
            $outgoingItems = ItemTransaction::with('item.brand', 'warehouse', 'transaction')
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereBetween('created_at', [$yesterday, $today])
                ->where(function($query) {
                    $query->where('unique_code', ItemTransactionUniqueCode::SALE->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::STOCK_TRANSFER->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::PURCHASE_RETURN->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::SALE_RETURN->value)
                          ->orWhere('unique_code', ItemTransactionUniqueCode::QUOTATION->value);
                })
                ->when($itemId, function ($query) use ($itemId) {
                    return $query->where('item_id', $itemId);
                })
                ->when($brandId, function ($query) use ($brandId) {
                    return $query->whereHas('item', function ($query) use ($brandId) {
                        $query->where('brand_id', $brandId);
                    });
                })
                ->get();

            if ($outgoingItems->count() == 0) {
                throw new \Exception('لا توجد عناصر خارجة خلال 24 ساعة الماضية!');
            }

            $recordsArray = [];

            foreach ($outgoingItems as $item) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($item->transaction_date),
                    'transaction_time' => Carbon::parse($item->created_at)->format('H:i:s'),
                    'transaction_type' => $item->transaction_type,
                    'invoice_or_bill_code' => $item->transaction ? $item->transaction->getTableCode() : '',
                    'party_name' => ($item->transaction && $item->transaction->party) ? $item->transaction->party->getFullName() : '',
                    'warehouse' => $item->warehouse->name,
                    'item_name' => $item->item->name,
                    'brand_name' => $item->item->brand->name ?? '',
                    'quantity' => $this->formatWithPrecision($item->quantity, comma: false),
                    'stock_impact' => $this->stockImpact->returnStockImpact($item->unique_code, $item->quantity)['quantity'],
                ];
            }

            return response()->json([
                'status' => true,
                'message' => "تم استرجاع البيانات بنجاح!",
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}
