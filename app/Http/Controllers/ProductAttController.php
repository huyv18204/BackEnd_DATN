<?php

namespace App\Http\Controllers;

use App\Models\ProductAtt;
use Illuminate\Http\Request;

class ProductAttController extends Controller
{
    public function index(Request $request, $product_id)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size', 5);
        $search['size_id'] = $request->sizeId;
        $search['color_id'] = $request->colorId;
        $query = ProductAtt::query()->where('product_id', $product_id);
        foreach ($search as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }
        $productVariants = $query->orderBy('id', $sort)->paginate($size);
        return response()->json($productVariants);
    }

    public function store(Request $request, int $product_id)
    {
        $variants = $request->validate([
            '*' => 'required|array',
            '*.size_id' => 'required|exists:sizes,id',
            '*.color_id' => 'required|exists:colors,id',
            '*.stock_quantity' => 'required|integer|min:0',
            '*.image' => 'nullable|string|max:255',
        ]);

        $insertData = [];
        $errors = [];

        foreach ($variants as $variant) {
            $variant['product_id'] = $product_id;
            $existingVariant = ProductAtt::query()
                ->where('size_id', $variant['size_id'])
                ->where('color_id', $variant['color_id'])
                ->where('product_id', $product_id)
                ->first();
            if ($existingVariant) {
                $errors[] = [
                    'variant' => $variant,
                    'message' => 'Size và color đã tồn tại'
                ];
            } else {
                $insertData[] = $variant;
            }
        }

        if (count($errors) == 0) {
            ProductAtt::query()->insert($insertData);
            return response()->json([
                'message' => 'Thêm mới thành công',
            ], 201);
        }

        return response()->json([
            'message' => 'Một số biến thể không được thêm thành công',
            'errors' => $errors
        ], 422);
    }


    public function update(Request $request, int $id_product, int $id)
    {
        $productAtt = ProductAtt::query()->find($id);
        if (!$productAtt) {
            return response()->json(['error' => "Biến thể không tồn tại"]);
        }
        $data = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'image' => 'nullable|string|max:255',
        ]);
        $data['product_id'] = $id_product;
        $productAtt->update($data);
        return response()->json([
            'message' => 'Cập nhật thành công',
        ]);
    }

    public function destroy(int $product_id , int $id)
    {
        $productAtt = ProductAtt::query()->find($id);
        if (!$productAtt) {
            return response()->json(['error' => "Biến thể không tồn tại"]);
        }

        $productAtt->delete();
        return response()->json(['message' => "Xoá thành công"]);
    }
}

