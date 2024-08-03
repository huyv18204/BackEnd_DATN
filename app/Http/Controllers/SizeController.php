<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size');
        $name = $request->query('name');
        $sort = $request->query('sort', "ASC");
        $sizes = Size::query();
        if ($name) {
            $sizes->where('name', $name);
        };
        $sizes->orderBy('id', $sort);

        if ($size) {
            $data = $sizes->paginate($size);
        } else {
            $data = $sizes->get();
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $size = $request->validate([
            'name' => 'required|string|unique:sizes,name|max:55'
        ]);
        $response = Size::query()->create($size);
        if ($response) {
            return response()->json("Thêm thành công");
        } else {
            return response()->json("Thêm thất bại");
        }
    }

    public function show($id)
    {
        $size = Size::query()->find($id);
        if (!$size) {
            return response()->json("Size không tồn tại");
        }
        return response()->json($size);
    }

    public function update(Request $request, $id)
    {
        $size = Size::query()->find($id);
        if (!$size) {
            return response()->json("Size không tồn tại");
        }
        $data = $request->validate([
            'name' => 'required|string|unique:sizes,name|max:55'
        ]);

        $response = $size->update($data);

        if (!$response) {
            return response()->json("Cập nhật thất bại");
        }
        return response()->json("Cập nhật thành công");
    }

    public function destroy($id)
    {
        $size = Size::query()->find($id);
        if (!$size) {
            return response()->json("Size không tồn tại");
        }
        $size->delete();
        return response()->json("Xoá thành công");
    }


    public function trash(Request $request)
    {
        $size = $request->query('size');
        $name = $request->query('name');
        $sort = $request->query('sort', "ASC");
        $sizes = Size::onlyTrashed();
        if ($name) {
            $sizes->where('name', $name);
        };
        $sizes->orderBy('id', $sort);

        if ($size) {
            $trash = $sizes->paginate($size);
        } else {
            $trash = $sizes->get();
        }

        return response()->json($trash);
    }

    public function restore($id)
    {
        $size = Size::withTrashed()->find($id);
        if (!$size) {
            return response()->json(["error" => "Size không tồn tại"]);
        }
        $size->restore();
        return response()->json(["message" => "Khôi phục thành công"]);
    }
}
