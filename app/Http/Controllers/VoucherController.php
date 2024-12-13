<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\VoucherRequest;
use App\Http\Response\ApiResponse;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();
        $size = $request->query('size');
        return $size ? ApiResponse::data($query->paginate($size)) : ApiResponse::data($query->get());
    }

    public function store(VoucherRequest $request)
    {
        try {
            $data = $request->validated();
            if (empty($data['voucher_code'])) {
                do {
                    $code = 'VC' . strtoupper(Str::random(6));
                } while (Voucher::where('voucher_code', $code)->exists());
            }
            Voucher::create($data);
            return ApiResponse::message("Thêm mới mã giảm giá thành công", Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi thêm voucher", $e->getMessage());
        }
    }

    public function show(string $id)
    {
        $voucher = $this->findOrFail($id);
        return ApiResponse::data($voucher);
    }


    public function update(VoucherRequest $request, string $id)
    {
        $data = $request->validated();
        $voucher = $this->findOrFail($id);
        try {
            $voucher->update($data);
            return ApiResponse::message("Sửa mã giảm giá thành công");
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi sửa voucher", $e->getMessage());
        }
    }

    public function getAllVouchers()
    {
        $voucher = Voucher::where('status', 'active')->get();
        return ApiResponse::data($voucher);
    }

    public function toggleStatus(string $id)
    {
        $voucher = $this->findOrFail($id);
        if ($voucher->status == 'pending' || $voucher->status == 'complete') {
            return ApiResponse::error("Chỉ có thể thay đổi trạng thái mã giảm giá đang hoạt động", Response::HTTP_BAD_REQUEST);
        }
        $voucher->status = $voucher->status === 'active' ? 'pause' : 'active';
        $voucher->save();
        return ApiResponse::message("Thay đổi mã giảm giá thành công");
    }

    public function destroy(string $id)
    {
        $voucher = $this->findOrFail($id);
        if ($voucher->status == 'active') {
            return ApiResponse::error("Không thể xóa mã giảm giá đang hoạt động");
        }
        $voucher->delete();
        return ApiResponse::message("Xóa mã giảm giá thành công");
    }

    public function findOrFail($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            throw new CustomException("Mã giảm giá không tồn tại", Response::HTTP_NOT_FOUND);
        }
        return $voucher;
    }
}
