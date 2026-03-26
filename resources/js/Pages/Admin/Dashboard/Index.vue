<script setup lang="ts">
/**
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * Enterprise Slot-Governed Adaptive Analytics Dashboard
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * A slot-governed, span-negotiating, expansion-reactive,
 * constraint-enforced analytical layout engine.
 *
 * Layout (Default State):
 * ┌────────────────────────────────────────────────────────┐
 * │ ROW 1 — Primary Hero Zone (Sales Summary, col-12)     │
 * ├────────────────────────────────────────────────────────┤
 * │ ROW 2 — KPI Strip (5 cards, internal grid-cols-5)     │
 * ├──────────────────────────────────┬─────────────────────┤
 * │ ROW 3 — Left Pair (col-8)       │ Right Stack (col-4) │
 * │ ┌──────────┬─────────────┐      │ ┌─────┬─────┐       │
 * │ │ Top 10   │ PO Analytics│      │ │ Mini│ Mini│       │
 * │ └──────────┴─────────────┘      │ ├─────┴─────┤       │
 * │   ↕ span-negotiating ↕          │ │  Medium   │       │
 * │                                  │ ├───────────┤       │
 * │                                  │ │ LowStock  │       │
 * │                                  │ │  Table    │       │
 * │                                  │ └───────────┘       │
 * ├──────────────────────────────────┴─────────────────────┤
 * │ ROW 4 — Operational Layer                              │
 * │ ┌─────────────────┬────────────────────┐               │
 * │ │ Supplier Proc.  │ Inventory Overview │               │
 * │ └─────────────────┴────────────────────┘               │
 * │   ↕ span-negotiating ↕                                 │
 * └────────────────────────────────────────────────────────┘
 */
import { ref, computed, onMounted, onUnmounted, nextTick, watch, markRaw } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useDashboardLayoutStore } from '@/stores/dashboardLayout'
import { useReport } from '@/composables/useReport'

// ─── Dashboard Components ───────────────────────────────────
import SalesSummaryCard from '@/Components/Dashboard/SalesSummaryCard.vue'
import KpiStrip from '@/Components/Dashboard/KpiStrip.vue'
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue'
import SpanHandle from '@/Components/Dashboard/SpanHandle.vue'
import MetricStack from '@/Components/Dashboard/MetricStack.vue'

// ─── Reused Chart Components ────────────────────────────────
import TopProductsChart from '@/Components/Reports/TopProductsChart.vue'
import PurchaseOrderChart from '@/Components/Reports/PurchaseOrderChart.vue'
import SupplierProcurementTable from '@/Components/Reports/SupplierProcurementTable.vue'
import InventoryOverviewChart from '@/Components/Reports/InventoryOverviewChart.vue'
import LowStockChart from '@/Components/Reports/LowStockChart.vue'
import UserAnalyticsChart from '@/Components/Reports/UserAnalyticsChart.vue'

// ─── Icons ──────────────────────────────────────────────────
import {
    TagIcon,
    ClipboardDocumentListIcon,
    BuildingStorefrontIcon,
    ArchiveBoxIcon,
    ExclamationTriangleIcon,
    UserGroupIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

// ─── Props ──────────────────────────────────────────────────

const props = defineProps<{
    userRole: string
}>()

const isAdmin = computed(() => props.userRole === 'admin')

// ─── Layout Store ───────────────────────────────────────────

const store = useDashboardLayoutStore()

onMounted(() => {
    store.loadLayout()
    checkBreakpoint()
    window.addEventListener('resize', checkBreakpoint)
    window.addEventListener('resize', updateWidths)
    nextTick(updateWidths)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkBreakpoint)
    window.removeEventListener('resize', updateWidths)
})

const checkBreakpoint = () => {
    store.setNarrow(window.innerWidth < 1024)
}

// ─── Shared Summary Data (single fetch, shared via props) ───

const { data: summaryData, loading: summaryLoading, fetch: fetchSummary } =
    useReport('/api/v1/reports/summary-cards')

onMounted(() => fetchSummary())

// ─── Row Container Refs & Widths ────────────────────────────

const analyticsRowRef = ref<HTMLElement | null>(null)
const operationalRowRef = ref<HTMLElement | null>(null)

const analyticsRowWidth = ref(800)
const operationalRowWidth = ref(1200)

const updateWidths = () => {
    if (analyticsRowRef.value) {
        analyticsRowWidth.value = analyticsRowRef.value.offsetWidth
    }
    if (operationalRowRef.value) {
        operationalRowWidth.value = operationalRowRef.value.offsetWidth
    }
}

// ─── Slot State Accessors ───────────────────────────────────

