<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAtt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAttController extends Controller
{
    public function index(Request $request, int $product_id)
    {
        $size = $request->query('size');
        $query = ProductAtt::query()->where('product_id', $product_id);

        $searchParams = $request->only(['color_id', 'size_id']);
        foreach ($searchParams as $key => $value) {
            if (!empty($value)) {
                $query->where($key, $value);
            }
        }

        $query->orderByDesc('id');

        $productAtts = $size ? $query->paginate($size) : $query->get();

        return response()->json($productAtts);
    }

    public function store(Request $request, int $product_id)
    {
        $data = $request->validate([
            '*.size_id' => 'required|integer|exists:sizes,id',
            '*.color_id' => 'required|integer|exists:colors,id',
            '*.stock_quantity' => 'required|integer|min:0',
            '*.image' => 'nullable|string|max:255',
        ], [], [
            '*.size_id' => 'Kích thước',
            '*.color_id' => 'Màu sắc',
            '*.stock_quantity' => 'Số lượng',
            '*.image' => 'Ảnh biến thể',
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'Không có dữ liệu biến thể'], 400);
        }

        try {
            DB::beginTransaction();

            foreach ($data as $item) {
                ProductAtt::create([
                    'product_id' => $product_id,
                    'size_id' => $item['size_id'],
                    'color_id' => $item['color_id'],
                    'stock_quantity' => $item['stock_quantity'],
                    'image' => $item['image'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Thêm mới biến thể thành công']);
        } catch (\Illuminate\Database\QueryException $exception) {
            DB::rollBack();

            if ($exception->errorInfo[1] == 1062) {
                return response()->json(['message' => 'Kích thước và màu sắc đã tồn tại'], 409);
            }
            return response()->json(['message' => 'Thêm mới thất bại'], 400);
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


    public function show(int $product_id, int $product_att_id)
    {
        if ($productAtt = ProductAtt::query()->find($product_att_id)) {
            return response()->json($productAtt, 200);
        }
        return response()->json(['message' => 'Biến thể không tồn tại'], 404);
    }
}
