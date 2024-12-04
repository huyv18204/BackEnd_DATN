<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('provinces')->truncate();
        DB::table('districts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $districts = [
            ['name' => 'Quận Nam Từ Liêm', 'code' => 3440],
            ['name' => 'Huyện Thường Tín', 'code' => 3303],
            ['name' => 'Huyện Phú Xuyên', 'code' => 3255],
            ['name' => 'Huyện Quốc Oai', 'code' => 2004],
            ['name' => 'Huyện Chương Mỹ', 'code' => 1915],
            ['name' => 'Huyện Ứng Hòa', 'code' => 1810],
            ['name' => 'Huyện Thanh Oai', 'code' => 1809],
            ['name' => 'Huyện Thạch Thất', 'code' => 1808],
            ['name' => 'Huyện Phúc Thọ', 'code' => 1807],
            ['name' => 'Huyện Mỹ Đức', 'code' => 1806],
            ['name' => 'Huyện Hoài Đức', 'code' => 1805],
            ['name' => 'Huyện Đan Phượng', 'code' => 1804],
            ['name' => 'Huyện Ba Vì', 'code' => 1803],
            ['name' => 'Thị xã Sơn Tây', 'code' => 1711],
            ['name' => 'Huyện Thanh Trì', 'code' => 1710],
            ['name' => 'Huyện Gia Lâm', 'code' => 1703],
            ['name' => 'Huyện Sóc Sơn', 'code' => 1583],
            ['name' => 'Huyện Đông Anh', 'code' => 1582],
            ['name' => 'Huyện Mê Linh', 'code' => 1581],
            ['name' => 'Quận Hà Đông', 'code' => 1542],
            ['name' => 'Quận Thanh Xuân', 'code' => 1493],
            ['name' => 'Quận Tây Hồ', 'code' => 1492],
            ['name' => 'Quận Long Biên', 'code' => 1491],
            ['name' => 'Quận Hoàng Mai', 'code' => 1490],
            ['name' => 'Quận Hoàn Kiếm', 'code' => 1489],
            ['name' => 'Quận Hai Bà Trưng', 'code' => 1488],
            ['name' => 'Quận Đống Đa', 'code' => 1486],
            ['name' => 'Quận Cầu Giấy', 'code' => 1485],
            ['name' => 'Quận Ba Đình', 'code' => 1484],
            ['name' => 'Quận Bắc Từ Liêm', 'code' => 1482],
        ];

        DB::table('provinces')->insert([
            "name" => "Hà Nội",
            'code' => '201',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($districts as $district) {
            DB::table('districts')->insert([
                'name' => $district['name'],
                'province_code' => '201',
                'code' => $district['code'],
                'shipping_fee' => rand(30000, 60000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
