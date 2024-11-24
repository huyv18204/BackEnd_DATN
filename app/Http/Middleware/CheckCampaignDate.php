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
        $campaigns = Campaign::whereIn('status', ['pending', 'active'])->get();
        if ($campaigns->isNotEmpty()) {
            foreach ($campaigns as $campaign) {
                $this->updateCampaignStatus($campaign);
            }
            $this->updateProductPrices($campaign);
        }

        return $next($request);
    }


    private function updateCampaignStatus(Campaign $campaign)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $startDate = Carbon::createFromFormat('H:i:s d/m/Y', $campaign->start_date);
        $endDate = Carbon::createFromFormat('H:i:s d/m/Y', $campaign->end_date);

        if ($startDate > $now) {
            $campaign->status = 'pending';
        } elseif ($endDate >= $now) {
            $campaign->status = 'active';
        } else {
            $campaign->status = 'complete';
        }

        return $campaign->save();
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
