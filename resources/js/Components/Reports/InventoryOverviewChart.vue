<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { ChartSkeleton } from '@/Components/Skeletons'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const isDark = computed(() => theme.value === 'dark')

const { data, loading, error, fetch } = useReport('/api/v1/reports/inventory-overview')

onMounted(() => fetch())

const LINE_COLORS = [
    '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6',
    '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#06b6d4',
]

const chartData = computed(() => {
    if (!data.value || !(data.value as any).timeline) {
        return { labels: [], datasets: [] }
    }

    const timeline = (data.value as any).timeline as { category: string; month: string; product_count: number }[]

    // Collect unique months & categories
    const months     = [...new Set(timeline.map(r => r.month))].sort()
    const categories = [...new Set(timeline.map(r => r.category))]

    // Build a lookup map
    const lookup: Record<string, Record<string, number>> = {}
    for (const row of timeline) {
        if (!lookup[row.category]) lookup[row.category] = {}
        lookup[row.category][row.month] = row.product_count
    }

    const datasets = categories.map((cat, i) => ({
        label: cat,
        data: months.map(m => lookup[cat]?.[m] ?? 0),
        borderColor: LINE_COLORS[i % LINE_COLORS.length],
        backgroundColor: LINE_COLORS[i % LINE_COLORS.length] + '20',
        pointBackgroundColor: LINE_COLORS[i % LINE_COLORS.length],
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 7,
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
    interaction: { mode: 'index' as const, intersect: false },
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                usePointStyle: true,
                color: isDark.value ? '#d1d5db' : '#374151',
            },
        },
        tooltip: {
            mode: 'index' as const,
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
        y: {
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
                text: 'Products Added',
                color: isDark.value ? '#d1d5db' : '#374151',
            },
        },
        x: {
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
    },
}))
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="line" :show-legend="true" :legend-items="3" />
        <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else class="h-full">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
