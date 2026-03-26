<script setup lang="ts">
/**
 * KpiStrip — KPI zone container with isolated drag context.
 *
 * Rules:
 * - Fixed size cards, no resizing
 * - Draggable only within KPI zone
 * - No cross-zone transfer
 * - Order persists via layout store
 * - Typography-driven emphasis, highly scannable
 */
import { computed } from 'vue'
import draggable from 'vuedraggable'
import { useDashboardLayoutStore } from '@/stores/dashboardLayout'
import KpiCard from './KpiCard.vue'
import {
    ShoppingCartIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    CubeIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    TruckIcon,
    BuildingStorefrontIcon,
    UsersIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps<{
    isAdmin: boolean
    summaryData: any
    summaryLoading: boolean
}>()

const store = useDashboardLayoutStore()

// ─── KPI Configuration Map ──────────────────────────────────

const KPI_CONFIG: Record<string, {
    icon: any
    color: string
    label: string
    format: 'currency' | 'number'
    href?: string
    adminOnly?: boolean
}> = {
    total_sales_orders: {
        icon: ShoppingCartIcon,
        color: 'bg-indigo-500',
        label: 'Total Sales Orders',
        format: 'number',
        href: '/admin/sales-orders',
    },
    total_sales_value: {
        icon: CurrencyDollarIcon,
        color: 'bg-emerald-500',
        label: 'Sales Revenue',
        format: 'currency',
        href: '/admin/sales-orders',
    },
    completed_sales: {
        icon: CheckCircleIcon,
        color: 'bg-green-500',
        label: 'Completed Sales',
        format: 'number',
    },
    total_products: {
        icon: CubeIcon,
        color: 'bg-blue-500',
        label: 'Total Products',
        format: 'number',
        href: '/admin/products',
        adminOnly: true,
    },
    total_purchase_orders: {
        icon: ClipboardDocumentListIcon,
        color: 'bg-amber-500',
        label: 'Purchase Orders',
        format: 'number',
        href: '/admin/purchase-orders',
        adminOnly: true,
    },
    low_stock_count: {
        icon: ExclamationTriangleIcon,
        color: 'bg-red-500',
        label: 'Low Stock Items',
        format: 'number',
        href: '/admin/products',
        adminOnly: true,
    },
    total_procurement_value: {
        icon: TruckIcon,
        color: 'bg-cyan-500',
        label: 'Procurement Value',
        format: 'currency',
        href: '/admin/purchase-orders',
        adminOnly: true,
    },
    total_suppliers: {
        icon: BuildingStorefrontIcon,
        color: 'bg-pink-500',
        label: 'Suppliers',
        format: 'number',
        href: '/admin/suppliers',
        adminOnly: true,
    },
    total_customers: {
        icon: UsersIcon,
        color: 'bg-teal-500',
        label: 'Customers',
        format: 'number',
        href: '/admin/customers',
        adminOnly: true,
    },
    total_users: {
        icon: UserGroupIcon,
        color: 'bg-violet-500',
        label: 'Active Users',
        format: 'number',
        href: '/admin/users',
        adminOnly: true,
    },
}

// ─── Ordered KPIs (writable computed for vuedraggable) ──────

const orderedKpis = computed({
    get: () => store.kpiOrder.filter(id => {
        const cfg = KPI_CONFIG[id]
        if (!cfg) return false
        if (cfg.adminOnly && !props.isAdmin) return false
        return true
    }),
    set: (val: string[]) => store.updateKpiOrder(val),
})

const getKpiValue = (key: string) => (props.summaryData as any)?.[key]

// Staff href adjustment
const getHref = (id: string) => {
    const cfg = KPI_CONFIG[id]
    if (!cfg?.href) return undefined
    if (!props.isAdmin) {
        return cfg.href.replace('/admin/', '/staff/')
    }
    return cfg.href
}
</script>

<template>
    <section aria-label="Key performance indicators">
        <draggable
            v-model="orderedKpis"
            item-key="id"
            :animation="250"
            ghost-class="opacity-30"
            drag-class="shadow-xl"
            :group="{ name: 'kpi', pull: false, put: false }"
            class="grid gap-4"
            :class="isAdmin ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5' : 'grid-cols-1 sm:grid-cols-3'"
        >
            <template #item="{ element }">
                <KpiCard
                    :key="element"
                    :label="KPI_CONFIG[element]?.label ?? element"
                    :value="getKpiValue(element)"
                    :loading="summaryLoading"
                    :icon="KPI_CONFIG[element]?.icon"
                    :color="KPI_CONFIG[element]?.color ?? 'bg-gray-500'"
                    :format="KPI_CONFIG[element]?.format"
                    :href="getHref(element)"
                />
            </template>
        </draggable>
    </section>
</template>
