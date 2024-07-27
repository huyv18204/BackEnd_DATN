<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->query('categoryId');
        $name = $request->query('name');
        $minPrice = $request->query('minPrice');
        $maxPrice = $request->query('maxPrice');
        $sort = $request->query('sort', 'ASC');
        $products = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }]);
        if ($categoryId) {
            $products->where('category_id', $categoryId);
        }
        if ($name) {
            $products->where('name', 'like', '%' . $name . '%');
        }
        if ($minPrice) {
            $products->where('regular_price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $products->where('regular_price', '<=', $maxPrice);
        }
        $products->orderBy('id', $sort);
        $products = $products->paginate(5);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer',
            'name' => 'required|string|max:55',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
            'thumbnail' => 'required|string|max:255'
        ]);

        $data['sku'] = strtoupper(Str::random(8));
        $data['slug'] = Str::slug($request->name . "-" . $data['sku']);
        $response = Product::query()->create($data);
        if ($response) {
            return response()->json("Thêm thành công");
        } else {
            return response()->json("Thêm thất bại", 400);
        }
    }

    public function show($slug)
    {
        $product = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }])
            ->where('slug', $slug)->first();

        if (!$product) {
            return response()->json(['error' => "Sản phẩm không tồn tại"]);
        }
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'category_id' => 'required|integer',
            'name' => 'required|string|max:55',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
            'thumbnail' => 'required|string|max:255'
        ]);
        $product = Product::query()->find($id);
        if (!$product) {
            return response()->json(['error' => "Sản phẩm không tồn tại"]);
        }
        if ($data['name'] != $product->name) {
            $data['slug'] = Str::slug($data['name'] . "-" . $product->sku);
        }
        $response = $product->update($data);
        if ($response) {
            return response()->json("Cập nhật thành công");
        } else {
            return response()->json("Cập nhật thất bại");

        }

    }

    public function destroy($id)
    {
        $product = Product::query()->find($id);
        if (!$product) {
            return response()->json("Sản phẩm không tồn tại");
        }
        $product->delete();
        return response()->json("Xoá thành công");

    }

    public function trash()
    {
        $trash = Product::onlyTrashed()->get();
        return response()->json($trash);
    }

    public function restore($id)
    {
        $category = Product::withTrashed()->find($id);
        if (!$category) {
            return response()->json(["error" => "Sản phẩm không tồn tại"]);
        }
        $category->restore();
        return response()->json(["message" => "Khôi phục thành công"]);
    }
}
