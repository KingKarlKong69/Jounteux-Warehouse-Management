<template>
    <div
        class="bg-white dark:bg-surface-850 shadow rounded-lg overflow-hidden"
        role="status"
        :aria-label="`Loading table with ${rows} rows`"
        aria-busy="true"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <!-- Header row -->
                <thead class="bg-gray-50 dark:bg-surface-800">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col"
                            class="px-6 py-3"
                        >
                            <div class="animate-pulse rounded h-3 bg-gray-200 dark:bg-gray-600" :style="{ width: headerWidth(col) }" />
                        </th>
                    </tr>
                </thead>
                <!-- Body rows -->
                <tbody class="bg-white dark:bg-surface-850 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="row in rows" :key="row">
                        <td
                            v-for="(col, ci) in columns"
                            :key="ci"
                            class="px-6 py-4 whitespace-nowrap"
                        >
                            <!-- Avatar + text cell (first column option) -->
                            <div v-if="ci === 0 && showAvatar" class="flex items-center gap-3">
                                <div class="animate-pulse rounded-full h-8 w-8 bg-gray-200 dark:bg-gray-600 shrink-0" />
                                <div class="space-y-1.5 flex-1">
                                    <div class="animate-pulse rounded h-3 bg-gray-200 dark:bg-gray-600" :style="{ width: cellWidth(ci, row) }" />
                                    <div class="animate-pulse rounded h-2.5 bg-gray-100 dark:bg-gray-700" style="width: 60%" />
                                </div>
                            </div>
                            <!-- Badge cell -->
                            <div v-else-if="badgeColumns.includes(ci)" class="flex">
                                <div class="animate-pulse rounded-full h-5 bg-gray-200 dark:bg-gray-600" :style="{ width: '72px' }" />
                            </div>
                            <!-- Action cell (last column option) -->
                            <div v-else-if="ci === columns - 1 && showActions" class="flex items-center gap-2">
                                <div class="animate-pulse rounded h-7 w-7 bg-gray-200 dark:bg-gray-600" />
                                <div class="animate-pulse rounded h-7 w-7 bg-gray-200 dark:bg-gray-600" />
                            </div>
                            <!-- Normal text cell -->
                            <div v-else class="animate-pulse rounded h-3 bg-gray-200 dark:bg-gray-600" :style="{ width: cellWidth(ci, row) }" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination skeleton -->
        <div v-if="showPagination" class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="animate-pulse rounded h-3 bg-gray-200 dark:bg-gray-600 w-40" />
            <div class="flex items-center gap-1">
                <div v-for="i in 5" :key="i" class="animate-pulse rounded h-8 w-8 bg-gray-200 dark:bg-gray-600" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    /** Number of table columns */
    columns?: number
    /** Number of skeleton rows */
    rows?: number
    /** Show avatar circle on first column */
    showAvatar?: boolean
    /** Show action button placeholders on last column */
    showActions?: boolean
    /** Column indices that should render as badge pills */
    badgeColumns?: number[]
    /** Show pagination skeleton below table */
    showPagination?: boolean
}>(), {
    columns: 6,
    rows: 8,
    showAvatar: false,
    showActions: true,
    badgeColumns: () => [],
    showPagination: true,
})

// Vary widths for visual realism — deterministic per cell
const headerWidths = ['64%', '72%', '56%', '60%', '68%', '52%', '48%', '70%']
const cellWidths  = ['80%', '65%', '75%', '55%', '70%', '60%', '85%', '50%']

const headerWidth = (col: number): string =>
    headerWidths[col % headerWidths.length]

const cellWidth = (col: number, row: number): string =>
    cellWidths[(col + row) % cellWidths.length]
</script>
