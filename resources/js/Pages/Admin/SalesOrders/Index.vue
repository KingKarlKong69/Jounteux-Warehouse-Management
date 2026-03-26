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
    DocumentArrowDownIcon,
    TableCellsIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';
import { useNotification } from '@/composables/useNotification';
import { useRouteLoading } from '@/composables/useRouteLoading';
import { TableSkeleton } from '@/Components/Skeletons';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    salesOrders: Object,
    filters: Object,
    customers: Array,
    categories: Array,
    statuses: Array,
});

// ─── Table Filters ───
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');
const showExportMenu = ref(false);

// ─── Export / Report ───
const exportUrl = (type) => {
    const params = new URLSearchParams({
        search: search.value,
        status: statusFilter.value,
        sort: sortField.value,
        direction: sortDirection.value,
    });
    if (type === 'csv') return route('admin.sales-orders.export.csv') + '?' + params.toString();
    if (type === 'excel') return route('admin.sales-orders.export.excel') + '?' + params.toString();
    if (type === 'report') return route('admin.sales-orders.report') + '?' + params.toString();
    return '#';
};

// Notification system
const { success, error } = useNotification();
const { isNavigating } = useRouteLoading();

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

// ─── Status Update Modal ───
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
    router.get(route('admin.sales-orders.index'), {
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

        const response = await fetch(`${route('admin.products.search')}?${params.toString()}`, {
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

// Debounced product search watcher
watch(productSearch, () => {
    clearTimeout(productSearchTimeout);
    productSearchTimeout = setTimeout(() => fetchProducts(), 300);
});

// Immediate fetch on category/sort change
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
    // Reset product search state
    productSearch.value = '';
    productCategoryFilter.value = '';
    productSortField.value = 'name';
    productSortDirection.value = 'asc';
    searchedProducts.value = [];
    hasSearched.value = false;
    showCreateModal.value = true;
    // Load all products initially
    nextTick(() => fetchProducts());
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

// Check if product is already in the order
const isProductInOrder = (productId) => {
    return createForm.items.some(item => item.product_id === productId);
};

// Check if product is out of stock
const isOutOfStock = (product) => {
    return (parseInt(product.current_stock) || 0) <= 0;
};

// Add product from search results to order items
const addProductToOrder = (product) => {
    if (isProductInOrder(product.id)) return;
    if (isOutOfStock(product)) return;
    createForm.items.push({
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        current_stock: product.current_stock,
        quantity: 1,
        unit_price: parseFloat(product.unit_price) || 0,
    });
};

// Remove item from order
const removeItem = (index) => {
    createForm.items.splice(index, 1);
};

const itemSubtotal = (item) => {
    return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
};

const totalAmount = computed(() => {
    return createForm.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

// Check if requested qty exceeds available stock
const isOverStock = (item) => {
    return (parseInt(item.quantity) || 0) > (parseInt(item.current_stock) || 0);
};

// Check if ANY item in the order exceeds stock
const hasOverStockItems = computed(() => {
    return createForm.items.some(item => isOverStock(item));
});

// Increment quantity (capped at current_stock)
const incrementQty = (item) => {
    const max = parseInt(item.current_stock) || 0;
    if (item.quantity < max) {
        item.quantity++;
    }
};

// Decrement quantity (min 1)
const decrementQty = (item) => {
    if (item.quantity > 1) {
        item.quantity--;
    }
};

// Clamp quantity on blur (auto-corrects invalid values)
const clampQuantity = (item) => {
    const max = parseInt(item.current_stock) || 0;
    const val = parseInt(item.quantity);
    if (isNaN(val) || val < 1) {
        item.quantity = 1;
    } else if (val > max) {
        item.quantity = max;
    }
};

const submitCreateForm = () => {
    if (createForm.items.length === 0) {
        error('Please add at least one product to the order.');
        return;
    }
    if (hasOverStockItems.value) {
        error('One or more items exceed available stock. Please adjust quantities.');
        return;
    }
    createForm.post(route('admin.sales-orders.store'), {
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
        const response = await fetch(route('admin.sales-orders.show', so.id), {
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
// STATUS UPDATE FUNCTIONS
// ═══════════════════════════════════════════════
const openStatusModal = (so, newStatus) => {
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
    statusForm.put(route('admin.sales-orders.update', statusSO.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            success(`SO status updated to ${statusForm.status.replace('_', ' ')}.`);
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
        for_processing: 'text-blue-600 hover:text-blue-800',
        for_shipment: 'text-purple-600 hover:text-purple-800',
        completed: 'text-green-600 hover:text-green-800',
        rejected: 'text-red-600 hover:text-red-800',
    };
    return colors[status] || 'text-gray-600';
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

// ═══════════════════════════════════════════════
// PAGINATION
// ═══════════════════════════════════════════════
const goToPage = (page) => {
    router.get(route('admin.sales-orders.index'), {
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Sales Orders" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Sales Orders</h1>
                <p class="mt-2 text-sm text-gray-600">Manage sales orders, stock outflow, and deliveries</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring focus:ring-emerald-300 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Create SO
                </button>
            </div>
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
                <!-- Export Dropdown -->
                <div class="relative">
                    <button
                        @click="showExportMenu = !showExportMenu"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition"
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
        </div>

        <!-- Skeleton Loading State -->
        <TableSkeleton v-if="isNavigating" :columns="8" :rows="8" :show-actions="true" :badge-columns="[4]" :show-pagination="true" />

        <!-- SO Table -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
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
                                <template v-for="nextStatus in so.allowed_transitions" :key="nextStatus">
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
            <Pagination :meta="salesOrders.meta" label="orders" @navigate="goToPage" />
        </div><!-- /SO Table -->

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

                                <!-- ═══════════════════════════════════════ -->
                                <!-- PRODUCT SEARCH & FILTER SECTION -->
                                <!-- ═══════════════════════════════════════ -->
                                <div class="mb-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <FunnelIcon class="h-5 w-5 text-emerald-600" />
                                        <h4 class="text-sm font-semibold text-gray-900">Find Products</h4>
                                    </div>
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <!-- Search -->
                                            <div class="sm:col-span-2 relative">
                                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                                <input
                                                    v-model="productSearch"
                                                    type="text"
                                                    placeholder="Search by SKU or product name..."
                                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white"
                                                />
                                            </div>
                                            <!-- Category filter -->
                                            <div>
                                                <select
                                                    v-model="productCategoryFilter"
                                                    class="w-full border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white py-2"
                                                >
                                                    <option value="">All Categories</option>
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </select>
                                            </div>
                                            <!-- Sort -->
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

                                        <!-- Product Results -->
                                        <div class="mt-3">
                                            <!-- Loading -->
                                            <div v-if="productSearchLoading" class="flex items-center justify-center py-4">
                                                <svg class="animate-spin h-5 w-5 text-emerald-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm text-emerald-600">Searching...</span>
                                            </div>

                                            <!-- No results -->
                                            <div v-else-if="hasSearched && searchedProducts.length === 0" class="text-center py-4">
                                                <CubeIcon class="mx-auto h-8 w-8 text-gray-300" />
                                                <p class="mt-1 text-sm text-gray-500">No products found</p>
                                            </div>

                                            <!-- Results list -->
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
                                                    <!-- Out of Stock badge -->
                                                    <span
                                                        v-if="isOutOfStock(product)"
                                                        class="ml-3 inline-flex items-center px-2.5 py-1 bg-red-100 text-red-600 text-xs font-medium rounded-md flex-shrink-0"
                                                    >
                                                        <ExclamationTriangleIcon class="h-3.5 w-3.5 mr-1" /> Out of Stock
                                                    </span>
                                                    <!-- Add button -->
                                                    <button
                                                        v-else-if="!isProductInOrder(product.id)"
                                                        type="button"
                                                        @click="addProductToOrder(product)"
                                                        class="ml-3 inline-flex items-center px-2.5 py-1 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700 transition flex-shrink-0"
                                                    >
                                                        <PlusIcon class="h-3.5 w-3.5 mr-1" /> Add
                                                    </button>
                                                    <!-- Already added badge -->
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

                                <!-- ═══════════════════════════════════════ -->
                                <!-- ORDER ITEMS LIST -->
                                <!-- ═══════════════════════════════════════ -->
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-semibold text-gray-900">Order Items ({{ createForm.items.length }})</h4>
                                    </div>

                                    <p v-if="createForm.errors.items" class="mb-2 text-sm text-red-600">{{ createForm.errors.items }}</p>

                                    <!-- Empty items state -->
                                    <div v-if="createForm.items.length === 0" class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center">
                                        <CubeIcon class="mx-auto h-8 w-8 text-gray-300" />
                                        <p class="mt-2 text-sm text-gray-500">No products added yet.</p>
                                        <p class="text-xs text-gray-400">Use the search above to find and add products to this order.</p>
                                    </div>

                                    <!-- Items table -->
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
                                                        <p v-if="createForm.errors[`items.${index}.product_id`]" class="mt-0.5 text-xs text-red-600">{{ createForm.errors[`items.${index}.product_id`] }}</p>
                                                    </td>
                                                    <td class="px-4 py-2.5">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button
                                                                type="button"
                                                                @click="decrementQty(item)"
                                                                :disabled="item.quantity <= 1"
                                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition text-sm font-bold"
                                                            >−</button>
                                                            <input
                                                                v-model.number="item.quantity"
                                                                type="number"
                                                                min="1"
                                                                :max="item.current_stock"
                                                                @blur="clampQuantity(item)"
                                                                class="w-14 text-center border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                                :class="{ 'border-red-500 bg-red-50': isOverStock(item), 'border-red-500': createForm.errors[`items.${index}.quantity`] }"
                                                            />
                                                            <button
                                                                type="button"
                                                                @click="incrementQty(item)"
                                                                :disabled="item.quantity >= item.current_stock"
                                                                class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition text-sm font-bold"
                                                            >+</button>
                                                        </div>
                                                        <p v-if="createForm.errors[`items.${index}.quantity`]" class="mt-0.5 text-xs text-red-600 text-center">{{ createForm.errors[`items.${index}.quantity`] }}</p>
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
                                                                :class="{ 'border-red-500': createForm.errors[`items.${index}.unit_price`] }"
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
                                    :disabled="createForm.processing || createForm.items.length === 0 || hasOverStockItems"
                                    class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 transition"
                                    :title="hasOverStockItems ? 'Fix items exceeding stock before submitting' : ''"
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
        <!-- STATUS UPDATE MODAL -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeStatusModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div :class="[
                                    'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10',
                                    statusForm.status === 'completed' ? 'bg-green-100' :
                                    statusForm.status === 'rejected' ? 'bg-red-100' : 'bg-blue-100'
                                ]">
                                    <component
                                        :is="statusActionIcon(statusForm.status)"
                                        :class="['h-6 w-6',
                                            statusForm.status === 'completed' ? 'text-green-600' :
                                            statusForm.status === 'rejected' ? 'text-red-600' : 'text-blue-600'
                                        ]"
                                    />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">{{ statusActionLabel(statusForm.status) }}</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            <template v-if="statusForm.status === 'completed'">
                                                <strong class="text-amber-700">Stock will be deducted.</strong> This will subtract all SO items from inventory. Insufficient stock will block completion.
                                            </template>
                                            <template v-else-if="statusForm.status === 'rejected'">
                                                This SO will be rejected. No stock changes will be made.
                                            </template>
                                            <template v-else>
                                                Update SO <strong>{{ statusSO?.so_number }}</strong> status to <strong>{{ statusActionLabel(statusForm.status) }}</strong>.
                                            </template>
                                        </p>
                                    </div>
                                    <div v-if="statusForm.status === 'rejected'" class="mt-4">
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
                                :class="[
                                    'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50',
                                    statusForm.status === 'completed' ? 'bg-green-600 hover:bg-green-700' :
                                    statusForm.status === 'rejected' ? 'bg-red-600 hover:bg-red-700' :
                                    'bg-emerald-600 hover:bg-emerald-700'
                                ]"
                            >
                                <svg v-if="statusForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Confirm
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
