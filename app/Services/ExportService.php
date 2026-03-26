<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reusable export service for generating CSV, Excel, and PDF reports.
 *
 * Follows SOLID principles — single responsibility for export logic.
 * Used by all module controllers via dependency injection or direct call.
 */
class ExportService
{
    /**
     * Generate a CSV download response.
     *
     * @param  Collection  $data      The dataset to export.
     * @param  array       $columns   Column definitions: ['header' => 'Header Label', 'key' => 'field_name'] or callable.
     * @param  string      $filename  The download filename (without extension).
     * @return StreamedResponse
     */
    public function exportCsv(Collection $data, array $columns, string $filename): StreamedResponse
    {
        $filename = $this->sanitizeFilename($filename) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, array_map(fn ($col) => $col['header'], $columns));

            // Data rows
            foreach ($data as $row) {
                $rowData = [];
                foreach ($columns as $col) {
                    if (isset($col['formatter']) && is_callable($col['formatter'])) {
                        $rowData[] = $col['formatter']($row);
                    } else {
                        $rowData[] = data_get($row, $col['key'], '—');
                    }
                }
                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate an Excel (XML Spreadsheet 2003) download response.
     *
     * @param  Collection  $data       The dataset to export.
     * @param  array       $columns    Column definitions.
     * @param  string      $filename   The download filename (without extension).
     * @param  string      $sheetName  The worksheet name.
     * @return Response
     */
    public function exportExcel(Collection $data, array $columns, string $filename, string $sheetName = 'Report'): Response
    {
        $filename = $this->sanitizeFilename($filename) . '.xlsx';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // Styles
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#F97316" ss:Pattern="Solid"/><Font ss:Color="#FFFFFF" ss:Bold="1"/></Style>';
        $xml .= '<Style ss:ID="dateStyle"><NumberFormat ss:Format="yyyy-mm-dd hh:mm:ss"/></Style>';
        $xml .= '<Style ss:ID="currency"><NumberFormat ss:Format="#,##0.00"/></Style>';
        $xml .= '</Styles>';

        $xml .= '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '"><Table>' . "\n";

        // Header row
        $xml .= '<Row>';
        foreach ($columns as $col) {
            $xml .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($col['header']) . '</Data></Cell>';
        }
        $xml .= '</Row>' . "\n";

        // Data rows
        foreach ($data as $row) {
            $xml .= '<Row>';
            foreach ($columns as $col) {
                $value = isset($col['formatter']) && is_callable($col['formatter'])
                    ? $col['formatter']($row)
                    : data_get($row, $col['key'], '');

                $type  = $col['type'] ?? 'String';
                $style = isset($col['style']) ? ' ss:StyleID="' . $col['style'] . '"' : '';

                $xml .= '<Cell' . $style . '><Data ss:Type="' . $type . '">' . htmlspecialchars((string) $value) . '</Data></Cell>';
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate a printable HTML report (Blade view).
     *
     * @param  string  $view     The Blade view name (e.g., 'reports.products').
     * @param  array   $data     Data to pass to the view.
     * @return \Illuminate\Contracts\View\View
     */
    public function generateReport(string $view, array $data): \Illuminate\Contracts\View\View
    {
        $data['generatedBy'] = request()->user()?->name ?? 'Unknown';
        $data['generatedAt'] = now()->timezone('Asia/Manila')->format('M d, Y h:i A');

        return view($view, $data);
    }

    /**
     * Build applied filters description for report display.
     *
     * @param  array  $filterMap  ['Filter Label' => $value, ...]
     * @return array
     */
    public function buildAppliedFilters(array $filterMap): array
    {
        $filters = [];

        foreach ($filterMap as $label => $value) {
            if (!empty($value)) {
                $filters[] = $label . ': ' . $value;
            }
        }

        return $filters;
    }

    /**
     * Build a date range description from request params.
     */
    public function buildDateRange(?string $dateFrom, ?string $dateTo, ?string $preset = null): string
    {
        if ($dateFrom && $dateTo) {
            return $dateFrom . ' to ' . $dateTo;
        } elseif ($dateFrom) {
            return 'From ' . $dateFrom;
        } elseif ($dateTo) {
            return 'Up to ' . $dateTo;
        } elseif ($preset) {
            return ucfirst(str_replace('_', ' ', $preset));
        }

        return 'All Time';
    }

    /**
     * Sanitize a filename for safe download.
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '-', $filename);
    }
}
