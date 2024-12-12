<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\BannerRequest;
use App\Http\Response\ApiResponse;
use App\Models\Banner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::all();
        return ApiResponse::data($banners);
    }

    public function store(BannerRequest $request)
    {
        $data = $request->validated();
        try {
            Banner::create($data);
            return ApiResponse::message("Thêm mới banner thành công", Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi thêm mới banner", $e->getMessage());
        }
    }

    public function show($id)
    {
        $banner = $this->findOrFail($id);
        return ApiResponse::data($banner);
    }

    public function update(BannerRequest $request, $id)
    {
        $banner = $this->findOrFail($id);
        $data = $request->validated();
        try {
            $banner->update($data);
            return ApiResponse::message("Sửa banner thành công");
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi thêm mới banner', $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $banner = $this->findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();
        return ApiResponse::message("Thay đổi trạng thái thành công");
    }


    public function destroy($id)
    {
        $banner = $this->findOrFail($id);
        $banner->delete();
        return ApiResponse::message("Xóa banner thành công");
    }

    public function findOrFail($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            throw new CustomException('Banner không tồn tại', Response::HTTP_NOT_FOUND);
        }
        return $banner;
    }
}
