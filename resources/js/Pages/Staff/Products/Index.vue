<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';

const { success, error, confirm } = useNotification();
import { 
    MagnifyingGlassIcon, 
    PlusIcon,
    PencilIcon,
    TrashIcon,
    FunnelIcon,
    XMarkIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ArchiveBoxIcon,
    CubeIcon,
    EyeIcon,
    ListBulletIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    products: Object,
    filters: Object,
    trashedCount: Number,
    categories: Array,
});

const search = ref(props.filters?.search || '');
const stockThreshold = ref(props.filters?.stock_threshold || '');
const categoryId = ref(props.filters?.category_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showDeleted = ref(props.filters?.show_deleted === 'true' || false);
const showFilters = ref(false);
const localCategories = ref([...(props.categories || [])]);
const viewMode = ref('card');

// Debounce search
let searchTimeout;
watch([search, stockThreshold, categoryId, dateFrom, dateTo, showDeleted], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('staff.products.index'), {
            search: search.value,
            stock_threshold: stockThreshold.value,
            category_id: categoryId.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            show_deleted: showDeleted.value ? 'true' : '',
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const clearFilters = () => {
    search.value = '';
    stockThreshold.value = '';
    categoryId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const deleteProduct = (id) => {
    confirm({
        title: 'Delete Product',
        message: 'Are you sure you want to delete this product? You can restore it later.',
        confirmLabel: 'Delete',
        destructive: true,
        onConfirm: () => {
            router.delete(route('staff.products.destroy', id), {
                preserveScroll: true,
                onSuccess: () => success('Product deleted successfully.'),
                onError: () => error('Failed to delete product.'),
            });
        },
    });
};

const restoreProduct = (id) => {
    confirm({
        title: 'Restore Product',
        message: 'Are you sure you want to restore this product?',
        confirmLabel: 'Restore',
        onConfirm: () => {
            router.put(route('staff.products.restore', id), {}, {
                preserveScroll: true,
                onSuccess: () => success('Product restored successfully.'),
                onError: () => error('Failed to restore product.'),
            });
        },
    });
};

const forceDeleteProduct = (id) => {
    confirm({
        title: 'Permanent Delete',
        message: 'This action cannot be undone! The product will be permanently removed.',
        confirmLabel: 'Delete Forever',
        destructive: true,
        onConfirm: () => {
            router.delete(route('staff.products.force-delete', id), {
                preserveScroll: true,
                onSuccess: () => success('Product permanently deleted.'),
                onError: () => error('Failed to permanently delete product.'),
            });
        },
    });
};

const getStockBadgeClass = (status) => {
    const classes = {
        'in_stock': 'bg-green-100 text-green-800',
        'low_stock': 'bg-yellow-100 text-yellow-800',
        'out_of_stock': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

// Pagination logic
const currentPage = computed(() => props.products?.meta?.current_page || 1);
const lastPage = computed(() => props.products?.meta?.last_page || 1);
const maxVisible = 5;

const paginationPages = computed(() => {
    const total = lastPage.value;
    const current = currentPage.value;

    if (total <= maxVisible + 2) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages = [];
    const half = Math.floor(maxVisible / 2);
    let start = Math.max(2, current - half);
    let end = Math.min(total - 1, current + half);

    if (current <= half + 2) {
        start = 2;
        end = Math.min(total - 1, maxVisible);
    }
    if (current >= total - half - 1) {
        start = Math.max(2, total - maxVisible + 1);
        end = total - 1;
    }

    pages.push(1);
    if (start > 2) pages.push('...');
    for (let i = start; i <= end; i++) pages.push(i);
    if (end < total - 1) pages.push('...');
    if (total > 1) pages.push(total);

    return pages;
});

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    router.get(route('staff.products.index'), {
        page,
        search: search.value,
        stock_threshold: stockThreshold.value,
        category_id: categoryId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        show_deleted: showDeleted.value ? 'true' : '',
    }, {
        preserveState: true,
        replace: true,
    });
};

const getStockBadgeText = (status) => {
    const text = {
        'in_stock': 'In Stock',
        'low_stock': 'Low Stock',
        'out_of_stock': 'Out of Stock',
    };
    return text[status] || 'Unknown';
};
</script>

<template>
    <Head title="Products" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Products</h1>
                <p class="mt-2 text-sm text-gray-600">Manage your product inventory</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                <!-- View Toggle -->
                <div class="inline-flex rounded-md shadow-sm">
                    <button
                        @click="viewMode = 'card'"
                        :class="['inline-flex items-center px-3 py-2 text-sm font-medium border rounded-l-md transition', viewMode === 'card' ? 'bg-customOrange text-white border-customOrange' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
                    >
                        <Squares2X2Icon class="h-4 w-4" />
                    </button>
                    <button
                        @click="viewMode = 'list'"
                        :class="['inline-flex items-center px-3 py-2 text-sm font-medium border-t border-b border-r rounded-r-md transition', viewMode === 'list' ? 'bg-customOrange text-white border-customOrange' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
                    >
                        <ListBulletIcon class="h-4 w-4" />
                    </button>
                </div>
                <Link
                    v-if="!showDeleted"
                    :href="route('staff.products.create')"
                    class="inline-flex items-center px-4 py-2 bg-customOrange border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-black active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add Product
                </Link>
            </div>
        </div>

        <!-- Active / Archives Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button
                    @click="showDeleted = false"
                    :class="[
                        'group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm transition',
                        !showDeleted
                            ? 'border-customOrange text-customOrange'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <CubeIcon :class="['mr-2 h-5 w-5', !showDeleted ? 'text-customOrange' : 'text-gray-400 group-hover:text-gray-500']" />
                    Active
                </button>
                <button
                    @click="showDeleted = true"
                    :class="[
                        'group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm transition',
                        showDeleted
                            ? 'border-customOrange text-customOrange'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <ArchiveBoxIcon :class="['mr-2 h-5 w-5', showDeleted ? 'text-customOrange' : 'text-gray-400 group-hover:text-gray-500']" />
                    Archives
                    <span v-if="trashedCount > 0" :class="[
                        'ml-2 px-2 py-0.5 rounded-full text-xs font-medium',
                        showDeleted ? 'bg-customOrange text-white' : 'bg-gray-100 text-gray-600'
                    ]">
                        {{ trashedCount }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Search and Filters -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or SKU..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <button
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    <FunnelIcon class="h-5 w-5 mr-2" />
                    Filters
                </button>
            </div>

            <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select
                            v-model="categoryId"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in localCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }} ({{ cat.id }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Threshold</label>
                        <input
                            v-model="stockThreshold"
                            type="number"
                            placeholder="e.g., 10"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-end">
                    <button
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                    >
                        <XMarkIcon class="h-5 w-5 mr-1" />
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Card Grid -->
        <div v-if="products?.data && products.data.length > 0 && viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="product in products.data"
                :key="product.id"
                :class="[
                    'bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 overflow-hidden',
                    showDeleted ? 'opacity-75 border-2 border-amber-200' : ''
                ]"
            >
                <!-- Archived Badge -->
                <div v-if="showDeleted" class="bg-amber-50 border-b border-amber-200 px-4 py-2">
                    <p class="text-xs font-medium text-amber-800 flex items-center">
                        <ArchiveBoxIcon class="h-4 w-4 mr-1" />
                        Archived Product
                    </p>
                </div>
                
                <!-- Product Image -->
                <div class="h-48 flex items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-50 to-indigo-100">
                    <img 
                        v-if="product.image_url" 
                        :src="product.image_url" 
                        :alt="product.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="text-center">
                        <p class="text-4xl font-bold text-indigo-600">{{ product.name.charAt(0) }}</p>
                        <p class="text-xs text-indigo-500 mt-2">{{ product.sku }}</p>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="p-4">
                    <div class="mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">{{ product.name }}</h3>
                        <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                        <span v-if="product.category" class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                            {{ product.category.name }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <p class="text-2xl font-bold text-emerald-600">₱{{ product.unit_price }}</p>
                    </div>

                    <!-- Stock Badge -->
                    <div class="mb-4">
                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStockBadgeClass(product.stock_status)]">
                            {{ getStockBadgeText(product.stock_status) }} ({{ product.current_stock }})
                        </span>
                    </div>

                    <!-- Actions -->
                    <div v-if="!showDeleted" class="flex gap-2">
                        <Link
                            :href="route('staff.products.show', product.id)"
                            class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-white hover:bg-indigo-50"
                        >
                            <EyeIcon class="h-4 w-4 mr-1" />
                            View
                        </Link>
                        <Link
                            :href="route('staff.products.edit', product.id)"
                            class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <PencilIcon class="h-4 w-4" />
                        </Link>
                        <button
                            @click="deleteProduct(product.id)"
                            class="inline-flex items-center justify-center px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                    
                    <!-- Archive Actions -->
                    <div v-else class="flex flex-col gap-2">
                        <button
                            @click="restoreProduct(product.id)"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50"
                        >
                            <ArrowPathIcon class="h-4 w-4 mr-1" />
                            Restore
                        </button>
                        <button
                            @click="forceDeleteProduct(product.id)"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-500 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100"
                        >
                            <ExclamationTriangleIcon class="h-4 w-4 mr-1" />
                            Delete Forever
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products List View -->
        <div v-if="products?.data && products.data.length > 0 && viewMode === 'list'" class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex-shrink-0 rounded-lg overflow-hidden bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center">
                                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-10 w-10 object-cover" />
                                    <span v-else class="text-sm font-bold text-indigo-600">{{ product.name.charAt(0) }}</span>
                                </div>
                                <div>
                                    <Link :href="route('staff.products.show', product.id)" class="text-sm font-medium text-gray-900 hover:text-customOrange">{{ product.name }}</Link>
                                    <p v-if="showDeleted" class="text-xs text-amber-600 font-medium">Archived</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ product.sku }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="product.category" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700">{{ product.category.name }}</span>
                            <span v-else class="text-sm text-gray-400">—</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-emerald-600">₱{{ product.unit_price }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', getStockBadgeClass(product.stock_status)]">
                                {{ getStockBadgeText(product.stock_status) }} ({{ product.current_stock }})
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div v-if="!showDeleted" class="flex items-center justify-end gap-1">
                                <Link :href="route('staff.products.show', product.id)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded" title="View">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link :href="route('staff.products.edit', product.id)" class="p-1.5 text-gray-600 hover:bg-gray-100 rounded" title="Edit">
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button @click="deleteProduct(product.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Delete">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <div v-else class="flex items-center justify-end gap-1">
                                <button @click="restoreProduct(product.id)" class="p-1.5 text-green-600 hover:bg-green-50 rounded" title="Restore">
                                    <ArrowPathIcon class="h-4 w-4" />
                                </button>
                                <button @click="forceDeleteProduct(product.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Delete Forever">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="!products?.data || products.data.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
            <div class="mx-auto h-24 w-24 text-gray-400 mb-4">
                <ArchiveBoxIcon v-if="showDeleted" class="w-full h-full" />
                <svg v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ showDeleted ? 'No archived products' : 'No products found' }}
            </h3>
            <p class="text-gray-500 mb-4">
                {{ showDeleted ? 'All your products are active. Archived products will appear here.' : 'Get started by creating a new product.' }}
            </p>
            <Link
                v-if="!showDeleted"
                :href="route('staff.products.create')"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                Add Your First Product
            </Link>
            <button
                v-else
                @click="showDeleted = false"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                <ArrowPathIcon class="h-5 w-5 mr-2" />
                Back to Active Products
            </button>
        </div>

        <!-- Pagination -->
        <div v-if="products?.data?.length > 0 && lastPage > 1" class="mt-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium">{{ products.meta.from }}</span> to <span class="font-medium">{{ products.meta.to }}</span> of <span class="font-medium">{{ products.meta.total }}</span> products
                </p>
                <nav class="flex items-center gap-1">
                    <button
                        v-if="currentPage > 1"
                        @click="goToPage(currentPage - 1)"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                    >
                        <ChevronLeftIcon class="h-4 w-4 mr-1" />
                        Previous
                    </button>
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
                                    ? 'bg-customOrange text-white border-customOrange shadow-sm'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            ]"
                        >
                            {{ page }}
                        </button>
                    </template>
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
    </AuthenticatedLayout>
</template>
