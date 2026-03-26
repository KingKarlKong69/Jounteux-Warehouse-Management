<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Report') — Jounteux Warehouse</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            background: #fff;
            font-size: 12px;
            line-height: 1.5;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }

        /* Header */
        .report-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #e5e7eb;
            margin-bottom: 24px;
        }
        .report-header img {
            height: 60px;
            margin-bottom: 8px;
        }
        .report-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .report-header .system-name {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 2px;
        }
        .report-header .subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        /* Meta */
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
            color: #6b7280;
        }
        .report-meta .meta-label { font-weight: 600; color: #374151; }
        .report-meta .meta-item { margin-bottom: 4px; }

        /* Applied Filters */
        .filters-section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .filters-section h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .filter-tag {
            display: inline-block;
            background: #e5e7eb;
            color: #374151;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 6px;
            margin-bottom: 4px;
        }

        /* Summary Cards */
        .summary-cards {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .summary-card {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            text-align: center;
        }
        .summary-card .label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .summary-card .value {
            font-size: 20px;
            font-weight: 700;
        }
        .summary-card.green  { border-left: 4px solid #10b981; }
        .summary-card.green  .value { color: #059669; }
        .summary-card.red    { border-left: 4px solid #ef4444; }
        .summary-card.red    .value { color: #dc2626; }
        .summary-card.blue   { border-left: 4px solid #3b82f6; }
        .summary-card.blue   .value { color: #2563eb; }
        .summary-card.purple { border-left: 4px solid #8b5cf6; }
        .summary-card.purple .value { color: #7c3aed; }
        .summary-card.amber  { border-left: 4px solid #f59e0b; }
        .summary-card.amber  .value { color: #d97706; }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        thead th {
            background: #f3f4f6;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 2px solid #d1d5db;
        }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-completed, .badge-in   { background: #d1fae5; color: #065f46; }
        .badge-pending                 { background: #fef3c7; color: #92400e; }
        .badge-processing              { background: #dbeafe; color: #1e40af; }
        .badge-draft                   { background: #f3f4f6; color: #6b7280; }
        .badge-rejected, .badge-out    { background: #fee2e2; color: #991b1b; }
        .badge-for_shipment            { background: #e0e7ff; color: #3730a3; }
        .badge-approved                { background: #dbeafe; color: #1e40af; }
        .badge-cancelled               { background: #f3f4f6; color: #6b7280; }
        .badge-warning                 { background: #fef3c7; color: #92400e; }
        .badge-danger                  { background: #fee2e2; color: #991b1b; }

        /* Footer */
        .report-footer {
            text-align: center;
            padding-top: 16px;
            border-top: 2px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
        }

        /* Print controls */
        .print-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .print-btn:hover { background: #4338ca; }
        .back-btn {
            position: fixed;
            top: 16px;
            right: 160px;
            background: #6b7280;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            z-index: 100;
            text-decoration: none;
        }
        .back-btn:hover { background: #4b5563; }

        @yield('extra-styles')

        @media print {
            body { font-size: 10px; }
            .container { padding: 12px; max-width: 100%; }
            .no-print { display: none !important; }
            .summary-cards { page-break-inside: avoid; }
            table { page-break-inside: auto; }
            table tr { page-break-inside: avoid; }
            thead th {
                background: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tbody tr:nth-child(even) {
                background: #f9fafb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
    <a href="javascript:history.back()" class="back-btn no-print">&larr; Back</a>

    <div class="container">
        <!-- Header -->
        <div class="report-header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
            <div class="system-name">Jounteux Warehouse Management System</div>
            <h1>@yield('title', 'Report')</h1>
            <div class="subtitle">@yield('subtitle', $dateRange ?? 'All Time')</div>
        </div>

        <!-- Meta -->
        <div class="report-meta">
            <div>
                <div class="meta-item"><span class="meta-label">Date Range:</span> {{ $dateRange ?? 'All Time' }}</div>
                @if(!empty($appliedFilters))
                    <div class="meta-item"><span class="meta-label">Filters Applied:</span> {{ count($appliedFilters) }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <div class="meta-item"><span class="meta-label">Generated By:</span> {{ $generatedBy ?? 'System' }}</div>
                <div class="meta-item"><span class="meta-label">Generated On:</span> {{ $generatedAt ?? now()->timezone('Asia/Manila')->format('M d, Y h:i A') }}</div>
                <div class="meta-item"><span class="meta-label">Total Entries:</span> {{ count($entries ?? []) }}</div>
            </div>
        </div>

        <!-- Applied Filters -->
        @if(!empty($appliedFilters))
            <div class="filters-section">
                <h3>Applied Filters</h3>
                @foreach($appliedFilters as $filter)
                    <span class="filter-tag">{{ $filter }}</span>
                @endforeach
            </div>
        @endif

        <!-- Summary (optional per module) -->
        @yield('summary')

        <!-- Content -->
        @yield('content')

        <!-- Footer -->
        <div class="report-footer">
            <p>This report was generated by Jounteux Warehouse Management System on {{ $generatedAt ?? now()->timezone('Asia/Manila')->format('M d, Y h:i A') }}.</p>
            <p>Data reflects the state at the time of generation and may differ from current values.</p>
        </div>
    </div>
</body>
</html>
