<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

//        // Tạo admin
//        DB::table('users')->insert([
//            'name' => "admin",
//            'email' => "admin@gmail.com",
//            'email_verified_at' => now(),
//            'password' => Hash::make('password'),
//            'role' => 'admin'
//        ]);

        // Tạo user thường
        for ($i = 0; $i < 10; $i++) {
            DB::table('users')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user'
            ]);
        }

        $users = DB::table('users')->get();
        $userIds = $users->where('role', 'user')->pluck('id')->toArray(); // Lấy danh sách user_id của user thường

        for ($day = 0; $day < 345; $day++) {
            $createdAt = Carbon::now()->startOfYear()->addDays($day);

            $numOrders = rand(10, 30);

            for ($i = 0; $i < $numOrders; $i++) {
                $order = Order::query()->create([
                    'order_code' => strtoupper(Str::random(10)),
                    'user_id' => $userIds[array_rand($userIds)], // Chọn user_id ngẫu nhiên từ danh sách user thường
                    'total_amount' => rand(100000, 1000000),
                    'total_product_amount' => rand(100000, 1000000),
                    'payment_method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())]->value,
                    'order_status' => OrderStatus::DELIVERED->value,
                    'payment_status' => PaymentStatus::PAID->value,
                    'order_address' => fake()->address,
                    'note' => fake()->sentence(),
                    'delivery_fee' => rand(30000, 100000),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $statusArray = [
                    "Chờ xác nhận",
                    "Đã xác nhận",
                    "Chờ lấy hàng",
                    "Đang giao",
                    "Đã giao",
                    "Đã nhận hàng"
                ];

                foreach ($statusArray as $status) {
                    OrderStatusHistory::query()->create([
                        'order_id' => $order->id,
                        'status' => $status,
                        'image' => "https://res.cloudinary.com/dqxshljwn/image/upload/v1733446344/clothing_shop/jb2ayroid0lidqaoab5g.png"
                    ]);
                }
            }
        }

        $orders = DB::table('orders')->get();
        $productAtts = DB::table('product_atts')->get();

        foreach ($orders as $order) {
            foreach ($productAtts->random(rand(1, 5)) as $productAtt) {
                $quantity = rand(1, 5);
                $unit_price = 200000;

                $thumbnail = "https://via.placeholder.com/150"; // URL ảnh giả định

                DB::table('order_details')->insert([
                    'order_id' => $order->id,
                    'product_id' => $productAtt->product_id,
                    'product_att_id' => $productAtt->id,
                    'quantity' => $quantity,
                    'size' => 'XL',
                    'color' => 'red',
                    'product_name' => 'Product N + 1',
                    'unit_price' => $unit_price,
                    'total_amount' => $unit_price * $quantity,
                    'thumbnail' => $thumbnail,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
