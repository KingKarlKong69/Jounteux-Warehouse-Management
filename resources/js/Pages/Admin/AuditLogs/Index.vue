<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarDaysIcon,
    ClockIcon,
    ShieldExclamationIcon,
    EyeIcon,
    XCircleIcon,
    TagIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
    meta: Object,
    filters: Object,
    actionTabs: Array,
    resourceTypes: Array,
});

// ─── Reactive filter state ───
const search = ref(props.filters?.search || '');
const activeTab = ref(props.filters?.action || 'all');
const resourceType = ref(props.filters?.resource_type || '');
const datePreset = ref(props.filters?.date_preset || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const perPage = ref(props.filters?.per_page || 20);

// Detail modal state
const showDetail = ref(false);
const selectedLog = ref(null);

// ─── Tab configuration ───
const tabs = [
    { key: 'all', label: 'All' },
    { key: 'login', label: 'Login' },
    { key: 'logout', label: 'Logout' },
    { key: 'create', label: 'Create' },
    { key: 'update', label: 'Update' },
    { key: 'archive', label: 'Archive' },
    { key: 'delete', label: 'Delete' },
    { key: 'blocked', label: 'Blocked' },
];

// ─── Date presets ───
const datePresets = [
    { key: '', label: 'All Time' },
    { key: 'daily', label: 'Today' },
    { key: 'weekly', label: 'This Week' },
    { key: 'monthly', label: 'This Month' },
    { key: 'yearly', label: 'This Year' },
];

// ─── Fetch (debounced for search, immediate for tabs/dates) ───
const fetchLogs = (page = 1) => {
    router.get(route('admin.audit-logs'), {
        search: search.value,
        action: activeTab.value,
        resource_type: resourceType.value,
        date_preset: datePreset.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        per_page: perPage.value,
        page,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Debounced search
let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchLogs(1), 400);
});

// Immediate filters
watch([activeTab, resourceType, datePreset, dateFrom, dateTo, perPage], () => {
    fetchLogs(1);
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

const hasActiveFilters = computed(() => {
    return search.value || activeTab.value !== 'all' || resourceType.value || datePreset.value || dateFrom.value || dateTo.value;
});

const clearFilters = () => {
    search.value = '';
    activeTab.value = 'all';
    resourceType.value = '';
    datePreset.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const openDetail = (log) => {
    selectedLog.value = log;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
    selectedLog.value = null;
};

// ─── Event label badge color mapping ───
const eventLabelBadgeClass = (color) => {
    const map = {
        green: 'bg-green-100 text-green-800 ring-green-600/20',
        gray: 'bg-gray-100 text-gray-700 ring-gray-500/20',
        blue: 'bg-blue-100 text-blue-800 ring-blue-600/20',
        amber: 'bg-amber-100 text-amber-800 ring-amber-600/20',
        orange: 'bg-orange-100 text-orange-800 ring-orange-600/20',
        red: 'bg-red-100 text-red-800 ring-red-600/20',
        emerald: 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        yellow: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
        sky: 'bg-sky-100 text-sky-800 ring-sky-600/20',
        indigo: 'bg-indigo-100 text-indigo-800 ring-indigo-600/20',
    };
    return map[color] || 'bg-gray-100 text-gray-700 ring-gray-500/20';
};

// ─── Action badge color mapping ───
const actionBadgeClass = (color) => {
    const map = {
        green: 'bg-green-50 text-green-700',
        gray: 'bg-gray-50 text-gray-600',
        blue: 'bg-blue-50 text-blue-700',
        amber: 'bg-amber-50 text-amber-700',
        orange: 'bg-orange-50 text-orange-700',
        red: 'bg-red-50 text-red-700',
        emerald: 'bg-emerald-50 text-emerald-700',
        yellow: 'bg-yellow-50 text-yellow-700',
    };
    return map[color] || 'bg-gray-50 text-gray-600';
};

// ─── Format change value for display ───
const formatValue = (val) => {
    if (val === null || val === undefined) return '—';
    if (val === true) return 'Yes';
    if (val === false) return 'No';
    if (typeof val === 'number') return val.toLocaleString();
    return String(val);
};

// ─── Pagination ───
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
    if (page < 1 || page > lastPage.value || page === currentPage.value || page === '...') return;
    fetchLogs(page);
};
</script>

<template>
    <Head title="Audit Logs" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <ShieldExclamationIcon class="h-8 w-8 mr-3 text-indigo-600" />
                        Audit Logs
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Track all user activities and system events. Read-only for compliance auditing.
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">
                        Total Records: <span class="font-semibold text-gray-900">{{ meta?.total || 0 }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Tabs -->
        <div class="mb-4 border-b border-gray-200">
            <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Action Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'whitespace-nowrap border-b-2 py-3 px-3 text-sm font-medium transition-colors duration-200',
                        activeTab === tab.key
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Search & Filters Bar -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by user, action, event, resource, IP..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Resource Type Filter -->
                <div class="flex items-center gap-2">
                    <FunnelIcon class="h-5 w-5 text-gray-400 flex-shrink-0" />
                    <select
                        v-model="resourceType"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                    >
                        <option v-for="rt in (resourceTypes || [])" :key="rt.value" :value="rt.value">{{ rt.label }}</option>
                    </select>
                </div>

                <!-- Date Preset Dropdown -->
                <div class="flex items-center gap-2">
                    <CalendarDaysIcon class="h-5 w-5 text-gray-400 flex-shrink-0" />
                    <select
                        v-model="datePreset"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white text-sm"
                    >
                        <option v-for="p in datePresets" :key="p.key" :value="p.key">{{ p.label }}</option>
                    </select>
                </div>

                <!-- Custom Date Range -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="From"
                    />
                    <span class="text-gray-400 text-sm">to</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="To"
                    />
                </div>

                <!-- Clear Filters -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                    <XMarkIcon class="h-4 w-4 mr-1" />
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Event
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Resource
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                IP Address
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="log in logs?.data"
                            :key="log.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <!-- User -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold"
                                        :class="log.user_role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700'"
                                    >
                                        {{ (log.user_name || '?')[0]?.toUpperCase() }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ log.user_name }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ log.user_role }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Event (action + event_label) -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset w-fit"
                                        :class="eventLabelBadgeClass(log.event_label_color)"
                                    >
                                        {{ log.event_label }}
                                    </span>
                                    <span v-if="log.event_label !== log.action_label" class="text-xs text-gray-400">
                                        {{ log.action_label }}
                                    </span>
                                </div>
                            </td>

                            <!-- Resource -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span v-if="log.resource_label" class="text-gray-900 font-medium">
                                    {{ log.resource_label }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>

                            <!-- IP Address -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ log.ip_address || '—' }}
                            </td>

                            <!-- Timestamp -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center">
                                    <ClockIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    <span :title="log.created_at_formatted">{{ log.created_at_human }}</span>
                                </div>
                            </td>

                            <!-- Details -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button
                                    @click="openDetail(log)"
                                    class="text-indigo-600 hover:text-indigo-800 inline-flex items-center"
                                >
                                    <EyeIcon class="h-4 w-4 mr-1" />
                                    View
                                </button>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!logs?.data || logs.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <ShieldExclamationIcon class="mx-auto h-12 w-12 text-gray-300" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No audit logs found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Try adjusting your search or filter criteria.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs?.data?.length > 0 && lastPage > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ meta.from }}</span> to <span class="font-medium">{{ meta.to }}</span> of <span class="font-medium">{{ meta.total }}</span> logs
                    </p>

                    <nav class="flex items-center gap-1">
                        <!-- Previous -->
                        <button
                            v-if="currentPage > 1"
                            @click="goToPage(currentPage - 1)"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                        >
                            <ChevronLeftIcon class="h-4 w-4 mr-1" />
                            Prev
                        </button>

                        <!-- Page numbers -->
                        <template v-for="(page, index) in paginationPages" :key="index">
                            <span
                                v-if="page === '...'"
                                class="px-3 py-2 text-sm text-gray-500 select-none"
                            >
                                &hellip;
                            </span>
                            <button
                                v-else
                                @click="goToPage(page)"
                                :class="[
                                    'px-3.5 py-2 text-sm font-medium border rounded-md transition',
                                    page === currentPage
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                        </template>

                        <!-- Next -->
                        <button
                            v-if="currentPage < lastPage"
                            @click="goToPage(currentPage + 1)"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                        >
                            Next
                            <ChevronRightIcon class="h-4 w-4 ml-1" />
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDetail" class="fixed inset-0 z-50 flex items-center justify-center">
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50" @click="closeDetail"></div>

                    <!-- Modal -->
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showDetail" class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[85vh] flex flex-col">
                            <!-- Header -->
                            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900 flex items-center">
                                        <EyeIcon class="h-5 w-5 mr-2 text-indigo-600" />
                                        Audit Log #{{ selectedLog?.id }}
                                    </h2>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset"
                                            :class="eventLabelBadgeClass(selectedLog?.event_label_color)"
                                        >
                                            {{ selectedLog?.event_label }}
                                        </span>
                                        <span v-if="selectedLog?.resource_label" class="text-sm text-gray-500">
                                            on {{ selectedLog.resource_label }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="closeDetail" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                                    <XCircleIcon class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="p-6 overflow-y-auto space-y-5">
                                <!-- Info Grid -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">User</p>
                                        <p class="text-sm font-medium text-gray-900">{{ selectedLog?.user_name }} (#{{ selectedLog?.user_id }})</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Role</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="selectedLog?.user_role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-sky-100 text-sky-800'"
                                        >
                                            {{ selectedLog?.user_role }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Action Type</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="actionBadgeClass(selectedLog?.action_color)"
                                        >
                                            {{ selectedLog?.action_label }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">IP Address</p>
                                        <p class="text-sm font-mono text-gray-900">{{ selectedLog?.ip_address || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Resource</p>
                                        <p class="text-sm text-gray-900">
                                            {{ selectedLog?.resource_label || '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase">Timestamp</p>
                                        <p class="text-sm text-gray-900">{{ selectedLog?.created_at_formatted }}</p>
                                    </div>
                                </div>

                                <!-- Structured Change Summary -->
                                <div v-if="selectedLog?.change_summary && selectedLog.change_summary.length > 0">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-3">Changes</p>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Previous</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">New</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100">
                                                <tr v-for="(change, idx) in selectedLog.change_summary" :key="idx">
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-700">{{ change.label }}</td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span v-if="change.old !== null && change.old !== undefined" class="text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                                            {{ formatValue(change.old) }}
                                                        </span>
                                                        <span v-else class="text-gray-400">—</span>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <span v-if="change.new !== null && change.new !== undefined" class="text-green-600 bg-green-50 px-1.5 py-0.5 rounded">
                                                            {{ formatValue(change.new) }}
                                                        </span>
                                                        <span v-else class="text-gray-400">—</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Old Values (raw fallback) -->
                                <div v-if="selectedLog?.old_values && (!selectedLog?.change_summary || selectedLog.change_summary.length === 0)">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">Previous Values</p>
                                    <pre class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800 overflow-x-auto max-h-48">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                                </div>

                                <!-- New Values (raw fallback) -->
                                <div v-if="selectedLog?.new_values && (!selectedLog?.change_summary || selectedLog.change_summary.length === 0)">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">New Values</p>
                                    <pre class="bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-800 overflow-x-auto max-h-48">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                                </div>

                                <!-- Metadata -->
                                <div v-if="selectedLog?.metadata && Object.keys(selectedLog.metadata).length > 0">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">Metadata</p>
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                                            <template v-for="(value, key) in selectedLog.metadata" :key="key">
                                                <template v-if="key !== 'changes'">
                                                    <dt class="text-xs font-medium text-gray-500 capitalize">{{ String(key).replace(/_/g, ' ') }}</dt>
                                                    <dd class="text-xs text-gray-800">{{ formatValue(value) }}</dd>
                                                </template>
                                            </template>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="p-4 border-t border-gray-200 flex justify-end">
                                <button
                                    @click="closeDetail"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
