<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Sale\SaleOrder;

class OrderController extends Controller
{
    /**
     * Get customer orders list
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $party = $request->user();
            $query = SaleOrder::where('party_id', $party->id);

            if ($request->has('status') && $request->status) {
                $query->where('order_status', $request->status);
            }

            if ($request->has('search') && $request->search) {
                $query->where('order_code', 'like', '%' . $request->search . '%');
            }

            $query->orderBy('created_at', 'desc');
            $orders = $query->paginate(20);

            $formattedOrders = $orders->map(function ($order) {
                $paidAmount = $order->paid_amount ?? 0;
                $grandTotal = $order->grand_total ?? 0;
                
                $dueAmount = $grandTotal - $paidAmount;

                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_date' => $order->order_date,
                    'order_status' => $order->order_status,
                    'grand_total' => (string) $grandTotal,
                    'paid_amount' => (string) $paidAmount,
                    'due_amount' => (string) $dueAmount,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => [
                    'orders' => $formattedOrders,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get orders exception', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'حدث خطأ'], 500);
        }
    }


    /**
     * Get order details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $party = $request->user();
            $order = SaleOrder::where('id', $id)->where('party_id', $party->id)->first();

            if (!$order) {
                return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
            }

            $paidAmount = $order->paid_amount ?? 0;
            $grandTotal = $order->grand_total ?? 0;
            $dueAmount = $grandTotal - $paidAmount;

            // Get tracking number from latest shipment tracking if exists
            $trackingNumber = null;
            if ($order->shipmentTrackings()->exists()) {
                $trackingNumber = $order->shipmentTrackings()->latest()->first()->tracking_number ?? null;
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_code' => $order->order_code,
                        'order_date' => $order->order_date,
                        'order_status' => $order->order_status,
                        'grand_total' => (string) $grandTotal,
                        'paid_amount' => (string) $paidAmount,
                        'due_amount' => (string) $dueAmount,
                        'note' => $order->note,
                        'delivery_date' => $order->delivery_date,
                        'tracking_number' => $trackingNumber,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get order details exception', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Get full order details with all related data
     */
    public function details(Request $request, int $id): JsonResponse
    {
        try {
            $party = $request->user();

            // Load order with all relationships
            $order = SaleOrder::with([
                'party',
                'carrier',
                'itemTransaction.item',
                'paymentTransaction'
            ])
            ->where('id', $id)
            ->where('party_id', $party->id)
            ->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'الطلب غير موجود'
                ], 404);
            }

            // Format party data
            $partyData = [
                'id' => $order->party->id,
                'first_name' => $order->party->first_name ?? '',
                'last_name' => $order->party->last_name ?? '',
                'email' => $order->party->email ?? '',
                'mobile' => $order->party->mobile ?? '',
                'shipping_address' => $order->party->shipping_address ?? null,
                'billing_address' => $order->party->billing_address ?? null,
            ];

            // Format carrier data
            $carrierData = [
                'id' => $order->carrier->id,
                'name' => $order->carrier->name ?? '',
                'email' => $order->carrier->email ?? null,
                'mobile' => $order->carrier->mobile ?? null,
                'phone' => $order->carrier->phone ?? null,
                'whatsapp' => $order->carrier->whatsapp ?? null,
                'address' => $order->carrier->address ?? null,
                'note' => $order->carrier->note ?? null,
                'created_by' => $order->carrier->created_by ?? null,
                'updated_by' => $order->carrier->updated_by ?? null,
                'status' => $order->carrier->status ?? null,
                'created_at' => $order->carrier->created_at?->toDateTimeString(),
                'updated_at' => $order->carrier->updated_at?->toDateTimeString(),
            ];

            // Format item transactions
            $itemTransactions = $order->itemTransaction->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'transaction_type' => $transaction->transaction_type,
                    'item_id' => $transaction->item_id,
                    'quantity' => (string) $transaction->quantity,
                    'unit_price' => (string) $transaction->unit_price,
                    'total' => (string) $transaction->total,
                    'item' => [
                        'id' => $transaction->item->id,
                        'name' => $transaction->item->name,
                        'sku' => $transaction->item->sku,
                    ]
                ];
            });

            // Format payment records
            $paymentRecords = $order->paymentTransaction->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'sale_order_id' => $payment->transaction_id,
                    'amount' => (string) $payment->amount,
                    'payment_type_id' => $payment->payment_type_id,
                    'note' => $payment->note ?? '',
                    'created_at' => $payment->created_at?->toDateTimeString(),
                    'updated_at' => $payment->updated_at?->toDateTimeString(),
                ];
            });

            // Format main order data
            $orderData = [
                'id' => $order->id,
                'order_date' => $order->order_date,
                'due_date' => $order->due_date ?? null,
                'prefix_code' => $order->prefix_code,
                'count_id' => (string) $order->count_id,
                'order_code' => $order->order_code,
                'order_status' => $order->order_status,
                'inventory_status' => $order->inventory_status,
                'inventory_deducted_at' => $order->inventory_deducted_at ?? null,
                'post_delivery_action' => $order->post_delivery_action ?? null,
                'post_delivery_action_at' => $order->post_delivery_action_at ?? null,
                'party_id' => $order->party_id,
                'state_id' => $order->state_id ?? null,
                'carrier_id' => $order->carrier_id,
                'note' => $order->note ?? null,
                'shipping_charge' => (string) ($order->shipping_charge ?? 0),
                'is_shipping_charge_distributed' => $order->is_shipping_charge_distributed ?? 0,
                'round_off' => (string) ($order->round_off ?? 0),
                'grand_total' => (string) ($order->grand_total ?? 0),
                'paid_amount' => (string) ($order->paid_amount ?? 0),
                'currency_id' => $order->currency_id,
                'exchange_rate' => (string) ($order->exchange_rate ?? 1),
                'created_by' => $order->created_by,
                'updated_by' => $order->updated_by,
                'created_at' => $order->created_at?->toDateTimeString(),
                'updated_at' => $order->updated_at?->toDateTimeString(),
                'party' => $partyData,
                'carrier' => $carrierData,
                'item_transactions' => $itemTransactions,
                'payment_records' => $paymentRecords,
            ];

            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل الطلب بنجاح',
                'data' => [
                    'order' => $orderData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get full order details exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الطلب'
            ], 500);
        }
    }
}
