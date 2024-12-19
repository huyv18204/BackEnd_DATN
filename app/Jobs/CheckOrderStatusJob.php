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

        // Tìm các đơn hàng chưa thanh toán và quá hạn
        $orders = Order::where('payment_method', 'VNPAY')
            ->where('payment_status', 'Chưa thanh toán')
            ->where('created_at', '<=', $minute)
            ->get();

        foreach ($orders as $order) {
            // Cập nhật trạng thái sang "thanh toán thất bại"
            $order->update(['payment_status' => 'Thanh toán thất bại']);
        }
    }
}
