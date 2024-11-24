<?php

namespace App\Models;

use App\Casts\ConvertDatetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'slug',
        'material',
        "sku",
        "gallery",
        "name",
        "thumbnail",
        "short_description",
        "long_description",
        "view",
        "regular_price",
        "reduced_price",
        "category_id",
        "is_active",
    ];

    protected $casts = [
        'is_active' => "boolean",
        'regular_price' => 'integer',
        'reduced_price' => 'integer',
        'gallery' => 'array',
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

    public function colorImages()
    {
        return $this->hasMany(ProductColorImage::class);
    }

    public static function generateUniqueSKU($productName, $colorName, $sizeName)
    {
        $productCode = strtoupper(substr($productName, 0, 2));
        $colorCode = strtoupper(substr($colorName, 0, 2));
        $sizeCode = strtoupper($sizeName);

        $skuBase = $productCode . $colorCode . $sizeCode;

        return $skuBase;
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
