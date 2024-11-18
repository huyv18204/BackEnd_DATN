<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Products\ProductRequest;
use App\Http\Response\ApiResponse;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use App\Traits\applyFilters;

class ProductController extends Controller
{
    use applyFilters;
    public function index(Request $request)
    {
        $query = Product::with(['category:id,name']);
        $products = $this->Filters($query, $request);
        return ApiResponse::data($products);
    }

    public function store(ProductRequest $request)
    {
        $dataProduct = $request->except(['product_att', 'gallery', 'product_color_images']);
        $dataProduct['gallery'] = $request->has('gallery') ? json_encode($request->gallery) : null;
        $dataProductAtts = $request->product_att;
        $dataProduct['slug'] = Str::slug($request->name);

        $colors = Color::whereIn('id', collect($dataProductAtts)->pluck('color_id')->toArray())->get()->keyBy('id');
        $sizes = Size::whereIn('id', collect($dataProductAtts)->flatMap(fn($att) => collect($att['sizes'])->pluck('size_id'))->toArray())->get()->keyBy('id');

        $productColorImages = [];
        $productAtts = [];
        $now = now()->toDateTimeString();

        try {
            DB::beginTransaction();

            $product = Product::create($dataProduct);

            foreach ($dataProductAtts as $productAtt) {
                $productColorImages[] = [
                    'product_id' => $product->id,
                    'color_id' => $productAtt['color_id'],
                    'image' => $productAtt['image'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                foreach ($productAtt['sizes'] as $variant) {
                    $color = $colors->get($productAtt['color_id']);
                    $size = $sizes->get($variant['size_id']);

                    $sku = Product::generateUniqueSKU($product->name, $color->name, $size->name);

                    $productAtts[] = [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'size_id' => $variant['size_id'],
                        'color_id' => $productAtt['color_id'],
                        'stock_quantity' => $variant['stock_quantity'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $productAtts = Product::checkAndResolveDuplicateSKUs($productAtts);
            DB::table('product_atts')->insert($productAtts);
            DB::table('product_color_images')->insert($productColorImages);

            DB::commit();

            return ApiResponse::message('Thêm mới sản phẩm thành công', Response::HTTP_CREATED);
        } catch (QueryException $exception) {
            DB::rollBack();
            throw new CustomException("Lỗi khi thêm sản phẩm", Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        }
    }
    public function show($id)
    {
        $product = Product::with([
            'category',
            'product_atts.color',
            'product_atts.size',
            'colorImages.color',
        ])->find($id);

        if (!$product) {
            return ApiResponse::error("Sản phẩm không tồn tại", Response::HTTP_NOT_FOUND);
        }

        $productData = $this->getProductData($product);

        return ApiResponse::data($productData);
    }

    public function getBySlug($slug)
    {
        $product = Product::with([
            'category',
            'product_atts.color',
            'product_atts.size',
            'colorImages.color',
        ])->where('slug', $slug)->first();

        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        $productData = $this->getProductData($product);

        return ApiResponse::data($productData);
    }

    public function update(ProductRequest $request, $id)
    {
        $data = $request->except('gallery');
        $data['gallery'] =  $request->has('gallery') ? json_encode($request->gallery) : null;
        $product = $this->findOrFail($id);
        if ($data['name'] != $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }
        try {
            $product->update($data);
            return ApiResponse::message("Cập nhật sản phẩm thành công");
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi cập nhật sản phẩm", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return ApiResponse::message("Xóa sản phẩm thành công");
        } catch (\Exception $e) {
            throw new CustomException('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }
    }

    public function trash(Request $request)
    {
        $query = Product::onlyTrashed()
            ->with('category:id,name');
        $trash =  $this->Filters($query, $request);
        return ApiResponse::data($trash);
    }

    public function restore($id)
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            return ApiResponse::message('Khôi phục sản phẩm thành công');
        } catch (Exception $e) {
            throw new CustomException('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND, $e->getMessage());
        }
    }

    private function findOrFail($id)
    {
        try {
            $product = Product::find($id);
            return $product;
        } catch (\Exception $e) {
            throw new CustomException('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND, $e->getMessage());
        }
    }



    private function getProductData($product)
    {
        try {
            $productData = [
                'material' => $product->material,
                'name' => $product->name,
                'thumbnail' => $product->thumbnail,
                'short_description' => $product->short_description,
                'long_description' => $product->long_description,
                'gallery' => $product->gallery,
                'regular_price' => $product->regular_price,
                'reduced_price' => $product->reduced_price,
                'category_id' => $product->category_id,
                'product_att' => [],
            ];

            $colorImagesGrouped = $product->colorImages->keyBy('color_id');

            foreach ($product->product_atts->groupBy('color_id') as $colorId => $productAtts) {
                $colorImage = $colorImagesGrouped->get($colorId);

                $productData['product_att'][] = [
                    'color_id' => $colorId,
                    'image' => $colorImage ? $colorImage->image : null,
                    'sizes' => $productAtts->map(function ($productAtt) {
                        return [
                            'size_id' => $productAtt->size_id,
                            'sku' => $productAtt->sku,
                            'stock_quantity' => $productAtt->stock_quantity
                        ];
                    })->toArray(),
                ];
            }

            return $productData;
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi xử lý dữ liệu sản phẩm", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
