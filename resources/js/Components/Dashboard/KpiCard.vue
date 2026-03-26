<script setup lang="ts">
/**
 * KpiCard — Individual KPI metric for the dashboard strip.
 *
 * Rules:
 * - Fixed size, no resizing
 * - Draggable only within KPI zone (parent handles drag)
 * - Typography-driven emphasis
 * - Highly scannable, no clutter
 */
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
    label: string
    value: any
    loading: boolean
    icon: any
    color: string
    format?: 'currency' | 'number'
    href?: string
}>()

const formattedValue = computed(() => {
    if (props.value == null) return '—'
    if (props.format === 'currency') {
        return `₱${Number(props.value).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
    }
    return Number(props.value).toLocaleString()
})

const navigate = () => {
    if (props.href) router.visit(props.href)
}
</script>

<template>
    <div
        class="relative overflow-hidden bg-white rounded-2xl border border-gray-200/60 shadow-sm p-4 flex items-center gap-4 transition-all duration-200 hover:shadow-md group select-none"
        :class="href ? 'cursor-pointer hover:border-indigo-200' : ''"
        @click="navigate"
    >
        <!-- Decorative top accent -->
        <div
            class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-current to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            :style="{ color: `var(--kpi-accent)` }"
        ></div>

        <!-- Icon -->
        <div class="shrink-0 flex items-center justify-center w-11 h-11 rounded-xl shadow-sm" :class="color">
            <component :is="icon" class="h-6 w-6 text-white" />
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-gray-500 truncate">{{ label }}</p>
            <p class="text-lg font-bold text-gray-900 tabular-nums">
                <template v-if="loading">
                    <span class="inline-block w-16 h-5 bg-gray-200 rounded animate-pulse"></span>
                </template>
                <template v-else>{{ formattedValue }}</template>
            </p>
        </div>
    </div>
</template>
