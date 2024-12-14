<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOrderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update order status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::where('order_status', 'Chờ lấy hàng')
            ->where('updated_at', '<', Carbon::now()->subMinutes(1))
            ->get();

        foreach ($orders as $order) {
            $order->update(['order_status' => 'Đã xác nhận']);
            $this->info("Order ID {$order->id} đã được chuyển trạng thái thành 'confirmed'");
        }
    }
}