<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Sizes\SizeRequest;
use App\Http\Response\ApiResponse;
use App\Models\Size;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        $sizes = Size::all();
        return ApiResponse::data($sizes);
    }

    public function store(SizeRequest $request)
    {
        $data = $request->validated();
        Size::create($data);
        return ApiResponse::message('Thêm mới kích cỡ thành công', 201);
    }

    public function update(SizeRequest $request, $id)
    {
        $size = Size::find($id);
        if (!$size) {
            return ApiResponse::error('Kích cỡ không tồn tại', 404);
        }
        $data = $request->validated();
        $size->update($data);
        return ApiResponse::message('Cập nhật kích cỡ thành công');
    }

    public function destroy($id)
    {
        $size = Size::find($id);
        if (!$size) {
            return ApiResponse::error('Kích cỡ không tồn tại', 404);
        }
        if ($size->product_atts()->exists()) {
            return ApiResponse::error('Không thể xóa vì kích cỡ này đang được sử dụng', 400);
        }
        $size->delete();
        return ApiResponse::message('Xóa kích cỡ thành công');
    }
}
