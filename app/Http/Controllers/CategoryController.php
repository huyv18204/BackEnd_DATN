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
    // public function index(Request $request)
    // {
    //     $sort = $request->input('sort', 'ASC');
    //     $size = $request->query('size');
    //     try {
    //         $query = Category::query();
    //         $query->when($request->query('id'), function ($query, $id) {
    //             $query->where('id', $id);
    //         });

    //         $query->when($request->query('status') !== null, function ($query) use ($request) {
    //             $status = filter_var($request->query('status'), FILTER_VALIDATE_BOOLEAN);
    //             $query->where('is_active', $status);
    //         });

    //         $query->when($request->query('name'), function ($query, $name) {
    //             $query->where('name', 'LIKE', '%' . $name . '%');
    //         });

    //         $query->orderBy('created_at', $sort);
    //         $categories = $size ? $query->paginate($size) : $query->get();
    //         return ApiResponse::data($categories, Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         throw new CustomException("Lỗi khi truy xuất danh mục",  $e->getMessage());
    //     }
    // }

    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at,ASC'); // Mặc định sắp xếp theo 'created_at' tăng dần
        $size = $request->query('size');

        try {
            $query = Category::query();

            // Lọc theo ID
            $query->when($request->query('id'), function ($query, $id) {
                $query->where('id', $id);
            });

            // Lọc theo trạng thái (is_active)
            $query->when($request->query('status') !== null, function ($query) use ($request) {
                $status = filter_var($request->query('status'), FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $status);
            });

            // Lọc theo tên (name)
            $query->when($request->query('name'), function ($query, $name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            });

            // Xử lý sắp xếp theo 'sort'
            $sortParams = explode(',', $sort);
            $sortField = $sortParams[0] ?? 'created_at';
            $sortDirection = strtoupper($sortParams[1] ?? 'DESC');

            $query->orderBy($sortField, $sortDirection);


            // Phân trang hoặc lấy tất cả kết quả
            $categories = $size ? $query->paginate($size) : $query->get();

            return ApiResponse::data($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh mục", $e->getMessage());
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
            if ($category->products()->exists()) {
                return response()->json([
                    'message' => 'Không thể xóa danh mục vì vẫn còn sản phẩm liên kết'
                ], 400);
            }
            $category->delete();
            return response()->json(['message' => 'Xóa danh mục thành công'], 200);
        }

        return response()->json(['message' => 'Danh mục không tồn tại'], 404);
    }

    public function toggleStatus(string $id)
    {
        $category = $this->findOrFail($id);
        try {
            $category->is_active = !$category->is_active;
            $category->save();
            return ApiResponse::message('Thay đổi trạng thái thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Thay đổi trạng thái danh mục thất bại', $e->getMessage());
        }
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
