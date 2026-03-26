<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useRouteLoading } from '@/composables/useRouteLoading';
import { TableSkeleton, StatCardSkeleton } from '@/Components/Skeletons';
import Pagination from '@/Components/Pagination.vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarDaysIcon,
    BookOpenIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ArrowsRightLeftIcon,
    CubeIcon,
    ChartBarIcon,
    DocumentArrowDownIcon,
    TableCellsIcon,
    DocumentTextIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    entries: Object,
    meta: Object,
    summary: Object,
    chartData: Object,
    filters: Object,
    products: Array,
    users: Array,
});

// ─── Reactive filter state ───
const search = ref(props.filters?.search || '');
const productId = ref(props.filters?.product_id || '');
const referenceType = ref(props.filters?.reference_type || '');
const movementType = ref(props.filters?.movement_type || '');
const userId = ref(props.filters?.user_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const datePreset = ref(props.filters?.date_preset || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');
const perPage = ref(props.filters?.per_page || 20);

// ─── UI state ───
const showChart = ref(false);
const showFilters = ref(false);
const showExportMenu = ref(false);
const { isNavigating } = useRouteLoading();

// ─── Date presets ───
const datePresets = [
    { key: '', label: 'All Time' },
    { key: 'today', label: 'Today' },
    { key: 'this_week', label: 'This Week' },
    { key: 'this_month', label: 'This Month' },
    { key: 'this_year', label: 'This Year' },
];

// ─── Reference types ───
const referenceTypes = [
    { value: '', label: 'All Types' },
    { value: 'purchase', label: 'Purchase Order' },
    { value: 'sale', label: 'Sales Order' },
    { value: 'adjustment', label: 'Adjustment' },
];

// ─── Movement types ───
const movementTypes = [
    { value: '', label: 'All Movements' },
    { value: 'in', label: 'IN' },
    { value: 'out', label: 'OUT' },
];

// ─── Current query params ───
const buildParams = (page = 1) => ({
    search: search.value,
    product_id: productId.value,
    reference_type: referenceType.value,
    movement_type: movementType.value,
    user_id: userId.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    date_preset: datePreset.value,
    sort: sortField.value,
    direction: sortDirection.value,
    per_page: perPage.value,
    page,
});

// ─── Fetch ───
const fetchData = (page = 1) => {
    router.get(route('admin.stock-ledger.index'), buildParams(page), {
        preserveState: true,
        replace: true,
    });
};

// Debounced search
let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchData(1), 400);
});

// Immediate filters
watch([productId, referenceType, movementType, userId, datePreset, dateFrom, dateTo, perPage], () => {
    fetchData(1);
});

// When switching date preset, clear custom range
watch(datePreset, (val) => {
    if (val) {
        dateFrom.value = '';
        dateTo.value = '';
    }
});

// When using custom range, clear preset
watch([dateFrom, dateTo], () => {
    if (dateFrom.value || dateTo.value) {
        datePreset.value = '';
    }
});

const clearFilters = () => {
    search.value = '';
    productId.value = '';
    referenceType.value = '';
    movementType.value = '';
    userId.value = '';
    datePreset.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';
};

// ─── Sorting ───
const toggleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
    fetchData(1);
};

// ─── Export / Report ───
const exportUrl = (type) => {
    const params = new URLSearchParams(buildParams(1));
    if (type === 'csv') return route('admin.stock-ledger.export.csv') + '?' + params.toString();
    if (type === 'excel') return route('admin.stock-ledger.export.excel') + '?' + params.toString();
    if (type === 'report') return route('admin.stock-ledger.report') + '?' + params.toString();
    return '#';
};

// ─── Chart config ───
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                usePointStyle: true,
                padding: 20,
                font: { size: 12, weight: '500' },
            },
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleFont: { size: 13, weight: '600' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
            displayColors: true,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#6b7280' },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(229, 231, 235, 0.5)' },
            ticks: { font: { size: 11 }, color: '#6b7280' },
        },
    },
};

// ─── Movement badge classes ───
const movementBadgeClass = (type) => {
    return type === 'in'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

// ─── Reference type badge ───
const referenceTypeBadgeClass = (type) => {
    return {
        purchase: 'bg-blue-100 text-blue-800',
        sale: 'bg-amber-100 text-amber-800',
        adjustment: 'bg-purple-100 text-purple-800',
    }[type] || 'bg-gray-100 text-gray-800';
};

// Pagination
const goToPage = (page) => {
    fetchData(page);
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return search.value || productId.value || referenceType.value ||
           movementType.value || userId.value || dateFrom.value ||
           dateTo.value || datePreset.value;
});
</script>

