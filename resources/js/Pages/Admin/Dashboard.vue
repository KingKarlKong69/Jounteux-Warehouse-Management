<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    UserIcon,
    ShoppingCartIcon,
    CubeIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

const stats = [
    {
        name: 'Total Users',
        value: '12',
        icon: UserIcon,
        color: 'bg-blue-500',
    },
    {
        name: 'Products',
        value: '156',
        icon: CubeIcon,
        color: 'bg-green-500',
    },
    {
        name: 'Sales Orders',
        value: '48',
        icon: ShoppingCartIcon,
        color: 'bg-purple-500',
    },
    {
        name: 'Purchase Orders',
        value: '23',
        icon: ClipboardDocumentListIcon,
        color: 'bg-orange-500',
    }
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <!--Page Header-->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-sm text-gray-600">Welcome back! Here's what's happening with your warehouse.</p>
        </div>

        <!--Stats Cards-->
        <!-- <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div
                v-for="stat in stats"
                :key="stat.name"
                class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200"
            >
                <div class="p-5">
                    <div class="flex items-center">
                        <div :class="[stat.color, 'flex-shrink-0 rounded-md p-3']">
                            <component :is="stat.icon" class="h-6 w-6 text-white" />
                        
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ stat.name }}
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900">
                                    {{ stat.value }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!--Quick Action-->
        <!-- <div class="bg-white shadow rounded-lg p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    href="/admin/products/create"
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-150"
                >
                    Add Product
                </a>
                <a
                    href="/admin/purchase-orders"
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-150"
                >
                    New Purchase Order
                </a>
                <a
                    href="/admin/sales-orders/"
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-150"
                >
                    New Sales Order
                </a>
                <a
                    href="/admin/audit-logs"
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 transition-colors duration-150"
                >
                    View Audit Logs
                </a>
            </div>
        </div> -->
    </AuthenticatedLayout>
</template>