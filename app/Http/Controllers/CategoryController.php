<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Categories\CategoryRequest;
use App\Http\Response\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        try {
            $query = Category::query();
            $query->when($request->query('id'), function ($query, $id) {
                $query->where('id', $id);
            });

            $query->when($request->query('name'), function ($query, $name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            });

            $query->orderBy('created_at', $sort);
            $categories = $size ? $query->paginate($size) : $query->get();
            return ApiResponse::data($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh mục",  $e->getMessage());
        }
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['category_code'] = $this->generateCategoryCode();
        try {
            Category::create($data);
            return ApiResponse::message('Thêm mới danh mục thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi thêm mới danh mục',  $e->getMessage());
        }
    }

    public function show($id)
    {
        $category = $this->findOrFail($id);
        return $category;
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = $this->findOrFail($id);
        $data = $request->validated();
        if ($data['name']) {
            $data['slug'] = Str::slug($data['name']);
        }
        try {
            $category->update($data);
            return ApiResponse::message('Cập nhật danh mục thành công');
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi cập nhật danh mục');
        }
    }

    public function getProductByCategory(string $slug)
    {
        if ($category = Category::where('slug', $slug)->first()) {
            $products = $category->products;
            return ApiResponse::data($products);
        }
        return ApiResponse::error('Danh mục không tồn tại', Response::HTTP_NOT_FOUND);
    }

    public function destroy(int $id)
    {
        if ($category = Category::find($id)) {
            $category->delete();
            return response()->json(['message' => 'Xóa danh mục thành công'], 200);
        }

        return response()->json(['message' => 'Danh mục không tồn tại'], 404);
    }

    public function trash(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        $query = Category::onlyTrashed();

        $query->when($request->query('id'), function ($query, $id) {
            $query->where('id', $id);
        });

        $query->when($request->query('name'), function ($query, $name) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        });

        $query->orderBy('id', $sort);
        $trash = $size ? $query->paginate($size) : $query->get();
        return ApiResponse::data($trash);
    }

    public function restore(int $id)
    {
        if ($category = Category::withTrashed()->find($id)) {
            $category->restore();
            return response()->json(['message' => 'Khôi phục thành công'], 200);
        }

        return response()->json(['error' => 'Danh mục không tồn tại'], 404);
    }

    private function findOrFail($id)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CustomException('Danh mục không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $category;
    }

    protected function generateCategoryCode()
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        $prevCode = "CA" . $currentDay . $currentMonth;

        $stt = DB::table('categories')->where("category_code", "LIKE", $prevCode . "%")->orderByDesc('id')->first();
        if ($stt) {
            $parts = explode('-', $stt->category_code);
            $lastPart = (int)end($parts) + 1;
            return $prevCode . '-' . str_pad($lastPart, 2, '0', STR_PAD_LEFT);
        } else {
            return $prevCode . '-' . "01";
        }
    }
}
