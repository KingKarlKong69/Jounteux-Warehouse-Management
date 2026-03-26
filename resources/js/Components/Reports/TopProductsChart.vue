<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Pie } from 'vue-chartjs'
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { ChartSkeleton } from '@/Components/Skeletons'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const isDark = computed(() => theme.value === 'dark')

const { data, loading, error, fetch } = useReport('/api/v1/reports/top-products')

onMounted(() => fetch())

const COLORS_LIGHT = [
    '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6',
    '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#06b6d4',
]
const COLORS_DARK = [
    '#818cf8', '#34d399', '#fbbf24', '#f87171', '#60a5fa',
    '#a78bfa', '#f472b6', '#2dd4bf', '#fb923c', '#22d3ee',
]

const chartData = computed(() => {
    if (!data.value || !Array.isArray(data.value)) {
        return { labels: [], datasets: [] }
    }

    const palette = isDark.value ? COLORS_DARK : COLORS_LIGHT

    return {
        labels: data.value.map((r: any) => r.product_name),
        datasets: [
            {
                data: data.value.map((r: any) => parseInt(r.total_quantity)),
                backgroundColor: palette.slice(0, data.value.length),
                borderWidth: 2,
                borderColor: isDark.value ? '#1f2937' : '#ffffff',
                hoverOffset: 10,
            },
        ],
    }
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
            position: (props.expanded ? 'right' : 'bottom') as 'right' | 'bottom',
            labels: {
                padding: 16,
                usePointStyle: true,
                pointStyle: 'circle',
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
            callbacks: {
                label: (ctx: any) => {
                    const item = data.value?.[ctx.dataIndex] as any
                    const revenue = item
                        ? `₱${Number(item.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                        : ''
                    return `${ctx.label}: ${ctx.raw} units — ${revenue}`
                },
            },
        },
    },
}))
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="doughnut" :show-legend="true" :legend-items="5" />
        <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else-if="!data || (data as any[]).length === 0" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500 text-sm">
            No sales data available
        </div>
        <div v-else class="h-full">
            <Pie :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
