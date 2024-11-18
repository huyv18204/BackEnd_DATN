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
use App\Traits\applyFilters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductAttController extends Controller
{
    use applyFilters;

    public function index(Request $request, int $productId)
    {
        $product = Product::select('id')->with([
            'product_atts:product_id,color_id,size_id,stock_quantity,sku',
            'colorImages:product_id,color_id,image',
            'product_atts.color:id,name',
            'product_atts.size:id,name'
        ])->find($productId);

        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }
        $colorImages = $product->colorImages->pluck('image', 'color_id');
        $query = $product->product_atts()->getQuery();
        $paginatedAtts = $this->Filters($query, $request);
        $result = collect($paginatedAtts); 

        if ($paginatedAtts instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $result = $paginatedAtts->getCollection();
        }

        $result = $result->map(function ($att) use ($colorImages) {
            return [
                'sku' => $att->sku,
                'image' => $colorImages[$att->color_id] ?? null,
                'color_id' => $att->color_id,
                'color_name' => $att->color?->name,
                'size_id' => $att->size_id,
                'size_name' => $att->size?->name,
                'stock_quantity' => $att->stock_quantity,
            ];
        });

        if ($paginatedAtts instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $paginatedAtts->setCollection($result);
            return ApiResponse::data($paginatedAtts);
        }

        return ApiResponse::data($result);
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
        $product_att = ProductAtt::find($product_att_id);
        if (!$product_att) {
            return ApiResponse::error('Biến thể không tồn tại', Response::HTTP_BAD_REQUEST);
        }
        $colorId = $request->color_id;
        $image = $request->image;
        $productColorImage = ProductColorImage::where('product_id', $product_id)->where('color_id', $colorId)->first();

        try {
            if ($request->stock_quantity) {
                $product_att->update(['stock_quantity' => $request->stock_quantity]);
            }

            if ($productColorImage && $image) {
                $productColorImage->update(['image' => $image]);
            }
            return ApiResponse::message('Cập nhật biến thể thành công');
        } catch (\Exception $e) {
            throw new CustomException('Cập nhật biến thể thất bại', $e->getMessage());
        }
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
