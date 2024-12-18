<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Color\ColorRequest;
use App\Http\Response\ApiResponse;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $colors = Color::all();
        return ApiResponse::data($colors);
    }

    public function store(ColorRequest $request)
    {
        $data = $request->validated();
        Color::create($data);
        return ApiResponse::message('Thêm mới màu sắc thành công', 201);
    }

    public function update(ColorRequest $request, $id)
    {
        $color = Color::find($id);
        if (!$color) {
            return ApiResponse::error('Màu sắc Không tồn tại', 404);
        }
        $data = $request->validated();
        $color->update($data);
        return ApiResponse::message('Cập nhật màu sắc thành công');
    }

    public function destroy($id)
    {
        $color = Color::find($id);
        if (!$color) {
            return ApiResponse::error('Màu sắc Không tồn tại', 404);
        }
        if ($color->product_atts()->exists()) {
            return ApiResponse::error('Không thể xóa vì màu này đang được sử dụng', 400);
        }
        $color->delete();
        return ApiResponse::message('Xóa màu sắc thành công');
    }
}
