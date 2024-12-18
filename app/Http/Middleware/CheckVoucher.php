<?php

namespace App\Http\Middleware;

use App\Models\Voucher;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckVoucher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $now = Carbon::now();

        $vouchers = Voucher::whereIn('status', ['pending', 'active'])->get();
        foreach ($vouchers as $voucher) {
            if ($voucher->status === 'pending' && $voucher->start_date && $now->greaterThanOrEqualTo($voucher->start_date)) {
                $voucher->status = 'active';
                $voucher->save();
            }

            if (($voucher->status === 'active' || $voucher->status === 'cancel')  && $voucher->end_date && $now->greaterThan($voucher->end_date)) {
                $voucher->status = 'expired';
                $voucher->save();
            }
        }

        return $next($request);
    }
}
