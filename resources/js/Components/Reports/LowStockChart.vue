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

const { data, loading, error, fetch } = useReport('/api/v1/reports/low-stock-items')

onMounted(() => fetch({ threshold: 10, sort: 'current_stock', direction: 'asc' }))

const chartData = computed(() => {
    if (!data.value || !Array.isArray(data.value) || data.value.length === 0) {
        return { labels: [], datasets: [] }
    }

    // Show max 15 items
    const items = (data.value as any[]).slice(0, 15)

    return {
        labels: items.map((r: any) => `${r.name} (${r.sku})`),
        datasets: [
            {
                label: 'Current Stock',
                data: items.map((r: any) => r.current_stock),
                backgroundColor: items.map((r: any) => {
                    if (r.current_stock <= 3) return 'rgba(239, 68, 68, 0.8)'   // red
                    if (r.current_stock <= 5) return 'rgba(245, 158, 11, 0.8)'  // amber
                    return 'rgba(59, 130, 246, 0.8)' // blue
                }),
                borderColor: items.map((r: any) => {
                    if (r.current_stock <= 3) return 'rgba(239, 68, 68, 1)'
                    if (r.current_stock <= 5) return 'rgba(245, 158, 11, 1)'
                    return 'rgba(59, 130, 246, 1)'
                }),
                borderWidth: 1,
                borderRadius: 4,
            },
        ],
    }
})

const chartOptions = computed(() => ({
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 800,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: { display: false },
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
                    const item = (data.value as any[])?.[ctx.dataIndex]
                    const cat = item?.category_name ?? 'Uncategorized'
                    return `Stock: ${ctx.raw} — ${cat}`
                },
                afterLabel: (ctx: any) => {
                    const item = (data.value as any[])?.[ctx.dataIndex]
                    if (item) {
                        return `Price: ₱${Number(item.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                    }
                    return ''
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            title: {
                display: true,
                text: 'Stock Quantity',
                color: isDark.value ? '#d1d5db' : '#374151',
            },
            ticks: {
                stepSize: 1,
                color: isDark.value ? '#9ca3af' : '#6b7280',
            },
            grid: {
                color: isDark.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
            },
        },
        y: {
            ticks: {
                font: { size: 11 },
                color: isDark.value ? '#9ca3af' : '#6b7280',
                callback: function(value: any, index: number) {
                    const label = (this as any).getLabelForValue(value) as string
                    // Truncate long labels
                    return label.length > 25 ? label.substring(0, 22) + '…' : label
                },
            },
            grid: {
                color: isDark.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
            },
        },
    },
}))
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="bar" :show-legend="false" />
        <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else-if="!data || (data as any[]).length === 0" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500 text-sm">
            No low-stock items found
        </div>
        <div v-else class="h-full">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
