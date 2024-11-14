<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAtt;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category' => function ($query) {
                    $query->select('id', 'name');
                },
            ]);

        $query->when($request->query('categoryId'), function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        $query->when($request->query('name'), function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%');
        });

        $query->when($request->query('minPrice'), function ($query, $minPrice) {
            $query->where('regular_price', '>=', $minPrice);
        });

        $query->when($request->query('maxPrice'), function ($query, $maxPrice) {
            $query->where('regular_price', '<=', $maxPrice);
        });

        $query->orderBy('id', $request->query('sort', 'ASC'));

        $size = $request->query('size');
        $products = $size ? $query->paginate($size) : $query->get();

        return response()->json($products);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:products,name',
            'regular_price' => 'required|numeric|min:0',
            'reduced_price' => 'numeric|min:0',
        ], [], [
            'name' => 'Tên sản phẩm',
            'regular_price' => 'Giá thường',
            'reduced_price' => 'Giá giảm',
        ]);
        $data = $request->except(['productatt']);
        $currentDay = date('d');
        $currentMonth = date('m');
        $prevSku = "PR" . $currentDay . $currentMonth;
        $prevProduct = Product::query()->where("sku", "LIKE", $prevSku . "%")
            ->orderByDesc('id')
            ->first();

        if ($prevProduct) {
            $parts = explode('-', $prevProduct->sku);
            $lastPart = (int)end($parts) + 1;
            $data['sku'] = $prevSku . '-' . str_pad($lastPart, 3, '0', STR_PAD_LEFT);
        } else {
            $data['sku'] = $prevSku . '-' . "001";
        }

        $data['slug'] = Str::slug($request->name . "-" . $data['sku']);
        $product_atts = $request->product_att;

        try {
            DB::beginTransaction();

            $product = Product::create($data);

            foreach ($product_atts as $product_att) {
                ProductAtt::create([
                    'product_id' => $product->id,
                    'size_id' => $product_att['size_id'],
                    'color_id' => $product_att['color_id'],
                    'image' => $product_att['image'] ?? null,
                    'stock_quantity' => $product_att['stock_quantity'],
                ]);
            }
            DB::commit();
            return response()->json([
                "message" => "Thêm mới sản phẩm thành công"
            ]);
        } catch (QueryException $exception) {
            DB::rollBack();
            $message = ($exception->errorInfo[1] == 1062)
                ? "Kích thước và màu sắc này đã tồn tại cho sản phẩm này." : "Thêm mới sản phẩm thất bại";

            $status = ($exception->errorInfo[1] == 1062) ? 409 : 400;

            return response()->json([
                "message" => $message,
            ], $status);
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
        $data = $request->validate(
            ['name' => 'required|unique:products,name,' . $id],
            [],
            ['name' => 'Tên sản phẩm']
        );
        $data = $request->all();
        $product = Product::query()->find($id);
        if (!$product) {
            return response()->json(['error' => "Sản phẩm không tồn tại"]);
        }
        if ($data['name'] != $product->name) {
            $data['slug'] = Str::slug($data['name'] . "-" . $product->sku);
        }
        $response = $product->update($data);
        $message = $response ? 'Cập nhật sản phẩm thành công' : 'Cập nhật sản phẩm thất bại';
        return response()->json(["message" => $message]);
    }

    public function getProductAtts(int $id)
    {
        $product = Product::with(['product_atts.size:name,id', 'product_atts.color:name,id'])->find($id);
        if ($product) {
            $product->product_atts->makeHidden(['size_id', 'color_id']);
            return response()->json($product, 200);
        }

        return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
    }

    public function destroy($id)
    {
        if ($product = Product::query()->find($id)) {
            $product->delete();
            return response()->json(['message' => 'Xóa sản phẩm thành công']);
        }
        return response()->json(['message' => 'Sản phẩm không tồn tại']);
    }

    public function trash(Request $request)
    {
        $query = Product::onlyTrashed()
            ->with(['category' => function ($query) {
                $query->select('id', 'name')->withTrashed();
            }]);

        $query->when($request->query('categoryId'), function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        $query->when($request->query('name'), function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%');
        });

        $query->when($request->query('minPrice'), function ($query, $minPrice) {
            $query->where('regular_price', '>=', $minPrice);
        });

        $query->when($request->query('maxPrice'), function ($query, $maxPrice) {
            $query->where('regular_price', '<=', $maxPrice);
        });

        $query->orderBy('id', $request->query('sort', 'ASC'));

        $size = $request->query('size');
        $trash = $size ? $query->paginate($size) : $query->get();

        return response()->json($trash);
    }

    public function restore($id)
    {
        if ($product = Product::withTrashed()->find($id)) {
            $product->restore();
            return response()->json(['message' => 'Khôi phục sản phẩm thành công'], 200);
        }
        return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
    }
}
