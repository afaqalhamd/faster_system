<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Sale\SaleOrder;

class SaleOrderStatusNotification extends Notification
{
    use Queueable;

    protected $saleOrder;
    protected $status;

    public function __construct(SaleOrder $saleOrder, $status)
    {
        $this->saleOrder = $saleOrder;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تحديث حالة الطلب',
            'message' => sprintf('تم تغيير حالة الطلب رقم %s إلى %s', $this->saleOrder->order_code, $this->status),
            'url' => route('sale.order.details', $this->saleOrder->id),
            'icon' => 'bx bx-refresh',
            'time' => now(),
            'sound' => 'notification.mp3',
            'order_id' => $this->saleOrder->id
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'title' => 'تحديث حالة الطلب',
            'message' => sprintf('تم تغيير حالة الطلب رقم %s إلى %s', $this->saleOrder->order_code, $this->status),
            'url' => route('sale.order.details', $this->saleOrder->id),
            'icon' => 'bx bx-refresh',
            'time' => now(),
            'sound' => 'notification.mp3',
            'order_id' => $this->saleOrder->id
        ];
    }
}