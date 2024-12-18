<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\VoucherRequest;
use App\Http\Response\ApiResponse;
use App\Models\Voucher;
use App\Models\VoucherUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            if (!$data['voucher_code']) {
                do {
                    $data['voucher_code'] = 'VC' . strtoupper(Str::random(6));
                } while (Voucher::where('voucher_code', $data['voucher_code'])->exists());
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
        if ($voucher->status !== 'pending') {
            throw new CustomException('Chỉ có thể sửa mã giảm giá ở trạng thái chờ', Response::HTTP_BAD_REQUEST);
        }
        try {
            $data['status'] = 'pending';
            if ($voucher->status == 'expired') {
                $data['used_count'] = 0;
            }
            $voucher->update($data);
            return ApiResponse::message("Sửa mã giảm giá thành công");
        } catch (\Exception $e) {
            throw new CustomException("Lỗi khi sửa voucher", $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $voucher = $this->findOrFail($id);
        if ($voucher->status != 'active') {
            throw new CustomException('Lỗi chỉ có thể thu hồi mã giảm giá đang hoạt động', Response::HTTP_BAD_REQUEST);
        }
        $voucher->status = 'cancel';
        $voucher->save();
        return ApiResponse::message('Mã giảm giá đã được thu hồi thành công', Response::HTTP_OK);
    }

    public function getAllVouchers()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            $user = null;
        }

        if ($user) {
            $vouchers = Voucher::where('status', 'active')
                ->whereDoesntHave('voucher_users', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();
        } else {
            $vouchers = Voucher::where('status', 'active')->get();
        }
        return ApiResponse::data($vouchers);
    }

    public function applyVoucher(VoucherRequest $request)
    {
        $voucherCode = $request->voucher_code;
        $totalOrder = $request->order_total;
        $userId = JWTAuth::parseToken()->authenticate()->id;
        $voucher = Voucher::where('voucher_code', $voucherCode)->first();

        if (!$voucher) {
            return ApiResponse::error('Mã giảm giá không tồn tại', Response::HTTP_NOT_FOUND);
        }

        if ($voucher->status != 'active') {
            return ApiResponse::error("Mã giảm giá đã không còn hoạt động", Response::HTTP_BAD_REQUEST);
        }

        if ($voucher->used_count >= $voucher->usage_limit) {
            $voucher->status = 'used';
            $voucher->save();
            return ApiResponse::error("Rất tiếc mã giảm giá đã đạt đến giới hạn lượt sử dụng");
        }

        if (VoucherUser::where('user_id', $userId)->where('voucher_id', $voucher->id)->exists()) {
            return ApiResponse::error("Bạn đã sử dụng mã giảm giá này rồi", Response::HTTP_BAD_REQUEST);
        }

        if ($voucher->min_order_value && $voucher->min_order_value > $totalOrder) {
            return ApiResponse::error("Mã giảm giá này chỉ có thể áp dụng cho đơn hàng có giá trị từ $voucher->min_order_value trở lên", Response::HTTP_BAD_REQUEST);
        }

        $discountPrice = 0;
        if ($voucher->discount_type == 'percentage') {
            $discountPrice = $totalOrder * ($voucher->discount_value / 100);
            if ($voucher->max_discount) {
                $discountPrice = min($discountPrice, $voucher->max_discount);
            }
        } elseif ($voucher->discount_type == 'fixed_amount') {
            $discountPrice = min($voucher->discount_value, $totalOrder);
            if ($voucher->max_discount) {
                $discountPrice = min($discountPrice, $voucher->max_discount);
            }
        }
        $finalTotal = $totalOrder - $discountPrice;
        return response()->json([
            "discountPrice" => $discountPrice,
            "totalOrder" => $finalTotal,
        ]);
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
