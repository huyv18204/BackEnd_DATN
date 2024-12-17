<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateCancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-cancel-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update orders have fails payment status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::where('order_status', 'Chờ xác nhận')
            ->where('payment_status', 'Thanh toán thất bại')
            ->where('updated_at', '<', Carbon::now()->subMinutes(1))
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'order_status' => 'Đã huỷ',
            ]);
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'order_status' => "Đã huỷ",
            ]);
            $this->info("Order ID {$order->id} đã được chuyển trạng thái thành 'Đã huỷ'");
        }
    }
}
