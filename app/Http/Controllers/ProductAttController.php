<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\ProductAtts\ProductAttRequest;
use App\Http\Response\ApiResponse;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductAtt;
use App\Models\ProductColorImage;
use App\Models\Size;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductAttController extends Controller
{
    public function index(Request $request, int $productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $variants = ProductAtt::with(['color', 'size'])
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->get()
                ->groupBy('color_id');
            $result = $variants->map(function ($items, $colorId) {
                $image = ProductColorImage::where('color_id', $colorId)->first();
                $sizes = $items->map(function ($item) {
                    return [
                        'size_id' => $item->size_id,
                        'sku' => $item->sku,
                        'stock_quantity' => $item->stock_quantity
                    ];
                });

                return [
                    'color_id' => $colorId,
                    'image' => $image ? $image->image : null,
                    'sizes' => $sizes
                ];
            });
            $result = $result->values()->toArray();

            return ApiResponse::data($result);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh sách biến thể", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(ProductAttRequest $request, int $productId)
    {
        $data = $request->validated();
        $productAtts = [];
        $productColorImages = [];
        $colors = Color::whereIn('id', collect($data)->pluck('color_id')->toArray())->get()->keyBy('id');
        $sizes = Size::whereIn('id', collect($data)->flatMap(fn($att) => collect($att['sizes'])->pluck('size_id'))->toArray())->get()->keyBy('id');
        $now = now()->toDateTimeString();
        try {
            $product = Product::findOrFail($productId);
            DB::beginTransaction();
            foreach ($data as $item) {
                $productColorImages[] = [
                    'product_id' => $productId,
                    'color_id' => $item['color_id'],
                    'image' => $item['image'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                foreach ($item['sizes'] as $variant) {
                    $color = $colors->get($item['color_id']);
                    $size = $sizes->get($variant['size_id']);
                    $sku = Product::generateUniqueSKU($product->name, $color->name, $size->name);
                    $productAtts[] = [
                        'product_id' => $productId,
                        'sku' => $sku,
                        'size_id' => $size->id,
                        'color_id' => $color->id,
                        'stock_quantity' => $variant['stock_quantity'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }
            $productAtts = Product::checkAndResolveDuplicateSKUs($productAtts);
            DB::table('product_atts')->insert($productAtts);
            DB::table('product_color_images')->insert($productColorImages);
            DB::commit();
            return ApiResponse::message('Thêm mới biến thể thành công', Response::HTTP_CREATED);
        } catch (\Illuminate\Database\QueryException $exception) {
            DB::rollBack();
            if ($exception->errorInfo[1] == 1062) {
                throw new CustomException('Kích thước và màu sắc đã tồn tại', Response::HTTP_BAD_REQUEST, $exception->getMessage());
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new CustomException(
                'Sản phẩm không tồn tại',
                Response::HTTP_NOT_FOUND,
                $e->getMessage()
            );
        }
    }

    public function update(Request $request, int $product_id, int $product_att_id)
    {
        $data = $request->only(['stock_quantity', 'image']);

        $product_att = ProductAtt::find($product_att_id);

        if (!$product_att) {
            return response()->json(['message' => 'Biến thể không tồn tại'], 404);
        }

        if ($product_att->update(
            [
                'id' => $product_att_id,
                'stock_quantity' => $data['stock_quantity'],
                'image' => $data['image'] ?? null,
            ]
        )) {
            return response()->json(['message' => 'Cập nhật biến thể thành công'], 200);
        }
        return response()->json(['message' => 'Cập nhật biến thể thất bại'], 400);
    }


    public function destroy(int $product_id, int $product_att_id)
    {
        if ($product_att = ProductAtt::query()->find($product_att_id)) {
            $product_att->delete();
            return response()->json(['message' => 'Xóa biến thể thành công'], 200);
        }
        return response()->json(['message' => 'Biến thể không tồn tại'], 404);
    }
}
