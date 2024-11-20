<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Campaigns\CampaignRequest;
use App\Http\Requests\Campaigns\StoreRequest;
use App\Http\Response\ApiResponse;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Traits\applyFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\String_;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends Controller
{
    use applyFilters;
    public function index(Request $request)
    {
        $query = Campaign::query();
        $campaigns = $this->Filters($query, $request);
        return ApiResponse::data($campaigns);
    }

    public function store(CampaignRequest $request)
    {
        $data = $request->validated();
        try {
            Campaign::create($data);
            return ApiResponse::message('Thêm mới chiến dịch thành công', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi thêm chiến dịch', $e->getMessage());
        }
    }

    public function show($id)
    {
        $campaigns = CampaignProduct::where('campaign_id', $id)->get();
        return ApiResponse::data($campaigns);
    }

    public function update(CampaignRequest $request, string $id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return ApiResponse::message('Chiến dịch không tồn tại', Response::HTTP_NOT_FOUND);
        }
        $data = $request->validated();
        try {
            $campaign->update($data);
            return ApiResponse::message('Cập nhật chiến dịch thành công');
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi cập nhật sản phẩm', $e->getMessage());
        }
    }

    public function addProduct(StoreRequest $request, string $id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return ApiResponse::message('Chiến dịch không tồn tại', Response::HTTP_NOT_FOUND);
        }
        $data = $request->validated();
        $now = now()->toDateTimeString();
        try {
            DB::beginTransaction();
            foreach ($data['product_id'] as $productId) {
                $dataProduct[] = [
                    'campaign_id' => $id,
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            CampaignProduct::insert($dataProduct);
            DB::commit();
            return ApiResponse::message('Thêm sản phẩm vào chiến dịch thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new CustomException('Lỗi khi thêm sản phẩm chiến dịch', $e->getMessage());
        }
    }

    public function destroy(string $id, string $productId)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return ApiResponse::error('Chiến dịch không tồn tại', Response::HTTP_NOT_FOUND);
        }
        if ($campaign->status == 'complete') {
            return ApiResponse::error('Không thể xóa sản phẩm khi chiến dịch đã hoàn thành', Response::HTTP_BAD_REQUEST);
        }
        $product = CampaignProduct::where('campaign_id', $id)->where('product_id', $productId)->first();
        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại', Response::HTTP_NOT_FOUND);
        }
        $product->delete();
        return ApiResponse::message('Xóa sản phẩm khỏi chiến dịch thành công');
    }
}
