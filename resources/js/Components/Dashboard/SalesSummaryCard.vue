<script setup lang="ts">
/**
 * SalesSummaryCard — Primary Hero Zone (Row 1)
 *
 * The visual anchor of the dashboard.
 * col-span-12, largest visual element.
 * Contains the DailySalesChart in a hero-sized container
 * with filter controls for time range.
 */
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    type ChartOptions,
    type ChartData,
    type TooltipItem,
} from 'chart.js'
import { useReport } from '@/composables/useReport'
import { useDashboardLayoutStore } from '@/stores/dashboardLayout'
import DashboardCard from './DashboardCard.vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'
import { ChartSkeleton } from '@/Components/Skeletons'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
    isAdmin: boolean
}>()

const store = useDashboardLayoutStore()
const isExpanded = computed(() => store.expandedCardId === 'sales-summary')

// ─── Data Fetching ──────────────────────────────────────────

const dayRange = ref(30)
const { data, loading, error, fetch: fetchReport } = useReport('/api/v1/reports/daily-sales')

onMounted(() => fetchReport({ days: dayRange.value }))

watch(dayRange, (days) => {
    fetchReport({ days })
})

const dayOptions = [
    { label: '7D', value: 7 },
    { label: '14D', value: 14 },
    { label: '30D', value: 30 },
    { label: '90D', value: 90 },
]

// ─── Chart Data ─────────────────────────────────────────────

const chartData = computed<ChartData<'bar'>>(() => {
    if (!data.value || !Array.isArray(data.value)) {
        return { labels: [], datasets: [] }
    }

    return {
        labels: data.value.map((r: any) => r.date),
        datasets: [
            {
                label: 'Sales Revenue (₱)',
                data: data.value.map((r: any) => parseFloat(r.total_amount)),
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(99, 102, 241, 0.9)',
            },
            {
                label: 'Order Count',
                data: data.value.map((r: any) => r.order_count),
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                borderRadius: 8,
                yAxisID: 'y1',
                hoverBackgroundColor: 'rgba(16, 185, 129, 0.85)',
            },
        ],
    }
})

// ─── Chart Options ──────────────────────────────────────────

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 600, easing: 'easeOutQuart' },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#374151',
                usePointStyle: true,
                pointStyle: 'rectRounded',
                padding: 20,
                font: { size: 12, weight: 500 },
            },
        },
        tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#111827',
            bodyColor: '#374151',
            borderColor: '#e5e7eb',
            borderWidth: 1,
            padding: 14,
            cornerRadius: 12,
            boxPadding: 6,
            callbacks: {
                label: (ctx: TooltipItem<'bar'>) => {
                    if (ctx.dataset.yAxisID === 'y1') return ` Orders: ${ctx.raw}`
                    return ` ₱${Number(ctx.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(229, 231, 235, 0.8)' },
            ticks: {
                color: '#6b7280',
                font: { size: 11 },
                callback: (value: string | number) => `₱${Number(value).toLocaleString()}`,
            },
        },
        y1: {
            position: 'right',
            beginAtZero: true,
            grid: { drawOnChartArea: false },
            ticks: { color: '#6b7280', font: { size: 11 }, stepSize: 1 },
        },
        x: {
            grid: { display: false },
            ticks: { color: '#6b7280', font: { size: 11 }, maxRotation: 45 },
        },
    },
}))

// ─── Computed Summary ───────────────────────────────────────

const totalRevenue = computed(() => {
    if (!data.value || !Array.isArray(data.value)) return 0
    return data.value.reduce((sum: number, r: any) => sum + parseFloat(r.total_amount || 0), 0)
})

const totalOrders = computed(() => {
    if (!data.value || !Array.isArray(data.value)) return 0
    return data.value.reduce((sum: number, r: any) => sum + (r.order_count || 0), 0)
})
</script>

<template>
    <DashboardCard
        card-id="sales-summary"
        title="Sales Summary"
        subtitle="Revenue & order trends"
        :icon="ChartBarIcon"
        icon-color="bg-indigo-500"
        :navigate-to="isAdmin ? '/admin/sales-orders' : '/staff/sales-orders'"
        :expandable="true"
    >
        <template #default="{ expanded }">
            <div class="flex flex-col h-full">
                <!-- Filter Controls & Summary -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 shrink-0">
                    <!-- Day Range Selector -->
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                        <button
                            v-for="opt in dayOptions"
                            :key="opt.value"
                            @click="dayRange = opt.value"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all duration-150"
                            :class="dayRange === opt.value
                                ? 'bg-white text-indigo-700 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <!-- Quick Stats -->
                    <div v-if="!loading" class="flex items-center gap-6 text-xs text-gray-500">
                        <span>
                            Revenue: <strong class="text-gray-900">₱{{ totalRevenue.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong>
                        </span>
                        <span>
                            Orders: <strong class="text-gray-900">{{ totalOrders.toLocaleString() }}</strong>
                        </span>
                    </div>
                </div>

                <!-- Chart Area -->
                <div
                    class="flex-1 min-h-0 transition-all duration-300"
                    :class="expanded ? 'h-[440px]' : 'h-[240px]'"
                >
                    <ChartSkeleton v-if="loading" variant="bar" :show-legend="true" :legend-items="2" />

                    <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
                        {{ error }}
                    </div>

                    <div v-else class="h-full">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </div>
            </div>
        </template>
    </DashboardCard>
</template>
