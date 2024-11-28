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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    const PATH_UPLOAD = 'products';
    public function index(Request $request)
    {
        $query = Product::with(['category:id,name']);
        $products = $this->Filters($query, $request);
        return ApiResponse::data($products);
    }

    public function store(ProductRequest $request)
    {
        $dataProduct = $request->except(['product_att']);

        if ($request->hasFile('thumbnail')) {
            $dataProduct['thumbnail'] = upload_image($request->file('thumbnail'), self::PATH_UPLOAD);
        }

        $dataProductAtts = $request->product_att;
        $dataProduct['slug'] = Str::slug($request->name);

        $colors = Color::whereIn('id', collect($dataProductAtts)->pluck('color_id')->toArray())->get()->keyBy('id');
        $sizes = Size::whereIn('id', collect($dataProductAtts)->pluck('size_id')->toArray())->get()->keyBy('id');

        $productAtts = [];
        $now = now()->toDateTimeString();

        try {
            DB::beginTransaction();

            $product = Product::create($dataProduct);

            foreach ($dataProductAtts as $productAtt) {
                $color = $colors->get($productAtt['color_id']);
                $size = $sizes->get($productAtt['size_id']);
                $sku = Product::generateUniqueSKU($product->name, $color->name ?? null, $size->name ?? null);

                if (isset($productAtt['image'])) {
                    $productAtt['image'] = upload_image($productAtt['image'], self::PATH_UPLOAD);
                }

                $productAtts[] = [
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'size_id' => $productAtt['size_id'],
                    'color_id' => $productAtt['color_id'],
                    'image' => $productAtt['image'] ?? null,
                    'regular_price' => $productAtt['regular_price'],
                    'reduced_price' => $productAtt['reduced_price'],
                    'stock_quantity' => $productAtt['stock_quantity'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $productAtts = Product::checkAndResolveDuplicateSKUs($productAtts);

            DB::table('product_atts')->insert($productAtts);

            DB::commit();

            return ApiResponse::message(
                'Thêm mới sản phẩm thành công',
                Response::HTTP_CREATED,
                ['product' => $product]
            );
        } catch (QueryException $exception) {
            DB::rollBack();
            if ($exception->errorInfo[1] == 1062) {
                throw new CustomException(
                    'Kích thước và màu sắc đã tồn tại',
                    $exception->getMessage(),
                    Response::HTTP_BAD_REQUEST
                );
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new CustomException("Lỗi khi thêm sản phẩm", $e->getMessage());
        }
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return ApiResponse::error("Sản phẩm không tồn tại", Response::HTTP_NOT_FOUND);
        }
        return ApiResponse::data($product);
    }

    public function getBySlug($slug)
    {
        $product = Product::with([
            'category',
            'product_atts.color',
            'product_atts.size',
        ])->where('slug', $slug)->first();

        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        $productData = $this->getProductData($product);

        return ApiResponse::data($productData);
    }

    public function update(ProductRequest $request, $id)
    {
        $data = $request->validated();
        $product = $this->findOrFail($id);
        if ($data['name'] != $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }
        $oldThumbnail = $product->thumbnail;
        try {
            if (isset($data['thumbnail'])) {
                $data['thumbnail'] = upload_image($request->thumbnail, self::PATH_UPLOAD);
            }
            $product->update($data);
            if (isset($oldThumbnail) && $oldThumbnail != $data['thumbnail']) {
                delete_image($oldThumbnail);
            }
            return ApiResponse::message("Cập nhật sản phẩm thành công");
        } catch (\Exception $e) {
            if (isset($data['thumbnail'])) {
                delete_image($data['thumbnail']);
            }
            throw new CustomException("Lỗi khi cập nhật sản phẩm", $e->getMessage());
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
            throw new CustomException('Sản phẩm không tồn tại', $e->getMessage());
        }
    }

    private function findOrFail($id)
    {
        try {
            $product = Product::findOrFail($id);
            return $product;
        } catch (\Exception $e) {
            throw new CustomException('Sản phẩm không tồn tại', $e->getMessage());
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


            foreach ($product->product_atts->groupBy('color_id') as $colorId => $productAtts) {

                $color = $productAtts->first()->color;
                $colorName = $color ? $color->name : null;


                $productData['product_att'][] = [
                    'color_id' => $colorId,
                    'color_name' => $colorName,
                    'image' => $productAtts->first()->image,
                    'sizes' => $productAtts->map(function ($productAtt) {
                        $size = $productAtt->size;
                        $sizeName = $size ? $size->name : null;

                        return [
                            'id' => $productAtt->id,
                            'size_id' => $productAtt->size_id,
                            'size_name' => $sizeName,
                            'image' => $productAtt->image,
                            'regular_price' =>  $productAtt->regular_price,
                            'reduced_price' =>  $productAtt->reduced_price,
                            'sku' => $productAtt->sku,
                            'stock_quantity' => $productAtt->stock_quantity
                        ];
                    })->toArray(),
                ];
            }

            return $productData;
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi xử lý dữ liệu sản phẩm", $e->getMessage());
        }
    }

    public function checkIsActive(Request $request): JsonResponse
    {
        $productIds = $request->input('product_id');
        $errors = [];
        $products = Product::withTrashed()->whereIn('id', $productIds)->get();
        foreach ($products as $product) {
            if ($product->deleted_at !== null) {
                $errors[] = "Sản phẩm {$product->name} không tồn tại.";
            }
        }
        if (count($errors) > 0) {
            return response()->json([
                'status' => 'error',
                'errors' => $errors
            ], 400);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Tất cả sản phẩm đều hợp lệ.'
        ]);
    }

    protected function Filters($query, $request)
    {
        $query->when($request->query('id'), fn($q, $id) => $q->where('id', $id));
        $query->when($request->query('categoryId'), fn($q, $categoryId) => $q->where('category_id', $categoryId));
        $query->when($request->query('name'), fn($q, $name) => $q->where('name', 'like', '%' . $name . '%'));

        $query->when(
            $request->query('sizeId'),
            fn($q, $sizeId) =>
            $q->whereHas(
                'product_atts',
                fn($subQuery) =>
                $subQuery->where('size_id', $sizeId)
            )
        );
        $query->when(
            $request->query('colorId'),
            fn($q, $colorId) =>
            $q->whereHas(
                'product_atts',
                fn($subQuery) =>
                $subQuery->where('color_id', $colorId)
            )
        );
        $query->when($request->query('minPrice'), fn($q, $minPrice) => $q->where('regular_price', '>=', $minPrice));
        $query->when($request->query('maxPrice'), fn($q, $maxPrice) => $q->where('regular_price', '<=', $maxPrice));

        $query->orderBy('created_at', $request->query('sort', 'ASC'));

        $size = $request->query('size');
        return $size ? $query->paginate($size) : $query->get();
    }
}
