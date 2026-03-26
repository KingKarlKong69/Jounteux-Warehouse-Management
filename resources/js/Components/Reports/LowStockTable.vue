<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { TableSkeleton } from '@/Components/Skeletons'

const props = defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const isDark = computed(() => theme.value === 'dark')

const { data, loading, error, fetch } = useReport('/api/v1/reports/low-stock-items')

const sortField = ref<'current_stock' | 'name' | 'sku' | 'unit_price'>('current_stock')
const sortDir   = ref<'asc' | 'desc'>('asc')

onMounted(() => fetch({ threshold: 10, sort: sortField.value, direction: sortDir.value }))

const toggleSort = (field: typeof sortField.value) => {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDir.value = 'asc'
    }
    fetch({ threshold: 10, sort: sortField.value, direction: sortDir.value })
}

const sortIcon = (field: string) => {
    if (sortField.value !== field) return '↕'
    return sortDir.value === 'asc' ? '↑' : '↓'
}

const items = computed(() => (data.value as any[] | null) ?? [])
</script>

<template>
    <div class="w-full h-full overflow-auto">
        <TableSkeleton v-if="loading" :columns="5" :rows="8" :show-pagination="false" />
        <div v-else-if="error" class="flex items-center justify-center h-32 text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else-if="items.length === 0" class="flex items-center justify-center h-32 text-gray-400 dark:text-gray-500 text-sm">
            No low-stock items found
        </div>
        <table v-else class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 cursor-pointer select-none hover:text-gray-900 dark:hover:text-gray-100"
                        @click="toggleSort('sku')">
                        SKU {{ sortIcon('sku') }}
                    </th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 cursor-pointer select-none hover:text-gray-900 dark:hover:text-gray-100"
                        @click="toggleSort('name')">
                        Product {{ sortIcon('name') }}
                    </th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Category</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300 cursor-pointer select-none hover:text-gray-900 dark:hover:text-gray-100"
                        @click="toggleSort('current_stock')">
                        Stock {{ sortIcon('current_stock') }}
                    </th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300 cursor-pointer select-none hover:text-gray-900 dark:hover:text-gray-100"
                        @click="toggleSort('unit_price')">
                        Price {{ sortIcon('unit_price') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="item in items" :key="item.id"
                    :class="item.current_stock <= 3 ? 'bg-red-50 dark:bg-red-900/20' : item.current_stock <= 5 ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                    <td class="px-3 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ item.sku }}</td>
                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                        <div class="flex items-center gap-1">
                            <ExclamationTriangleIcon v-if="item.current_stock <= 3" class="h-4 w-4 text-red-500 dark:text-red-400 shrink-0" />
                            {{ item.name }}
                        </div>
                    </td>
                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ item.category_name ?? '—' }}</td>
                    <td class="px-3 py-2 text-right font-semibold"
                        :class="item.current_stock <= 3 ? 'text-red-600 dark:text-red-400' : item.current_stock <= 5 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-800 dark:text-gray-200'">
                        {{ item.current_stock }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                        ₱{{ Number(item.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
