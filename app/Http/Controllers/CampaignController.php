<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Requests\Campaigns\CampaignRequest;
use App\Http\Requests\Campaigns\StoreRequest;
use App\Http\Response\ApiResponse;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Category;
use App\Models\Product;
use App\Traits\applyFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $oldStartDate = $campaign->start_date;
        $oldEndDate = $campaign->end_date;
        $newStartDate = $data['start_date'] ?? $oldStartDate;
        $newEndDate = $data['end_date'] ?? $oldEndDate;

        try {
            $campaign->update($data);

            if ($newStartDate !== $oldStartDate || $newEndDate !== $oldEndDate) {
                $campaign->status = $newStartDate > now()
                    ? 'pending'
                    : ($newEndDate < now() ? 'complete' : 'active');
                $campaign->save();
            }

            $this->updateProductPrices($campaign);
            return ApiResponse::message('Cập nhật chiến dịch thành công');
        } catch (\Exception $e) {
            throw new CustomException('Lỗi khi cập nhật chiến dịch', $e->getMessage());
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
            $this->updateProductPrices();
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

    public function category()
    {
        $categories = Category::whereNotNull('parent_id')
            ->with('parent')
            ->get();

        $categoriesWithDetails = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->full_path,
            ];
        });
        return response()->json($categoriesWithDetails);
    }

    public function filter(Request $request)
    {
        $query = Product::select(
            'products.id',
            'products.name',
            'products.thumbnail',
            // 'products.category_id',
            'products.regular_price',
            DB::raw('IFNULL(SUM(product_atts.stock_quantity), 0) as total_stock_quantity')
        )
            ->leftJoin('product_atts', 'products.id', '=', 'product_atts.product_id')
            ->groupBy('products.id', 'products.name', 'products.thumbnail', 'products.regular_price');

        $query->when($request->query('id'), fn($q, $id) => $q->where('products.id', $id));
        $query->when($request->query('categoryId'), fn($q, $categoryId) => $q->where('products.category_id', $categoryId));
        $query->when($request->query('name'), fn($q, $name) => $q->where('products.name', 'like', '%' . $name . '%'));
        $query->when($request->query('minPrice'), fn($q, $minPrice) => $q->where('products.regular_price', '>=', $minPrice));
        $query->when($request->query('maxPrice'), fn($q, $maxPrice) => $q->where('products.regular_price', '<=', $maxPrice));

        if ($request->query('minStockQuantity')) {
            $query->havingRaw('SUM(product_atts.stock_quantity) >= ?', [$request->query('minStockQuantity')]);
        }
        if ($request->query('maxStockQuantity')) {
            $query->havingRaw('SUM(product_atts.stock_quantity) <= ?', [$request->query('maxStockQuantity')]);
        }

        $sort = $request->query('sort', 'ASC');

        if ($request->query('sortByPrice')) {
            $sortByPrice = $request->query('sortByPrice') == 'desc' ? 'desc' : 'asc';
            $query->orderBy('products.regular_price', $sortByPrice);
        }

        if ($request->query('sortByStockQuantity')) {
            $sortByStockQuantity = $request->query('sortByStockQuantity') == 'desc' ? 'desc' : 'asc';
            $query->orderByRaw('SUM(product_atts.stock_quantity) ' . $sortByStockQuantity);
        }

        if (!request()->has('sortByPrice') && !request()->has('sortByStockQuantity')) {
            $query->orderBy('products.id', $sort);
        }

        $size = $request->query('size');
        return $size ? $query->paginate($size) : $query->get();
    }

    public function toggleStatus(string $id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return ApiResponse::error('Chiến dịch không tồn tại', Response::HTTP_NOT_FOUND);
        }
        if ($campaign->status == 'pending') {
            return ApiResponse::error('Chiến dịch chưa bắt đầu, không thể thay đổi trạng thái', Response::HTTP_BAD_REQUEST);
        }

        if ($campaign->status == 'complete') {
            return ApiResponse::error('Chiến dịch đã kết thúc, không thể thay đổi trạng thái', Response::HTTP_BAD_REQUEST);
        }

        $campaign->status = $campaign->status == 'active' ? 'pause' : 'active';
        $campaign->save();
        $this->updateProductPrices();
        return ApiResponse::message('Cập nhật trạng thái thành công');
    }

    protected function updateProductPrices()
    {
        $discounts = DB::table('campaign_products')
            ->join('campaigns', 'campaign_products.campaign_id', '=', 'campaigns.id')
            ->whereIn('campaigns.status', ['active'])
            ->select('campaign_products.product_id', DB::raw('MAX(campaigns.discount_percentage) as max_discount'))
            ->groupBy('campaign_products.product_id')
            ->get()
            ->pluck('max_discount', 'product_id');
        if ($discounts) {
            DB::update("UPDATE products SET reduced_price = 0");
        }
        $caseStatements = [];
        foreach ($discounts as $productId => $discount) {
            $caseStatements[] = "WHEN id = {$productId} THEN regular_price * (1 - {$discount} / 100)";
        }
        if (!empty($caseStatements)) {
            $caseQuery = implode(' ', $caseStatements);
            DB::update("
                        UPDATE products
                        SET reduced_price = CASE {$caseQuery} ELSE reduced_price END
                    ");
        }
    }
}