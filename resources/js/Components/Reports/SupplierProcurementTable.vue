<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useReport } from '@/composables/useReport'
import { useTheme } from '@/composables/useTheme'
import { TableSkeleton } from '@/Components/Skeletons'

const props = defineProps<{ expanded: boolean }>()

const { theme } = useTheme()
const isDark = computed(() => theme.value === 'dark')

const { data, loading, error, fetch } = useReport('/api/v1/reports/supplier-procurement')

onMounted(() => fetch())

const items = computed(() => (data.value as any[] | null) ?? [])
</script>

<template>
    <div class="w-full h-full overflow-auto">
        <TableSkeleton v-if="loading" :columns="5" :rows="8" :show-pagination="false" />
        <div v-else-if="error" class="flex items-center justify-center h-32 text-red-500 text-sm">
            {{ error }}
        </div>
        <div v-else-if="items.length === 0" class="flex items-center justify-center h-32 text-gray-400 dark:text-gray-500 text-sm">
            No supplier data available
        </div>
        <table v-else class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Supplier</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Contact</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total POs</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total Amount</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Last Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-200">{{ item.company_name }}</td>
                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ item.contact_person ?? '—' }}</td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ item.total_orders }}</td>
                    <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">
                        ₱{{ Number(item.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-500 dark:text-gray-400 text-xs">{{ item.last_order_date ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
