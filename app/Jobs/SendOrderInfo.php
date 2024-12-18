<?php

namespace App\Jobs;

use App\Mail\OrderInfo;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $email;
    public string $status;
    public Order $order;
    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $status, Order $order)
    {
        $this->email = $email;
        $this->status = $status;
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new OrderInfo($this->email, $this->status, $this->order));
    }
}
