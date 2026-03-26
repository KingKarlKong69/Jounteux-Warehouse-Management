<template>
    <div
        :class="['grid gap-4', gridColsClass]"
        role="status"
        :aria-label="`Loading ${cards} summary cards`"
        aria-busy="true"
    >
        <div
            v-for="i in cards"
            :key="i"
            class="bg-white dark:bg-surface-850 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 flex items-center gap-4"
        >
            <!-- Icon placeholder -->
            <div class="animate-pulse rounded-xl h-11 w-11 shrink-0 bg-gray-200 dark:bg-gray-600" />
            <!-- Text area -->
            <div class="flex-1 space-y-2">
                <div class="animate-pulse rounded h-2.5 bg-gray-200 dark:bg-gray-600 w-20" />
                <div class="animate-pulse rounded h-5 bg-gray-200 dark:bg-gray-600" :style="{ width: valueWidth(i) }" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    /** Number of stat cards to show */
    cards?: number
    /** Grid columns class */
    gridCols?: string
}>(), {
    cards: 4,
    gridCols: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
})

const gridColsClass = computed(() => props.gridCols)

const widths = ['48px', '64px', '40px', '56px', '52px', '44px']
const valueWidth = (i: number): string => widths[(i - 1) % widths.length]
</script>
