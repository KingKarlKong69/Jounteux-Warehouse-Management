<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
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

const { data, loading, error, fetch } = useReport('/api/v1/reports/user-analytics')

onMounted(() => fetch())

const COLORS: Record<string, string> = {
    admin: '#6366f1',
    staff: '#10b981',
}

const chartData = computed(() => {
    if (!data.value || !(data.value as any).distribution) {
        return { labels: [], datasets: [] }
    }

    const dist = (data.value as any).distribution as { role: string; count: number }[]

    return {
        labels: dist.map(r => r.role.charAt(0).toUpperCase() + r.role.slice(1)),
        datasets: [
            {
                data: dist.map(r => r.count),
                backgroundColor: dist.map(r => COLORS[r.role] || '#9ca3af'),
                borderWidth: 3,
                borderColor: isDark.value ? '#1f2937' : '#ffffff',
                hoverOffset: 10,
            },
        ],
    }
})

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
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
                label: (ctx: any) => `${ctx.label}: ${ctx.raw} users`,
            },
        },
    },
}))

const totalUsers = computed(() => (data.value as any)?.total_users ?? 0)
const blockedUsers = computed(() => (data.value as any)?.blocked_users ?? 0)
</script>

<template>
    <div class="w-full h-full">
        <ChartSkeleton v-if="loading" variant="doughnut" :show-legend="true" :legend-items="2" />
        <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else class="h-full flex flex-col">
            <div class="flex items-center gap-4 mb-2 text-xs text-gray-500 dark:text-gray-400 shrink-0">
                <span>Total: <strong class="text-gray-800 dark:text-gray-200">{{ totalUsers }}</strong></span>
                <span>Blocked: <strong class="text-red-600 dark:text-red-400">{{ blockedUsers }}</strong></span>
            </div>
            <div class="flex-1 min-h-0">
                <Doughnut :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>
