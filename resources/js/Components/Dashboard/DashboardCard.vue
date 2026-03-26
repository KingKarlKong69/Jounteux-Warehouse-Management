<script setup lang="ts">
/**
 * DashboardCard — Slot-aware card wrapper for the analytics dashboard.
 *
 * Features:
 * - Consistent header with icon, title, subtitle
 * - Expand/collapse toggle (expansion-reactive)
 * - Navigate-to-module link
 * - Swap with peer slot button
 * - Smooth transitions
 * - No resize handles (span is governed by store)
 */
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDashboardLayoutStore } from '@/stores/dashboardLayout'
import {
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    ArrowTopRightOnSquareIcon,
    ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'

const props = withDefaults(defineProps<{
    cardId: string
    title: string
    subtitle?: string
    icon: any
    iconColor: string
    navigateTo?: string
    expandable?: boolean
    swappable?: boolean
}>(), {
    expandable: true,
    swappable: false,
})

const emit = defineEmits<{
    (e: 'swap'): void
}>()

const store = useDashboardLayoutStore()
const isExpanded = computed(() => store.expandedCardId === props.cardId)

const toggleExpand = () => {
    if (props.expandable) {
        store.toggleExpand(props.cardId)
    }
}

const navigate = () => {
    if (props.navigateTo) router.visit(props.navigateTo)
}
</script>

<template>
    <div
        class="bg-white rounded-2xl shadow-sm border border-gray-200/60 flex flex-col overflow-hidden group relative"
        :class="[
            isExpanded
                ? 'ring-2 ring-indigo-100 shadow-lg'
                : 'hover:shadow-md',
            'transition-all duration-300 ease-out',
        ]"
    >
        <!-- ═══ Card Header ═══ -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white shrink-0">
            <div
                class="flex items-center gap-3 min-w-0 flex-1"
                :class="navigateTo ? 'cursor-pointer' : ''"
                @click="navigate"
            >
                <div
                    class="shrink-0 flex items-center justify-center w-9 h-9 rounded-xl shadow-sm"
                    :class="iconColor"
                >
                    <component :is="icon" class="h-5 w-5 text-white" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ title }}</h3>
                    <p v-if="subtitle" class="text-xs text-gray-400 truncate">{{ subtitle }}</p>
                </div>
            </div>

            <div class="flex items-center gap-0.5 shrink-0">
                <!-- Swap with peer -->
                <button
                    v-if="swappable"
                    @click="emit('swap')"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors opacity-0 group-hover:opacity-100"
                    title="Swap with peer"
                >
                    <ArrowsRightLeftIcon class="h-4 w-4" />
                </button>

                <!-- Navigate -->
                <button
                    v-if="navigateTo"
                    @click="navigate"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                    title="Open module"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                </button>

                <!-- Expand/Collapse -->
                <button
                    v-if="expandable"
                    @click="toggleExpand"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                    :title="isExpanded ? 'Collapse' : 'Expand'"
                >
                    <ArrowsPointingInIcon v-if="isExpanded" class="h-4 w-4" />
                    <ArrowsPointingOutIcon v-else class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- ═══ Card Content ═══ -->
        <div class="flex-1 p-4 min-h-0 overflow-hidden">
            <slot :expanded="isExpanded" />
        </div>
    </div>
</template>
