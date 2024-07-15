<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Quần Nam'],
            ['name' => 'Quần Nữ'],
            ['name' => 'Quần Trẻ em'],
            ['name' => 'Áo Nam'],
            ['name' => 'Áo Nữ'],
            ['name' => 'Áo Trẻ em'],
        ]);


        $colors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White'];

        foreach ($colors as $color) {
            DB::table('product_colors')->insert([
                'color' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $size) {
            DB::table('product_sizes')->insert([
                'size' => $size,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $countCategories = DB::table('categories')->count("id");

        for ($i = 1; $i <= 10; $i++) {
            $sku = strtoupper(fake()->regexify('[A-Za-z0-9]{8}'));
            $name = fake()->realText . $i;
            DB::table('products')->insert([
                "slug" => Str::slug($name . '-' . $sku),
                "material"=> "Vai",
                "sku" => $sku,
                'name' => 'Product ' . $i,
                "thumbnail" => fake()->imageUrl(30, 30),
                'short_description' => 'Short description for product ' . $i,
                'long_description' => 'Long description for product ' . $i,
                "regular_price" => rand(30000, 1000000),
                "reduced_price" => rand(30000, 1000000),
                'category_id' => rand(0, $countCategories),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = DB::table('products')->get();
        $sizes = DB::table('product_sizes')->get();
        $colors = DB::table('product_colors')->get();

        foreach ($products as $product) {
            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    DB::table('product_variants')->insert([
                        'product_id' => $product->id,
                        'product_size_id' => $size->id,
                        'product_color_id' => $color->id,
                        "regular_price" => rand(30000, 1000000),
                        "reduced_price" => rand(30000, 1000000),
                        'stock' => rand(1, 10),
                        'variants_image' => fake()->imageUrl(30, 30),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
