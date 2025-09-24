<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderDetailResource extends JsonResource
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
                'email' => $this->party->email,
                'address' => $this->party->address,
                'latitude' => $this->party->latitude,
                'longitude' => $this->party->longitude
            ],
            'carrier' => [
                'id' => $this->carrier->id,
                'name' => $this->carrier->name
            ],
            'items' => $this->getItemTransactions(),
            'totals' => [
                'subtotal' => $this->getSubtotal(),
                'discount' => $this->getTotalDiscount(),
                'tax' => $this->getTotalTax(),
                'shipping' => $this->shipping_charge,
                'total' => $this->grand_total
            ],
            'payments' => $this->getPaymentTransactions(),
            'total_amount' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->grand_total - $this->paid_amount,
            'status' => $this->order_status,
            'delivery_status' => $this->order_status,
            'payment_status' => $this->getPaymentStatus(),
            'inventory_status' => $this->inventory_status,
            'notes' => $this->note,
            'signature' => $this->signature,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get item transactions
     *
     * @return array
     */
    private function getItemTransactions()
    {
        return $this->itemTransaction->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'item_id' => $transaction->item->id,
                'name' => $transaction->item->name,
                'sku' => $transaction->item->sku,
                'quantity' => $transaction->quantity,
                'unit' => $transaction->unit->name ?? '',
                'price' => $transaction->unit_price,
                'discount' => $transaction->discount,
                'tax' => $transaction->tax_amount,
                'total' => $transaction->total,
                'batch_number' => $transaction->batch->batch_no ?? null,
                'serial_numbers' => $transaction->itemSerialTransaction->pluck('itemSerialMaster.serial_code')->toArray()
            ];
        })->toArray();
    }

    /**
     * Get payment transactions
     *
     * @return array
     */
    private function getPaymentTransactions()
    {
        return $this->paymentTransaction->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_type' => $payment->paymentType->name ?? '',
                'payment_date' => $payment->transaction_date,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->note
            ];
        })->toArray();
    }

    /**
     * Get subtotal (sum of item totals)
     *
     * @return float
     */
    private function getSubtotal()
    {
        return $this->itemTransaction->sum(function ($transaction) {
            return $transaction->quantity * $transaction->unit_price;
        });
    }

    /**
     * Get total discount
     *
     * @return float
     */
    private function getTotalDiscount()
    {
        return $this->itemTransaction->sum('discount_amount');
    }

    /**
     * Get total tax
     *
     * @return float
     */
    private function getTotalTax()
    {
        return $this->itemTransaction->sum('tax_amount');
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
