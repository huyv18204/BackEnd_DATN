<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'ASC');
        $query = Category::query();
        $search['name'] = $request->name;
        $search['id'] = $request->id;
        foreach ($search as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }
        $categories = $query->orderBy('id', $sort)
                    ->paginate(2, ['id', 'name', 'is_active']);
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'name' => 'required|min:6|max:100|unique:categories,name',
            ]
        );
        Category::query()->create($data);
        return response()->json([
            'message' => 'Thêm mới thành công'
        ]);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|min:6|max:100|unique:categories,name,' . $id,
            'is_active' => 'required'
        ]);
        $category->update($data);
        return response()->json(
            [
                'message' => 'Cập nhật thành công'
            ]
        );
    }

    public function destroy(int $id)
    {
        $category = Category::query()->findOrFail($id);
        $category->delete();
        return response()->json([
            'message' => 'Xóa thành công'
        ]);
    }
}
