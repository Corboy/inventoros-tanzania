<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockAdjustment;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\SavedReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for generating reports.
 *
 * Handles various inventory and sales reports including
 * inventory valuation, stock movement, sales analysis,
 * low stock alerts, and category performance.
 */
class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $savedReports = SavedReport::accessibleBy($user)
            ->with('creator:id,name')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Reports/Index', [
            'savedReports' => $savedReports,
        ]);
    }

    /**
     * Inventory Valuation Report.
     *
     * @param  Request  $request  The incoming HTTP request
     */
    public function inventoryValuation(Request $request): Response
    {
        $organizationId = $request->user()->organization_id;

        $products = Product::forOrganization($organizationId)
            ->with(['category', 'location'])
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category?->name,
                    'location' => $product->location?->name,
                    'stock' => $product->stock,
                    'price' => $product->price,
                    'purchase_price' => $product->purchase_price ?? 0,
                    'stock_value' => $product->stock * $product->price,
                    'cost_value' => $product->stock * ($product->purchase_price ?? 0),
                    'profit_potential' => $product->stock * ($product->price - ($product->purchase_price ?? 0)),
                ];
            });

        $summary = [
            'total_items' => $products->count(),
            'total_quantity' => $products->sum('stock'),
            'total_stock_value' => $products->sum('stock_value'),
            'total_cost_value' => $products->sum('cost_value'),
            'total_profit_potential' => $products->sum('profit_potential'),
        ];

        // Group by category
        $byCategory = $products->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category ?: 'Uncategorized',
                'items' => $items->count(),
                'quantity' => $items->sum('stock'),
                'value' => $items->sum('stock_value'),
            ];
        })->values();

        return Inertia::render('Reports/InventoryValuation', [
            'products' => $products,
            'summary' => $summary,
            'byCategory' => $byCategory,
        ]);
    }

    /**
     * Stock Movement Report.
     *
     * @param  Request  $request  The incoming HTTP request
     */
    public function stockMovement(Request $request): Response
    {
        $organizationId = $request->user()->organization_id;

        $query = StockAdjustment::with(['product', 'user'])
            ->forOrganization($organizationId);

        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Summary statistics over the SAME filtered set as the table (clone so
        // each aggregate gets its own builder). Previously these ignored the
        // date/product/type filters, so the headline totals contradicted the
        // rows shown.
        $summary = [
            'total_adjustments' => (clone $query)->count(),
            'total_increases' => (clone $query)->where('adjustment_quantity', '>', 0)->sum('adjustment_quantity'),
            'total_decreases' => abs((clone $query)->where('adjustment_quantity', '<', 0)->sum('adjustment_quantity')),
            'net_change' => (clone $query)->sum('adjustment_quantity'),
        ];

        $adjustments = $query->latest()->paginate(50)->withQueryString();

        // Get products for filter
        $products = Product::forOrganization($organizationId)
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/StockMovement', [
            'adjustments' => $adjustments,
            'summary' => $summary,
            'products' => $products,
            'filters' => $request->only(['date_from', 'date_to', 'product_id', 'type']),
        ]);
    }

    /**
     * Sales Analysis & Periodic Reporting.
     * Supports Day, Week, Month, Year, and Custom ranges with profit and payment insights.
     *
     * @param  Request  $request  The incoming HTTP request
     */
    public function salesAnalysis(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $period = (string) ($request->period ?? ($request->hasAny(['date_from', 'date_to']) ? 'custom' : 'last_30_days'));
        $now = now();

        switch ($period) {
            case 'today':
                $dateFrom = $now->copy()->startOfDay()->format('Y-m-d');
                $dateTo = $now->copy()->endOfDay()->format('Y-m-d');
                $granularity = 'hour';
                $periodLabel = 'Leo (' . $now->translatedFormat('d M Y') . ')';
                break;
            case 'yesterday':
                $yesterday = $now->copy()->subDay();
                $dateFrom = $yesterday->copy()->startOfDay()->format('Y-m-d');
                $dateTo = $yesterday->copy()->endOfDay()->format('Y-m-d');
                $granularity = 'hour';
                $periodLabel = 'Jana (' . $yesterday->translatedFormat('d M Y') . ')';
                break;
            case 'this_week':
                $dateFrom = $now->copy()->startOfWeek()->format('Y-m-d');
                $dateTo = $now->copy()->endOfWeek()->format('Y-m-d');
                $granularity = 'day';
                $periodLabel = 'Wiki Hii';
                break;
            case 'last_week':
                $lastWeek = $now->copy()->subWeek();
                $dateFrom = $lastWeek->copy()->startOfWeek()->format('Y-m-d');
                $dateTo = $lastWeek->copy()->endOfWeek()->format('Y-m-d');
                $granularity = 'day';
                $periodLabel = 'Wiki Iliyopita';
                break;
            case 'last_7_days':
                $dateFrom = $now->copy()->subDays(6)->format('Y-m-d');
                $dateTo = $now->copy()->format('Y-m-d');
                $granularity = 'day';
                $periodLabel = 'Siku 7 Zilizopita';
                break;
            case 'this_month':
                $dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
                $dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
                $granularity = 'day';
                $periodLabel = 'Mwezi Huu (' . $now->translatedFormat('F Y') . ')';
                break;
            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                $dateFrom = $lastMonth->copy()->startOfMonth()->format('Y-m-d');
                $dateTo = $lastMonth->copy()->endOfMonth()->format('Y-m-d');
                $granularity = 'day';
                $periodLabel = 'Mwezi Uliopita (' . $lastMonth->translatedFormat('F Y') . ')';
                break;
            case 'this_year':
                $dateFrom = $now->copy()->startOfYear()->format('Y-m-d');
                $dateTo = $now->copy()->endOfYear()->format('Y-m-d');
                $granularity = 'month';
                $periodLabel = 'Mwaka Huu (' . $now->format('Y') . ')';
                break;
            case 'last_year':
                $lastYear = $now->copy()->subYear();
                $dateFrom = $lastYear->copy()->startOfYear()->format('Y-m-d');
                $dateTo = $lastYear->copy()->endOfYear()->format('Y-m-d');
                $granularity = 'month';
                $periodLabel = 'Mwaka Uliopita (' . $lastYear->format('Y') . ')';
                break;
            case 'custom':
            default:
                $dateFrom = $request->date_from ?? $now->copy()->subDays(30)->format('Y-m-d');
                $dateTo = $request->date_to ?? $now->copy()->format('Y-m-d');
                $daysDiff = \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo));
                if ($daysDiff <= 1) {
                    $granularity = 'hour';
                } elseif ($daysDiff <= 62) {
                    $granularity = 'day';
                } else {
                    $granularity = 'month';
                }
                $periodLabel = 'Kipindi Maalum (' . $dateFrom . ' hadi ' . $dateTo . ')';
                break;
        }

        $fromTimestamp = $dateFrom . ' 00:00:00';
        $toTimestamp = $dateTo . ' 23:59:59';

        // Orders summary in window
        $ordersQuery = Order::forOrganization($organizationId)
            ->whereBetween('order_date', [$fromTimestamp, $toTimestamp]);

        $summaryRow = (clone $ordersQuery)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue')
            ->first();

        $totalOrders = (int) ($summaryRow->total_orders ?? 0);
        $totalRevenue = (float) ($summaryRow->total_revenue ?? 0);

        // Calculate Cost of Goods Sold (COGS) and Items Sold
        $itemsAgg = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.organization_id', $organizationId)
            ->whereBetween('orders.order_date', [$fromTimestamp, $toTimestamp])
            ->selectRaw('
                COALESCE(SUM(order_items.quantity), 0) as total_items_sold,
                COALESCE(SUM(order_items.quantity * COALESCE(products.purchase_price, 0)), 0) as total_cost
            ')
            ->first();

        $totalItemsSold = (int) ($itemsAgg->total_items_sold ?? 0);
        $totalCost = (float) ($itemsAgg->total_cost ?? 0);
        $grossProfit = max(0, $totalRevenue - $totalCost);
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0.0;
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;

        $summary = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_items_sold' => $totalItemsSold,
            'average_order_value' => $avgOrderValue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
        ];

        // Sales by status
        $byStatus = (clone $ordersQuery)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status instanceof \BackedEnum ? $row->status->value : (string) ($row->status ?? ''),
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        // Top selling products with profit margin
        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.organization_id', $organizationId)
            ->whereBetween('orders.order_date', [$fromTimestamp, $toTimestamp])
            ->selectRaw('
                order_items.product_id,
                order_items.product_name,
                order_items.sku,
                SUM(order_items.quantity) as quantity_sold,
                SUM(order_items.total) as revenue,
                SUM(order_items.quantity * COALESCE(products.purchase_price, 0)) as cost
            ')
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
                'cost' => (float) $row->cost,
                'profit' => max(0, (float) $row->revenue - (float) $row->cost),
            ])
            ->values();

        // Payment methods breakdown (Cash, Mobile Money, Bank)
        $ordersForMeta = (clone $ordersQuery)->select('id', 'total', 'metadata')->get();
        $paymentMap = [];
        foreach ($ordersForMeta as $ord) {
            $meta = is_array($ord->metadata) ? $ord->metadata : [];
            $method = (string) ($meta['payment_method'] ?? 'cash');
            if (! isset($paymentMap[$method])) {
                $paymentMap[$method] = [
                    'method' => $method,
                    'count' => 0,
                    'revenue' => 0.0,
                ];
            }
            $paymentMap[$method]['count']++;
            $paymentMap[$method]['revenue'] += (float) $ord->total;
        }
        $paymentMethods = array_values($paymentMap);
        usort($paymentMethods, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Sales by Cashier / Staff
        $salesByUser = Order::query()
            ->where('orders.organization_id', $organizationId)
            ->whereNull('orders.deleted_at')
            ->whereBetween('orders.order_date', [$fromTimestamp, $toTimestamp])
            ->leftJoin('users', 'users.id', '=', 'orders.created_by')
            ->selectRaw('COALESCE(users.name, "Msimamizi / Kaunta") as user_name, COUNT(orders.id) as orders_count, COALESCE(SUM(orders.total), 0) as total_revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [
                'user_name' => (string) $row->user_name,
                'orders_count' => (int) $row->orders_count,
                'total_revenue' => (float) $row->total_revenue,
            ])
            ->values();

        // Trend Breakdown (Hour, Day, or Month)
        $ordersForTrend = (clone $ordersQuery)->select('order_date', 'total')->get();
        $trendBuckets = [];

        if ($granularity === 'hour') {
            for ($h = 6; $h < 24; $h++) {
                $hKey = sprintf('%02d:00', $h);
                $trendBuckets[$hKey] = ['date' => $hKey, 'orders' => 0, 'revenue' => 0.0];
            }
            foreach ($ordersForTrend as $ord) {
                $hKey = \Carbon\Carbon::parse($ord->order_date)->format('H:00');
                if (! isset($trendBuckets[$hKey])) {
                    $trendBuckets[$hKey] = ['date' => $hKey, 'orders' => 0, 'revenue' => 0.0];
                }
                $trendBuckets[$hKey]['orders']++;
                $trendBuckets[$hKey]['revenue'] += (float) $ord->total;
            }
            ksort($trendBuckets);
        } elseif ($granularity === 'month') {
            foreach ($ordersForTrend as $ord) {
                $mKey = \Carbon\Carbon::parse($ord->order_date)->format('Y-m');
                if (! isset($trendBuckets[$mKey])) {
                    $trendBuckets[$mKey] = ['date' => $mKey, 'orders' => 0, 'revenue' => 0.0];
                }
                $trendBuckets[$mKey]['orders']++;
                $trendBuckets[$mKey]['revenue'] += (float) $ord->total;
            }
            ksort($trendBuckets);
        } else {
            foreach ($ordersForTrend as $ord) {
                $dKey = \Carbon\Carbon::parse($ord->order_date)->format('Y-m-d');
                if (! isset($trendBuckets[$dKey])) {
                    $trendBuckets[$dKey] = ['date' => $dKey, 'orders' => 0, 'revenue' => 0.0];
                }
                $trendBuckets[$dKey]['orders']++;
                $trendBuckets[$dKey]['revenue'] += (float) $ord->total;
            }
            ksort($trendBuckets);
        }

        $dailySales = array_values($trendBuckets);

        // CSV Export Handler
        if ($request->export === 'csv') {
            $filename = sprintf('ripoti-ya-mauzo-%s-%s.csv', $period, date('Ymd-His'));
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            return response()->streamDownload(function () use ($periodLabel, $summary, $dailySales, $topProducts, $paymentMethods) {
                $handle = fopen('php://output', 'w');
                // UTF-8 BOM for Excel Swahili compatibility
                fputs($handle, "\xEF\xBB\xBF");

                fputcsv($handle, ['INVENTOROS TANZANIA - RIPOTI YA MAUZO NA FAIDA']);
                fputcsv($handle, ['Kipindi:', $periodLabel]);
                fputcsv($handle, ['Tarehe Iliyozalishwa:', now()->toDateTimeString()]);
                fputcsv($handle, []);

                fputcsv($handle, ['MUHTASARI WA KIFEDHA']);
                fputcsv($handle, ['Jumla ya Mauzo (TSh)', number_format($summary['total_revenue'], 2)]);
                fputcsv($handle, ['Gharama za Ununuzi (TSh)', number_format($summary['total_cost'], 2)]);
                fputcsv($handle, ['Faida Ghafi (TSh)', number_format($summary['gross_profit'], 2)]);
                fputcsv($handle, ['Asilimia ya Faida', $summary['profit_margin'].'%']);
                fputcsv($handle, ['Idadi ya Mauzo/Oda', $summary['total_orders']]);
                fputcsv($handle, ['Jumla ya Bidhaa Zilizouzwa', $summary['total_items_sold']]);
                fputcsv($handle, ['Wastani kwa Oda (TSh)', number_format($summary['average_order_value'], 2)]);
                fputcsv($handle, []);

                fputcsv($handle, ['MCHANGANUO KWA WAKATI']);
                fputcsv($handle, ['Kipindi / Tarehe', 'Idadi ya Mauzo', 'Mapato (TSh)']);
                foreach ($dailySales as $row) {
                    fputcsv($handle, [$row['date'], $row['orders'], number_format($row['revenue'], 2)]);
                }
                fputcsv($handle, []);

                fputcsv($handle, ['BIDHAA ZINAZOTEMBEA ZAIDI']);
                fputcsv($handle, ['Jina la Bidhaa', 'SKU', 'Vipande Vilivyouzwa', 'Mapato (TSh)', 'Gharama (TSh)', 'Faida (TSh)']);
                foreach ($topProducts as $prod) {
                    fputcsv($handle, [
                        $prod['product_name'],
                        $prod['sku'],
                        $prod['quantity_sold'],
                        number_format($prod['revenue'], 2),
                        number_format($prod['cost'], 2),
                        number_format($prod['profit'], 2),
                    ]);
                }
                fputcsv($handle, []);

                fputcsv($handle, ['MCHANGANUO WA NJIA ZA MALIPO']);
                fputcsv($handle, ['Njia ya Malipo', 'Idadi ya Miamala', 'Kiasi (TSh)']);
                foreach ($paymentMethods as $pm) {
                    fputcsv($handle, [ucfirst($pm['method']), $pm['count'], number_format($pm['revenue'], 2)]);
                }
                fclose($handle);
            }, $filename, $headers);
        }

        return Inertia::render('Reports/SalesAnalysis', [
            'summary' => $summary,
            'byStatus' => $byStatus,
            'topProducts' => $topProducts,
            'dailySales' => $dailySales,
            'paymentMethods' => $paymentMethods,
            'salesByUser' => $salesByUser,
            'filters' => [
                'period' => $period,
                'period_label' => $periodLabel,
                'granularity' => $granularity,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Low Stock Report.
     *
     * @param  Request  $request  The incoming HTTP request
     */
    public function lowStock(Request $request): Response
    {
        $organizationId = $request->user()->organization_id;

        $products = Product::forOrganization($organizationId)
            ->with(['category', 'location'])
            ->where('is_active', true)
            ->whereRaw('stock <= min_stock')
            ->orderBy('stock', 'asc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category?->name,
                    'location' => $product->location?->name,
                    'current_stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    'max_stock' => $product->max_stock,
                    'deficit' => max(0, $product->min_stock - $product->stock),
                    'status' => $product->stock <= 0 ? 'out_of_stock' : 'low_stock',
                    'price' => $product->price,
                    // Reorder up to max_stock; fall back to reorder_point /
                    // min_stock when it's null, and never let a null or
                    // already-satisfied target produce a negative cost.
                    'reorder_cost' => max(0, ($product->max_stock ?? $product->reorder_point ?? $product->min_stock ?? 0) - $product->stock)
                        * ($product->purchase_price ?? $product->price),
                ];
            });

        $summary = [
            'total_low_stock' => $products->count(),
            'out_of_stock' => $products->where('status', 'out_of_stock')->count(),
            'low_stock' => $products->where('status', 'low_stock')->count(),
            'total_reorder_cost' => $products->sum('reorder_cost'),
        ];

        return Inertia::render('Reports/LowStock', [
            'products' => $products,
            'summary' => $summary,
        ]);
    }

    /**
     * Category Performance Report.
     *
     * @param  Request  $request  The incoming HTTP request
     */
    public function categoryPerformance(Request $request): Response
    {
        $organizationId = $request->user()->organization_id;

        // Get all products grouped by category
        $products = Product::forOrganization($organizationId)
            ->with('category')
            ->where('is_active', true)
            ->get()
            ->groupBy('category_id');

        $categoryStats = $products->map(function ($items, $categoryId) {
            $category = $items->first()->category;

            return [
                'category_id' => $categoryId,
                'category_name' => $category?->name ?? 'Uncategorized',
                'product_count' => $items->count(),
                'total_stock' => $items->sum('stock'),
                'total_value' => $items->sum(function ($p) {
                    return $p->stock * $p->price;
                }),
                'low_stock_items' => $items->filter(function ($p) {
                    return $p->stock <= $p->min_stock;
                })->count(),
            ];
        })->values()->sortByDesc('total_value');

        return Inertia::render('Reports/CategoryPerformance', [
            'categories' => $categoryStats,
            'summary' => [
                'total_categories' => $categoryStats->count(),
                'total_products' => $categoryStats->sum('product_count'),
                'total_value' => $categoryStats->sum('total_value'),
            ],
        ]);
    }
}
