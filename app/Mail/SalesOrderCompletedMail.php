<?php

namespace App\Mail;

use App\Models\Sales_Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalesOrderCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sales_Order $salesOrder;

    /**
     * Create a new message instance.
     */
    public function __construct(Sales_Order $salesOrder)
    {
        $this->salesOrder = $salesOrder->load('customer', 'items.product');
    }

    /**
     * Build the message.
     */
    public function build(): static
    {
        return $this->subject("Your Order {$this->salesOrder->so_number} Has Been Completed")
                    ->view('emails.sales_order_completed');
    }
}
