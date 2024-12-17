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
                ->where('order_status', 'Đã giao')
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
        } elseif ($month !== null) {
            $startOfMonth = Carbon::createFromFormat('Y-m', "$year-$month")->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', "$year-$month")->endOfMonth();

            $statistics = DB::table('orders')
                ->selectRaw("
                    DAY(created_at) as day,
                    SUM(total_amount) as revenue,
                    COUNT(id) as orders,
                    COUNT(DISTINCT user_id) as customers
                ")
                ->where('order_status', 'Đã giao')
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

    public function getTimelineData()
    {
        $timelineData = DB::table('orders')
            ->selectRaw("
                order_status,
                id,
                TIMESTAMPDIFF(HOUR, created_at, NOW()) as time_diff,
                CASE 
                    WHEN order_status = 'Chờ xác nhận' THEN 'orange'
                    WHEN order_status = 'Đã giao' THEN 'green'
                    WHEN order_status = 'Đang giao' THEN 'blue'
                    WHEN order_status = 'Đã huỷ' THEN 'red'
                    ELSE 'gray'
                END as color
            ")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($timelineData);
    }


    public function getOrders()
    {
        $orders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'orders.id',
                'users.name as customer_name',
                'orders.order_address as address',
                'orders.total_amount as total'
            )
            ->orderBy('orders.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($orders);
    }

    public function getTrendingProducts()
    {
        $trendingProducts = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->where('orders.order_status', 'Đã giao')
            ->selectRaw('
                order_details.product_name as name,
                COUNT(*) as orders,
                SUM(order_details.total_amount) as revenue
            ')
            ->groupBy('order_details.product_name')
            ->orderByDesc('orders')
            ->limit(5)
            ->get()
            ->map(function ($product, $index) {
                $product->rank = $index + 1;
                $product->revenue = '$' . number_format($product->revenue, 2);
                return $product;
            });

        return response()->json($trendingProducts);
    }
}
