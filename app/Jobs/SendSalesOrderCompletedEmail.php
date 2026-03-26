<?php

namespace App\Jobs;

use App\Mail\SalesOrderCompletedMail;
use App\Models\Sales_Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSalesOrderCompletedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    public Sales_Order $salesOrder;

    /**
     * Create a new job instance.
     */
    public function __construct(Sales_Order $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->salesOrder->load('customer', 'items.product');

        $customerEmail = $this->salesOrder->customer?->email;

        if (!$customerEmail) {
            Log::warning("SendSalesOrderCompletedEmail: No email for customer on SO {$this->salesOrder->so_number}");
            return;
        }

        Mail::to($customerEmail)
            ->send(new SalesOrderCompletedMail($this->salesOrder));

        Log::info("Sales order completion email sent for {$this->salesOrder->so_number} to {$customerEmail}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendSalesOrderCompletedEmail FAILED for SO {$this->salesOrder->so_number}: {$exception->getMessage()}");
    }
}
