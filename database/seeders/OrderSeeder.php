<?php
//
//namespace Database\Seeders;
//
//use App\Enums\OrderStatus;
//use App\Enums\PaymentMethod;
//use App\Enums\PaymentStatus;
//use App\Models\Order;
//use App\Models\OrderStatusHistory;
//use Carbon\Carbon;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
//use Illuminate\Database\Seeder;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Hash;
//use Illuminate\Support\Str;
//
//class OrderSeeder extends Seeder
//{
//    /**
//     * Run the database seeds.
//     */
//    public function run(): void
//    {
//        $users = DB::table('users')->insert([
//            'name' => "admin",
//            'email' => "admin@gmail.com",
//            'email_verified_at' => now(),
//            'password' => Hash::make('password'),
//            'role' => 'admin'
//        ]);
//        for ($day = 0; $day < 345; $day++) {
//            $createdAt = Carbon::now()->startOfYear()->addDays($day);
//
//            $numOrders = rand(10, 30);
//
//            for ($i = 0; $i < $numOrders; $i++) {
//                $order = Order::query()->create([
//                    'order_code' => strtoupper(Str::random(10)),
//                    'user_id' => 1,
//                    'total_amount' => rand(100000, 1000000),
//                    'total_product_amount' => rand(100000, 1000000),
//                    'payment_method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())]->value,
//                    'order_status' => OrderStatus::DELIVERED->value,
//                    'payment_status' => PaymentStatus::PAID->value,
//                    'order_address' => fake()->address,
//                    'note' => fake()->sentence(),
//                    'delivery_fee' => rand(30000, 100000),
//                    'created_at' => $createdAt,
//                    'updated_at' => $createdAt,
//                ]);
//
//                $statusArray = [
//                    "Chờ xác nhận",
//                    "Đã xác nhận",
//                    "Chờ lấy hàng",
//                    "Đang giao",
//                    "Đã giao",
//                    "Đã nhận hàng"
//                ];
//
//                foreach ($statusArray as $status) {
//                    OrderStatusHistory::query()->create([
//                        'order_id' => $order->id,
//                        'status' => $status,
//                        'image' => "https://res.cloudinary.com/dqxshljwn/image/upload/v1733446344/clothing_shop/jb2ayroid0lidqaoab5g.png"
//                    ]);
//                }
//            }
//        }
//
//        $orders = DB::table('orders')->get();
//        $productAtts = DB::table('product_atts')->get();
//
//
//        foreach ($orders as $order) {
//            foreach ($productAtts->random(rand(1, 5)) as $productAtt) {
//                $quantity = rand(1, 5);
//                $unit_price = 200000;
//
//                // Lấy một ảnh ngẫu nhiên từ mảng $images
//                $thumbnail = $images[array_rand($images)];
//
//                DB::table('order_details')->insert([
//                    'order_id' => $order->id,
//                    'product_id' => $productAtt->product_id,
//                    'product_att_id' => $productAtt->id,
//                    'quantity' => $quantity,
//                    'size' => 'XL',
//                    'color' => 'red',
//                    'product_name' => 'Product N + 1',
//                    'unit_price' => $unit_price,
//                    'total_amount' => $unit_price * $quantity,
//                    'thumbnail' => $thumbnail,
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//        }
//    }
//
//}
//}
