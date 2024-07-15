<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->truncate();
        for ($i = 0; $i < 10; $i++) {
            DB::table('customers')->insert([
                "name" => fake()->realText(55),
                "email" => fake()->email(),
                "phone" => fake()->phoneNumber(),
                "address" => "hà noi",
                "password" => hash('sha256', 'password'),
                "image" => fake()->imageUrl(100, 100)
            ]);
        }
    }
}
