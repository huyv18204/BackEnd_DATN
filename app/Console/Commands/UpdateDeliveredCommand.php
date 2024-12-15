<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDeliveredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-delivered-command';

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
        $orders = Order::where('order_status', 'Đã giao')
            ->where('updated_at', '<', Carbon::now()->subMinutes(1))
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'order_status' => 'Đã nhận hàng',
            ]);
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'order_status' => "Đã nhận hàng",
            ]);
            $this->info("Order ID {$order->id} đã được chuyển trạng thái thành 'Đã nhận hàng'");
        }
    }
}
