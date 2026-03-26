<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    ArrowPathIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpDownIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    XMarkIcon,
    TrashIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    XCircleIcon,
    TruckIcon,
    CogIcon,
    ExclamationTriangleIcon,
    FunnelIcon,
    CubeIcon,
    ShoppingCartIcon,
} from '@heroicons/vue/24/outline';
import { useNotification } from '@/composables/useNotification';

const props = defineProps({
    salesOrders: Object,
    filters: Object,
    customers: Array,
    categories: Array,
    statuses: Array,
    staffRestricted: Boolean,
});

// ─── Table Filters ───
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');

// Notification system
const { success, error } = useNotification();

// ─── Create SO Modal State ───
const showCreateModal = ref(false);
const createForm = useForm({
    customer_id: '',
    order_date: new Date().toISOString().split('T')[0],
    delivery_date: '',
    notes: '',
    items: [],
});

// ─── Product Search State (inside Create Modal) ───
const productSearch = ref('');
const productCategoryFilter = ref('');
const productSortField = ref('name');
const productSortDirection = ref('asc');
const searchedProducts = ref([]);
const productSearchLoading = ref(false);
const hasSearched = ref(false);

// ─── View SO Modal ───
const showViewModal = ref(false);
const viewingSO = ref(null);
const viewLoading = ref(false);

// ─── Status Update Modal (reject only for staff) ───
const showStatusModal = ref(false);
const statusForm = useForm({ status: '', notes: '' });
const statusSO = ref(null);

