<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        $searchParams = $request->only(['name', 'id']);

        $query = Category::query();

        foreach ($searchParams as $key => $value) {
            if ($value) {
                $query->where($key, 'LIKE', "%{$value}%");
            }
        }

        $query->orderBy('id', $sort);
        $categories = $size ? $query->paginate($size) : $query->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|min:6|max:100|unique:categories,name',
        ], [], [
            'name' => 'tên danh mục'
        ]);

        $data['slug'] = Str::slug($request->name);

        $initials = implode('', array_map(fn ($word) => mb_substr($word, 0, 1), explode(' ', $request->name)));
        $data['sku'] = $initials;
        $count = 1;
        while (Category::where('sku', $data['sku'])->exists()) {
            $data['sku'] = "{$initials}-{$count}";
            $count++;
        }

        $category = Category::create($data);

        return response()->json([
            $category ? 'message' : 'error' => $category ? 'Thêm mới thành công' : 'Thêm thất bại'
        ], $category ? 200 : 500);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['error' => 'Danh mục không tồn tại'], 404);
        }

        $data = $request->validate([
            'name' => 'required|min:6|max:100|unique:categories,name,' . $id,
        ], [], [
            'name' => 'Tên Danh mục'
        ]);

        if ($category->name != $request->name) {
            $data['slug'] = Str::slug($request->name);
        }

        $response = $category->update($data);

        return response()->json([
            $response ? 'message' : 'error' => $response ? 'Cập nhật danh mục thành công' : 'Cập nhật danh mục thất bại'
        ], $response ? 200 : 500);
    }

    public function destroy(int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Xóa thành công'], 200);
    }

    public function show(int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }

        return response()->json($category, 200);
    }

    public function getBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }

        return response()->json($category, 200);
    }

    public function trash(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $size = $request->query('size');
        $searchParams = $request->only(['name', 'id']);

        $query = Category::onlyTrashed();

        foreach ($searchParams as $key => $value) {
            if ($value) {
                $query->where($key, 'LIKE', "%{$value}%");
            }
        }

        $query->orderBy('id', $sort);
        $trash = $size ? $query->paginate($size) : $query->get();
        return response()->json($trash, 200);
    }

    public function restore(int $id)
    {
        $category = Category::withTrashed()->find($id);

        if (!$category) {
            return response()->json(['error' => 'Danh mục không tồn tại'], 404);
        }

        if (!$category->trashed()) {
            return response()->json(['error' => 'Danh mục chưa bị xóa'], 400);
        }

        $category->restore();

        return response()->json(['message' => 'Khôi phục thành công'], 200);
    }
}
