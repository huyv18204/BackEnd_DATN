<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStatistics(Request $request)
    {
        $statistics = collect();
    
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year', Carbon::now()->year);
    
    
        if ($week !== null) {
            $startOfWeek = Carbon::now()->startOfWeek()->subWeeks($week === 'last' ? 1 : ($week === 'current' ? 0 : $week));
            $endOfWeek = Carbon::now()->endOfWeek()->subWeeks($week === 'last' ? 1 : ($week === 'current' ? 0 : $week));
    
            $statistics = DB::table('orders')
                ->selectRaw("
                    CASE WHEN DAYOFWEEK(created_at) = 1 THEN 7 ELSE DAYOFWEEK(created_at) - 1 END as weekday,
                    SUM(total_amount) as revenue,
                    COUNT(id) as orders,
                    COUNT(DISTINCT user_id) as customers
                ")
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->groupByRaw('weekday')
                ->orderByRaw('weekday')
                ->get();
    
            $totalRevenue = $statistics->sum('revenue');
            $totalOrders = $statistics->sum('orders');
            $totalCustomers = $statistics->sum('customers');
    
            $weekdays = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
            $statistics = collect(range(1, 7))->map(function ($day) use ($statistics, $weekdays) {
                $stat = $statistics->firstWhere('weekday', $day) ?? (object) ['weekday' => $day, 'revenue' => 0, 'orders' => 0, 'customers' => 0];
                $stat->day = $weekdays[$day];
                return $stat;
            });
        }
        // Nếu truyền tháng
        elseif ($month !== null) {
            $startOfMonth = Carbon::createFromFormat('Y-m', "$year-$month")->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', "$year-$month")->endOfMonth();
    
            $statistics = DB::table('orders')
                ->selectRaw("
                    DAY(created_at) as day,
                    SUM(total_amount) as revenue,
                    COUNT(id) as orders,
                    COUNT(DISTINCT user_id) as customers
                ")
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupByRaw('DAY(created_at)')
                ->orderByRaw('DAY(created_at)')
                ->get();
    
            $totalRevenue = $statistics->sum('revenue');
            $totalOrders = $statistics->sum('orders');
            $totalCustomers = $statistics->sum('customers');
    
            $allDays = range(1, Carbon::createFromFormat('Y-m', "$year-$month")->daysInMonth);
            $statistics = collect($allDays)->map(function ($day) use ($statistics) {
                $stat = $statistics->firstWhere('day', $day) ?? (object) ['day' => $day, 'revenue' => 0, 'orders' => 0, 'customers' => 0];
                return $stat;
            });
        } else {
            return response()->json(['error' => 'Invalid week or month input.'], 400);
        }
    
        return response()->json([
            'total_revenue' => (int) $totalRevenue,
            'total_orders' => (int) $totalOrders,
            'total_customers' => (int) $totalCustomers,
            'statistics' => $statistics->map(fn($stat) => [
                'day' => $stat->day,
                'revenue' => (int) $stat->revenue,
                'orders' => (int) $stat->orders,
                'customers' => (int) $stat->customers,
            ]),
        ]);
    }
    
    
}
