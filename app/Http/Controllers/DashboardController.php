<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getWeeklyStatistics(Request $request)
    {
        $week = $request->input('week', 'current');

        if ($week === 'current') {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
        } elseif ($week === 'last') {
            $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
            $endOfWeek = Carbon::now()->subWeek()->endOfWeek();
        } elseif (is_numeric($week)) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $startOfWeek = $startOfMonth->copy()->addWeeks($week - 1)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            if ($startOfWeek->month !== $startOfMonth->month) {
                $startOfWeek = $startOfMonth->copy();
            }
            if ($endOfWeek->month !== $endOfMonth->month) {
                $endOfWeek = $endOfMonth->copy();
            }
        } else {
            return response()->json(['error' => 'Invalid week parameter'], 400);
        }

        $statistics = DB::table('orders')
            ->selectRaw("
                CASE
                    WHEN DAYOFWEEK(created_at) = 1 THEN 7
                    ELSE DAYOFWEEK(created_at) - 1
                END as weekday,
                DAYNAME(created_at) as day,
                SUM(total_amount) as revenue,
                COUNT(id) as orders,
                COUNT(DISTINCT user_id) as customers
            ")
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupByRaw('weekday, DAYNAME(created_at)')
            ->orderByRaw('weekday')
            ->get();

        return response()->json($statistics->map(function ($stat) {
            return [
                'day' => $stat->day,
                'revenue' => (int) $stat->revenue,
                'orders' => (int) $stat->orders,
                'customers' => (int) $stat->customers,
            ];
        }));
    }
}
