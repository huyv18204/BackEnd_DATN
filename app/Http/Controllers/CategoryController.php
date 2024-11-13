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
            $query = Category::query()
                ->whereNull('parent_id')
                ->with('children.children');
            $query->when($request->query('id'), function ($query, $id) {
                $query->where('id', $id);
            });

            $query->when($request->query('name'), function ($query, $name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            });

            $query->orderBy('id', $sort);
            $categories = $size ? $query->paginate($size) : $query->get();
            return ApiResponse::data($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh mục", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function listParent(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        try {
            $query = Category::query()->whereNull('parent_id');

            $query->when($request->query('id'), function ($query, $id) {
                $query->where('id', $id);
            });

            $query->when($request->query('name'), function ($query, $name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            });

            $query->orderBy('id', $sort);
            $categories = $size ? $query->paginate($size) : $query->get();
            return ApiResponse::data($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh mục", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(CategoryRequest $request)
    {
        $parentIds = $request->input('parent_id', []);
        $data = $request->validated();
        try {
            if (empty($parentIds)) {
                $existingCategory = Category::where('name', $data['name'])->first();
                if ($existingCategory) {
                    return ApiResponse::error('Tên danh mục đã tồn tại', Response::HTTP_BAD_REQUEST);
                }
                $data['category_code'] = $this->generateCategoryCode();
                $data['slug'] = $this->generateUniqueSlug($data['name']);
                $category = Category::query()->create($data);
                return ApiResponse::message('Thêm mới danh mục cha thành công', Response::HTTP_CREATED);
            } else {
                DB::beginTransaction();
                foreach ($parentIds as $parentId) {
                    $existingCategory = Category::where('name', $data['name'])
                        ->where('parent_id', $parentId)
                        ->first();

                    if ($existingCategory) {
                        return ApiResponse::error('Tên danh mục này đã tồn tại trong danh mục cha.', Response::HTTP_BAD_REQUEST);
                    }

                    $data['category_code'] = $this->generateCategoryCode();
                    $data['slug'] = $this->generateUniqueSlug($data['name'], $parentId);

                    Category::create([
                        'name' => $data['name'],
                        'category_code' => $data['category_code'],
                        'slug' => $data['slug'],
                        'parent_id' => $parentId,
                    ]);
                }
                DB::commit();
                return ApiResponse::message('Thêm mới danh mục con thành công', Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException('Thêm mới danh mục thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = $this->findOrFail($id);
        $data = $request->validated();
        $parentIds = $request->input('parent_id', []);

        try {
            if (empty($parentIds)) {
                $existingCategory = Category::where('name', $data['name'])
                    ->where('id', '!=', $id)
                    ->first();
                if ($existingCategory) {
                    return ApiResponse::error('Tên danh mục đã tồn tại', Response::HTTP_BAD_REQUEST);
                }
                $category->name = $data['name'];
                $category->slug = $this->generateUniqueSlug($data['name']);
                $category->save();

                return ApiResponse::message('Cập nhật danh mục cha thành công', Response::HTTP_OK);
            } else {
                try {
                    DB::beginTransaction();

                    foreach ($parentIds as $parentId) {
                        if ($parentId == $category->id) {
                            return ApiResponse::error('Danh mục không thể làm cha của chính nó, vui lòng chọn danh mục khác.', Response::HTTP_BAD_REQUEST);
                        }

                        $existingCategory = Category::where('name', $data['name'])
                            ->where('parent_id', $parentId)
                            ->where('id', '!=', $id)
                            ->first();

                        if ($existingCategory) {
                            return ApiResponse::error('Tên danh mục này đã tồn tại trong danh mục cha.', Response::HTTP_BAD_REQUEST);
                        }

                        $category->name = $data['name'];
                        $category->slug = $this->generateUniqueSlug($data['name'], $parentId);
                        $category->parent_id = $parentId;

                        if (!empty($data['image'])) {
                            $category->image = $data['image'];
                        }

                        $category->save();
                    }
                    DB::commit();
                    return ApiResponse::message('Cập nhật danh mục con thành công', Response::HTTP_OK);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw new CustomException('Lỗi khi cập nhật danh mục', Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        } catch (\Exception $e) {
            throw new CustomException('Cập nhật danh mục thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function listChildren(Request $request, string $id)
    {
        $category = $this->findOrFailParentId($id);
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        try {
            $query = Category::query()->where('parent_id', $id);

            $query->when($request->query('id'), function ($query, $id) {
                $query->where('id', $id);
            });

            $query->when($request->query('name'), function ($query, $name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            });

            $query->orderBy('id', $sort);
            $categories = $size ? $query->paginate($size) : $query->get();
            return ApiResponse::data($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi truy xuất danh mục con", Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function toggleStatus(string $id)
    {
        $category = $this->findOrFail($id);
        try {
            $category->is_active = !$category->is_active;
            $category->save();
            return ApiResponse::message("Chuyển đổi trạng thái thành công", Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi chuyển trạng thái", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id)
    {
        $category = $this->findOrFail($id);

        if ($category->parent_id === null && $category->children()->exists()) {
            return ApiResponse::error('Không thể xóa danh mục cha có danh mục con', Response::HTTP_BAD_REQUEST);
        }

        if ($category->parent_id !== null) {
            $products = Product::where('category_id', $category->id)->get();

            if ($products->isNotEmpty()) {
                foreach ($products as $product) {
                    $product->category_id = 1;
                    $product->save();
                }
            }
        }
        $category->delete();

        return ApiResponse::error('Xóa danh mục thành công', Response::HTTP_OK);
    }


    public function getBySlug(string $slug)
    {
        if ($category = Category::where('slug', $slug)->first()) {
            return response()->json($category, 200);
        }
        return response()->json(['message' => 'Danh mục không tồn tại'], 404);
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

    protected function generateUniqueSlug($name, $parentId = null)
    {
        $slug = Str::slug($name);

        if ($parentId) {
            $parentCategory = Category::find($parentId);
            if ($parentCategory) {
                $slug = Str::slug($parentCategory->name) . '-' . $slug;
            }
        }

        return $slug;
    }

    private function findOrFail($id)
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CustomException('Danh mục không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $category;
    }

    private function findOrFailParentId($id)
    {
        $category = Category::whereNull('parent_id')->find($id);
        if (!$category) {
            throw new CustomException('Danh mục cha không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $category;
    }
}
