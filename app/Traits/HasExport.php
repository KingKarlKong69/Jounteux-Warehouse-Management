<?php

namespace App\Traits;

use App\Services\ExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait for controllers that support CSV, Excel, and PDF exports.
 *
 * Controllers using this trait must implement:
 *   - getExportQuery(Request): Builder    — returns the filtered query
 *   - getExportColumns(): array           — returns column definitions
 *   - getExportFilename(): string         — returns base filename
 *   - getReportView(): string             — returns the Blade view name
 *   - getReportTitle(): string            — returns the report title
 *
 * Column definition format:
 *   ['header' => 'Display Name', 'key' => 'field_name', 'type' => 'String|Number', 'formatter' => callable]
 */
trait HasExport
{
    /**
     * Export as CSV file.
     */
    public function exportCsv(Request $request)
    {
        $service = app(ExportService::class);
        $data    = $this->getExportQuery($request)->get();
        $columns = $this->getExportColumns();
        $filename = $this->getExportFilename() . '-' . now()->format('Y-m-d-His');

        return $service->exportCsv($data, $columns, $filename);
    }

    /**
     * Export as Excel (XML Spreadsheet 2003) file.
     */
    public function exportExcel(Request $request)
    {
        $service = app(ExportService::class);
        $data    = $this->getExportQuery($request)->get();
        $columns = $this->getExportColumns();
        $filename = $this->getExportFilename() . '-' . now()->format('Y-m-d-His');

        return $service->exportExcel($data, $columns, $filename, $this->getReportTitle());
    }

    /**
     * Generate a printable PDF report (HTML Blade view).
     */
    public function report(Request $request)
    {
        $service = app(ExportService::class);
        $data    = $this->getExportQuery($request)->get();

        $dateRange = $service->buildDateRange(
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('date_preset')
        );

        $appliedFilters = $this->buildReportFilters($request);

        return $service->generateReport($this->getReportView(), [
            'entries'        => $data,
            'title'          => $this->getReportTitle(),
            'dateRange'      => $dateRange,
            'appliedFilters' => $appliedFilters,
        ]);
    }

    /**
     * Build applied filters for report display.
     * Override in controller for custom filter labels.
     */
    protected function buildReportFilters(Request $request): array
    {
        $service = app(ExportService::class);

        return $service->buildAppliedFilters([
            'Search' => $request->input('search'),
        ]);
    }
}
