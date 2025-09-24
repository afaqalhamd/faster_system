<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Sale\SaleOrder;
use App\Services\GeneralDataService;
use Illuminate\Support\Facades\Auth;

class StatusController extends Controller
{
    protected $generalDataService;

    public function __construct(GeneralDataService $generalDataService)
    {
        $this->generalDataService = $generalDataService;
    }

    /**
     * Get available delivery statuses
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$user || !$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Get sale order statuses
            $statuses = $this->generalDataService->getSaleOrderStatus();

            // Filter statuses for delivery users
            $deliveryStatuses = collect($statuses)->filter(function ($status) {
                return in_array($status['id'], ['Delivery', 'POD', 'Returned', 'Cancelled']);
            })->values();

            return response()->json([
                'status' => true,
                'data' => $deliveryStatuses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve statuses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user is delivery personnel
     *
     * @param mixed $user
     * @return bool
     */
    private function isDeliveryUser($user): bool
    {
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }
}
