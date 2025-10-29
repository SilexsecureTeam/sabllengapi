<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject($this->getSubject())
                    ->view('emails.orders.status');
    }

    protected function getSubject()
    {
        switch ($this->order->order_status) {
            case 'Order Placed':
            case 'Packed':
            case 'Processing':
                return 'Thank you for your order!';
            case 'Shipped':
                return 'Your order is on its way!';
            case 'Delivered':
                return 'Your order has been delivered.';
            default:
                return 'Order Update';
        }
    }
}