// ═══════════════════════════════════════════════
// TABLE FILTER LOGIC
// ═══════════════════════════════════════════════
let searchTimeout;
watch([search, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

const applyFilters = () => {
    router.get(route('staff.sales-orders.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

const toggleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const getSortIcon = (field) => {
    if (sortField.value !== field) return ChevronUpDownIcon;
    return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon;
};

// ═══════════════════════════════════════════════
// PRODUCT SEARCH API (for Create SO Modal)
// ═══════════════════════════════════════════════
let productSearchTimeout;

const fetchProducts = async () => {
    productSearchLoading.value = true;
    hasSearched.value = true;
    try {
        const params = new URLSearchParams();
        if (productSearch.value) params.append('search', productSearch.value);
        if (productCategoryFilter.value) params.append('category_id', productCategoryFilter.value);
        params.append('sort', productSortField.value);
        params.append('direction', productSortDirection.value);

        const response = await fetch(`${route('staff.products.search')}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        searchedProducts.value = data.products || [];
    } catch (e) {
        console.error('Product search failed:', e);
        searchedProducts.value = [];
    } finally {
        productSearchLoading.value = false;
    }
};

watch(productSearch, () => {
    clearTimeout(productSearchTimeout);
    productSearchTimeout = setTimeout(() => fetchProducts(), 300);
});

watch([productCategoryFilter, productSortField, productSortDirection], () => {
    fetchProducts();
});

// ═══════════════════════════════════════════════
// CREATE SO FUNCTIONS
// ═══════════════════════════════════════════════
const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.order_date = new Date().toISOString().split('T')[0];
    createForm.delivery_date = '';
    createForm.items = [];
    productSearch.value = '';
    productCategoryFilter.value = '';
    productSortField.value = 'name';
    productSortDirection.value = 'asc';
    searchedProducts.value = [];
    hasSearched.value = false;
    showCreateModal.value = true;
    nextTick(() => fetchProducts());
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const isProductInOrder = (productId) => {
    return createForm.items.some(item => item.product_id === productId);
};

const addProductToOrder = (product) => {
    if (isProductInOrder(product.id)) return;
    createForm.items.push({
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        current_stock: product.current_stock,
        quantity: 1,
        unit_price: parseFloat(product.unit_price) || 0,
    });
};

const removeItem = (index) => {
    createForm.items.splice(index, 1);
};

const itemSubtotal = (item) => {
    return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
};

const totalAmount = computed(() => {
    return createForm.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const isOverStock = (item) => {
    return (parseInt(item.quantity) || 0) > (parseInt(item.current_stock) || 0);
};

const submitCreateForm = () => {
    if (createForm.items.length === 0) {
        error('Please add at least one product to the order.');
        return;
    }
    createForm.post(route('staff.sales-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success('Sales Order created successfully.');
        },
    });
};

// ═══════════════════════════════════════════════
// VIEW SO FUNCTIONS
// ═══════════════════════════════════════════════
const openViewModal = async (so) => {
    viewLoading.value = true;
    showViewModal.value = true;
    try {
        const response = await fetch(route('staff.sales-orders.show', so.id), {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        viewingSO.value = data.salesOrder;
    } catch (e) {
        console.error(e);
        error('Failed to load SO details.');
        showViewModal.value = false;
    } finally {
        viewLoading.value = false;
    }
};

const closeViewModal = () => {
    showViewModal.value = false;
    viewingSO.value = null;
};

// ═══════════════════════════════════════════════
// STATUS UPDATE — STAFF CAN ONLY REJECT
// ═══════════════════════════════════════════════
const openStatusModal = (so, newStatus) => {
    // Staff can only reject
    if (newStatus !== 'rejected') return;
    statusSO.value = so;
    statusForm.status = newStatus;
    statusForm.notes = '';
    statusForm.clearErrors();
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    statusSO.value = null;
    statusForm.reset();
    statusForm.clearErrors();
};

const submitStatusUpdate = () => {
    statusForm.put(route('staff.sales-orders.update', statusSO.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            success('Sales Order has been rejected.');
        },
        onError: (errors) => {
            if (errors.status) {
                error(errors.status);
                closeStatusModal();
            }
        },
    });
};

// ═══════════════════════════════════════════════
// STATUS DISPLAY HELPERS
// ═══════════════════════════════════════════════
const statusBadgeClass = (color) => {
    const classes = {
        yellow: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        blue: 'bg-blue-100 text-blue-800 border-blue-200',
        purple: 'bg-purple-100 text-purple-800 border-purple-200',
        green: 'bg-green-100 text-green-800 border-green-200',
        red: 'bg-red-100 text-red-800 border-red-200',
        gray: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return classes[color] || classes.gray;
};

const statusActionLabel = (status) => {
    const labels = {
        for_processing: 'Start Processing',
        for_shipment: 'Mark for Shipment',
        completed: 'Mark Completed',
        rejected: 'Reject',
    };
    return labels[status] || status;
};

const statusActionColor = (status) => {
    const colors = {
        rejected: 'text-red-600 hover:text-red-800',
    };
    return colors[status] || 'text-gray-400 cursor-not-allowed';
};

const statusActionIcon = (status) => {
    const icons = {
        for_processing: CogIcon,
        for_shipment: TruckIcon,
        completed: CheckCircleIcon,
        rejected: XCircleIcon,
    };
    return icons[status] || ArrowPathIcon;
};

// Staff can only use the "rejected" transition
const staffAllowedTransitions = (transitions) => {
    return (transitions || []).filter(t => t === 'rejected');
};

// ═══════════════════════════════════════════════
// PAGINATION
// ═══════════════════════════════════════════════
const currentPage = computed(() => props.salesOrders?.meta?.current_page || 1);
const lastPage = computed(() => props.salesOrders?.meta?.last_page || 1);
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
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    router.get(route('staff.sales-orders.index'), {
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="My Sales Orders" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Sales Orders</h1>
                <p class="mt-2 text-sm text-gray-600">View and manage your own sales orders</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring focus:ring-emerald-300 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Create SO
                </button>
            </div>
        </div>

        <!-- Staff note -->
        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
            <p class="text-sm text-amber-800">
                <ExclamationTriangleIcon class="h-4 w-4 inline mr-1" />
                You can create sales orders and reject your own orders. Processing, shipment, and completion are handled by admins.
            </p>
        </div>

        <!-- Table Filters -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by SO number or customer..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-white"
                >
                    <option value="">All Statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                    </option>
                </select>
            </div>
        </div>

        <!-- SO Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('so_number')">
                                <div class="flex items-center gap-1">SO Number <component :is="getSortIcon('so_number')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('order_date')">
                                <div class="flex items-center gap-1">Order Date <component :is="getSortIcon('order_date')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('delivery_date')">
                                <div class="flex items-center gap-1">Delivery Date <component :is="getSortIcon('delivery_date')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('status')">
                                <div class="flex items-center gap-1">Status <component :is="getSortIcon('status')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('total_amount')">
                                <div class="flex items-center gap-1">Total <component :is="getSortIcon('total_amount')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="so in salesOrders?.data" :key="so.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-medium text-gray-900">{{ so.so_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700">{{ so.customer?.customer_name || '—' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ so.order_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ so.delivery_date || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', statusBadgeClass(so.status_color)]">
                                    {{ so.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ so.items_count || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">₱{{ so.total_amount }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openViewModal(so)" class="text-gray-600 hover:text-gray-900 mr-2" title="View Details">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <!-- Staff can only reject -->
                                <template v-for="nextStatus in staffAllowedTransitions(so.allowed_transitions)" :key="nextStatus">
                                    <button
                                        @click="openStatusModal(so, nextStatus)"
                                        :class="['mr-1 transition', statusActionColor(nextStatus)]"
                                        :title="statusActionLabel(nextStatus)"
                                    >
                                        <component :is="statusActionIcon(nextStatus)" class="h-5 w-5" />
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!salesOrders?.data?.length">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <ShoppingCartIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No sales orders found</h3>
                                <p class="mt-1 text-sm text-gray-500">Create a new sales order to get started.</p>
                                <div class="mt-6">
                                    <button @click="openCreateModal" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                                        <PlusIcon class="h-5 w-5 mr-2" /> Create SO
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="salesOrders?.meta?.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">Previous</button>
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">Next</button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ salesOrders.meta.from }}</span> to
                        <span class="font-medium">{{ salesOrders.meta.to }}</span> of
                        <span class="font-medium">{{ salesOrders.meta.total }}</span> results
                    </p>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                            <ChevronLeftIcon class="h-5 w-5" />
                        </button>
                        <template v-for="(page, idx) in paginationPages" :key="idx">
                            <span v-if="page === '...'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">…</span>
                            <button v-else @click="goToPage(page)" :class="['relative inline-flex items-center px-4 py-2 border text-sm font-medium', page === currentPage ? 'z-10 bg-emerald-50 border-emerald-500 text-emerald-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50']">{{ page }}</button>
                        </template>
                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                            <ChevronRightIcon class="h-5 w-5" />
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CREATE SO MODAL -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-start justify-center min-h-screen px-4 pt-6 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeCreateModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-full max-w-4xl">
                        <form @submit.prevent="submitCreateForm">
                            <div class="bg-white px-6 pt-6 pb-4 max-h-[85vh] overflow-y-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-medium text-gray-900">Create Sales Order</h3>
                                    <button type="button" @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                                        <XMarkIcon class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Customer + Dates + Notes -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                                        <select
                                            v-model="createForm.customer_id"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                            :class="{ 'border-red-500': createForm.errors.customer_id }"
                                        >
                                            <option value="">Select customer</option>
                                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_name }}</option>
                                        </select>
                                        <p v-if="createForm.errors.customer_id" class="mt-1 text-sm text-red-600">{{ createForm.errors.customer_id }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Date <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="createForm.order_date"
                                            type="date"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                            :class="{ 'border-red-500': createForm.errors.order_date }"
                                        />
                                        <p v-if="createForm.errors.order_date" class="mt-1 text-sm text-red-600">{{ createForm.errors.order_date }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                                        <input
                                            v-model="createForm.delivery_date"
                                            type="date"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                            :class="{ 'border-red-500': createForm.errors.delivery_date }"
                                        />
                                        <p v-if="createForm.errors.delivery_date" class="mt-1 text-sm text-red-600">{{ createForm.errors.delivery_date }}</p>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        v-model="createForm.notes"
                                        rows="2"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        placeholder="Optional notes..."
                                    ></textarea>
                                </div>

                                <!-- PRODUCT SEARCH & FILTER -->
                                <div class="mb-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <FunnelIcon class="h-5 w-5 text-emerald-600" />
                                        <h4 class="text-sm font-semibold text-gray-900">Find Products</h4>
                                    </div>
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <div class="sm:col-span-2 relative">
                                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                                <input
                                                    v-model="productSearch"
                                                    type="text"
                                                    placeholder="Search by SKU or product name..."
                                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white"
                                                />
                                            </div>
                                            <div>
                                                <select
                                                    v-model="productCategoryFilter"
                                                    class="w-full border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white py-2"
                                                >
                                                    <option value="">All Categories</option>
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="productSortField"
                                                    class="flex-1 border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white py-2"
                                                >
                                                    <option value="name">Name</option>
                                                    <option value="sku">SKU</option>
                                                    <option value="unit_price">Price</option>
                                                    <option value="current_stock">Stock</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    @click="productSortDirection = productSortDirection === 'asc' ? 'desc' : 'asc'"
                                                    class="px-2 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 transition"
                                                    :title="productSortDirection === 'asc' ? 'Ascending' : 'Descending'"
                                                >
                                                    <component :is="productSortDirection === 'asc' ? ChevronUpIcon : ChevronDownIcon" class="h-4 w-4 text-gray-600" />
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <div v-if="productSearchLoading" class="flex items-center justify-center py-4">
                                                <svg class="animate-spin h-5 w-5 text-emerald-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm text-emerald-600">Searching...</span>
                                            </div>
                                            <div v-else-if="hasSearched && searchedProducts.length === 0" class="text-center py-4">
                                                <CubeIcon class="mx-auto h-8 w-8 text-gray-300" />
                                                <p class="mt-1 text-sm text-gray-500">No products found</p>
                                            </div>
                                            <div v-else-if="searchedProducts.length > 0" class="bg-white rounded-md border border-gray-200 divide-y divide-gray-100 max-h-48 overflow-y-auto">
                                                <div
                                                    v-for="product in searchedProducts"
                                                    :key="product.id"
                                                    class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition-colors"
                                                >
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-mono text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">{{ product.sku }}</span>
                                                            <span class="text-sm text-gray-900 truncate">{{ product.name }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-3 mt-0.5">
                                                            <span class="text-xs text-gray-500">₱{{ parseFloat(product.unit_price).toFixed(2) }}</span>
                                                            <span :class="['text-xs', product.current_stock > 0 ? 'text-green-600' : 'text-red-500 font-medium']">
                                                                Stock: {{ product.current_stock }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        v-if="!isProductInOrder(product.id)"
                                                        @click="addProductToOrder(product)"
                                                        class="ml-3 inline-flex items-center px-2.5 py-1 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700 transition flex-shrink-0"
                                                    >
                                                        <PlusIcon class="h-3.5 w-3.5 mr-1" /> Add
                                                    </button>
                                                    <span
                                                        v-else
                                                        class="ml-3 inline-flex items-center px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-md flex-shrink-0"
                                                    >
                                                        <CheckCircleIcon class="h-3.5 w-3.5 mr-1" /> Added
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ORDER ITEMS LIST -->
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-semibold text-gray-900">Order Items ({{ createForm.items.length }})</h4>
                                    </div>
                                    <p v-if="createForm.errors.items" class="mb-2 text-sm text-red-600">{{ createForm.errors.items }}</p>
                                    <div v-if="createForm.items.length === 0" class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center">
                                        <CubeIcon class="mx-auto h-8 w-8 text-gray-300" />
                                        <p class="mt-2 text-sm text-gray-500">No products added yet.</p>
                                        <p class="text-xs text-gray-400">Use the search above to find and add products to this order.</p>
                                    </div>
                                    <div v-else class="border rounded-lg overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Product</th>
                                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 w-28">Qty</th>
                                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 w-36">Unit Price</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 w-28">Subtotal</th>
                                                    <th class="px-4 py-2 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <tr v-for="(item, index) in createForm.items" :key="item.product_id" class="hover:bg-gray-50">
                                                    <td class="px-4 py-2.5">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-mono text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">{{ item.product_sku }}</span>
                                                            <span class="text-sm text-gray-900">{{ item.product_name }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span :class="['text-xs', isOverStock(item) ? 'text-red-500 font-semibold' : 'text-gray-400']">
                                                                Available: {{ item.current_stock }}
                                                            </span>
                                                            <span v-if="isOverStock(item)" class="inline-flex items-center text-xs text-red-500 font-medium">
                                                                <ExclamationTriangleIcon class="h-3.5 w-3.5 mr-0.5" />
                                                                Exceeds stock
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2.5">
                                                        <input
                                                            v-model.number="item.quantity"
                                                            type="number"
                                                            min="1"
                                                            class="w-full text-center border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                                                            :class="{ 'border-red-500 bg-red-50': isOverStock(item) }"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2.5">
                                                        <div class="relative">
                                                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                                                            <input
                                                                v-model.number="item.unit_price"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="w-full pl-6 text-center border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                                                            />
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right">
                                                        <span class="text-sm font-medium text-gray-900">₱{{ itemSubtotal(item).toFixed(2) }}</span>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="removeItem(index)"
                                                            class="text-red-400 hover:text-red-600 transition"
                                                            title="Remove item"
                                                        >
                                                            <TrashIcon class="h-4 w-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="bg-gray-50">
                                                <tr>
                                                    <td colspan="3" class="px-4 py-2.5 text-right text-sm font-semibold text-gray-700">Total</td>
                                                    <td class="px-4 py-2.5 text-right text-lg font-bold text-emerald-700">₱{{ totalAmount.toFixed(2) }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3 border-t">
                                <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing || createForm.items.length === 0"
                                    class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 transition"
                                >
                                    <svg v-if="createForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Create Sales Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- VIEW SO DETAIL MODAL -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeViewModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-full max-w-2xl">
                        <div class="px-6 pt-6 pb-4">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Sales Order Details</h3>
                                <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
                            </div>

                            <div v-if="viewLoading" class="flex items-center justify-center py-12">
                                <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <template v-if="viewingSO && !viewLoading">
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <p class="text-xs text-gray-500">SO Number</p>
                                        <p class="text-sm font-mono font-medium text-gray-900">{{ viewingSO.so_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Status</p>
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', statusBadgeClass(viewingSO.status_color)]">{{ viewingSO.status_label }}</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Customer</p>
                                        <p class="text-sm text-gray-900">{{ viewingSO.customer?.customer_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Order Date</p>
                                        <p class="text-sm text-gray-900">{{ viewingSO.order_date }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Delivery Date</p>
                                        <p class="text-sm text-gray-900">{{ viewingSO.delivery_date || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Created By</p>
                                        <p class="text-sm text-gray-900">{{ viewingSO.creator?.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Total Amount</p>
                                        <p class="text-sm font-medium text-gray-900">₱{{ viewingSO.total_amount }}</p>
                                    </div>
                                </div>

                                <div v-if="viewingSO.notes" class="mb-6">
                                    <p class="text-xs text-gray-500 mb-1">Notes</p>
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ viewingSO.notes }}</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Items ({{ viewingSO.items?.length || 0 }})</h4>
                                    <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Product</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Stock</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Qty</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Unit Price</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="item in viewingSO.items" :key="item.id">
                                                <td class="px-4 py-2">
                                                    <span class="text-sm text-gray-900">{{ item.product?.name }}</span>
                                                    <span class="text-xs text-gray-400 ml-1 font-mono">({{ item.product?.sku }})</span>
                                                </td>
                                                <td class="px-4 py-2 text-right">
                                                    <span :class="['text-sm', item.product?.current_stock >= item.quantity ? 'text-green-600' : 'text-red-600 font-medium']">
                                                        {{ item.product?.current_stock ?? '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-700">₱{{ item.unit_price }}</td>
                                                <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">₱{{ item.subtotal }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="4" class="px-4 py-2 text-right text-sm font-medium text-gray-700">Total</td>
                                                <td class="px-4 py-2 text-right text-sm font-bold text-gray-900">₱{{ viewingSO.total_amount }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </template>
                        </div>

                        <div class="bg-gray-50 px-6 py-3 flex justify-end">
                            <button @click="closeViewModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- REJECT SO MODAL (Staff only action) -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeStatusModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <XCircleIcon class="h-6 w-6 text-red-600" />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">Reject Sales Order</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            This SO will be rejected. No stock changes will be made.
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                                        <textarea
                                            v-model="statusForm.notes"
                                            rows="2"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                            placeholder="Reason for rejection..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                @click="submitStatusUpdate"
                                :disabled="statusForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                <svg v-if="statusForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Reject Order
                            </button>
                            <button
                                @click="closeStatusModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
