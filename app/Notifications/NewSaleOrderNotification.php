<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Sale\SaleOrder;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class NewSaleOrderNotification extends Notification
{
    use Queueable;

    protected $saleOrder;

    public function __construct(SaleOrder $saleOrder)
    {
        $this->saleOrder = $saleOrder;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلب بيع جديد',
            'message' => 'تم إنشاء طلب بيع جديد برقم #' . $this->saleOrder->order_code,
            'sale_order_id' => $this->saleOrder->id,
            'url' => route('sale.order.details', $this->saleOrder->id),
            'sound' => 'notification.mp3',
            'icon' => 'bx-cart-alt'
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'title' => 'طلب بيع جديد',
            'message' => 'تم إنشاء طلب بيع جديد برقم #' . $this->saleOrder->order_code,
            'sale_order_id' => $this->saleOrder->id,
            'url' => route('sale.order.details', $this->saleOrder->id),
            'sound' => 'notification.mp3',
            'icon' => 'bx-cart-alt'
        ];
    }

    // public function toFirebase($notifiable)
    // {
    //     if (!$notifiable->fc_token) {
    //         return;
    //     }

    //     try {
    //         $credentialsFile = storage_path('app/authenticationapp-86aae-firebase-adminsdk-617cw-26f69fbfe1.json');
    //         $firebase = (new Factory)->withServiceAccount($credentialsFile);
    //         $messaging = $firebase->createMessaging();

    //         $message = CloudMessage::withTarget('token', $notifiable->fc_token)
    //             ->withNotification([
    //                 'title' => 'طلب بيع جديد',
    //                 'body' => 'تم إنشاء طلب بيع جديد برقم #' . $this->saleOrder->order_code
    //             ])
    //             ->withData([
    //                 'sale_order_id' => (string)$this->saleOrder->id,
    //                 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    //                 'sound' => 'notification.mp3',
    //                 'priority' => 'high'
    //             ])->withTimeToLive(0);

    //         return $messaging->send($message);
    //     } catch (\Exception $e) {
    //         Log::error('Firebase Notification Error: ' . $e->getMessage());
    //         return null;
    //     }
    // }
}