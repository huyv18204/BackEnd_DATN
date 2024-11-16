<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        DB::table('product_color_images')->truncate();
        DB::table('colors')->truncate();
        DB::table('sizes')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_details')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        // Users seeding
        for ($i = 1; $i <= 10; $i++) {
            DB::table('users')->insert([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'address' => fake()->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Categories seeding
        for ($i = 1; $i <= 10; $i++) {

            $currentDay = date('d');
            $currentMonth = date('m');
            $prevCode = "CA" . $currentDay . $currentMonth;

            $stt = DB::table('categories')->where("category_code", "LIKE", $prevCode . "%")->orderByDesc('id')->first();
            if ($stt) {
                $parts = explode('-', $stt->category_code);
                $lastPart = (int)end($parts) + 1;
                $categoryCode = $prevCode . '-' . str_pad($lastPart, 2, '0', STR_PAD_LEFT);
            } else {
                $categoryCode = $prevCode . '-' . "01";
            }

            $name = fake()->name();
            DB::table('categories')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
                'category_code' => $categoryCode
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
            DB::table('products')->insert([
                "slug" => Str::slug($name),
                "material" => "Vai",
                'name' => $name,
                "thumbnail" => fake()->imageUrl(30, 30),
                'short_description' => 'Short description for product ' . $i,
                'long_description' => 'Long description for product ' . $i,
                "regular_price" => rand(30000, 1000000),
                "reduced_price" => rand(30000, 1000000),
                'category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = DB::table('products')->get();
        $sizes = DB::table('sizes')->get();
        $colors = DB::table('colors')->get();

        foreach ($products as $product) {
            foreach ($colors as $color) {
                DB::table('product_color_images')->insert([
                    'product_id' => $product->id,
                    'color_id' => $color->id,
                    'image' => 'image_' . $color->name . '.quandz',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $productCode = strtoupper(substr($product->name, 0, 2));
                    $colorCode = strtoupper(substr($color->name, 0, 2));
                    $sizeCode = strtoupper($size->name);

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
                        'size_id' => $size->id,
                        'color_id' => $color->id,
                        'stock_quantity' => rand(1, 10),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Orders seeding
        $users = DB::table('users')->get();
        for ($i = 1; $i <= 10; $i++) {
            $userId = $users->random()->id;
            DB::table('orders')->insert([
                'order_code' => strtoupper(Str::random(10)),
                'user_id' => $userId,
                'total_amount' => rand(100000, 1000000),
                'payment_method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())]->value,
                'order_status' => OrderStatus::cases()[array_rand(OrderStatus::cases())]->value,
                'payment_status' => PaymentStatus::cases()[array_rand(PaymentStatus::cases())]->value,
                'order_address' => fake()->address,
                'note' => fake()->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Order details seeding
        $orders = DB::table('orders')->get();
        $productAtts = DB::table('product_atts')->get();
        foreach ($orders as $order) {
            foreach ($productAtts->random(rand(1, 5)) as $productAtt) {
                $quantity = rand(1, 5);
                $unit_price = 200000;
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
                    'thumbnail' => 'fake/image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
