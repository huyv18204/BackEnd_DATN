<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\Order;

class CheckOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $minute = Carbon::now()->subMinutes(1);

        $orders = Order::query()->where('payment_method', 'VNPAY')
            ->where('payment_status', 'Chưa thanh toán')
            ->where('created_at', '<=', $minute)
            ->get();

        foreach ($orders as $order) {
            $order->update(['payment_method' => 'Thanh toán thất bại']);
        }
    }
}
