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
    type ChartOptions,
    type ChartData,
    type TooltipItem,
} from 'chart.js'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { ChartSkeleton } from '@/Components/Skeletons'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const { data, loading, error, fetch } = useReport('/api/v1/reports/daily-sales')

onMounted(() => fetch())

const isDark = computed(() => theme.value === 'dark')

/* ==============================
   CHART DATA (STRONGLY TYPED)
============================== */

const chartData = computed<ChartData<'bar'>>(() => {
    if (!data.value || !Array.isArray(data.value)) {
        return {
            labels: [],
            datasets: [],
        }
    }

    return {
        labels: data.value.map((r: any) => r.date),
        datasets: [
            {
                label: 'Sales Revenue (₱)',
                data: data.value.map((r: any) =>
                    parseFloat(r.total_amount)
                ),
                backgroundColor: isDark.value
                    ? 'rgba(251, 146, 60, 0.6)'
                    : 'rgba(99, 102, 241, 0.7)',
                borderColor: isDark.value
                    ? 'rgba(251, 146, 60, 1)'
                    : 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: isDark.value
                    ? 'rgba(251, 146, 60, 0.85)'
                    : 'rgba(99, 102, 241, 0.9)',
            },
            {
                label: 'Order Count',
                data: data.value.map((r: any) => r.order_count),
                backgroundColor: isDark.value
                    ? 'rgba(52, 211, 153, 0.5)'
                    : 'rgba(16, 185, 129, 0.6)',
                borderColor: isDark.value
                    ? 'rgba(52, 211, 153, 1)'
                    : 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                borderRadius: 8,
                yAxisID: 'y1',
                hoverBackgroundColor: isDark.value
                    ? 'rgba(52, 211, 153, 0.8)'
                    : 'rgba(16, 185, 129, 0.85)',
            },
        ],
    }
})

/* ==============================
   CHART OPTIONS (STRONGLY TYPED)
============================== */

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 800,
        easing: 'easeOutQuart',
    },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: isDark.value ? '#d1d5db' : '#374151',
                usePointStyle: true,
                pointStyle: 'rectRounded',
                padding: 16,
                font: {
                    size: 12,
                    weight: 500,
                },
            },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1f2937' : '#ffffff',
            titleColor: isDark.value ? '#f9fafb' : '#111827',
            bodyColor: isDark.value ? '#d1d5db' : '#374151',
            borderColor: isDark.value ? '#374151' : '#e5e7eb',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 12,
            boxPadding: 6,
            callbacks: {
                label: (ctx: TooltipItem<'bar'>) => {
                    if (ctx.dataset.yAxisID === 'y1') {
                        return ` Orders: ${ctx.raw}`
                    }

                    return ` ₱${Number(ctx.raw).toLocaleString(
                        'en-PH',
                        { minimumFractionDigits: 2 }
                    )}`
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: isDark.value
                    ? 'rgba(75, 85, 99, 0.3)'
                    : 'rgba(229, 231, 235, 0.8)',
                drawBorder: false,
            },
            ticks: {
                color: isDark.value ? '#9ca3af' : '#6b7280',
                font: { size: 11 },
                callback: (value: string | number) =>
                    `₱${Number(value).toLocaleString()}`,
            },
        },
        y1: {
            position: 'right',
            beginAtZero: true,
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                color: isDark.value ? '#9ca3af' : '#6b7280',
                font: { size: 11 },
                stepSize: 1,
            },
        },
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: isDark.value ? '#9ca3af' : '#6b7280',
                font: { size: 11 },
                maxRotation: 45,
            },
        },
    },
}))
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="bar" :show-legend="true" :legend-items="2" />

        <div
            v-else-if="error"
            class="flex items-center justify-center h-full text-red-500 dark:text-red-400 text-sm"
        >
            {{ error }}
        </div>

        <div v-else class="h-full">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>