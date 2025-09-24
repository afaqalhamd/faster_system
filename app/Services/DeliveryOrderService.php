<?php

namespace App\Services;

use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderService
{
    /**
     * Get delivery orders for a user
     *
     * @param User $user
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getDeliveryOrders(User $user, array $filters = [])
    {
        $query = SaleOrder::with(['party', 'carrier'])
            ->where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('order_status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_code', 'like', "%{$searchTerm}%")
                    ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                        $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Validate if user can access order
     *
     * @param User $user
     * @param SaleOrder $order
     * @return bool
     */
    public function canUserAccessOrder(User $user, SaleOrder $order): bool
    {
        return $user->carrier_id == $order->carrier_id &&
               in_array($order->order_status, ['Delivery', 'POD', 'Returned', 'Cancelled']);
    }

    /**
     * Check if user is delivery personnel
     *
     * @param User $user
     * @return bool
     */
    public function isDeliveryUser(User $user): bool
    {
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }

    /**
     * Get payment status based on amounts
     *
     * @param SaleOrder $order
     * @return string
     */
    public function getPaymentStatus(SaleOrder $order): string
    {
        $balance = $order->grand_total - $order->paid_amount;

        if ($balance == 0) {
            return 'paid';
        } elseif ($order->paid_amount == 0) {
            return 'unpaid';
        } else {
            return 'partially_paid';
        }
    }
}
