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
                'product_category' => function ($query) {
                    $query->select('id', 'name');
                },
                'product_galleries' => function ($query) {
                    $query->select('id', 'gallery_image');
                }]);
        if ($categoryId) {
            $products->where('product_category_id', $categoryId);
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
            'product_category_id' => 'required|integer',
            'stock' => 'required|integer',
            'name' => 'required|string|max:55',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
        ]);
        $imageFile = $request->file("thumbnail");
        if ($imageFile) {
            $extension = $imageFile->getClientOriginalExtension();
            $url = strtolower(Str::random(10)) . time() . "." . $extension;
            $data['thumbnail'] = $url;
        }
        $data['sku'] = strtoupper(Str::random(8));
        $product = Product::query()->create($data);
        if ($product) {
            return response()->json("Thêm thành công");
        } else {
            return response()->json("Thêm thất bại",400);
        }
    }

    public function show($id)
    {
        $product = Product::query()
            ->with([
                'product_category' => function ($query) {
                    $query->select('id', 'name');
                },
                'product_galleries' => function ($query) {
                    $query->select('id', 'gallery_image');
                }])
            ->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_category_id' => 'required|integer',
            'stock' => 'required|integer',
            'name' => 'required|string|max:55',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'nullable|numeric|min:0',
        ]);
        $product = Product::query()->findOrFail($id);
        if ($product) {
            $imageFile = $request->file("thumbnail");
            if ($imageFile) {
                $extension = $imageFile->getClientOriginalExtension();
                $url = strtolower(Str::random(10)) . time() . "." . $extension;
                $data['thumbnail'] = $url;
                unset($data['_method']);
            } else {
                unset($data['thumbnail']);
                unset($data['_method']);
            }
            $productUpdate = Product::query()->where("id", $product->id)->update($data);
            if ($productUpdate) {
                return response()->json("Cập nhật thành công");
            } else {
                return response()->json("Cập nhật thất bại");
            }
        }

    }

    public function destroy($id)
    {
        $product = Product::query()->findOrFail($id);
        if ($product) {
            Product::query()->where("id", $id)->delete();
            return response()->json("Xoá thành công");
        }
        return response()->json("Xoá thất bại");
    }
}
