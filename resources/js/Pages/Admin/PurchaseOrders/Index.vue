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
    PaperAirplaneIcon,
    ExclamationTriangleIcon,
    FunnelIcon,
    CubeIcon,
    DocumentArrowDownIcon,
    TableCellsIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';
import { useNotification } from '@/composables/useNotification';
import { useRouteLoading } from '@/composables/useRouteLoading';
import { TableSkeleton } from '@/Components/Skeletons';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    purchaseOrders: Object,
    filters: Object,
    suppliers: Array,
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
    if (type === 'csv') return route('admin.purchase-orders.export.csv') + '?' + params.toString();
    if (type === 'excel') return route('admin.purchase-orders.export.excel') + '?' + params.toString();
    if (type === 'report') return route('admin.purchase-orders.report') + '?' + params.toString();
    return '#';
};

// Notification system
const { success, error } = useNotification();
const { isNavigating } = useRouteLoading();

// ─── Create PO Modal State ───
const showCreateModal = ref(false);
const createForm = useForm({
    supplier_id: '',
    order_date: new Date().toISOString().split('T')[0],
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

// ─── View PO Modal ───
const showViewModal = ref(false);
const viewingPO = ref(null);
const viewLoading = ref(false);

// ─── Status Update Modal ───
const showStatusModal = ref(false);
const statusForm = useForm({ status: '', notes: '' });
const statusPO = ref(null);

// ═══════════════════════════════════════════════
// TABLE FILTER LOGIC
// ═══════════════════════════════════════════════
let searchTimeout;
watch([search, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

const applyFilters = () => {
    router.get(route('admin.purchase-orders.index'), {
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
// PRODUCT SEARCH API (for Create PO Modal)
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
// CREATE PO FUNCTIONS
// ═══════════════════════════════════════════════
const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.order_date = new Date().toISOString().split('T')[0];
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

// Add product from search results to order items
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

const submitCreateForm = () => {
    if (createForm.items.length === 0) {
        error('Please add at least one product to the order.');
        return;
    }
    createForm.post(route('admin.purchase-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            success('Purchase Order created successfully.');
        },
    });
};

// ═══════════════════════════════════════════════
// VIEW PO FUNCTIONS
// ═══════════════════════════════════════════════
const openViewModal = async (po) => {
    viewLoading.value = true;
    showViewModal.value = true;
    try {
        const response = await fetch(route('admin.purchase-orders.show', po.id), {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        viewingPO.value = data.purchaseOrder;
    } catch (e) {
        console.error(e);
        error('Failed to load PO details.');
        showViewModal.value = false;
    } finally {
        viewLoading.value = false;
    }
};

const closeViewModal = () => {
    showViewModal.value = false;
    viewingPO.value = null;
};

// ═══════════════════════════════════════════════
// STATUS UPDATE FUNCTIONS
// ═══════════════════════════════════════════════
const openStatusModal = (po, newStatus) => {
    statusPO.value = po;
    statusForm.status = newStatus;
    statusForm.notes = '';
    statusForm.clearErrors();
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    statusPO.value = null;
    statusForm.reset();
    statusForm.clearErrors();
};

const submitStatusUpdate = () => {
    statusForm.put(route('admin.purchase-orders.update', statusPO.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            success(`PO status updated to ${statusForm.status.replace('_', ' ')}.`);
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
        indigo: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        purple: 'bg-purple-100 text-purple-800 border-purple-200',
        green: 'bg-green-100 text-green-800 border-green-200',
        red: 'bg-red-100 text-red-800 border-red-200',
        gray: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return classes[color] || classes.gray;
};

const statusActionLabel = (status) => {
    const labels = {
        approved: 'Approve',
        processing: 'Start Processing',
        for_shipment: 'Mark for Shipment',
        completed: 'Mark Completed',
        rejected: 'Reject',
        cancelled: 'Cancel',
    };
    return labels[status] || status;
};

const statusActionColor = (status) => {
    const colors = {
        approved: 'text-blue-600 hover:text-blue-800',
        processing: 'text-indigo-600 hover:text-indigo-800',
        for_shipment: 'text-purple-600 hover:text-purple-800',
        completed: 'text-green-600 hover:text-green-800',
        rejected: 'text-red-600 hover:text-red-800',
        cancelled: 'text-gray-600 hover:text-gray-800',
    };
    return colors[status] || 'text-gray-600';
};

const statusActionIcon = (status) => {
    const icons = {
        approved: CheckCircleIcon,
        processing: CogIcon,
        for_shipment: TruckIcon,
        completed: CheckCircleIcon,
        rejected: XCircleIcon,
        cancelled: XCircleIcon,
    };
    return icons[status] || ArrowPathIcon;
};

// Pagination
const goToPage = (page) => {
    router.get(route('admin.purchase-orders.index'), {
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Purchase Orders" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Orders</h1>
                <p class="mt-2 text-sm text-gray-600">Manage purchase orders and stock intake</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring focus:ring-indigo-300 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Create PO
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
                        placeholder="Search by PO number or supplier..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                >
                    <option value="">All Statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
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
        <TableSkeleton v-if="isNavigating" :columns="7" :rows="8" :show-actions="true" :badge-columns="[3]" :show-pagination="true" />

        <!-- PO Table -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('po_number')">
                                <div class="flex items-center gap-1">PO Number <component :is="getSortIcon('po_number')" class="h-4 w-4" /></div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" @click="toggleSort('order_date')">
                                <div class="flex items-center gap-1">Order Date <component :is="getSortIcon('order_date')" class="h-4 w-4" /></div>
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
                        <tr v-for="po in purchaseOrders?.data" :key="po.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-medium text-gray-900">{{ po.po_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700">{{ po.supplier?.company_name || '—' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ po.order_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', statusBadgeClass(po.status_color)]">
                                    {{ po.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ po.items_count || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">₱{{ po.total_amount }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openViewModal(po)" class="text-gray-600 hover:text-gray-900 mr-2" title="View Details">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <template v-for="nextStatus in po.allowed_transitions" :key="nextStatus">
                                    <button
                                        @click="openStatusModal(po, nextStatus)"
                                        :class="['mr-1 transition', statusActionColor(nextStatus)]"
                                        :title="statusActionLabel(nextStatus)"
                                    >
                                        <component :is="statusActionIcon(nextStatus)" class="h-5 w-5" />
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!purchaseOrders?.data?.length">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No purchase orders found</h3>
                                <p class="mt-1 text-sm text-gray-500">Create a new purchase order to get started.</p>
                                <div class="mt-6">
                                    <button @click="openCreateModal" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                        <PlusIcon class="h-5 w-5 mr-2" /> Create PO
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="purchaseOrders.meta" label="orders" @navigate="goToPage" />
        </div><!-- /PO Table -->

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CREATE PO MODAL -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-start justify-center min-h-screen px-4 pt-6 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeCreateModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-full max-w-4xl">
                        <form @submit.prevent="submitCreateForm">
                            <div class="bg-white px-6 pt-6 pb-4 max-h-[85vh] overflow-y-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-medium text-gray-900">Create Purchase Order</h3>
                                    <button type="button" @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                                        <XMarkIcon class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Supplier + Date + Notes -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                                        <select
                                            v-model="createForm.supplier_id"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            :class="{ 'border-red-500': createForm.errors.supplier_id }"
                                        >
                                            <option value="">Select supplier</option>
                                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.company_name }}</option>
                                        </select>
                                        <p v-if="createForm.errors.supplier_id" class="mt-1 text-sm text-red-600">{{ createForm.errors.supplier_id }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Date <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="createForm.order_date"
                                            type="date"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            :class="{ 'border-red-500': createForm.errors.order_date }"
                                        />
                                        <p v-if="createForm.errors.order_date" class="mt-1 text-sm text-red-600">{{ createForm.errors.order_date }}</p>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        v-model="createForm.notes"
                                        rows="2"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Optional notes..."
                                    ></textarea>
                                </div>

                                <!-- ═══════════════════════════════════════ -->
                                <!-- PRODUCT SEARCH & FILTER SECTION -->
                                <!-- ═══════════════════════════════════════ -->
                                <div class="mb-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <FunnelIcon class="h-5 w-5 text-indigo-600" />
                                        <h4 class="text-sm font-semibold text-gray-900">Find Products</h4>
                                    </div>
                                    <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <!-- Search -->
                                            <div class="sm:col-span-2 relative">
                                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                                <input
                                                    v-model="productSearch"
                                                    type="text"
                                                    placeholder="Search by SKU or product name..."
                                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white"
                                                />
                                            </div>
                                            <!-- Category filter -->
                                            <div>
                                                <select
                                                    v-model="productCategoryFilter"
                                                    class="w-full border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white py-2"
                                                >
                                                    <option value="">All Categories</option>
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </select>
                                            </div>
                                            <!-- Sort -->
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="productSortField"
                                                    class="flex-1 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white py-2"
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
                                                <svg class="animate-spin h-5 w-5 text-indigo-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm text-indigo-600">Searching...</span>
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
                                                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ product.sku }}</span>
                                                            <span class="text-sm text-gray-900 truncate">{{ product.name }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-3 mt-0.5">
                                                            <span class="text-xs text-gray-500">₱{{ parseFloat(product.unit_price).toFixed(2) }}</span>
                                                            <span class="text-xs text-gray-400">Stock: {{ product.current_stock }}</span>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        v-if="!isProductInOrder(product.id)"
                                                        @click="addProductToOrder(product)"
                                                        class="ml-3 inline-flex items-center px-2.5 py-1 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 transition flex-shrink-0"
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
                                                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ item.product_sku }}</span>
                                                            <span class="text-sm text-gray-900">{{ item.product_name }}</span>
                                                        </div>
                                                        <span class="text-xs text-gray-400">Stock: {{ item.current_stock }}</span>
                                                        <p v-if="createForm.errors[`items.${index}.product_id`]" class="mt-0.5 text-xs text-red-600">{{ createForm.errors[`items.${index}.product_id`] }}</p>
                                                    </td>
                                                    <td class="px-4 py-2.5">
                                                        <input
                                                            v-model.number="item.quantity"
                                                            type="number"
                                                            min="1"
                                                            class="w-full text-center border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                                            :class="{ 'border-red-500': createForm.errors[`items.${index}.quantity`] }"
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
                                                                class="w-full pl-6 text-center border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
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
                                                    <td class="px-4 py-2.5 text-right text-lg font-bold text-indigo-700">₱{{ totalAmount.toFixed(2) }}</td>
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
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition"
                                >
                                    <svg v-if="createForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Create Purchase Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- VIEW PO DETAIL MODAL -->
        <!-- ═══════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeViewModal"></div>
                    <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-full max-w-2xl">
                        <div class="px-6 pt-6 pb-4">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Purchase Order Details</h3>
                                <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
                            </div>

                            <div v-if="viewLoading" class="flex items-center justify-center py-12">
                                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <template v-if="viewingPO && !viewLoading">
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <p class="text-xs text-gray-500">PO Number</p>
                                        <p class="text-sm font-mono font-medium text-gray-900">{{ viewingPO.po_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Status</p>
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', statusBadgeClass(viewingPO.status_color)]">{{ viewingPO.status_label }}</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Supplier</p>
                                        <p class="text-sm text-gray-900">{{ viewingPO.supplier?.company_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Order Date</p>
                                        <p class="text-sm text-gray-900">{{ viewingPO.order_date }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Created By</p>
                                        <p class="text-sm text-gray-900">{{ viewingPO.creator?.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Total Amount</p>
                                        <p class="text-sm font-medium text-gray-900">₱{{ viewingPO.total_amount }}</p>
                                    </div>
                                </div>

                                <div v-if="viewingPO.notes" class="mb-6">
                                    <p class="text-xs text-gray-500 mb-1">Notes</p>
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ viewingPO.notes }}</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Items ({{ viewingPO.items?.length || 0 }})</h4>
                                    <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Product</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Qty</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Unit Price</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="item in viewingPO.items" :key="item.id">
                                                <td class="px-4 py-2">
                                                    <span class="text-sm text-gray-900">{{ item.product?.name }}</span>
                                                    <span class="text-xs text-gray-400 ml-1 font-mono">({{ item.product?.sku }})</span>
                                                </td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-700">{{ item.quantity }}</td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-700">₱{{ item.unit_price }}</td>
                                                <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">₱{{ item.subtotal }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="3" class="px-4 py-2 text-right text-sm font-medium text-gray-700">Total</td>
                                                <td class="px-4 py-2 text-right text-sm font-bold text-gray-900">₱{{ viewingPO.total_amount }}</td>
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
                                    statusForm.status === 'rejected' || statusForm.status === 'cancelled' ? 'bg-red-100' : 'bg-blue-100'
                                ]">
                                    <component
                                        :is="statusActionIcon(statusForm.status)"
                                        :class="['h-6 w-6',
                                            statusForm.status === 'completed' ? 'text-green-600' :
                                            statusForm.status === 'rejected' || statusForm.status === 'cancelled' ? 'text-red-600' : 'text-blue-600'
                                        ]"
                                    />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">{{ statusActionLabel(statusForm.status) }}</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            <template v-if="statusForm.status === 'completed'">
                                                <strong class="text-green-700">Stock will be updated.</strong> This will add all PO items to inventory. This action cannot be undone.
                                            </template>
                                            <template v-else-if="statusForm.status === 'rejected'">
                                                This PO will be rejected. No stock changes will be made.
                                            </template>
                                            <template v-else-if="statusForm.status === 'cancelled'">
                                                This PO will be cancelled. No stock changes will be made.
                                            </template>
                                            <template v-else>
                                                Update PO <strong>{{ statusPO?.po_number }}</strong> status to <strong>{{ statusActionLabel(statusForm.status) }}</strong>.
                                            </template>
                                        </p>
                                    </div>
                                    <div v-if="['rejected', 'cancelled'].includes(statusForm.status)" class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                                        <textarea
                                            v-model="statusForm.notes"
                                            rows="2"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="Reason for rejection/cancellation..."
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
                                    statusForm.status === 'rejected' || statusForm.status === 'cancelled' ? 'bg-red-600 hover:bg-red-700' :
                                    'bg-indigo-600 hover:bg-indigo-700'
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
