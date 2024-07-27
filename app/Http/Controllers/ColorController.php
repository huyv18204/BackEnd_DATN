<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size', 5);
        $name = $request->query('name');
        $sort = $request->query('sort', "ASC");
        $colors = Color::query();
        if ($name) {
            $colors->where('name', $name);
        };
        $colors->orderBy('id', $sort);
        $data = $colors->paginate($size);
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $color = $request->validate([
            'name' => 'required|string|unique:colors,name|max:55'
        ]);
        $response = Color::query()->create($color);
        if ($response) {
            return response()->json("Thêm thành công");
        } else {
            return response()->json("Thêm thất bại");
        }
    }

    public function show($id)
    {
        $color = Color::query()->find($id);
        if (!$color) {
            return response()->json("Màu không tồn tại");
        }
        return response()->json($color);
    }

    public function update(Request $request, $id)
    {
        $color = Color::query()->find($id);
        if (!$color) {
            return response()->json("Màu không tồn tại");
        }
        $data = $request->validate([
            'name' => 'required|string|unique:colors,name|max:55'
        ]);

        $response = $color->update($data);

        if (!$response) {
            return response()->json("Cập nhật thất bại");
        }
        return response()->json("Cập nhật thành công");
    }

    public function destroy($id)
    {
        $color = Color::query()->find($id);
        if (!$color) {
            return response()->json("Màu không tồn tại");
        }
        $color->delete();
        return response()->json("Xoá thành công");
    }


    public function trash(){
        $trash = Color::onlyTrashed()->get();
        return response()->json($trash);
    }

    public function restore($id){
        $color = Color::withTrashed()->find($id);
        if(!$color){
            return response()->json(["error"=> "Màu không tồn tại"]);
        }
        $color->restore();
        return response()->json(["message"=> "Khôi phục thành công"]);
    }

}
