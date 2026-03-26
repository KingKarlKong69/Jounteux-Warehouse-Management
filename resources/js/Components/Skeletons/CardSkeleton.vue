<template>
    <div
        :class="[
            'grid gap-6',
            gridColsClass,
        ]"
        role="status"
        :aria-label="`Loading ${cards} cards`"
        aria-busy="true"
    >
        <div
            v-for="i in cards"
            :key="i"
            class="bg-white dark:bg-surface-850 rounded-lg shadow overflow-hidden"
        >
            <!-- Image placeholder -->
            <div v-if="showImage" class="animate-pulse bg-gray-200 dark:bg-gray-700" :style="{ height: imageHeight }" />

            <!-- Content area -->
            <div class="p-4 space-y-3">
                <!-- Title -->
                <div class="animate-pulse rounded h-4 bg-gray-200 dark:bg-gray-600" :style="{ width: titleWidth(i) }" />

                <!-- Subtitle / secondary text -->
                <div class="animate-pulse rounded h-3 bg-gray-100 dark:bg-gray-700 w-3/4" />

                <!-- Meta row (price + badge etc) -->
                <div v-if="showMeta" class="flex items-center justify-between pt-1">
                    <div class="animate-pulse rounded h-4 w-16 bg-gray-200 dark:bg-gray-600" />
                    <div class="animate-pulse rounded-full h-5 w-20 bg-gray-200 dark:bg-gray-600" />
                </div>

                <!-- Description lines -->
                <div v-if="showDescription" class="space-y-2 pt-1">
                    <div class="animate-pulse rounded h-2.5 bg-gray-100 dark:bg-gray-700 w-full" />
                    <div class="animate-pulse rounded h-2.5 bg-gray-100 dark:bg-gray-700 w-5/6" />
                </div>

                <!-- Action buttons -->
                <div v-if="showActions" class="flex items-center gap-2 pt-2">
                    <div class="animate-pulse rounded h-8 flex-1 bg-gray-200 dark:bg-gray-600" />
                    <div class="animate-pulse rounded h-8 w-8 bg-gray-200 dark:bg-gray-600" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    /** Number of skeleton cards */
    cards?: number
    /** Grid columns (Tailwind responsive classes) */
    gridCols?: string
    /** Show image placeholder area */
    showImage?: boolean
    /** Height of image placeholder */
    imageHeight?: string
    /** Show price/badge meta row */
    showMeta?: boolean
    /** Show description lines */
    showDescription?: boolean
    /** Show action button row */
    showActions?: boolean
}>(), {
    cards: 6,
    gridCols: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    showImage: true,
    imageHeight: '192px',
    showMeta: true,
    showDescription: false,
    showActions: true,
})

const gridColsClass = computed(() => props.gridCols)

const titleWidths = ['72%', '85%', '60%', '78%', '67%', '90%']
const titleWidth = (i: number): string => titleWidths[(i - 1) % titleWidths.length]
</script>
