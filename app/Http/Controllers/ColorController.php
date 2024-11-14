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
        $size = $request->query('size');
        $name = $request->query('name');
        $sort = $request->query('sort', "ASC");

        try {
            $colors = Color::query();

            if ($name) {
                $colors->where('name', 'like', "%$name%");
            }

            $colors->orderBy('id', $sort);
            $colors = $size ? $colors->paginate($size) : $colors->get();

            return ApiResponse::data($colors, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi truy xuất danh sách màu sắc', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(ColorRequest $request)
    {
        $data = $request->validated();
        try {
            Color::create($data);
            return ApiResponse::message('Thêm mới màu sắc thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException('Thêm mới màu sắc thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function update(ColorRequest $request, $id)
    {
        $color = $this->findOrFail($id);
        $data = $request->validated();
        try {
            $color->update($data);
            return ApiResponse::message('Cập nhật màu sắc thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Cập nhật màu sắc thất bại, vui lòng thử lại sau.', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $color = $this->findOrFail($id);
        try {
            $color->is_active = !$color->is_active;
            $color->save();
            return ApiResponse::message('Thay đổi trạng thái thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Thay đổi trạng thái màu sắc thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $color = $this->findOrFail($id);
        if ($color->product_atts()->exists()) {
            return ApiResponse::error('Không thể xóa vì màu này đang được sử dụng', Response::HTTP_BAD_REQUEST);
        }
        try {
            $color->delete();
            return ApiResponse::message('Xóa màu sắc thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Xóa màu sắc thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function findOrFail($id)
    {
        $color = Color::query()->find($id);
        if (!$color) {
            throw new CustomException('Màu sắc Không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $color;
    }
}
