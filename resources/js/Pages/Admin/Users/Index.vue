<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    LockClosedIcon,
    LockOpenIcon,
    ExclamationTriangleIcon,
    EyeIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

interface UserItem {
    id: number;
    name: string;
    email: string;
    contact_number: string | null;
    role: string;
    role_label: string;
    is_blocked: boolean;
    blocked_at: string | null;
    blocked_at_formatted: string | null;
    block_reason: string | null;
    block_reason_label: string | null;
    failed_login_attempts: number;
    created_at: string;
    created_at_formatted: string;
    updated_at: string;
    updated_at_formatted: string;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number | null;
            to: number | null;
        };
    };
    filters: {
        search: string;
        role: string;
        date_preset: string;
        date_from: string;
        date_to: string;
        per_page: number;
    };
    roleTabs: string[];
    stats: {
        total: number;
        admins: number;
        staff: number;
        blocked: number;
    };
}>();

// ── Reactive filter state ──
const search = ref(props.filters.search);
const activeRole = ref(props.filters.role || 'all');
const datePreset = ref(props.filters.date_preset);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const showDateFilters = ref(false);

// ── Modal state ──
const showBlockModal = ref(false);
const showDeleteModal = ref(false);
const showDetailModal = ref(false);
const targetUser = ref<UserItem | null>(null);
const deleteConfirmName = ref('');
const deleteAdminPassword = ref('');
const deleteErrors = ref<Record<string, string>>({});
const processing = ref(false);

// ── Debounced search ──
let searchTimeout: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const params: Record<string, any> = {};
        if (search.value) params.search = search.value;
        if (activeRole.value && activeRole.value !== 'all') params.role = activeRole.value;
        if (datePreset.value) params.date_preset = datePreset.value;
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;

        router.get(route('admin.users.index'), params, {
            preserveState: true,
            replace: true,
        });
    }, 400);
};

watch([search], applyFilters);

const setRoleTab = (tab: string) => {
    activeRole.value = tab;
    applyFilters();
};

const setDatePreset = (preset: string) => {
    datePreset.value = preset;
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const applyCustomDate = () => {
    datePreset.value = '';
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    activeRole.value = 'all';
    datePreset.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

// ── Pagination ──
const currentPage = computed(() => props.users.meta.current_page);
const lastPage = computed(() => props.users.meta.last_page);

const paginationPages = computed(() => {
    const total = lastPage.value;
    const current = currentPage.value;
    const maxVisible = 5;

    if (total <= maxVisible + 2) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages: (number | string)[] = [];
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

const goToPage = (page: number | string) => {
    if (typeof page !== 'number' || page < 1 || page > lastPage.value || page === currentPage.value) return;
    const params: Record<string, any> = { page };
    if (search.value) params.search = search.value;
    if (activeRole.value && activeRole.value !== 'all') params.role = activeRole.value;
    if (datePreset.value) params.date_preset = datePreset.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get(route('admin.users.index'), params, {
        preserveState: true,
        replace: true,
    });
};

// ── Actions ──
const openBlockModal = (user: UserItem) => {
    targetUser.value = user;
    showBlockModal.value = true;
};

const confirmBlock = () => {
    if (!targetUser.value) return;
    processing.value = true;
    router.post(route('admin.users.block', targetUser.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showBlockModal.value = false;
            targetUser.value = null;
            processing.value = false;
            notifySuccess('User blocked successfully.');
        },
        onError: () => {
            processing.value = false;
            notifyError('Failed to block user.');
        },
    });
};

const unblockUser = (user: UserItem) => {
    processing.value = true;
    router.post(route('admin.users.unblock', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            processing.value = false;
            notifySuccess('User unblocked successfully.');
        },
        onError: () => {
            processing.value = false;
            notifyError('Failed to unblock user.');
        },
    });
};

const openDeleteModal = (user: UserItem) => {
    targetUser.value = user;
    deleteConfirmName.value = '';
    deleteAdminPassword.value = '';
    deleteErrors.value = {};
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!targetUser.value) return;
    processing.value = true;
    deleteErrors.value = {};

    router.delete(route('admin.users.destroy', targetUser.value.id), {
        data: {
            confirm_name: deleteConfirmName.value,
            admin_password: deleteAdminPassword.value,
        },
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            targetUser.value = null;
            processing.value = false;
            notifySuccess('User deleted successfully.');
        },
        onError: (errors) => {
            deleteErrors.value = errors as Record<string, string>;
            processing.value = false;
        },
    });
};

const openDetailModal = (user: UserItem) => {
    targetUser.value = user;
    showDetailModal.value = true;
};

