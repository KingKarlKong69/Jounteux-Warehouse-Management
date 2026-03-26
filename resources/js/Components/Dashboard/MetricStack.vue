<script setup lang="ts">
/**
 * MetricStack — Right column supporting stack (Row 3, col-span-4).
 *
 * Structure:
 * 1. Two mini metrics side-by-side (Micro level)
 * 2. One medium metric (Secondary level)
 * 3. Low Stock Details table (Alert intelligence)
 *
 * LowStock slightly elevated in emphasis, not dominance.
 */
import MiniMetricCard from './MiniMetricCard.vue'
import DashboardCard from './DashboardCard.vue'
import LowStockTable from '@/Components/Reports/LowStockTable.vue'
import {
    ExclamationTriangleIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps<{
    summaryData: any
    summaryLoading: boolean
}>()

const getValue = (key: string) => (props.summaryData as any)?.[key]
</script>

<template>
    <div class="flex flex-col gap-4 h-full">
        <!-- ═══ Mini Metrics (2 side-by-side) ═══ -->
        <div class="grid grid-cols-2 gap-4">
            <MiniMetricCard
                label="Low Stock Items"
                :value="getValue('low_stock_count')"
                :loading="summaryLoading"
                :icon="ExclamationTriangleIcon"
                color="bg-red-500"
                accent="text-red-600"
            />
            <MiniMetricCard
                label="Active Users"
                :value="getValue('total_users')"
                :loading="summaryLoading"
                :icon="UserGroupIcon"
                color="bg-violet-500"
            />
        </div>

        <!-- ═══ Medium Metric ═══ -->
        <MiniMetricCard
            label="Total Customers"
            :value="getValue('total_customers')"
            :loading="summaryLoading"
            :icon="UsersIcon"
            color="bg-teal-500"
        />

        <!-- ═══ Low Stock Details (Alert Intelligence) ═══ -->
        <DashboardCard
            card-id="low-stock-details"
            title="Low Stock Details"
            subtitle="Products below threshold"
            :icon="ExclamationTriangleIcon"
            icon-color="bg-red-500"
            navigate-to="/admin/products"
            :expandable="false"
        >
            <template #default>
                <div class="h-[220px]">
                    <LowStockTable :expanded="false" />
                </div>
            </template>
        </DashboardCard>
    </div>
</template>
