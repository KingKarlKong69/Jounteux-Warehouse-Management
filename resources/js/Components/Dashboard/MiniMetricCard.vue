<script setup lang="ts">
/**
 * MiniMetricCard — Compact metric card for the supporting stack.
 *
 * Hierarchy: Micro → Secondary → Alert intelligence
 */
import { computed } from 'vue'

const props = defineProps<{
    label: string
    value: any
    loading: boolean
    icon: any
    color: string
    format?: 'currency' | 'number'
    accent?: string
}>()

const formatted = computed(() => {
    if (props.value == null) return '—'
    if (props.format === 'currency') {
        return `₱${Number(props.value).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
    }
    return Number(props.value).toLocaleString()
})
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm p-4 flex flex-col justify-between transition hover:shadow-md">
        <div class="flex items-center gap-2 mb-2">
            <div
                class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                :class="color"
            >
                <component :is="icon" class="h-4 w-4 text-white" />
            </div>
            <p class="text-xs font-medium text-gray-500 truncate">{{ label }}</p>
        </div>
        <p class="text-xl font-bold tabular-nums" :class="accent ?? 'text-gray-900'">
            <template v-if="loading">
                <span class="inline-block w-14 h-6 bg-gray-200 rounded animate-pulse"></span>
            </template>
            <template v-else>{{ formatted }}</template>
        </p>
    </div>
</template>
