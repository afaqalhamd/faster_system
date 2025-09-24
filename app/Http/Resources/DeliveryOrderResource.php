<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'prefix_code' => $this->prefix_code,
            'count_id' => $this->count_id,
            'order_date' => $this->order_date,
            'due_date' => $this->due_date,
            'party' => [
                'id' => $this->party->id,
                'name' => $this->party->first_name . ' ' . $this->party->last_name,
                'phone' => $this->party->phone,
                'address' => $this->party->address,
                'latitude' => $this->party->latitude,
                'longitude' => $this->party->longitude
            ],
            'carrier' => [
                'id' => $this->carrier->id,
                'name' => $this->carrier->name
            ],
            'total_amount' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->grand_total - $this->paid_amount,
            'status' => $this->order_status,
            'delivery_status' => $this->order_status,
            'payment_status' => $this->getPaymentStatus(),
            'items_count' => $this->itemTransaction->count(),
            'notes' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get payment status based on amounts
     *
     * @return string
     */
    private function getPaymentStatus()
    {
        $balance = $this->grand_total - $this->paid_amount;

        if ($balance == 0) {
            return 'paid';
        } elseif ($this->paid_amount == 0) {
            return 'unpaid';
        } else {
            return 'partially_paid';
        }
    }
}
