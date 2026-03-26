<template>
    <div
        class="flex flex-col items-center justify-center h-full w-full"
        role="status"
        aria-label="Loading chart"
        aria-busy="true"
    >
        <!-- Bar chart variant -->
        <div v-if="variant === 'bar'" class="flex items-end gap-2 w-full max-w-xs h-40 px-4">
            <div
                v-for="(h, i) in barHeights"
                :key="i"
                class="animate-pulse bg-gray-200 dark:bg-gray-600 rounded-t flex-1"
                :style="{ height: h, animationDelay: `${i * 75}ms` }"
            />
        </div>

        <!-- Line chart variant -->
        <div v-else-if="variant === 'line'" class="w-full h-40 px-4 flex items-center">
            <svg class="w-full h-full" viewBox="0 0 300 100" preserveAspectRatio="none">
                <path
                    d="M0,80 C30,60 60,75 90,50 C120,25 150,60 180,40 C210,20 240,55 270,35 L300,45"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="text-gray-200 dark:text-gray-600 animate-pulse"
                />
                <path
                    d="M0,80 C30,60 60,75 90,50 C120,25 150,60 180,40 C210,20 240,55 270,35 L300,45 L300,100 L0,100 Z"
                    class="text-gray-100 dark:text-gray-700 animate-pulse fill-current"
                    opacity="0.5"
                />
            </svg>
        </div>

        <!-- Doughnut/pie chart variant -->
        <div v-else-if="variant === 'doughnut'" class="flex items-center justify-center h-40">
            <div class="animate-pulse rounded-full h-32 w-32 border-[16px] border-gray-200 dark:border-gray-600" />
        </div>

        <!-- Generic chart placeholder -->
        <div v-else class="flex items-end gap-2 w-full max-w-xs h-40 px-4">
            <div
                v-for="(h, i) in barHeights"
                :key="i"
                class="animate-pulse bg-gray-200 dark:bg-gray-600 rounded-t flex-1"
                :style="{ height: h, animationDelay: `${i * 75}ms` }"
            />
        </div>

        <!-- Axis labels -->
        <div class="flex justify-between w-full max-w-xs px-4 mt-2" v-if="variant !== 'doughnut'">
            <div v-for="i in 5" :key="i" class="animate-pulse rounded h-2 w-6 bg-gray-200 dark:bg-gray-600" />
        </div>

        <!-- Legend -->
        <div v-if="showLegend" class="flex items-center gap-4 mt-3">
            <div v-for="i in legendItems" :key="i" class="flex items-center gap-1.5">
                <div class="animate-pulse rounded-full h-2.5 w-2.5 bg-gray-200 dark:bg-gray-600" />
                <div class="animate-pulse rounded h-2 w-12 bg-gray-200 dark:bg-gray-600" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    /** Chart type hint: 'bar', 'line', 'doughnut' */
    variant?: 'bar' | 'line' | 'doughnut' | 'generic'
    /** Show legend placeholders */
    showLegend?: boolean
    /** Number of legend items */
    legendItems?: number
}>(), {
    variant: 'bar',
    showLegend: true,
    legendItems: 3,
})

// Staggered bar heights for realistic look
const barHeights = computed(() => {
    const heights = ['60%', '85%', '45%', '70%', '55%', '90%', '40%', '75%']
    return heights
})
</script>
