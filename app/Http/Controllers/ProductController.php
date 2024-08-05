<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $size = $request->query("size");
        $products = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }
            ]);
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

        if ($size) {
            $products = $products->paginate($size);
        } else {
            $products = $products->get();
        }
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
            'thumbnail' => 'required|string|max:255',
            'material' => 'string|max:255'
        ], [], [
            'category_id' => 'Id danh mục',
            'name' => 'Tên sản phẩm',
            'short_description' => 'Mô tả ngắn',
            'long_description' => 'Mô tả dài',
            'regular_price' => 'Giá',
            'reduced_price' => 'Giá khuyến mại',
            'thumbnail' => 'Ảnh sản phẩm',
            'material' => 'chất liệu',
        ]);
        $category = DB::table('categories')->where('id', $data['category_id'])->first();
        $currentDay = date('d');
        $currentMonth = date('m');
        $skuPrev = DB::table('products')->where("sku", "LIKE", $category->sku . $currentDay . $currentMonth . "%")->orderByDesc('id')->first();
        if ($skuPrev) {
            $parts = explode('-', $skuPrev->sku);
            $lastPart = (int)end($parts) + 1;
            $data['sku'] = $category->sku . $currentDay . $currentMonth . '-' . str_pad($lastPart, 3, '0', STR_PAD_LEFT);;
        } else {
            $data['sku'] = $category->sku . $currentDay . $currentMonth . '-' . "001";
        }

        $data['slug'] = Str::slug($request->name . "-" . $data['sku']);
        $response = Product::query()->create($data);
        if ($response) {
            return response()->json([
                "message" => "Thêm thành công",
                "data" => $response
            ]);
        } else {
            return response()->json("Thêm thất bại", 400);
        }
    }

    public function show($id)
    {
        $product = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }
            ])
            ->find($id);

        if (!$product) {
            return response()->json(['error' => "Sản phẩm không tồn tại"]);
        }
        return response()->json($product);
    }

    public function getBySlug($slug)
    {
        $product = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }
            ])
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
            'thumbnail' => 'required|string|max:255',
            'material' => 'string|max:255'
        ], [], [
            'category_id' => 'Id danh mục',
            'name' => 'Tên sản phẩm',
            'short_description' => 'Mô tả ngắn',
            'long_description' => 'Mô tả dài',
            'regular_price' => 'Giá',
            'reduced_price' => 'Giá khuyến mại',
            'thumbnail' => 'Ảnh sản phẩm',
            'material' => 'chất liệu',
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

    public function trash(Request $request)
    {
        $categoryId = $request->query('categoryId');
        $name = $request->query('name');
        $minPrice = $request->query('minPrice');
        $maxPrice = $request->query('maxPrice');
        $sort = $request->query('sort', 'ASC');
        $size = $request->query("size");
        $products = Product::onlyTrashed()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name')->withTrashed();
                }
            ]);
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

        if ($size) {
            $trash = $products->paginate($size);
        } else {
            $trash = $products->get();
        }

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