const roleTabLabel = (tab: string) => {
    if (tab === 'all') return `All (${props.stats.total})`;
    if (tab === 'admin') return `Admins (${props.stats.admins})`;
    if (tab === 'staff') return `Staff (${props.stats.staff})`;
    return tab;
};

const datePresets = [
    { label: 'All Time', value: '' },
    { label: 'Today', value: 'daily' },
    { label: 'This Week', value: 'weekly' },
    { label: 'This Month', value: 'monthly' },
    { label: 'This Year', value: 'yearly' },
];

const getRoleBadge = (role: string) => {
    return role === 'admin'
        ? 'bg-purple-100 text-purple-800'
        : 'bg-blue-100 text-blue-800';
};

const getBlockReasonLabel = (reason: string | null) => {
    if (!reason) return 'Unknown';
    const map: Record<string, string> = {
        'failed_login_attempts': 'Failed Login Attempts',
        'manual_admin_block': 'Manually Blocked by Admin',
    };
    return map[reason] || reason;
};

// Notification system — replaces Inertia flash props
const { success: notifySuccess, error: notifyError } = useNotification();
const page = usePage();

// Bridge: convert Inertia server-side flash to composable toast on page load
const serverFlash = computed(() => (page.props as any).flash?.success as string | null);
if (serverFlash.value) {
    notifySuccess(serverFlash.value);
}
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage system users, roles, and account status</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex items-center px-4 py-2 bg-customOrange border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-black active:bg-gray-900 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add New User
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow px-4 py-3">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
            </div>
            <div class="bg-white rounded-lg shadow px-4 py-3">
                <p class="text-sm font-medium text-gray-500">Admins</p>
                <p class="text-2xl font-bold text-purple-600">{{ stats.admins }}</p>
            </div>
            <div class="bg-white rounded-lg shadow px-4 py-3">
                <p class="text-sm font-medium text-gray-500">Staff</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.staff }}</p>
            </div>
            <div class="bg-white rounded-lg shadow px-4 py-3">
                <p class="text-sm font-medium text-gray-500">Blocked</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.blocked }}</p>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <!-- Search bar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                    />
                </div>
                <button
                    @click="showDateFilters = !showDateFilters"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    <FunnelIcon class="h-5 w-5 mr-2" />
                    Date Filters
                </button>
                <button
                    v-if="search || activeRole !== 'all' || datePreset || dateFrom || dateTo"
                    @click="clearFilters"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                    <XMarkIcon class="h-5 w-5 mr-1" />
                    Clear
                </button>
            </div>

            <!-- Role tabs -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="tab in roleTabs"
                    :key="tab"
                    @click="setRoleTab(tab)"
                    :class="[
                        'px-4 py-1.5 rounded-full text-sm font-medium transition',
                        activeRole === tab
                            ? 'bg-customOrange text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ roleTabLabel(tab) }}
                </button>
            </div>

            <!-- Date filters -->
            <div v-if="showDateFilters" class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Quick presets -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="dp in datePresets"
                            :key="dp.value"
                            @click="setDatePreset(dp.value)"
                            :class="[
                                'px-3 py-1 rounded-md text-xs font-medium transition',
                                datePreset === dp.value
                                    ? 'bg-customOrange text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ dp.label }}
                        </button>
                    </div>

                    <span class="text-gray-300">|</span>

                    <!-- Custom range -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-customOrange focus:border-customOrange"
                            @change="applyCustomDate"
                        />
                        <span class="text-gray-500 text-sm">to</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-customOrange focus:border-customOrange"
                            @change="applyCustomDate"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ u.id }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ u.name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ u.email }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ u.contact_number || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getRoleBadge(u.role)]">
                                    <ShieldCheckIcon v-if="u.role === 'admin'" class="h-3.5 w-3.5 mr-1" />
                                    {{ u.role_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span v-if="u.is_blocked" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <LockClosedIcon class="h-3.5 w-3.5 mr-1" />
                                    Blocked
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <LockOpenIcon class="h-3.5 w-3.5 mr-1" />
                                    Active
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ u.created_at_formatted }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View Detail -->
                                    <button
                                        @click="openDetailModal(u)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition"
                                        title="View Details"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </button>

                                    <!-- Edit -->
                                    <Link
                                        :href="route('admin.users.edit', u.id)"
                                        class="p-1.5 text-blue-400 hover:text-blue-600 rounded-md hover:bg-blue-50 transition"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>

                                    <!-- Block / Unblock -->
                                    <button
                                        v-if="!u.is_blocked"
                                        @click="openBlockModal(u)"
                                        class="p-1.5 text-orange-400 hover:text-orange-600 rounded-md hover:bg-orange-50 transition"
                                        title="Block User"
                                    >
                                        <LockClosedIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-else
                                        @click="unblockUser(u)"
                                        class="p-1.5 text-green-400 hover:text-green-600 rounded-md hover:bg-green-50 transition"
                                        title="Unblock User"
                                    >
                                        <LockOpenIcon class="h-4 w-4" />
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        @click="openDeleteModal(u)"
                                        class="p-1.5 text-red-400 hover:text-red-600 rounded-md hover:bg-red-50 transition"
                                        title="Delete User"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div v-if="users.data.length === 0" class="text-center py-12">
                <UserGroupIcon class="mx-auto h-16 w-16 text-gray-300" />
                <p class="mt-4 text-lg font-medium text-gray-600">No users found</p>
                <p class="text-gray-400">Try adjusting your search or filters.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.data.length > 0 && lastPage > 1" class="mt-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium">{{ users.meta.from }}</span> to <span class="font-medium">{{ users.meta.to }}</span> of <span class="font-medium">{{ users.meta.total }}</span> users
                </p>

                <nav class="flex items-center gap-1">
                    <button
                        v-if="currentPage > 1"
                        @click="goToPage(currentPage - 1)"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                    >
                        <ChevronLeftIcon class="h-4 w-4 mr-1" />
                        Prev
                    </button>

                    <template v-for="(page, index) in paginationPages" :key="index">
                        <span v-if="page === '...'" class="px-3 py-2 text-sm text-gray-500 select-none">&hellip;</span>
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

        <!-- ─── Block Confirmation Modal ─── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showBlockModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" @click="showBlockModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                <ShieldExclamationIcon class="h-6 w-6 text-orange-600" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900">Block User</h3>
                                <p class="text-sm text-gray-500">This will immediately prevent the user from logging in.</p>
                            </div>
                        </div>

                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                            <p class="text-sm text-orange-800">
                                Are you sure you want to block <strong>{{ targetUser?.name }}</strong> ({{ targetUser?.email }})?
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                @click="showBlockModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmBlock"
                                :disabled="processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Blocking...' : 'Block User' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ─── Secure Delete Modal ─── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900">Permanently Delete User</h3>
                                <p class="text-sm text-gray-500">This action cannot be undone.</p>
                            </div>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-sm text-red-800">
                                You are about to permanently delete <strong>{{ targetUser?.name }}</strong> ({{ targetUser?.email }}).
                            </p>
                        </div>

                        <!-- Errors -->
                        <div v-if="Object.keys(deleteErrors).length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p v-for="(err, key) in deleteErrors" :key="key" class="text-sm text-red-700">{{ err }}</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Confirm name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Type the user's full name to confirm: <strong class="text-red-600">{{ targetUser?.name }}</strong>
                                </label>
                                <input
                                    v-model="deleteConfirmName"
                                    type="text"
                                    placeholder="Type full name..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                />
                            </div>

                            <!-- Admin password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Enter your admin password
                                </label>
                                <input
                                    v-model="deleteAdminPassword"
                                    type="password"
                                    placeholder="Your password..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                @click="showDeleteModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmDelete"
                                :disabled="processing || !deleteConfirmName || !deleteAdminPassword"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Deleting...' : 'Delete Permanently' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ─── User Detail Modal ─── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDetailModal && targetUser" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" @click="showDetailModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">User Details</h3>
                            <button @click="showDetailModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">ID</p>
                                    <p class="text-sm text-gray-900">{{ targetUser.id }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Role</p>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', getRoleBadge(targetUser.role)]">
                                        {{ targetUser.role_label }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Full Name</p>
                                <p class="text-sm text-gray-900">{{ targetUser.name }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Email</p>
                                <p class="text-sm text-gray-900">{{ targetUser.email }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Contact Number</p>
                                <p class="text-sm text-gray-900">{{ targetUser.contact_number || '—' }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                                    <span v-if="targetUser.is_blocked" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <LockClosedIcon class="h-3 w-3 mr-1" />
                                        Blocked
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <LockOpenIcon class="h-3 w-3 mr-1" />
                                        Active
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Failed Login Attempts</p>
                                    <p class="text-sm text-gray-900">{{ targetUser.failed_login_attempts }}</p>
                                </div>
                            </div>

                            <div v-if="targetUser.is_blocked" class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-xs font-medium text-red-600 uppercase">Blocked At</p>
                                        <p class="text-sm text-red-800">{{ targetUser.blocked_at_formatted || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-red-600 uppercase">Block Reason</p>
                                        <p class="text-sm text-red-800">{{ getBlockReasonLabel(targetUser.block_reason) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Created At</p>
                                    <p class="text-sm text-gray-900">{{ targetUser.created_at_formatted }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Updated At</p>
                                    <p class="text-sm text-gray-900">{{ targetUser.updated_at_formatted }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
