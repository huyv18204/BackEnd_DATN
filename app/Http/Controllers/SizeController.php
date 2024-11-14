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
        $size = $request->query('size');
        $name = $request->query('name');
        $sort = $request->query('sort', "ASC");

        try {
            $sizes = Size::query();

            if ($name) {
                $sizes->where('name', 'like', "%$name%");
            }

            $sizes->orderBy('id', $sort);
            $sizes = $size ? $sizes->paginate($size) : $sizes->get();

            return ApiResponse::data($sizes, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi truy xuất danh sách kích cỡ', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(SizeRequest $request)
    {
        $data = $request->validated();
        try {
            Size::create($data);
            return ApiResponse::message('Thêm mới kích cỡ thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException('Thêm mới kích cỡ thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function update(SizeRequest $request, $id)
    {
        $size = $this->findOrFail($id);
        $data = $request->validated();
        try {
            $size->update($data);
            return ApiResponse::message('Cập nhật kích cỡ thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Cập nhật kích cỡ thất bại, vui lòng thử lại sau.', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $size = $this->findOrFail($id);
        try {
            $size->is_active = !$size->is_active;
            $size->save();
            return ApiResponse::message('Thay đổi trạng thái thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Thay đổi trạng thái kích cỡ thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $size = $this->findOrFail($id);
        if ($size->product_atts()->exists()) {
            return ApiResponse::error('Không thể xóa vì kích cỡ này đang được sử dụng', Response::HTTP_BAD_REQUEST);
        }
        try {
            $size->delete();
            return ApiResponse::message('Xóa kích cỡ thành công', Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new CustomException('Xóa kích cỡ thất bại', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function findOrFail($id)
    {
        $size = Size::query()->find($id);
        if (!$size) {
            throw new CustomException('Kích cỡ không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $size;
    }
}