const analyticsLeft = computed(() => store.slots['analytics-left'])
const analyticsRight = computed(() => store.slots['analytics-right'])
const operationalLeft = computed(() => store.slots['operational-left'])
const operationalRight = computed(() => store.slots['operational-right'])

// ─── Expansion Detection ────────────────────────────────────

const isAnyAnalyticsExpanded = computed(() => {
    return store.expandedCardId === analyticsLeft.value?.currentCardId
        || store.expandedCardId === analyticsRight.value?.currentCardId
})

const isAnyOperationalExpanded = computed(() => {
    return store.expandedCardId === operationalLeft.value?.currentCardId
        || store.expandedCardId === operationalRight.value?.currentCardId
})

// ─── Card Component Registry ────────────────────────────────

interface CardConfig {
    component: any
    title: string
    subtitle: string
    icon: any
    iconColor: string
    navigateTo?: string
}

const CARD_REGISTRY: Record<string, CardConfig> = {
    'top-products': {
        component: markRaw(TopProductsChart),
        title: 'Top Selling Products',
        subtitle: 'By quantity sold',
        icon: markRaw(TagIcon),
        iconColor: 'bg-emerald-500',
        navigateTo: '/admin/products',
    },
    'purchase-analytics': {
        component: markRaw(PurchaseOrderChart),
        title: 'Purchase Order Analytics',
        subtitle: 'Procurement trends by status',
        icon: markRaw(ClipboardDocumentListIcon),
        iconColor: 'bg-amber-500',
        navigateTo: '/admin/purchase-orders',
    },
    'low-stock-chart': {
        component: markRaw(LowStockChart),
        title: 'Low Stock Items',
        subtitle: 'Products below stock threshold',
        icon: markRaw(ExclamationTriangleIcon),
        iconColor: 'bg-red-500',
        navigateTo: '/admin/products',
    },
    'user-analytics': {
        component: markRaw(UserAnalyticsChart),
        title: 'User Analytics',
        subtitle: 'Role distribution & growth',
        icon: markRaw(UserGroupIcon),
        iconColor: 'bg-violet-500',
        navigateTo: '/admin/users',
    },
    'supplier-procurement': {
        component: markRaw(SupplierProcurementTable),
        title: 'Supplier Procurement',
        subtitle: 'PO aggregates per supplier',
        icon: markRaw(BuildingStorefrontIcon),
        iconColor: 'bg-pink-500',
        navigateTo: '/admin/suppliers',
    },
    'inventory-overview': {
        component: markRaw(InventoryOverviewChart),
        title: 'Inventory Overview',
        subtitle: 'Product growth by category',
        icon: markRaw(ArchiveBoxIcon),
        iconColor: 'bg-blue-500',
        navigateTo: '/admin/products',
    },
}

const getCard = (cardId: string): CardConfig | undefined => CARD_REGISTRY[cardId]

// ─── Span Negotiation Handlers ──────────────────────────────

