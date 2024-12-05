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
        // Lấy tham số 'period' từ request (default là 'week')
        $period = $request->input('period', 'week');

        // Nếu lọc theo tuần
        if ($period === 'week') {
            $week = $request->input('week', 'current');

            // Khởi tạo ngày bắt đầu và kết thúc của tuần
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            if ($week === 'last') {
                $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
                $endOfWeek = Carbon::now()->subWeek()->endOfWeek();
            } elseif (is_numeric($week)) {
                $startOfWeek = Carbon::now()->subWeeks($week)->startOfWeek();
                $endOfWeek = Carbon::now()->subWeeks($week)->endOfWeek();
            }

            // Lấy thống kê theo tuần
            $statistics = DB::table('orders')
                ->selectRaw("
                    CASE
                        WHEN DAYOFWEEK(created_at) = 1 THEN 7
                        ELSE DAYOFWEEK(created_at) - 1
                    END as weekday,
                    SUM(total_amount) as revenue,
                    COUNT(id) as orders,
                    COUNT(DISTINCT user_id) as customers
                ")
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->groupByRaw('weekday')
                ->orderByRaw('weekday')
                ->get();

            // Tạo danh sách tất cả các ngày trong tuần (7 ngày)
            $allDays = range(1, 7);
            $statistics = $statistics->keyBy('weekday');

            // Tên ngày trong tuần theo kiểu "Thứ 2", "Thứ 3", ..., "Chủ nhật"
            $weekdays = [
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                7 => 'Chủ nhật',
            ];

            // Thêm dữ liệu cho những ngày không có đơn hàng (trả về 0)
            $statistics = collect($allDays)->map(function ($day) use ($statistics, $weekdays) {
                // Nếu không có dữ liệu cho ngày đó, tạo dữ liệu mặc định
                $stat = $statistics->get($day, (object) [
                    'weekday' => $day,
                    'revenue' => 0,
                    'orders' => 0,
                    'customers' => 0
                ]);
                // Đảm bảo rằng thuộc tính day có tồn tại
                $stat->day = $weekdays[$day];
                return $stat;
            });
        }

        // Nếu lọc theo tháng
        elseif ($period === 'month') {
            // Lấy tham số tháng và năm từ request (default là tháng và năm hiện tại)
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);

            // Xác định tháng và năm cần lọc
            $startOfMonth = Carbon::createFromFormat('Y-m', $year . '-' . $month)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $year . '-' . $month)->endOfMonth();

            // Lấy thống kê theo tháng
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

            // Tạo danh sách tất cả các ngày trong tháng (1 đến 31 ngày)
            $allDays = range(1, Carbon::createFromFormat('Y-m', $year . '-' . $month)->daysInMonth);
            $statistics = $statistics->keyBy('day');

            // Thêm dữ liệu cho những ngày không có đơn hàng (trả về 0)
            $statistics = collect($allDays)->map(function ($day) use ($statistics) {
                // Nếu không có dữ liệu cho ngày đó, tạo dữ liệu mặc định
                $stat = $statistics->get($day, (object) [
                    'day' => $day,
                    'revenue' => 0,
                    'orders' => 0,
                    'customers' => 0
                ]);
                return $stat;
            });
        } else {
            return response()->json(['error' => 'Invalid period type. Use "week" or "month".'], 400);
        }

        // Xử lý thống kê theo ngày cho cả tuần hoặc tháng
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
