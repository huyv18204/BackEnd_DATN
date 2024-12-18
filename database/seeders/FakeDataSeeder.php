<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_atts')->truncate();
        DB::table('colors')->truncate();
        DB::table('sizes')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_details')->truncate();
        DB::table('users')->truncate();
        DB::table('vehicles')->truncate();
        DB::table('delivery_people')->truncate();
        DB::table('order_status_histories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $images = [
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446344/clothing_shop/jb2ayroid0lidqaoab5g.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446344/clothing_shop/i7m6envqrbowkj6rzzqf.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/c9dcxisaaimpsqeivagz.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/yaw6lr2aepa2btg1n5em.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/a5rkh9godczhjjrpxvvb.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/lfe98fan695nly5n4yxe.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/xpayxghsrayvzxllhtxj.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446343/clothing_shop/tqsdcw1rw1ieecg7hwcr.png',
            'https://res.cloudinary.com/dqxshljwn/image/upload/v1733446342/clothing_shop/qrofsvpk54u0oqkcnxvi.jpg'
        ];

        // Users seeding
        for ($i = 1; $i <= 10; $i++) {
            DB::table('users')->insert([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'address' => fake()->address,
                'created_at' => now(),
                'updated_at' => now(),
                // Fake sdt
                'phone' => fake()->numerify('##########'),
            ]);
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'abc@gmail.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );


        $categories = ['Áo khoác', 'Áo Sơ Mi', 'Áo Chống Nắng', 'Áo Nỉ', 'Váy'];
        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category),
                'category_code' => $this->generateCategoryCode(),
                'is_active' => true,
            ]);
        }

        // Colors seeding
        $colors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White'];
        foreach ($colors as $color) {
            DB::table('colors')->insert([
                'name' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Sizes seeding
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $size) {
            DB::table('sizes')->insert([
                'name' => $size,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Products seeding
        $countCategories = DB::table('categories')->count("id");
        for ($i = 1; $i <= 10; $i++) {
            $categoryId = rand(1, $countCategories);
            $name = fake()->name() . $i;
            $thumbnail = $images[array_rand($images)];

            DB::table('products')->insert([
                "slug" => Str::slug($name),
                "material" => "Vai",
                'name' => $name,
                "thumbnail" => $thumbnail,
                'short_description' => 'Short description for product ' . $i,
                'long_description' => 'Long description for product ' . $i,
                "regular_price" => rand(30000, 1000000),
                "reduced_price" => rand(10000, 29999),
                'category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = DB::table('products')->get();
        $sizes = DB::table('sizes')->get();
        $colors = DB::table('colors')->get();

        foreach ($products as $product) {
            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $productCode = strtoupper(substr($product->name, 0, 2));
                    $colorCode = strtoupper(substr($color->name, 0, 2));
                    $sizeCode = strtoupper($size->name);
                    $thumbnail = $images[array_rand($images)];
                    $skuBase = $productCode . $colorCode . $sizeCode;
                    $skuCode = '001';
                    do {
                        $sku = $skuBase . $skuCode;
                        $exists = DB::table('product_atts')
                            ->where('sku', $sku)
                            ->exists();

                        if ($exists) {
                            $skuCode = str_pad((int)$skuCode + 1, 3, '0', STR_PAD_LEFT);
                        }
                    } while ($exists);
                    DB::table('product_atts')->insert([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        "regular_price" => rand(30000, 1000000),
                        "reduced_price" => rand(10000, 29999),
                        'size_id' => $size->id,
                        'color_id' => $color->id,
                        'stock_quantity' => rand(1, 10),
                        'is_active' => true,
                        'image' => $thumbnail,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $users = DB::table('users')->get();
        for ($day = 0; $day < 345; $day++) {
            $createdAt = Carbon::now()->startOfYear()->addDays($day);

            $numOrders = rand(10, 30);

            for ($i = 0; $i < $numOrders; $i++) {
                $userId = $users->random()->id;

                // Tạo đơn hàng
                $order = Order::query()->create([
                    'order_code' => strtoupper(Str::random(10)),
                    'user_id' => $userId,
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

                // Tạo lịch sử trạng thái cho từng đơn hàng
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

        // Mảng chứa các URL hình ảnh giả để chọn ngẫu nhiên


        foreach ($orders as $order) {
            foreach ($productAtts->random(rand(1, 5)) as $productAtt) {
                $quantity = rand(1, 5);
                $unit_price = 200000;

                // Lấy một ảnh ngẫu nhiên từ mảng $images
                $thumbnail = $images[array_rand($images)];

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

    protected function generateCategoryCode()
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        $prevCode = "CA" . $currentDay . $currentMonth;

        $stt = DB::table('categories')->where("category_code", "LIKE", $prevCode . "%")->orderByDesc('id')->first();
        if ($stt) {
            $parts = explode('-', $stt->category_code);
            $lastPart = (int)end($parts) + 1;
            return $prevCode . '-' . str_pad($lastPart, 2, '0', STR_PAD_LEFT);
        } else {
            return $prevCode . '-' . "01";
        }
    }
}
