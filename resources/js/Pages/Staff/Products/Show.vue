<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    PencilIcon,
    CubeIcon,
    TagIcon,
    CurrencyDollarIcon,
    ClockIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    MinusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    product: Object,
    stockLedgers: Array,
});

const getStockBadgeClass = (status) => {
    const classes = {
        'in_stock': 'bg-green-100 text-green-800 border-green-200',
        'low_stock': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'out_of_stock': 'bg-red-100 text-red-800 border-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStockBadgeText = (status) => {
    const text = {
        'in_stock': 'In Stock',
        'low_stock': 'Low Stock',
        'out_of_stock': 'Out of Stock',
    };
    return text[status] || 'Unknown';
};

const getMovementIcon = (type) => {
    if (type === 'in') return ArrowUpIcon;
    if (type === 'out') return ArrowDownIcon;
    return MinusIcon;
};

const getMovementClass = (type) => {
    if (type === 'in') return 'text-green-600 bg-green-50';
    if (type === 'out') return 'text-red-600 bg-red-50';
    return 'text-gray-600 bg-gray-50';
};
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <Link
                :href="route('staff.products.index')"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
            >
                <ArrowLeftIcon class="h-4 w-4 mr-1" />
                Back to Products
            </Link>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ product.name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">SKU: {{ product.sku }}</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <Link
                        :href="route('staff.products.edit', product.id)"
                        class="inline-flex items-center px-4 py-2 bg-customOrange border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-black transition"
                    >
                        <PencilIcon class="h-4 w-4 mr-2" />
                        Edit Product
                    </Link>
                </div>
            </div>
        </div>

        <!-- Product Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Product Image -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="h-64 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-indigo-100">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="text-center">
                            <p class="text-6xl font-bold text-indigo-600">{{ product.name.charAt(0) }}</p>
                            <p class="text-sm text-indigo-500 mt-2">{{ product.sku }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Product Information</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Category -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 p-2 bg-indigo-50 rounded-lg">
                                <TagIcon class="h-5 w-5 text-indigo-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Category</p>
                                <p class="text-base font-semibold text-gray-900">
                                    {{ product.category?.name || 'Uncategorized' }}
                                    <span v-if="product.category" class="text-sm font-normal text-gray-500">({{ product.category.id }})</span>
                                </p>
                            </div>
                        </div>

                        <!-- Unit Price -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 p-2 bg-emerald-50 rounded-lg">
                                <CurrencyDollarIcon class="h-5 w-5 text-emerald-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Unit Price</p>
                                <p class="text-base font-semibold text-emerald-700">₱{{ product.unit_price }}</p>
                            </div>
                        </div>

                        <!-- Current Stock -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 p-2 bg-blue-50 rounded-lg">
                                <CubeIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Current Stock</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-base font-semibold text-gray-900">{{ product.current_stock }} units</p>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border', getStockBadgeClass(product.stock_status)]">
                                        {{ getStockBadgeText(product.stock_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Created At -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 p-2 bg-gray-50 rounded-lg">
                                <ClockIcon class="h-5 w-5 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Created</p>
                                <p class="text-base font-semibold text-gray-900">{{ product.created_at }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="product.description" class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-500 mb-2">Description</p>
                        <p class="text-gray-700 leading-relaxed">{{ product.description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Recent Stock Movements</h2>
                <p class="text-sm text-gray-500 mt-1">Last 20 stock ledger entries for this product</p>
            </div>

            <div v-if="stockLedgers && stockLedgers.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="entry in stockLedgers" :key="entry.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span :class="['inline-flex items-center justify-center w-7 h-7 rounded-full', getMovementClass(entry.movement_type)]">
                                        <component :is="getMovementIcon(entry.movement_type)" class="h-4 w-4" />
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ entry.movement_label }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ entry.reference_type_label }}</span>
                                <span v-if="entry.reference_label" class="text-sm text-gray-500 ml-1">({{ entry.reference_label }})</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span :class="['text-sm font-semibold', entry.movement_type === 'in' ? 'text-green-600' : entry.movement_type === 'out' ? 'text-red-600' : 'text-gray-600']">
                                    {{ entry.movement_type === 'in' ? '+' : entry.movement_type === 'out' ? '-' : '' }}{{ entry.quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-medium text-gray-900">{{ entry.balance_after }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700">{{ entry.created_by_name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">{{ entry.created_at_formatted }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="px-6 py-12 text-center">
                <CubeIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No stock movements</h3>
                <p class="mt-1 text-sm text-gray-500">Stock ledger entries will appear here after purchase or sales orders are processed.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
