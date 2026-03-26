/**
 * ─────────────────────────────────────────────────────────────
 * Navigation Configuration — Scalable Role-Based Architecture
 * ─────────────────────────────────────────────────────────────
 *
 * This module implements a configuration-driven navigation system
 * following the Open/Closed Principle:
 *
 *   - Adding a new FEATURE: add one entry to ALL_NAV_ITEMS.
 *   - Adding a new ROLE:    add one entry to ROLE_FEATURE_MAP.
 *   - No modification to filtering / rendering logic required.
 *
 * Architecture:
 *   1. ALL_NAV_ITEMS   — Single source of truth for every feature.
 *   2. ROLE_FEATURE_MAP — Declarative role → feature access control.
 *   3. buildNavigation() — Pure function that filters & maps items.
 */

import {
    HomeIcon,
    UserGroupIcon,
    CubeIcon,
    BuildingStorefrontIcon,
    UserIcon,
    ShoppingCartIcon,
    ClipboardDocumentListIcon,
    ClipboardDocumentCheckIcon,
    ChartBarIcon,
    TagIcon,
    BookOpenIcon,
} from '@heroicons/vue/24/outline'
import type { Component } from 'vue'

// ─── Types ───────────────────────────────────────────────────

export interface NavItemConfig {
    /** Display name shown in sidebar */
    name: string
    /** Unique feature key used for access control */
    feature: string
    /** Role → Ziggy route name mapping */
    routes: Record<string, string>
    /** Heroicon component */
    icon: Component
}

export interface ResolvedNavItem {
    name: string
    href: string
    icon: Component
    children?: ResolvedNavItem[]
}

// ─── Master Navigation Registry ──────────────────────────────
// Single source of truth. Every navigable feature is listed once.
// The `routes` map allows each role to resolve to its own endpoint.

export const ALL_NAV_ITEMS: NavItemConfig[] = [
    {
        name: 'Dashboard',
        feature: 'dashboard',
        routes: {
            admin: 'admin.dashboard',
            staff: 'staff.dashboard',
        },
        icon: HomeIcon,
    },
    {
        name: 'Users',
        feature: 'users',
        routes: {
            admin: 'admin.users.index',
        },
        icon: UserGroupIcon,
    },
    {
        name: 'Products',
        feature: 'products',
        routes: {
            admin: 'admin.products.index',
            staff: 'staff.products.index',
        },
        icon: CubeIcon,
    },
    {
        name: 'Categories',
        feature: 'categories',
        routes: {
            admin: 'admin.categories.index',
        },
        icon: TagIcon,
    },
    {
        name: 'Suppliers',
        feature: 'suppliers',
        routes: {
            admin: 'admin.suppliers.index',
            staff: 'staff.suppliers.index',
        },
        icon: BuildingStorefrontIcon,
    },
    {
        name: 'Customers',
        feature: 'customers',
        routes: {
            admin: 'admin.customers.index',
            staff: 'staff.customers.index',
        },
        icon: UserIcon,
    },
    {
        name: 'Purchase Orders',
        feature: 'purchase_orders',
        routes: {
            admin: 'admin.purchase-orders.index',
        },
        icon: ShoppingCartIcon,
    },
    {
        name: 'Sales Orders',
        feature: 'sales_orders',
        routes: {
            admin: 'admin.sales-orders.index',
            staff: 'staff.sales-orders.index',
        },
        icon: ClipboardDocumentListIcon,
    },
    // {
    //     name: 'Reports',
    //     feature: 'reports',
    //     routes: {
    //         admin: 'reports.index',
    //         staff: 'reports.index',
    //     },
    //     icon: ChartBarIcon,
    // },
    {
        name: 'Audit Logs',
        feature: 'audit_logs',
        routes: {
            admin: 'admin.audit-logs',
        },
        icon: ClipboardDocumentCheckIcon,
    },
    {
        name: 'Stock Ledger',
        feature: 'stock_ledger',
        routes: {
            admin: 'admin.stock-ledger.index',
        },
        icon: BookOpenIcon,
    },
]

// ─── Role → Feature Access Control Map ───────────────────────
// Declarative. To grant a role access to a feature, add its key.
// To create an entirely new role, add one entry here — done.

export const ROLE_FEATURE_MAP: Record<string, string[]> = {
    admin: [
        'dashboard',
        'users',
        'products',
        'categories',
        'suppliers',
        'customers',
        'purchase_orders',
        'sales_orders',
        // 'reports',
        'audit_logs',
        'stock_ledger',
    ],
    staff: [
        'dashboard',
        'products',
        'suppliers',
        'customers',
        'sales_orders',
        // 'reports',
    ],
}

// ─── Dashboard Route Map ─────────────────────────────────────
// Quick lookup for the default landing page per role.

export const DASHBOARD_ROUTES: Record<string, string> = {
    admin: 'admin.dashboard',
    staff: 'staff.dashboard',
}

// ─── Navigation Builder ──────────────────────────────────────
// Pure function — no side effects, easily testable.

/**
 * Build the filtered navigation array for a given role.
 *
 * @param role          The user's role string (e.g. "admin", "staff")
 * @param routeResolver Ziggy `route()` function (injected for decoupling)
 * @returns             Array of resolved nav items the role can access
 */
export function buildNavigation(
    role: string,
    routeResolver: (name: string) => string,
): ResolvedNavItem[] {
    const allowedFeatures = ROLE_FEATURE_MAP[role] ?? []

    return ALL_NAV_ITEMS
        .filter(item => {
            // Feature must be in the role's allowed list
            if (!allowedFeatures.includes(item.feature)) return false
            // Role must have a route defined for this feature
            if (!item.routes[role]) return false
            return true
        })
        .map(item => ({
            name: item.name,
            href: routeResolver(item.routes[role]),
            icon: item.icon,
        }))
}
