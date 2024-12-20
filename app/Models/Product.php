<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
   
    use HasFactory;
    protected $fillable = [
        'slug',
        'material',
        "sku",
        "name",
        "thumbnail",
        "short_description",
        "long_description",
        "view",
        "regular_price",
        "reduced_price",
        "category_id",
        "is_active"
    ];

    protected $casts = [
        'is_active' => "boolean",
        'regular_price' => 'integer',
        'reduced_price' => 'integer',
        'created_at' => ConvertDatetime::class,
        'updated_at' => ConvertDatetime::class,
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product_atts()
    {
        return $this->hasMany(ProductAtt::class);
    }

    public static function generateUniqueSKU($productName, $colorName, $sizeName)
    {
        $removeAccents = fn($string) => iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    
        $productCode = strtoupper(substr($removeAccents($productName), 0, 2));
        $colorCode = strtoupper(substr($removeAccents($colorName), 0, 2));
        $sizeCode = strtoupper($removeAccents($sizeName));
    
        return $productCode . $colorCode . $sizeCode;
    }

    public static function checkAndResolveDuplicateSKUs(array $productAtts)
    {

        $existingSKUs = ProductAtt::pluck('sku')->toArray();
        $existingSKUSet = array_flip($existingSKUs);
        $newSKUSet = [];


        foreach ($productAtts as &$productAtt) {
            $sku = $productAtt['sku'];
            $suffix = 1;

            while (isset($existingSKUSet[$sku]) || isset($newSKUSet[$sku])) {
                $sku = $productAtt['sku'] . '-00' . $suffix;
                $suffix++;
            }

            $productAtt['sku'] = $sku;
            $newSKUSet[$sku] = true;
        }

        return $productAtts;
    }
}
