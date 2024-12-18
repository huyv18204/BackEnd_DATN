<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\CategoryRequest;
use App\Http\Response\ApiResponse;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sortField = $request->input('sortField', 'created_at');
        $size = $request->query('size');
        $sortDirection = $request->query('sort', 'DESC');
        $query = Category::query();

        $query->when($request->query('id'), function ($query, $id) {
            $query->where('id', $id);
        });

        $query->when($request->query('name'), function ($query, $name) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        });

        $query->orderBy($sortField, $sortDirection);

        $categories = $size ? $query->paginate($size) : $query->get();

        return ApiResponse::data($categories);
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['category_code'] = $this->generateCategoryCode();
        Category::create($data);
        return ApiResponse::message('Thêm mới danh mục thành công', 201);
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return ApiResponse::error('Danh mục không tồn tại', 404);
        }
        $data = $request->validated();
        if ($data['name']) {
            $data['slug'] = Str::slug($data['name']);
        }
        $category->update($data);
        return ApiResponse::message('Cập nhật danh mục thành công');
    }

    public function getProductByCategory($slug)
    {
        if ($category = Category::where('slug', $slug)->first()) {
            $products = $category->products;
            return ApiResponse::data($products);
        }
        return ApiResponse::error('Danh mục không tồn tại', 404);
    }

    public function destroy(int $id)
    {
        if ($category = Category::find($id)) {
            if ($category->products()->exists()) {
                return ApiResponse::error('Không thể xóa danh mục vì vẫn còn sản phẩm liên kết', 400);
            }
            $category->delete();
            return ApiResponse::message('Xóa danh mục thành công');
        }
        return ApiResponse::error('Danh mục không tồn tại', 404);
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
