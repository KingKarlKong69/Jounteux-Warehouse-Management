<script setup>
import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

/**
 * Unified Pagination component.
 *
 * Props:
 *   meta  — { current_page, last_page, from, to, total }  (Laravel resource meta)
 *   label — (optional) noun for "Showing X to Y of Z <label>" (default: "results")
 *
 * Emits:
 *   navigate(page: number) — parent handles the actual router.get / fetch
 */
const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    label: {
        type: String,
        default: 'results',
    },
});

const emit = defineEmits(['navigate']);

const currentPage = computed(() => props.meta?.current_page || 1);
const lastPage = computed(() => props.meta?.last_page || 1);
const maxVisible = 5;

const paginationPages = computed(() => {
    const total = lastPage.value;
    const current = currentPage.value;
    if (total <= maxVisible + 2) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [];
    const half = Math.floor(maxVisible / 2);
    let start = Math.max(2, current - half);
    let end = Math.min(total - 1, current + half);
    if (current <= half + 2) { start = 2; end = Math.min(total - 1, maxVisible); }
    if (current >= total - half - 1) { start = Math.max(2, total - maxVisible + 1); end = total - 1; }
    pages.push(1);
    if (start > 2) pages.push('...');
    for (let i = start; i <= end; i++) pages.push(i);
    if (end < total - 1) pages.push('...');
    if (total > 1) pages.push(total);
    return pages;
});

const goToPage = (page) => {
    if (page === '...' || page < 1 || page > lastPage.value || page === currentPage.value) return;
    emit('navigate', page);
};
</script>

<template>
    <div v-if="lastPage > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <!-- Mobile: Previous / Next only -->
        <div class="flex justify-between sm:hidden">
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition"
            >
                <ChevronLeftIcon class="h-4 w-4 mr-1" /> Previous
            </button>
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition"
            >
                Next <ChevronRightIcon class="h-4 w-4 ml-1" />
            </button>
        </div>

        <!-- Desktop: Full pagination -->
        <div class="hidden sm:flex sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700">
                Showing <span class="font-medium">{{ meta.from }}</span> to
                <span class="font-medium">{{ meta.to }}</span> of
                <span class="font-medium">{{ meta.total }}</span> {{ label }}
            </p>
            <nav class="flex items-center gap-1">
                <!-- Previous -->
                <button
                    v-if="currentPage > 1"
                    @click="goToPage(currentPage - 1)"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                >
                    <ChevronLeftIcon class="h-4 w-4 mr-1" /> Previous
                </button>

                <!-- Page numbers -->
                <template v-for="(page, index) in paginationPages" :key="index">
                    <span
                        v-if="page === '...'"
                        class="px-3 py-2 text-sm text-gray-500 select-none"
                    >&hellip;</span>
                    <button
                        v-else
                        @click="goToPage(page)"
                        :class="[
                            'px-3.5 py-2 text-sm font-medium border rounded-md transition',
                            page === currentPage
                                ? 'bg-customOrange text-white border-customOrange shadow-sm'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                        ]"
                    >{{ page }}</button>
                </template>

                <!-- Next -->
                <button
                    v-if="currentPage < lastPage"
                    @click="goToPage(currentPage + 1)"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                >
                    Next <ChevronRightIcon class="h-4 w-4 ml-1" />
                </button>
            </nav>
        </div>
    </div>
</template>
