<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\VoucherRequest;
use App\Http\Response\ApiResponse;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();
        $size = $request->query('size');
        return $size ? ApiResponse::data($query->paginate($size)) : ApiResponse::data($query->get());;
    }

    public function store(VoucherRequest $request)
    {
        try {
            $data = $request->validated();

            $currentDate = now();
            if ($currentDate->between($data['start_date'], $data['end_date'])) {
                $data['status'] = 'Đang diễn ra';
            }
            Voucher::create($data);
            return ApiResponse::message("Thêm mới mã giảm giá thành công", Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi thêm voucher", $e->getMessage());
        }
    }


    public function update(VoucherRequest $request, string $id)
    {
        $data = $request->validated();
        $voucher = $this->findOrFail($id);
        $currentDate = now();
        if ($currentDate->between($data['start_date'], $data['end_date'])) {
            $data['status'] = 'Đang diễn ra';
        }
        try {
            $voucher->update($data);
            return ApiResponse::message("Sửa mã giảm giá thành công");
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi sửa voucher", $e->getMessage());
        }
    }

    public function getAllVouchers()
    {
        $voucher = Voucher::where('status', 'Đang diễn ra');
        return ApiResponse::data($voucher);
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
