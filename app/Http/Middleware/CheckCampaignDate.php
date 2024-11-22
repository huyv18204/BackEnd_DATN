<?php

namespace App\Http\Middleware;

use App\Models\Campaign;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckCampaignDate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $today = Carbon::today();
        $campaigns = Campaign::where(function ($query) use ($today) {
            $query->whereDate('start_date', $today)
                ->orWhereDate('end_date', $today);
        })->get();
        if ($campaigns->isNotEmpty()) {
            $this->updateProductPrices();
        }

        return $next($request);
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
