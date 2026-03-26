<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Resources\AuditLogResource;
use App\Models\Audit_Logs;
use App\Traits\HasIndexFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    use HasIndexFilters;
    /**
     * Display audit logs with filtering, search, and pagination.
     * Strictly read-only – no create, update, or delete actions.
     */
    public function index(Request $request)
    {
        $query = Audit_Logs::query()->with('user:id,name,role');

        // ── Search ──
        $this->applySearch($query, $request->input('search'), [
            'user_name', 'user_role', 'action', 'event_label', 'ip_address', 'auditable_type',
        ]);

        // ── Action tab filter ──
        if ($action = $request->input('action')) {
            $tabGroups = AuditAction::tabGroups();
            if (isset($tabGroups[$action])) {
                $query->forAction($tabGroups[$action]);
            } elseif (in_array($action, AuditAction::values())) {
                $query->forAction($action);
            }
        }

        // ── Resource type filter ──
        if ($resourceType = $request->input('resource_type')) {
            $modelMap = [
                'user'           => 'App\\Models\\User',
                'product'        => 'App\\Models\\Products',
                'sales_order'    => 'App\\Models\\Sales_Order',
                'purchase_order' => 'App\\Models\\Purchase_Order',
                'customer'       => 'App\\Models\\Customer',
                'supplier'       => 'App\\Models\\Supplier',
                'category'       => 'App\\Models\\Category',
            ];
            if (isset($modelMap[$resourceType])) {
                $query->where('auditable_type', $modelMap[$resourceType]);
            }
        }

        // ── Date preset filter (daily / weekly / monthly / yearly) ──
        if ($preset = $request->input('date_preset')) {
            $query->datePreset($preset);
        }

        // ── Custom date range ──
        $query->dateBetween(
            $request->input('date_from'),
            $request->input('date_to')
        );

        // ── Pagination (server-side) ──
        $perPage = min($request->input('per_page', 20), 100);
        $logs = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => AuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'action' => $request->input('action', 'all'),
                'resource_type' => $request->input('resource_type', ''),
                'date_preset' => $request->input('date_preset', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'per_page' => $perPage,
            ],
            'actionTabs' => collect(AuditAction::tabGroups())->keys(),
            'resourceTypes' => [
                ['value' => '', 'label' => 'All Resources'],
                ['value' => 'user', 'label' => 'Users'],
                ['value' => 'product', 'label' => 'Products'],
                ['value' => 'sales_order', 'label' => 'Sales Orders'],
                ['value' => 'purchase_order', 'label' => 'Purchase Orders'],
                ['value' => 'customer', 'label' => 'Customers'],
                ['value' => 'supplier', 'label' => 'Suppliers'],
                ['value' => 'category', 'label' => 'Categories'],
            ],
        ]);
    }
}
