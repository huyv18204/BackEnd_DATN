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
        $size = $request->query('size', 5);
        $query = Category::query();
        $search['name'] = $request->name;
        $search['id'] = $request->id;
        foreach ($search as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }
        $categories = $query->orderBy('id', $sort)->paginate($size);
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $category = $request->validate(
            [
                'name' => 'required|min:6|max:100|unique:categories,name',
            ]
        );

        $category['slug'] = Str::slug($request->name);
        $response = Category::query()->create($category);

        if (!$response) {
            return response()->json([
                'error' => 'Thêm thất bại'
            ]);
        }
        return response()->json([
            'message' => 'Thêm mới thành công'
        ]);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::query()->find($id);
        if (!$category) {
            return response()->json(
                [
                    'error' => 'Danh mục không tồn tại'
                ]
            );
        }
        $data = $request->validate([
            'name' => 'required|min:6|max:100|unique:categories,name,' . $id
        ]);

        if ($category->name != $request->name) {
            $data['slug'] = Str::slug($request->name);
        }

        $response = $category->update($data);

        if (!$response) {
            return response()->json(
                [
                    'error' => 'Cập nhật thất bại'
                ]
            );
        }
        return response()->json(
            [
                'message' => 'Cập nhật thành công'
            ]
        );
    }

    public function destroy(int $id)
    {
        $category = Category::query()->find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Danh mục không tồn tại'
            ]);
        }
        $category->delete();
        return response()->json([
            'message' => 'Xóa thành công'
        ]);
    }

    public function show($slug)
    {
        $category = Category::query()->where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Danh mục không tồn tại'
            ]);
        }
        return response()->json($category);
    }

    public function trash(){
        $trash = Category::onlyTrashed()->get();
        return response()->json($trash);
    }

    public function restore($id){
        $category = Category::withTrashed()->find($id);
        if(!$category){
            return response()->json(["error"=> "Danh mục không tồn tại"]);
        }
        $category->restore();
        return response()->json(["message"=> "Khôi phục thành công"]);
    }

}

