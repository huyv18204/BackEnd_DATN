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

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_att_size')->truncate();
        DB::table('product_atts')->truncate();
        DB::table('colors')->truncate();
        DB::table('sizes')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        for($i = 1; $i <= 10; $i++){
            $name = fake()->name();

            $words = explode(' ', $name);
            $initials = '';
            foreach ($words as $word) {
                $initials .= mb_substr($word, 0, 1);
            }
            DB::table('categories')->insert([
                'name' => $name,
                'slug' =>Str::slug($name),
                'sku' => strtoupper($initials)
            ]);
        }
        $colors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White'];

        foreach ($colors as $color) {
            DB::table('colors')->insert([
                'name' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $size) {
            DB::table('sizes')->insert([
                'name' => $size,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $countCategories = DB::table('categories')->count("id");

        for ($i = 1; $i <= 10; $i++) {
            $categoryId = rand(1, $countCategories);
            $category = DB::table('categories')->where('id',$categoryId)->first();
            $currentDay = date('d');
            $currentMonth = date('m');
            $DayAndMonth = $category->sku.$currentDay.$currentMonth;

            $stt = DB::table('products')->where("sku", "LIKE", $DayAndMonth."%")->orderByDesc('id')->first();

            if($stt){
                $parts = explode('-', $stt->sku);
                $lastPart =(int)end($parts) + 1;
                $sku = $category->sku.$currentDay.$currentMonth.'-'.str_pad($lastPart, 3, '0', STR_PAD_LEFT);;
            }else{
                $sku = $category->sku.$currentDay.$currentMonth.'-'. "001";
            }
            $name = fake()->name() . $i;
            DB::table('products')->insert([
                "slug" => Str::slug($name . '-' . $sku),
                "material"=> "Vai",
                "sku" => $sku,
                'name' => $name,
                "thumbnail" => fake()->imageUrl(30, 30),
                'short_description' => 'Short description for product ' . $i,
                'long_description' => 'Long description for product ' . $i,
                "regular_price" => rand(30000, 1000000),
                "reduced_price" => rand(30000, 1000000),
                'category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = DB::table('products')->get();
        $sizes = DB::table('sizes')->get();
        $colors = DB::table('colors')->get();

        foreach ($products as $product) {
                foreach ($colors as $color) {
                    DB::table('product_atts')->insert([
                        'product_id' => $product->id,
                        'color_id' => $color->id,
                        'image' => fake()->imageUrl(30, 30),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

        }

        $productAtts = DB::table('product_atts')->get();
        foreach ($productAtts as $productAtt){
            foreach ($sizes as $size) {
                DB::table('product_att_size')->insert([
                   'product_att_id' => $productAtt->id,
                    'size_id' => $size->id,
                    'stock_quantity' => rand(1, 10),
                ]);

            }
        }
    }
}
