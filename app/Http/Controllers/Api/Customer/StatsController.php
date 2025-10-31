<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    /**
     * Get customer statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customer = $request->user();

            // Get total orders count
            $totalOrders = $customer->saleOrders()->count();

            // Get pending orders (قيد الانتظار)
            $pendingOrders = $customer->saleOrders()
                ->where('order_status', 'pending')
                ->count();

            // Get active orders (pending, processing, shipped, out_for_delivery)
            $activeOrders = $customer->saleOrders()
                ->whereIn('order_status', ['pending', 'processing', 'shipped', 'delivery'])
                ->count();

            // Get completed orders (delivered)
            $completedOrders = $customer->saleOrders()
                ->where('order_status', 'pod')
                ->count();

            // Calculate total spent (sum of grand_total for delivered orders)
            $totalSpent = $customer->saleOrders()
                ->where('order_status', 'pod')
                ->sum('grand_total') ?? 0;

            // Get pending payments (to_pay from customer balance)
            $pendingPayments = $customer->to_pay ?? 0;

            $stats = [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'total_spent' => (string) $totalSpent,
                'pending_payments' => (string) $pendingPayments,
                
            ];

            Log::info('Customer stats retrieved successfully', [
                'customer_id' => $customer->id,
                'stats' => $stats
            ]);

            return response()->json([
                'status' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get customer stats exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }
}