const onAnalyticsNegotiate = (delta: number) => {
    const slot = analyticsLeft.value
    if (!slot) return
    store.negotiateSpan('analytics-left', slot.span + delta)
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

const onOperationalNegotiate = (delta: number) => {
    const slot = operationalLeft.value
    if (!slot) return
    store.negotiateSpan('operational-left', slot.span + delta)
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

// ─── Swap Handlers ──────────────────────────────────────────

const swapAnalyticsPair = () => {
    store.swapCards('analytics-left', 'analytics-right')
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

const swapOperationalPair = () => {
    store.swapCards('operational-left', 'operational-right')
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

// ─── Reset Layout ───────────────────────────────────────────

const resetLayout = () => {
    store.resetLayout()
    nextTick(() => window.dispatchEvent(new Event('resize')))
}

// ─── Watch expansion for chart reflow ───────────────────────

watch(() => store.expandedCardId, () => {
    nextTick(() => window.dispatchEvent(new Event('resize')))
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="max-w-[1440px] mx-auto space-y-6">

            <!-- ═══════════════════════════════════════════════
                 PAGE HEADER
                 ═══════════════════════════════════════════════ -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                        {{ isAdmin ? 'Analytics Dashboard' : 'Sales Dashboard' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ isAdmin
                            ? 'Enterprise analytics — resize panels, reorder KPIs, expand charts'
                            : 'Your sales performance at a glance'
                        }}
                    </p>
                </div>
                <button
                    @click="resetLayout"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-start sm:self-auto"
                >
                    <ArrowPathIcon class="h-3.5 w-3.5" />
                    Reset Layout
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════
                 ROW 1 — PRIMARY HERO ZONE
                 Sales Summary (col-span-12)
                 ═══════════════════════════════════════════════ -->
            <section aria-label="Sales summary">
                <SalesSummaryCard :is-admin="isAdmin" />
            </section>

            <!-- ═══════════════════════════════════════════════
                 ROW 2 — KPI STRIP (Isolated Drag Context)
                 5 KPI cards — fixed size, reorderable
                 ═══════════════════════════════════════════════ -->
            <KpiStrip
                :is-admin="isAdmin"
                :summary-data="summaryData"
                :summary-loading="summaryLoading"
            />

            <!-- ═══════════════════════════════════════════════
                 ROW 3 — CORE ANALYTICS MATRIX (Admin Only)
                 Left Pair (8 cols) + Right Stack (4 cols)
                 ═══════════════════════════════════════════════ -->
            <section
                v-if="isAdmin"
                aria-label="Core analytics"
                class="grid grid-cols-12 gap-6"
            >
                <!-- Left Analytical Pair (8/12 cols) -->
                <div
                    class="col-span-12 lg:col-span-8 transition-all duration-300"
                    :class="{ 'lg:!col-span-12': isAnyAnalyticsExpanded }"
                >
                    <div
                        ref="analyticsRowRef"
                        class="flex h-full"
                        :class="store.isNarrow || isAnyAnalyticsExpanded
                            ? 'flex-col gap-6'
                            : 'flex-row'"
                    >
                        <!-- ─── Slot: analytics-left ─── -->
                        <div
                            class="transition-all duration-300 ease-out min-w-0"
                            :style="!store.isNarrow && !isAnyAnalyticsExpanded
                                ? { flex: `${analyticsLeft.span} 0 0%` }
                                : undefined"
                        >
                            <DashboardCard
                                v-if="getCard(analyticsLeft.currentCardId)"
                                :card-id="analyticsLeft.currentCardId"
                                :title="getCard(analyticsLeft.currentCardId)!.title"
                                :subtitle="getCard(analyticsLeft.currentCardId)!.subtitle"
                                :icon="getCard(analyticsLeft.currentCardId)!.icon"
                                :icon-color="getCard(analyticsLeft.currentCardId)!.iconColor"
                                :navigate-to="getCard(analyticsLeft.currentCardId)!.navigateTo"
                                :swappable="!store.isNarrow"
                                @swap="swapAnalyticsPair"
                            >
                                <template #default="{ expanded }">
                                    <div
                                        :class="expanded ? 'h-[500px]' : 'h-[340px]'"
                                        class="transition-all duration-300"
                                    >
                                        <component
                                            :is="getCard(analyticsLeft.currentCardId)!.component"
                                            :expanded="expanded"
                                        />
                                    </div>
                                </template>
                            </DashboardCard>
                        </div>

                        <!-- ─── Span Negotiation Handle ─── -->
                        <SpanHandle
                            v-if="!store.isNarrow && !isAnyAnalyticsExpanded"
                            :container-width="analyticsRowWidth"
                            :total-span="8"
                            :disabled="store.isNarrow"
                            @negotiate="onAnalyticsNegotiate"
                        />

                        <!-- ─── Slot: analytics-right ─── -->
                        <div
                            class="transition-all duration-300 ease-out min-w-0"
                            :style="!store.isNarrow && !isAnyAnalyticsExpanded
                                ? { flex: `${analyticsRight.span} 0 0%` }
                                : undefined"
                        >
                            <DashboardCard
                                v-if="getCard(analyticsRight.currentCardId)"
                                :card-id="analyticsRight.currentCardId"
                                :title="getCard(analyticsRight.currentCardId)!.title"
                                :subtitle="getCard(analyticsRight.currentCardId)!.subtitle"
                                :icon="getCard(analyticsRight.currentCardId)!.icon"
                                :icon-color="getCard(analyticsRight.currentCardId)!.iconColor"
                                :navigate-to="getCard(analyticsRight.currentCardId)!.navigateTo"
                                :swappable="!store.isNarrow"
                                @swap="swapAnalyticsPair"
                            >
                                <template #default="{ expanded }">
                                    <div
                                        :class="expanded ? 'h-[500px]' : 'h-[340px]'"
                                        class="transition-all duration-300"
                                    >
                                        <component
                                            :is="getCard(analyticsRight.currentCardId)!.component"
                                            :expanded="expanded"
                                        />
                                    </div>
                                </template>
                            </DashboardCard>
                        </div>
                    </div>
                </div>

                <!-- Right Supporting Stack (4/12 cols) -->
                <div
                    class="col-span-12 lg:col-span-4 transition-all duration-300"
                    :class="{ 'lg:!col-span-12': isAnyAnalyticsExpanded }"
                >
                    <MetricStack
                        :summary-data="summaryData"
                        :summary-loading="summaryLoading"
                    />
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 ROW 4 — OPERATIONAL LAYER (Admin Only)
                 Symmetrical pair — chart-ready, balanced weight
                 ═══════════════════════════════════════════════ -->
            <section v-if="isAdmin" aria-label="Operational analytics">
                <div
                    ref="operationalRowRef"
                    class="flex h-full"
                    :class="store.isNarrow || isAnyOperationalExpanded
                        ? 'flex-col gap-6'
                        : 'flex-row'"
                >
                    <!-- ─── Slot: operational-left ─── -->
                    <div
                        class="transition-all duration-300 ease-out min-w-0"
                        :style="!store.isNarrow && !isAnyOperationalExpanded
                            ? { flex: `${operationalLeft.span} 0 0%` }
                            : undefined"
                    >
                        <DashboardCard
                            v-if="getCard(operationalLeft.currentCardId)"
                            :card-id="operationalLeft.currentCardId"
                            :title="getCard(operationalLeft.currentCardId)!.title"
                            :subtitle="getCard(operationalLeft.currentCardId)!.subtitle"
                            :icon="getCard(operationalLeft.currentCardId)!.icon"
                            :icon-color="getCard(operationalLeft.currentCardId)!.iconColor"
                            :navigate-to="getCard(operationalLeft.currentCardId)!.navigateTo"
                            :swappable="!store.isNarrow"
                            @swap="swapOperationalPair"
                        >
                            <template #default="{ expanded }">
                                <div
                                    :class="expanded ? 'h-[500px]' : 'h-[340px]'"
                                    class="transition-all duration-300"
                                >
                                    <component
                                        :is="getCard(operationalLeft.currentCardId)!.component"
                                        :expanded="expanded"
                                    />
                                </div>
                            </template>
                        </DashboardCard>
                    </div>

                    <!-- ─── Span Negotiation Handle ─── -->
                    <SpanHandle
                        v-if="!store.isNarrow && !isAnyOperationalExpanded"
                        :container-width="operationalRowWidth"
                        :total-span="12"
                        :disabled="store.isNarrow"
                        @negotiate="onOperationalNegotiate"
                    />

                    <!-- ─── Slot: operational-right ─── -->
                    <div
                        class="transition-all duration-300 ease-out min-w-0"
                        :style="!store.isNarrow && !isAnyOperationalExpanded
                            ? { flex: `${operationalRight.span} 0 0%` }
                            : undefined"
                    >
                        <DashboardCard
                            v-if="getCard(operationalRight.currentCardId)"
                            :card-id="operationalRight.currentCardId"
                            :title="getCard(operationalRight.currentCardId)!.title"
                            :subtitle="getCard(operationalRight.currentCardId)!.subtitle"
                            :icon="getCard(operationalRight.currentCardId)!.icon"
                            :icon-color="getCard(operationalRight.currentCardId)!.iconColor"
                            :navigate-to="getCard(operationalRight.currentCardId)!.navigateTo"
                            :swappable="!store.isNarrow"
                            @swap="swapOperationalPair"
                        >
                            <template #default="{ expanded }">
                                <div
                                    :class="expanded ? 'h-[500px]' : 'h-[340px]'"
                                    class="transition-all duration-300"
                                >
                                    <component
                                        :is="getCard(operationalRight.currentCardId)!.component"
                                        :expanded="expanded"
                                    />
                                </div>
                            </template>
                        </DashboardCard>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 STAFF VIEW — Simplified Analytics
                 ═══════════════════════════════════════════════ -->
            <section
                v-if="!isAdmin"
                aria-label="Your analytics"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <DashboardCard
                    card-id="top-products"
                    title="Top Selling Products"
                    subtitle="By quantity sold"
                    :icon="TagIcon"
                    icon-color="bg-emerald-500"
                    navigate-to="/staff/products"
                >
                    <template #default="{ expanded }">
                        <div :class="expanded ? 'h-[500px]' : 'h-[340px]'" class="transition-all duration-300">
                            <TopProductsChart :expanded="expanded" />
                        </div>
                    </template>
                </DashboardCard>

                <DashboardCard
                    card-id="low-stock-chart"
                    title="Low Stock Items"
                    subtitle="Products below threshold"
                    :icon="ExclamationTriangleIcon"
                    icon-color="bg-red-500"
                    navigate-to="/staff/products"
                >
                    <template #default="{ expanded }">
                        <div :class="expanded ? 'h-[500px]' : 'h-[340px]'" class="transition-all duration-300">
                            <LowStockChart :expanded="expanded" />
                        </div>
                    </template>
                </DashboardCard>
            </section>

        </div>
    </AuthenticatedLayout>
</template>
