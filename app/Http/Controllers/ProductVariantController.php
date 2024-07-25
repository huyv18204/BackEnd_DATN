<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function show(Request $request, string $id)
    {
        $sort = $request->input('sort', 'ASC');
        $search['product_size_id'] = $request->sizeId;
        $search['product_color_id'] = $request->colorId;
        $query = ProductVariant::where('product_id', $id);
        foreach ($search as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }
        $productVariants = $query->orderBy('id', $sort)->get();
        return response()->json($productVariants);
    }

    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'product_size_id' => 'required|exists:product_sizes,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'variants_image' => 'nullable|max:255',
        ]);
        $data['product_id'] = $id;
        $existingVariant = ProductVariant::where('product_size_id', $data['product_size_id'])
            ->where('product_color_id', $data['product_color_id'])
            ->where('product_id', $id)
            ->first();

        if ($existingVariant) {
            return response()->json(['error' => 'Size và color đã tồn tại'], 401);
        }

        $productVariant = ProductVariant::create($data);

        return response()->json([
            'message' => 'Thêm mới thành công'
        ], 201);
    }

    public function update(Request $request, int $id, int $variant_id)
    {
        $data = $request->validate([
            'product_size_id' => 'required|exists:product_sizes,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'variants_image' => 'nullable|max:255',
        ]);
        $data['product_id'] = $id;
        $existingVariant = ProductVariant::where('product_size_id', $data['product_size_id'])
            ->where('product_color_id', $data['product_color_id'])
            ->where('product_id', $id)
            ->where('id', '!=', $variant_id)
            ->first();

        if ($existingVariant) {
            return response()->json(['error' => 'Size và color đã tồn tại'], 401);
        }
        $productVariant = ProductVariant::findOrFail($variant_id);
        $productVariant->update($data);
        return response()->json([
            'message' => 'Cập nhật thành công',
        ]);
    }

    public function destroy(){}
}
