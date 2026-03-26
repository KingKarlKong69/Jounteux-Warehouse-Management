<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { ChartSkeleton } from '@/Components/Skeletons'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const isDark = computed(() => theme.value === 'dark')

const { data, loading, error, fetch } = useReport('/api/v1/reports/purchase-order-analytics')

onMounted(() => fetch())

const STATUS_COLORS: Record<string, string> = {
    pending:      '#f59e0b',
    approved:     '#3b82f6',
    processing:   '#8b5cf6',
    for_shipment: '#06b6d4',
    completed:    '#10b981',
    rejected:     '#ef4444',
    cancelled:    '#9ca3af',
}

const chartData = computed(() => {
    if (!data.value || !(data.value as any).status_over_time) {
        return { labels: [], datasets: [] }
    }

    const rows = (data.value as any).status_over_time as {
        status: string; month: string; count: number; total_amount: string
    }[]

    const months   = [...new Set(rows.map(r => r.month))].sort()
    const statuses = [...new Set(rows.map(r => r.status))]

    const lookup: Record<string, Record<string, number>> = {}
    for (const row of rows) {
        if (!lookup[row.status]) lookup[row.status] = {}
        lookup[row.status][row.month] = row.count
    }

    const datasets = statuses.map(status => ({
        label: status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
        data: months.map(m => lookup[status]?.[m] ?? 0),
        backgroundColor: STATUS_COLORS[status] || '#9ca3af',
        borderRadius: 6,
    }))

    return { labels: months, datasets }
})

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 800,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                usePointStyle: true,
                color: isDark.value ? '#d1d5db' : '#374151',
            },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1f2937' : '#ffffff',
            titleColor: isDark.value ? '#f3f4f6' : '#111827',
            bodyColor: isDark.value ? '#d1d5db' : '#374151',
            borderColor: isDark.value ? '#374151' : '#e5e7eb',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
        },
    },
    scales: {
        x: {
            stacked: true,
            ticks: {
                color: isDark.value ? '#9ca3af' : '#6b7280',
            },
            grid: {
                color: isDark.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
            },
            title: {
                display: true,
                text: 'Month',
                color: isDark.value ? '#d1d5db' : '#374151',
            },
        },
        y: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                color: isDark.value ? '#9ca3af' : '#6b7280',
            },
            grid: {
                color: isDark.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
            },
            title: {
                display: true,
                text: 'Orders',
                color: isDark.value ? '#d1d5db' : '#374151',
            },
        },
    },
}))

const totalValue = computed(() => {
    const v = (data.value as any)?.total_value ?? 0
    return Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })
})
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="bar" :show-legend="true" :legend-items="4" />
        <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else class="h-full flex flex-col">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 shrink-0">
                Total Procurement: <strong class="text-gray-800 dark:text-gray-200">₱{{ totalValue }}</strong>
            </div>
            <div class="flex-1 min-h-0">
                <Bar :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>
