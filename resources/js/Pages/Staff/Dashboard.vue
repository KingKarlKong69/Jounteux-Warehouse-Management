<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { CubeIcon, ShoppingCartIcon, ClipboardDocumentListIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { useGreeting } from '@/composables/useGreeting';
import { useTheme } from '@/composables/useTheme';
import { computed } from 'vue';

const { isDark } = useTheme();
const { greeting, currentTime, currentDate } = useGreeting();
const user = usePage().props.auth?.user;

const stats = [
    { name: 'Available Products', value: '156', icon: CubeIcon, gradient: 'from-emerald-500 to-green-600' },
    { name: 'My Sales Orders', value: '12', icon: ShoppingCartIcon, gradient: 'from-orange-500 to-amber-600' },
];

const quickActions = [
    { label: 'View Products', href: '/staff/products', icon: CubeIcon, gradient: 'from-emerald-500 to-green-600' },
    { label: 'Create Sales Order', href: '/staff/sales-orders/create', icon: ClipboardDocumentListIcon, gradient: 'from-orange-500 to-amber-600' },
];
</script>

<template>
    <Head title="Staff Dashboard" />

    <AuthenticatedLayout>
        <!-- Greeting Header Zone -->
        <section class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ greeting }}, {{ user?.name?.split(' ')[0] ?? 'Staff' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ currentDate }} &middot; {{ currentTime }} &middot; Manage your daily tasks here.
                    </p>
                </div>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 mb-8">
            <div
                v-for="stat in stats"
                :key="stat.name"
                class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 group"
            >
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.name }}</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                        </div>
                        <div :class="['bg-gradient-to-br', stat.gradient, 'flex-shrink-0 rounded-xl p-3 shadow-lg']">
                            <component :is="stat.icon" class="h-7 w-7 text-white" />
                        </div>
                    </div>
                </div>
                <!-- Decorative gradient accent -->
                <div :class="['absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r', stat.gradient, 'opacity-0 group-hover:opacity-100 transition-opacity duration-300']"></div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Link
                    v-for="action in quickActions"
                    :key="action.label"
                    :href="action.href"
                    :class="[
                        'inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl text-sm font-semibold text-white',
                        'bg-gradient-to-r', action.gradient,
                        'hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all duration-200'
                    ]"
                >
                    <component :is="action.icon" class="h-5 w-5" />
                    {{ action.label }}
                </Link>
            </div>
        </section>
    </AuthenticatedLayout>
</template>