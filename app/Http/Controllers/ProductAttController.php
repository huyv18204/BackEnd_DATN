<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\ProductAtts\ProductAttRequest;
use App\Http\Response\ApiResponse;
use App\Models\Cart;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductAtt;
use App\Models\Size;
use App\Traits\applyFilters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductAttController extends Controller
{
    use ApplyFilters;

    // public function index(Request $request, int $productId)
    // {
    //     $product = Product::with('product_atts.color', 'product_atts.size')->find($productId);

    //     if (!$product) {
    //         return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
    //     }

    //     $query = $product->product_atts();

    //     if ($request->has('size_id')) {
    //         $query->where('size_id', $request->input('size_id'));
    //     }

    //     if ($request->has('color_id')) {
    //         $query->where('color_id', $request->input('color_id'));
    //     }

    //     $size = $request->query('size');

    //     if ($size) {
    //         $filteredProductAtts = $query->paginate($size);
    //     } else {
    //         $filteredProductAtts = $query->get();
    //     }

    //     $result = $filteredProductAtts->map(function ($variant) {
    //         return [
    //             'id' => $variant->id,
    //             'product_id' => $variant->product_id,
    //             'color_id' => $variant->color_id,
    //             'color_name' => $variant->color->name ?? null,
    //             'size_id' => $variant->size_id,
    //             'size_name' => $variant->size->name ?? null,
    //             'sku' => $variant->sku,
    //             'regular_price' => $variant->regular_price,
    //             'reduced_price' => $variant->reduced_price,
    //             'image' => $variant->image,
    //             'stock_quantity' => $variant->stock_quantity,
    //             'is_active' => $variant->is_active,
    //             'created_at' => $variant->created_at,
    //             'updated_at' => $variant->updated_at,
    //         ];
    //     });

    //     if ($size) {
    //         return ApiResponse::data($filteredProductAtts);
    //     }

    //     return ApiResponse::data($result);
    // }

    public function index(Request $request, int $productId)
    {
        // Lấy sản phẩm và các thuộc tính liên quan
        $product = Product::with(['product_atts.color', 'product_atts.size'])->find($productId);

        // Kiểm tra sản phẩm tồn tại
        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }

        // Truy vấn các thuộc tính sản phẩm
        $query = $product->product_atts();

        // Lọc theo size_id nếu có
        if ($request->filled('size_id')) {
            $query->where('size_id', $request->input('size_id'));
        }

        // Lọc theo color_id nếu có
        if ($request->filled('color_id')) {
            $query->where('color_id', $request->input('color_id'));
        }

        // Xử lý tham số 'sort'
        $sort = $request->input('sort', 'created_at,DESC');
        $sortParams = explode(',', $sort);

        // Lấy trường và hướng sắp xếp
        $sortField = $sortParams[0] ?? 'created_at';
        $sortDirection = strtoupper($sortParams[1] ?? 'DESC');

        // Kiểm tra và áp dụng sắp xếp
        if (in_array($sortDirection, ['ASC', 'DESC'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Lấy số lượng bản ghi mỗi trang từ tham số `size`
        $size = $request->query('size');

        // Phân trang hoặc lấy toàn bộ dữ liệu
        $filteredProductAtts = $size ? $query->paginate($size) : $query->get();

        // Định dạng kết quả trả về
        $result = $filteredProductAtts->map(function ($variant) {
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'color_id' => $variant->color_id,
                'color_name' => $variant->color->name ?? null,
                'size_id' => $variant->size_id,
                'size_name' => $variant->size->name ?? null,
                'sku' => $variant->sku,
                'product_att_code' => $variant->product_att_code,
                'regular_price' => $variant->regular_price,
                'reduced_price' => $variant->reduced_price,
                'image' => $variant->image,
                'stock_quantity' => $variant->stock_quantity,
                'is_active' => $variant->is_active,
                'created_at' => $variant->created_at,
                'updated_at' => $variant->updated_at,
            ];
        });

        // Trả về kết quả phân trang hoặc danh sách đã định dạng
        return $size ? ApiResponse::data($filteredProductAtts) : ApiResponse::data($result);
    }


    public function store(ProductAttRequest $request, int $productId)
    {
        $data = $request->validated();
        $productAtts = [];
        $colors = Color::whereIn('id', collect($data)->pluck('color_id')->toArray())->get()->keyBy('id');
        $sizes = Size::whereIn('id', collect($data)->pluck('size_id')->toArray())->get()->keyBy('id');
        $now = now()->toDateTimeString();
        try {
            $product = Product::findOrFail($productId);
            DB::beginTransaction();
            foreach ($data as $variant) {
                $color = $colors->get($variant['color_id']);
                $size = $sizes->get($variant['size_id']);
                $sku = Product::generateUniqueSKU($product->name, $color->name, $size->name);
                $productAtts[] = [
                    'product_id' => $productId,
                    'sku' => $sku,
                    'size_id' => $variant['size_id'],
                    'color_id' => $variant['color_id'],
                    'image' => $variant['image'] ?? null,
                    'regular_price' => $variant['regular_price'] ?? null,
                    'reduced_price' => $variant['reduced_price'] ?? null,
                    'stock_quantity' => $variant['stock_quantity'],
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            $productAtts = Product::checkAndResolveDuplicateSKUs($productAtts);
            DB::table('product_atts')->insert($productAtts);
            DB::commit();
            return ApiResponse::message('Thêm mới biến thể thành công', Response::HTTP_CREATED);
        } catch (\Illuminate\Database\QueryException $exception) {
            DB::rollBack();
            if ($exception->errorInfo[1] == 1062) {
                throw new CustomException('Kích thước và màu sắc đã tồn tại', $exception->getMessage(), Response::HTTP_BAD_REQUEST,);
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new CustomException(
                'Lỗi khi thêm sản phẩm',
                $e->getMessage()
            );
        }
    }

    public function show($id)
    {
        $productAtt = ProductAtt::find($id);
        if (!$productAtt) {
            return ApiResponse::error("Sản phẩm không tồn tại", Response::HTTP_NOT_FOUND);
        }
        return $productAtt;
    }

    public function update(ProductAttRequest $request, int $product_id, int $product_att_id)
    {
        $product_att = ProductAtt::find($product_att_id);
        if (!$product_att) {
            return ApiResponse::error('Biến thể không tồn tại', Response::HTTP_BAD_REQUEST);
        }
        $data = $request->validated();
        try {
            $product_att->update($data);
            return ApiResponse::message('Cập nhật biến thể thành công');
        } catch (\Exception $e) {
            throw new CustomException('Cập nhật biến thể thất bại', $e->getMessage());
        }
    }

    public function destroy(int $product_id, int $product_att_id)
    {
        $product_att = ProductAtt::find($product_att_id);

        if (!$product_att) {
            return response()->json(['message' => 'Biến thể không tồn tại'], 404);
        }

        Cart::whereHas('productAtt', function ($query) use ($product_id) {
            $query->where('product_id', $product_id);
        })->delete();

        $product_att->delete();

        return response()->json(['message' => 'Xóa biến thể thành công'], 200);
    }
}
