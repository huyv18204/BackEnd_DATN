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
        foreach ($campaigns as $campaign) {
            $startDate = Carbon::createFromFormat('H:i:s d/m/Y', $campaign->start_date);
            $endDate = Carbon::createFromFormat('H:i:s d/m/Y', $campaign->end_date);

            if ($startDate->isToday()) {
                $this->updateProductPrices($campaign, true);
            }

            if ($endDate->isToday()) {
                $this->updateProductPrices($campaign, false);
            }
        }

        return $next($request);
    }

    protected function updateProductPrices(Campaign $campaign, bool $startCampaign)
    {
        $productData = $campaign->products()->select('regular_price', 'product_id')->get();
        $newPrice = $startCampaign
            ? DB::raw('regular_price * (1 - ' . $campaign->discount_percentage . ' / 100)')
            : 0;
            
        DB::table('products')
            ->whereIn('id', $productData->pluck('product_id')->toArray())
            ->update(['reduced_price' => $newPrice]);
    }
}
