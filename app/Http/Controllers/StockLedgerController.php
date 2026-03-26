<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockLedgerResource;
use App\Models\Products;
use App\Models\Stock_Ledger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockLedgerController extends Controller
{
    /**
     * Display the stock ledger with filtering, summary, and chart data.
     * Strictly read-only – no create, update, or delete actions.
     */
    public function index(Request $request)
    {
        // ── Base query with eager loading ──
        $query = Stock_Ledger::query()
            ->with(['product:id,sku,name,current_stock', 'creator:id,name']);

        // ── Apply filters ──
        $this->applyFilters($query, $request);

        // ── Clone query for summaries before pagination ──
        $summaryQuery = clone $query;
        $chartQuery   = clone $query;

        // ── Sorting ──
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['created_at', 'quantity', 'balance_after', 'movement_type', 'reference_type'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        // ── Pagination ──
        $perPage = min($request->input('per_page', 20), 100);
        $entries = $query->paginate($perPage)->withQueryString();

        // ── Summary calculations ──
        $summary = $this->calculateSummary($summaryQuery, $request);

        // ── Chart data (daily aggregation) ──
        $chartData = $this->calculateChartData($chartQuery);

        // ── Reference data for filter dropdowns ──
        $products = Products::select('id', 'sku', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'label' => $p->sku . ' — ' . $p->name,
            ]);

        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/StockLedger/Index', [
            'entries'   => StockLedgerResource::collection($entries),
            'meta'      => [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
                'from'         => $entries->firstItem(),
                'to'           => $entries->lastItem(),
            ],
            'summary'   => $summary,
            'chartData' => $chartData,
            'filters'   => [
                'search'         => $request->input('search', ''),
                'product_id'     => $request->input('product_id', ''),
                'reference_type' => $request->input('reference_type', ''),
                'movement_type'  => $request->input('movement_type', ''),
                'user_id'        => $request->input('user_id', ''),
                'date_from'      => $request->input('date_from', ''),
                'date_to'        => $request->input('date_to', ''),
                'date_preset'    => $request->input('date_preset', ''),
                'sort'           => $sortField,
                'direction'      => $sortDirection,
                'per_page'       => $perPage,
            ],
            'products'  => $products,
            'users'     => $users,
        ]);
    }

    /**
     * Export stock ledger as CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = Stock_Ledger::query()
            ->with(['product:id,sku,name', 'creator:id,name']);

        $this->applyFilters($query, $request);
        $query->orderBy('created_at', 'desc');

        $entries = $query->get();

        $filename = 'stock-ledger-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, [
                'Date', 'Product SKU', 'Product Name', 'Type', 'Reference',
                'Movement', 'Quantity', 'Balance After', 'Processed By',
            ]);
            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->created_at?->format('Y-m-d H:i:s'),
                    $entry->product?->sku ?? '—',
                    $entry->product?->name ?? 'Unknown',
                    ucfirst($entry->reference_type),
                    $this->formatReference($entry),
                    strtoupper($entry->movement_type),
                    $entry->quantity,
                    $entry->balance_after,
                    $entry->creator?->name ?? 'System',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export stock ledger as Excel (XLSX via simple XML spreadsheet).
     */
    public function exportExcel(Request $request)
    {
        $query = Stock_Ledger::query()
            ->with(['product:id,sku,name', 'creator:id,name']);

        $this->applyFilters($query, $request);
        $query->orderBy('created_at', 'desc');

        $entries = $query->get();

        $filename = 'stock-ledger-' . now()->format('Y-m-d-His') . '.xlsx';

        // Build XML Spreadsheet 2003 format (opens in Excel natively)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/></Style>';
        $xml .= '<Style ss:ID="dateStyle"><NumberFormat ss:Format="yyyy-mm-dd hh:mm:ss"/></Style>';
        $xml .= '</Styles>';
        $xml .= '<Worksheet ss:Name="Stock Ledger"><Table>' . "\n";

        // Header row
        $headers = ['Date', 'Product SKU', 'Product Name', 'Type', 'Reference', 'Movement', 'Quantity', 'Balance After', 'Processed By'];
        $xml .= '<Row>';
        foreach ($headers as $h) {
            $xml .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>';
        }
        $xml .= '</Row>' . "\n";

        // Data rows
        foreach ($entries as $entry) {
            $xml .= '<Row>';
            $xml .= '<Cell ss:StyleID="dateStyle"><Data ss:Type="String">' . ($entry->created_at?->format('Y-m-d H:i:s') ?? '') . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($entry->product?->sku ?? '—') . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($entry->product?->name ?? 'Unknown') . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . ucfirst($entry->reference_type) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($this->formatReference($entry)) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . strtoupper($entry->movement_type) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="Number">' . $entry->quantity . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="Number">' . $entry->balance_after . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($entry->creator?->name ?? 'System') . '</Data></Cell>';
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate printable PDF report (HTML print view).
     */
    public function report(Request $request)
    {
        $query = Stock_Ledger::query()
            ->with(['product:id,sku,name', 'creator:id,name']);

        $this->applyFilters($query, $request);
        $query->orderBy('created_at', 'desc');

        $entries = $query->get();

        // Summary
        $summaryQuery = Stock_Ledger::query()
            ->with(['product:id,sku,name,current_stock']);
        $this->applyFilters($summaryQuery, $request);

        $summary = $this->calculateSummary($summaryQuery, $request);

        $dateRange = '';
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateRange = $request->input('date_from') . ' to ' . $request->input('date_to');
        } elseif ($request->filled('date_from')) {
            $dateRange = 'From ' . $request->input('date_from');
        } elseif ($request->filled('date_to')) {
            $dateRange = 'Up to ' . $request->input('date_to');
        } elseif ($request->filled('date_preset')) {
            $dateRange = ucfirst($request->input('date_preset'));
        } else {
            $dateRange = 'All Time';
        }

        $appliedFilters = [];
        if ($request->filled('product_id')) {
            $product = Products::find($request->input('product_id'));
            $appliedFilters[] = 'Product: ' . ($product ? $product->sku . ' — ' . $product->name : '#' . $request->input('product_id'));
        }
        if ($request->filled('reference_type')) {
            $appliedFilters[] = 'Type: ' . ucfirst($request->input('reference_type'));
        }
        if ($request->filled('movement_type')) {
            $appliedFilters[] = 'Movement: ' . strtoupper($request->input('movement_type'));
        }
        if ($request->filled('search')) {
            $appliedFilters[] = 'Search: "' . $request->input('search') . '"';
        }

        return view('reports.stock-ledger', [
            'entries'        => $entries,
            'summary'        => $summary,
            'dateRange'      => $dateRange,
            'appliedFilters' => $appliedFilters,
            'generatedBy'    => $request->user()?->name ?? 'Unknown',
            'generatedAt'    => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    /**
     * Apply all filters to a query.
     */
    private function applyFilters($query, Request $request): void
    {
        // Search (SKU, product name, reference number)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('sku', 'like', "%{$search}%")
                       ->orWhere('name', 'like', "%{$search}%");
                });

                // Also match reference patterns like PO-0001, SO-0005
                if (preg_match('/^(PO|SO|ADJ)-?(\d+)$/i', $search, $matches)) {
                    $refType = match (strtoupper($matches[1])) {
                        'PO'  => 'purchase',
                        'SO'  => 'sale',
                        'ADJ' => 'adjustment',
                        default => null,
                    };
                    $refId = (int) $matches[2];
                    if ($refType) {
                        $q->orWhere(function ($rq) use ($refType, $refId) {
                            $rq->where('reference_type', $refType)
                               ->where('reference_id', $refId);
                        });
                    }
                }
            });
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Reference type filter
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->input('reference_type'));
        }

        // Movement type filter
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('created_by', $request->input('user_id'));
        }

        // Date preset
        if ($preset = $request->input('date_preset')) {
            $now = now();
            switch ($preset) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [
                        $now->startOfWeek()->startOfDay(),
                        $now->copy()->endOfWeek()->endOfDay(),
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', $now->month)
                          ->whereYear('created_at', $now->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', $now->year);
                    break;
            }
        }

        // Custom date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
    }

    /**
     * Calculate summary values from a query.
     */
    private function calculateSummary($query, Request $request): array
    {
        $totalIn = (clone $query)->where('movement_type', 'in')->sum('quantity');
        $totalOut = (clone $query)->where('movement_type', 'out')->sum('quantity');
        $netMovement = $totalIn - $totalOut;

        $currentStock = null;
        if ($request->filled('product_id')) {
            $product = Products::find($request->input('product_id'));
            $currentStock = $product?->current_stock;
        }

        return [
            'total_in'      => (int) $totalIn,
            'total_out'     => (int) $totalOut,
            'net_movement'  => (int) $netMovement,
            'current_stock' => $currentStock,
        ];
    }

    /**
     * Calculate daily aggregated chart data.
     */
    private function calculateChartData($query): array
    {
        $dailyData = (clone $query)
            ->select(
                DB::raw("DATE(created_at) as date"),
                'movement_type',
                DB::raw("SUM(quantity) as total_qty")
            )
            ->groupBy(DB::raw("DATE(created_at)"), 'movement_type')
            ->orderBy(DB::raw("DATE(created_at)"))
            ->get();

        $dates = $dailyData->pluck('date')->unique()->sort()->values();
        $inData = [];
        $outData = [];

        foreach ($dates as $date) {
            $inData[] = (int) $dailyData->where('date', $date)->where('movement_type', 'in')->sum('total_qty');
            $outData[] = (int) $dailyData->where('date', $date)->where('movement_type', 'out')->sum('total_qty');
        }

        return [
            'labels'  => $dates->toArray(),
            'datasets' => [
                [
                    'label'           => 'Total IN',
                    'data'            => $inData,
                    'borderColor'     => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Total OUT',
                    'data'            => $outData,
                    'borderColor'     => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
            ],
        ];
    }

    /**
     * Format reference for exports.
     */
    private function formatReference($entry): string
    {
        if (!$entry->reference_id) {
            return '—';
        }
        return match ($entry->reference_type) {
            'purchase'   => 'PO-' . str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT),
            'sale'       => 'SO-' . str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT),
            'adjustment' => 'ADJ-' . str_pad($entry->reference_id, 4, '0', STR_PAD_LEFT),
            default      => '#' . $entry->reference_id,
        };
    }
}