<template>
    <Head title="Stock Ledger" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <BookOpenIcon class="h-8 w-8 mr-3 text-indigo-600" />
                        Stock Ledger
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Immutable inventory movement history. Read-only audit-grade ledger.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">
                            Total Entries: <span class="font-semibold text-gray-900">{{ meta?.total || 0 }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skeleton Loading State -->
        <template v-if="isNavigating">
            <StatCardSkeleton :cards="4" grid-cols="grid-cols-1 sm:grid-cols-2 lg:grid-cols-4" class="mb-6" />
            <TableSkeleton :columns="8" :rows="10" :show-actions="false" :badge-columns="[2, 4]" :show-pagination="true" />
        </template>

        <template v-else>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total IN -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total IN</p>
                        <p class="mt-2 text-2xl font-bold text-green-600">{{ summary?.total_in?.toLocaleString() ?? 0 }}</p>
                    </div>
                    <div class="flex-shrink-0 p-3 bg-green-50 rounded-lg">
                        <ArrowTrendingUpIcon class="h-6 w-6 text-green-600" />
                    </div>
                </div>
            </div>

            <!-- Total OUT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total OUT</p>
                        <p class="mt-2 text-2xl font-bold text-red-600">{{ summary?.total_out?.toLocaleString() ?? 0 }}</p>
                    </div>
                    <div class="flex-shrink-0 p-3 bg-red-50 rounded-lg">
                        <ArrowTrendingDownIcon class="h-6 w-6 text-red-600" />
                    </div>
                </div>
            </div>

            <!-- Net Movement -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Movement</p>
                        <p class="mt-2 text-2xl font-bold" :class="(summary?.net_movement ?? 0) >= 0 ? 'text-blue-600' : 'text-orange-600'">
                            {{ (summary?.net_movement ?? 0) >= 0 ? '+' : '' }}{{ summary?.net_movement?.toLocaleString() ?? 0 }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 p-3 bg-blue-50 rounded-lg">
                        <ArrowsRightLeftIcon class="h-6 w-6 text-blue-600" />
                    </div>
                </div>
            </div>

            <!-- Current Stock (only when single product filtered) -->
            <div v-if="summary?.current_stock !== null && summary?.current_stock !== undefined"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock</p>
                        <p class="mt-2 text-2xl font-bold text-purple-600">{{ summary.current_stock?.toLocaleString() ?? 0 }}</p>
                    </div>
                    <div class="flex-shrink-0 p-3 bg-purple-50 rounded-lg">
                        <CubeIcon class="h-6 w-6 text-purple-600" />
                    </div>
                </div>
            </div>

            <!-- Placeholder when no current stock (keep grid alignment) -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 border-dashed p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Current Stock</p>
                        <p class="mt-2 text-sm text-gray-400">Filter by product to see</p>
                    </div>
                    <div class="flex-shrink-0 p-3 bg-gray-50 rounded-lg">
                        <CubeIcon class="h-6 w-6 text-gray-300" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Chart (Collapsible) -->
        <div class="mb-6">
            <button
                @click="showChart = !showChart"
                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition"
                :class="showChart
                    ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm'"
            >
                <ChartBarIcon class="h-4 w-4 mr-2" />
                {{ showChart ? 'Hide Analytics' : 'Show Analytics' }}
                <component :is="showChart ? ChevronUpIcon : ChevronDownIcon" class="h-4 w-4 ml-2" />
            </button>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-[500px]"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 max-h-[500px]"
                leave-to-class="opacity-0 max-h-0"
            >
                <div v-show="showChart" class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                        Stock Movement Trends
                    </h3>
                    <div v-if="chartData?.labels?.length > 0" style="height: 300px;">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                    <div v-else class="flex items-center justify-center h-48 text-gray-400">
                        <div class="text-center">
                            <ChartBarIcon class="mx-auto h-10 w-10 mb-2 text-gray-300" />
                            <p class="text-sm">No chart data available for the current filters.</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Search & Filters Bar -->
        <div class="mb-6 bg-white shadow-sm rounded-xl border border-gray-200 p-4">
            <!-- Primary row: search + date preset + toggle -->
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by SKU, product name, reference (PO-0001, SO-0005)..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    />
                </div>

                <!-- Date Preset -->
                <div class="flex items-center gap-2">
                    <CalendarDaysIcon class="h-5 w-5 text-gray-400 flex-shrink-0" />
                    <select
                        v-model="datePreset"
                        class="px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                    >
                        <option v-for="p in datePresets" :key="p.key" :value="p.key">{{ p.label }}</option>
                    </select>
                </div>

                <!-- Custom Date Range -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <span class="text-gray-400 text-sm">to</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Toggle advanced filters -->
                <button
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center px-3 py-2.5 text-sm font-medium border rounded-lg transition"
                    :class="showFilters || hasActiveFilters
                        ? 'text-indigo-700 bg-indigo-50 border-indigo-300'
                        : 'text-gray-600 bg-white border-gray-300 hover:bg-gray-50'"
                >
                    <FunnelIcon class="h-4 w-4 mr-1.5" />
                    Filters
                    <span v-if="hasActiveFilters" class="ml-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-xs text-white">!</span>
                </button>

                <!-- Clear Filters -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                    <XMarkIcon class="h-4 w-4 mr-1" />
                    Clear
                </button>

                <!-- Export Dropdown -->
                <div class="relative">
                    <button
                        @click="showExportMenu = !showExportMenu"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition"
                    >
                        <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
                        Export
                        <ChevronDownIcon class="h-4 w-4 ml-2" />
                    </button>
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div v-if="showExportMenu"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                            @mouseleave="showExportMenu = false"
                        >
                            <a :href="exportUrl('csv')"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <TableCellsIcon class="h-4 w-4 mr-3 text-green-600" />
                                Export CSV
                            </a>
                            <a :href="exportUrl('excel')"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <DocumentTextIcon class="h-4 w-4 mr-3 text-blue-600" />
                                Export Excel
                            </a>
                            <hr class="my-1 border-gray-100" />
                            <a :href="exportUrl('report')"
                               target="_blank"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <DocumentArrowDownIcon class="h-4 w-4 mr-3 text-indigo-600" />
                                Generate PDF Report
                            </a>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Advanced Filters Row (collapsible) -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-40"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 max-h-40"
                leave-to-class="opacity-0 max-h-0"
            >
                <div v-show="showFilters" class="mt-4 pt-4 border-t border-gray-200 overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Product -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Product</label>
                            <select
                                v-model="productId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                            >
                                <option value="">All Products</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.label }}</option>
                            </select>
                        </div>

                        <!-- Reference Type -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Reference Type</label>
                            <select
                                v-model="referenceType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                            >
                                <option v-for="t in referenceTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>

                        <!-- Movement Type -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Movement</label>
                            <select
                                v-model="movementType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                            >
                                <option v-for="m in movementTypes" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>

                        <!-- User -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Processed By</label>
                            <select
                                v-model="userId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                            >
                                <option value="">All Users</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none"
                                @click="toggleSort('created_at')"
                            >
                                <div class="flex items-center gap-1">
                                    Date
                                    <template v-if="sortField === 'created_at'">
                                        <ArrowUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3 text-indigo-600" />
                                        <ArrowDownIcon v-else class="h-3 w-3 text-indigo-600" />
                                    </template>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none"
                                @click="toggleSort('reference_type')"
                            >
                                <div class="flex items-center gap-1">
                                    Type
                                    <template v-if="sortField === 'reference_type'">
                                        <ArrowUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3 text-indigo-600" />
                                        <ArrowDownIcon v-else class="h-3 w-3 text-indigo-600" />
                                    </template>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reference
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none"
                                @click="toggleSort('movement_type')"
                            >
                                <div class="flex items-center gap-1">
                                    Movement
                                    <template v-if="sortField === 'movement_type'">
                                        <ArrowUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3 text-indigo-600" />
                                        <ArrowDownIcon v-else class="h-3 w-3 text-indigo-600" />
                                    </template>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none"
                                @click="toggleSort('quantity')"
                            >
                                <div class="flex items-center justify-end gap-1">
                                    Quantity
                                    <template v-if="sortField === 'quantity'">
                                        <ArrowUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3 text-indigo-600" />
                                        <ArrowDownIcon v-else class="h-3 w-3 text-indigo-600" />
                                    </template>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none"
                                @click="toggleSort('balance_after')"
                            >
                                <div class="flex items-center justify-end gap-1">
                                    Balance After
                                    <template v-if="sortField === 'balance_after'">
                                        <ArrowUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3 text-indigo-600" />
                                        <ArrowDownIcon v-else class="h-3 w-3 text-indigo-600" />
                                    </template>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Processed By
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="entry in entries?.data"
                            :key="entry.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span :title="entry.created_at_formatted">{{ entry.created_at_formatted }}</span>
                            </td>

                            <!-- Product -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ entry.product_name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ entry.product_sku }}</div>
                            </td>

                            <!-- Reference Type -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="referenceTypeBadgeClass(entry.reference_type)"
                                >
                                    {{ entry.reference_type_label }}
                                </span>
                            </td>

                            <!-- Reference -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a v-if="entry.reference_url"
                                   :href="entry.reference_url"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline"
                                >
                                    {{ entry.reference_label }}
                                </a>
                                <span v-else class="text-gray-400">{{ entry.reference_label }}</span>
                            </td>

                            <!-- Movement -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="movementBadgeClass(entry.movement_type)"
                                >
                                    {{ entry.movement_label }}
                                </span>
                            </td>

                            <!-- Quantity -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold"
                                :class="entry.movement_type === 'in' ? 'text-green-700' : 'text-red-700'"
                            >
                                {{ entry.movement_type === 'in' ? '+' : '-' }}{{ entry.quantity?.toLocaleString() }}
                            </td>

                            <!-- Balance After -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ entry.balance_after?.toLocaleString() }}
                            </td>

                            <!-- Processed By -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ entry.created_by_name }}
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!entries?.data || entries.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <BookOpenIcon class="mx-auto h-12 w-12 text-gray-300" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No ledger entries found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Try adjusting your search or filter criteria.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="meta" label="entries" @navigate="goToPage" />
        </div>
        </template><!-- /skeleton else -->

        <!-- Immutability Notice -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 text-center">
                <BookOpenIcon class="inline h-4 w-4 mr-1 text-gray-400" />
                Stock ledger entries are immutable. No create, edit, or delete actions are permitted.
                All movements are system-generated from Purchase Orders, Sales Orders, and Adjustments.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
