<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick, markRaw } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import draggable from 'vuedraggable'
import { useReport } from '@/composables/useReport'
import { useLayoutPersistence, type PersistedCardSize } from '@/composables/useLayoutPersistence'
import type { CardSize } from '@/composables/useCardResize'

// ─── Report Components ────────────────────────────────────────
import ReportCard from '@/Components/Reports/ReportCard.vue'
import DailySalesChart from '@/Components/Reports/DailySalesChart.vue'
import TopProductsChart from '@/Components/Reports/TopProductsChart.vue'
import InventoryOverviewChart from '@/Components/Reports/InventoryOverviewChart.vue'
import UserAnalyticsChart from '@/Components/Reports/UserAnalyticsChart.vue'
import PurchaseOrderChart from '@/Components/Reports/PurchaseOrderChart.vue'
import LowStockTable from '@/Components/Reports/LowStockTable.vue'
import LowStockChart from '@/Components/Reports/LowStockChart.vue'
import SupplierProcurementTable from '@/Components/Reports/SupplierProcurementTable.vue'

// ─── Icons ────────────────────────────────────────────────────
import {
    ShoppingCartIcon,
    CubeIcon,
    ArchiveBoxIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    BuildingStorefrontIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    UsersIcon,
    TruckIcon,
    TagIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

// ─── Props ────────────────────────────────────────────────────
const props = defineProps<{
    userRole: string
}>()

const isAdmin = computed(() => props.userRole === 'admin')

// ─── Summary Cards ────────────────────────────────────────────
const { data: summaryData, loading: summaryLoading, fetch: fetchSummary } =
    useReport('/api/v1/reports/summary-cards')

onMounted(() => fetchSummary())

interface SummaryCard {
    label: string
    key: string
    icon: any
    color: string
    href?: string
    format?: 'currency' | 'number'
    adminOnly?: boolean
}

const summaryCardDefs = computed<SummaryCard[]>(() => {
    const shared: SummaryCard[] = [
        { label: 'Total Sales Orders', key: 'total_sales_orders', icon: ShoppingCartIcon, color: 'bg-indigo-500', href: isAdmin.value ? '/admin/sales-orders' : '/staff/sales-orders', format: 'number' },
        { label: 'Sales Revenue', key: 'total_sales_value', icon: CurrencyDollarIcon, color: 'bg-emerald-500', href: isAdmin.value ? '/admin/sales-orders' : '/staff/sales-orders', format: 'currency' },
        { label: 'Completed Sales', key: 'completed_sales', icon: CheckCircleIcon, color: 'bg-green-500', format: 'number' },
    ]

    const adminCards: SummaryCard[] = [
        { label: 'Total Products', key: 'total_products', icon: CubeIcon, color: 'bg-blue-500', href: '/admin/products', format: 'number', adminOnly: true },
        { label: 'Low Stock Items', key: 'low_stock_count', icon: ExclamationTriangleIcon, color: 'bg-red-500', href: '/admin/products', format: 'number', adminOnly: true },
        { label: 'Total Users', key: 'total_users', icon: UserGroupIcon, color: 'bg-violet-500', href: '/admin/users', format: 'number', adminOnly: true },
        { label: 'Purchase Orders', key: 'total_purchase_orders', icon: ClipboardDocumentListIcon, color: 'bg-amber-500', href: '/admin/purchase-orders', format: 'number', adminOnly: true },
        { label: 'Procurement Value', key: 'total_procurement_value', icon: TruckIcon, color: 'bg-cyan-500', href: '/admin/purchase-orders', format: 'currency', adminOnly: true },
        { label: 'Suppliers', key: 'total_suppliers', icon: BuildingStorefrontIcon, color: 'bg-pink-500', href: '/admin/suppliers', format: 'number', adminOnly: true },
        { label: 'Customers', key: 'total_customers', icon: UsersIcon, color: 'bg-teal-500', href: '/admin/customers', format: 'number', adminOnly: true },
    ]

    return isAdmin.value ? [...shared, ...adminCards] : shared
})

const formatValue = (val: any, format?: 'currency' | 'number') => {
    if (val == null) return '—'
    if (format === 'currency') return `₱${Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
    return Number(val).toLocaleString()
}

// ─── Report Card Definitions ──────────────────────────────────

interface ReportDef {
    id: string
    title: string
    subtitle: string
    icon: any
    iconColor: string
    navigateTo?: string
    component: any
    adminOnly?: boolean
}

const allReports: ReportDef[] = [
    {
        id: 'daily-sales',
        title: 'Daily Sales Summary',
        subtitle: 'Revenue & orders per day',
        icon: ChartBarIcon,
        iconColor: 'bg-indigo-500',
        navigateTo: isAdmin.value ? '/admin/sales-orders' : '/staff/sales-orders',
        component: markRaw(DailySalesChart),
    },
    {
        id: 'top-products',
        title: 'Top 10 Best-Selling Products',
        subtitle: 'By quantity sold',
        icon: TagIcon,
        iconColor: 'bg-emerald-500',
        navigateTo: isAdmin.value ? '/admin/products' : '/staff/products',
        component: markRaw(TopProductsChart),
    },
    {
        id: 'low-stock-chart',
        title: 'Low Stock Items',
        subtitle: 'Products below stock threshold',
        icon: ExclamationTriangleIcon,
        iconColor: 'bg-red-500',
        navigateTo: isAdmin.value ? '/admin/products' : '/staff/products',
        component: markRaw(LowStockChart),
    },
    {
        id: 'inventory-overview',
        title: 'Inventory Overview',
        subtitle: 'Product growth by category',
        icon: ArchiveBoxIcon,
        iconColor: 'bg-blue-500',
        navigateTo: '/admin/products',
        component: markRaw(InventoryOverviewChart),
        adminOnly: true,
    },
    {
        id: 'user-analytics',
        title: 'User Analytics',
        subtitle: 'Role distribution & growth',
        icon: UserGroupIcon,
        iconColor: 'bg-violet-500',
        navigateTo: '/admin/users',
        component: markRaw(UserAnalyticsChart),
        adminOnly: true,
    },
    {
        id: 'purchase-order-analytics',
        title: 'Purchase Order Analytics',
        subtitle: 'Procurement trends by status',
        icon: ClipboardDocumentListIcon,
        iconColor: 'bg-amber-500',
        navigateTo: '/admin/purchase-orders',
        component: markRaw(PurchaseOrderChart),
        adminOnly: true,
    },
    {
        id: 'low-stock-table',
        title: 'Low Stock Details',
        subtitle: 'Detailed table view',
        icon: ExclamationTriangleIcon,
        iconColor: 'bg-red-500',
        navigateTo: '/admin/products',
        component: markRaw(LowStockTable),
        adminOnly: true,
    },
    {
        id: 'supplier-procurement',
        title: 'Supplier Procurement Summary',
        subtitle: 'PO aggregates per supplier',
        icon: BuildingStorefrontIcon,
        iconColor: 'bg-pink-500',
        navigateTo: '/admin/suppliers',
        component: markRaw(SupplierProcurementTable),
        adminOnly: true,
    },
]

const visibleReports = computed(() =>
    allReports.filter(r => !r.adminOnly || isAdmin.value)
)

// ─── Draggable Order + Resize Persistence ────────────────────

const { savedOrder, saveOrder, savedSizes, saveCardSize } = useLayoutPersistence(props.userRole)

const orderedReports = ref<ReportDef[]>([])

const applyOrder = () => {
    const visible = visibleReports.value
    if (savedOrder.value.length > 0) {
        const lookup = Object.fromEntries(visible.map(r => [r.id, r]))
        const ordered: ReportDef[] = []
        for (const id of savedOrder.value) {
            if (lookup[id]) {
                ordered.push(lookup[id])
                delete lookup[id]
            }
        }
        // Append any new cards not in saved order
        ordered.push(...Object.values(lookup))
        orderedReports.value = ordered
    } else {
        orderedReports.value = [...visible]
    }
}

onMounted(applyOrder)

const onDragEnd = () => {
    saveOrder(orderedReports.value.map(r => r.id))
}

/**
 * Get persisted size for a card, or return default.
 */
const getInitialSize = (id: string): CardSize => {
    const saved = savedSizes.value[id]
    if (saved) return { ...saved }
    return { width: null, height: 340, colSpan: 1 }
}

/**
 * Persist card size when user finishes resizing.
 */
const onCardResizeEnd = (id: string, size: CardSize) => {
    saveCardSize(id, { width: size.width, height: size.height, colSpan: size.colSpan })
    // Force chart redraw after resize settles
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

// ─── Expand/Collapse ──────────────────────────────────────────

const expandedCardId = ref<string | null>(null)

const toggleExpand = (id: string) => {
    expandedCardId.value = expandedCardId.value === id ? null : id
    // Trigger chart resize after DOM update
    nextTick(() => {
        window.dispatchEvent(new Event('resize'))
    })
}
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- ═══ Page Header Zone ═══ -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ isAdmin ? 'Analytics Dashboard' : 'Sales Analytics' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ isAdmin ? 'Enterprise-grade insights — drag to reorder, resize to customize' : 'Your sales performance at a glance' }}
                    </p>
                </div>
            </div>

            <!-- ═══ KPI Summary Zone ═══ -->
            <section aria-label="Key metrics">
                <div class="grid gap-4"
                     :class="isAdmin ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5' : 'grid-cols-1 sm:grid-cols-3'">
                    <div v-for="card in summaryCardDefs" :key="card.key"
                         class="relative overflow-hidden bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-200/60 dark:border-gray-700/50 shadow-sm p-4 flex items-center gap-4 transition-all duration-200 hover:shadow-md dark:hover:shadow-lg dark:hover:shadow-black/20 group"
                         :class="card.href ? 'cursor-pointer hover:border-orange-200 dark:hover:border-orange-800/50' : ''"
                         @click="card.href ? router.visit(card.href) : null">
                        <!-- Decorative gradient accent -->
                        <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                             :class="{
                                 'from-indigo-500 to-blue-500': card.color.includes('indigo'),
                                 'from-emerald-500 to-green-500': card.color.includes('emerald') || card.color.includes('green'),
                                 'from-blue-500 to-cyan-500': card.color.includes('blue'),
                                 'from-red-500 to-rose-500': card.color.includes('red'),
                                 'from-violet-500 to-purple-500': card.color.includes('violet'),
                                 'from-amber-500 to-orange-500': card.color.includes('amber'),
                                 'from-cyan-500 to-teal-500': card.color.includes('cyan'),
                                 'from-pink-500 to-rose-500': card.color.includes('pink'),
                                 'from-teal-500 to-emerald-500': card.color.includes('teal'),
                                 'from-orange-500 to-amber-500': !card.color.includes('indigo') && !card.color.includes('emerald') && !card.color.includes('blue') && !card.color.includes('red') && !card.color.includes('violet') && !card.color.includes('amber') && !card.color.includes('cyan') && !card.color.includes('pink') && !card.color.includes('teal') && !card.color.includes('green'),
                             }"
                        ></div>
                        <div class="shrink-0 flex items-center justify-center w-11 h-11 rounded-xl shadow-sm" :class="card.color">
                            <component :is="card.icon" class="h-6 w-6 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ card.label }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                <template v-if="summaryLoading">
                                    <span class="inline-block w-12 h-5 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></span>
                                </template>
                                <template v-else>
                                    {{ formatValue((summaryData as any)?.[card.key], card.format) }}
                                </template>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ Chart Analytics Zone (Draggable Grid) ═══ -->
            <section aria-label="Analytics charts">
                <draggable
                    v-model="orderedReports"
                    item-key="id"
                    handle=".drag-handle"
                    :animation="250"
                    ghost-class="opacity-30"
                    drag-class="shadow-2xl"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start"
                    style="max-width: 100%; overflow: hidden;"
                    @end="onDragEnd"
                >
                    <template #item="{ element }">
                        <ReportCard
                            :id="element.id"
                            :title="element.title"
                            :subtitle="element.subtitle"
                            :icon="element.icon"
                            :icon-color="element.iconColor"
                            :navigate-to="element.navigateTo"
                            :expanded="expandedCardId === element.id"
                            :initial-size="getInitialSize(element.id)"
                            @toggle-expand="toggleExpand(element.id)"
                            @resize-end="(s: CardSize) => onCardResizeEnd(element.id, s)"
                        >
                            <template #default="{ expanded }">
                                <component :is="element.component" :expanded="expanded" />
                            </template>
                        </ReportCard>
                    </template>
                </draggable>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
