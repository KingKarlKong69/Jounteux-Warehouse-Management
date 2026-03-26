<script setup>
import { ref, watch, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import { useRouteLoading } from '@/composables/useRouteLoading';
import { TableSkeleton } from '@/Components/Skeletons';
import Pagination from '@/Components/Pagination.vue';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpDownIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    XMarkIcon,
    UserGroupIcon,
    EnvelopeIcon,
    PhoneIcon,
    ArchiveBoxIcon,
    DocumentArrowDownIcon,
    TableCellsIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customers: Object,
    filters: Object,
    trashedCount: Number,
});

// Filters
const search = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sort || 'customer_name');
const sortDirection = ref(props.filters?.direction || 'asc');
const showDeleted = ref(props.filters?.show_deleted === 'true' || false);
const showExportMenu = ref(false);

// ─── Export / Report ───
const exportUrl = (type) => {
    const params = new URLSearchParams({
        search: search.value,
        sort: sortField.value,
        direction: sortDirection.value,
        show_deleted: showDeleted.value ? 'true' : '',
    });
    if (type === 'csv') return route('admin.customers.export.csv') + '?' + params.toString();
    if (type === 'excel') return route('admin.customers.export.excel') + '?' + params.toString();
    if (type === 'report') return route('admin.customers.report') + '?' + params.toString();
    return '#';
};

// Notification system
const { success, error, confirm } = useNotification();
const { isNavigating } = useRouteLoading();

// Modal state
const showModal = ref(false);
const modalMode = ref('add'); // 'add' | 'edit' | 'delete'
const editingCustomerId = ref(null);
const customerToDelete = ref(null);

// Form for customer
const form = useForm({
    customer_name: '',
    email: '',
    phone: '',
    address: '',
});

// Debounce search
let searchTimeout;
watch([search, showDeleted], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const applyFilters = () => {
    router.get(route('admin.customers.index'), {
        search: search.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
        show_deleted: showDeleted.value ? 'true' : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
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

// Phone formatting helpers
const phoneDisplay = ref('');

const formatPhoneForDisplay = (raw) => {
    if (!raw) return '';
    const digits = raw.replace(/\D/g, '').replace(/^0+/, '');
    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return digits.slice(0, 3) + ' ' + digits.slice(3);
    return digits.slice(0, 3) + ' ' + digits.slice(3, 6) + ' ' + digits.slice(6, 10);
};

const onPhoneInput = (e) => {
    let digits = e.target.value.replace(/\D/g, '');
    digits = digits.replace(/^0+/, '');
    digits = digits.slice(0, 10);
    form.phone = digits;
    phoneDisplay.value = formatPhoneForDisplay(digits);
    e.target.value = phoneDisplay.value;
};

// Modal functions
const openAddModal = () => {
    modalMode.value = 'add';
    form.reset();
    form.clearErrors();
    phoneDisplay.value = '';
    showModal.value = true;
};

const openEditModal = (customer) => {
    modalMode.value = 'edit';
    editingCustomerId.value = customer.id;
    form.customer_name = customer.customer_name;
    form.email = customer.email;
    form.phone = customer.phone;
    phoneDisplay.value = formatPhoneForDisplay(customer.phone);
    form.address = customer.address || '';
    form.clearErrors();
    showModal.value = true;
};

const openDeleteModal = (customer) => {
    modalMode.value = 'delete';
    customerToDelete.value = customer;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editingCustomerId.value = null;
    customerToDelete.value = null;
};

const submitForm = () => {
    if (modalMode.value === 'add') {
        form.post(route('admin.customers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                success('Customer created successfully.');
            },
        });
    } else if (modalMode.value === 'edit') {
        form.put(route('admin.customers.update', editingCustomerId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                success('Customer updated successfully.');
            },
        });
    }
};

const confirmDelete = () => {
    if (!customerToDelete.value) return;
    router.delete(route('admin.customers.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            success('Customer deleted successfully.');
        },
        onError: (errors) => {
            closeModal();
            error(errors.delete || 'Failed to delete customer.');
        },
    });
};

const restoreCustomer = (id) => {
    confirm({
        title: 'Restore Customer',
        message: 'Are you sure you want to restore this customer?',
        confirmLabel: 'Restore',
        onConfirm: () => {
            router.put(route('admin.customers.restore', id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    success('Customer restored successfully.');
                },
            });
        },
    });
};

const forceDeleteCustomer = (id) => {
    confirm({
        title: 'Permanent Delete',
        message: 'This action cannot be undone! The customer will be permanently removed.',
        confirmLabel: 'Delete Forever',
        destructive: true,
        onConfirm: () => {
            router.delete(route('admin.customers.force-delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    success('Customer permanently deleted.');
                },
                onError: (errors) => {
                    error(errors.delete || 'Failed to permanently delete customer.');
                },
            });
        },
    });
};

// Pagination
const goToPage = (page) => {
    router.get(route('admin.customers.index'), {
        page,
        search: search.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
        show_deleted: showDeleted.value ? 'true' : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Customers" />

    <AuthenticatedLayout>


        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
                <p class="mt-2 text-sm text-gray-600">Manage your customer directory</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                <!-- Add Customer Button -->
                <button
                    v-if="!showDeleted"
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 active:bg-teal-900 focus:outline-none focus:border-teal-900 focus:ring focus:ring-teal-300 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add Customer
                </button>
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
                            ? 'border-teal-500 text-teal-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <UserGroupIcon :class="['mr-2 h-5 w-5', !showDeleted ? 'text-teal-500' : 'text-gray-400 group-hover:text-gray-500']" />
                    Active
                </button>
                <button
                    @click="showDeleted = true"
                    :class="[
                        'group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm transition',
                        showDeleted
                            ? 'border-teal-500 text-teal-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <ArchiveBoxIcon :class="['mr-2 h-5 w-5', showDeleted ? 'text-teal-500' : 'text-gray-400 group-hover:text-gray-500']" />
                    Archives
                    <span v-if="trashedCount > 0" :class="[
                        'ml-2 px-2 py-0.5 rounded-full text-xs font-medium',
                        showDeleted ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-600'
                    ]">
                        {{ trashedCount }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Search Bar -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by customer name, email, or phone..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                    />
                </div>
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
        <TableSkeleton v-if="isNavigating" :columns="7" :rows="8" :show-avatar="true" :show-actions="true" :show-pagination="true" />

        <!-- Customers Table -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                @click="toggleSort('customer_name')"
                            >
                                <div class="flex items-center gap-1">
                                    Customer Name
                                    <component :is="getSortIcon('customer_name')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                @click="toggleSort('email')"
                            >
                                <div class="flex items-center gap-1">
                                    Email
                                    <component :is="getSortIcon('email')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Phone
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Address
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                @click="toggleSort('created_at')"
                            >
                                <div class="flex items-center gap-1">
                                    Created
                                    <component :is="getSortIcon('created_at')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                SOs
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="customer in customers?.data"
                            :key="customer.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-teal-100 rounded-full flex items-center justify-center">
                                        <UserGroupIcon class="h-5 w-5 text-teal-600" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ customer.customer_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-700">
                                    <EnvelopeIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                    {{ customer.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-700">
                                    <PhoneIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                    <span class="text-gray-500 font-medium">63+</span>{{ customer.phone_formatted || customer.phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="customer.address" class="text-sm text-gray-500 max-w-[200px] truncate" :title="customer.address">
                                    {{ customer.address }}
                                </div>
                                <span v-else class="text-sm text-gray-400 italic">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ customer.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        (customer.sales_orders_count || 0) > 0
                                            ? 'bg-teal-100 text-teal-800'
                                            : 'bg-gray-100 text-gray-600'
                                    ]"
                                >
                                    {{ customer.sales_orders_count || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <!-- Active customer actions -->
                                <template v-if="!showDeleted">
                                    <button
                                        @click="openEditModal(customer)"
                                        class="text-teal-600 hover:text-teal-900 mr-3 transition"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="openDeleteModal(customer)"
                                        :class="[
                                            'transition',
                                            (customer.sales_orders_count || 0) > 0
                                                ? 'text-gray-300 cursor-not-allowed'
                                                : 'text-red-600 hover:text-red-900'
                                        ]"
                                        :disabled="(customer.sales_orders_count || 0) > 0"
                                        :title="(customer.sales_orders_count || 0) > 0 ? 'Cannot delete — has linked SOs' : 'Delete'"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </template>

                                <!-- Trashed customer actions -->
                                <template v-else>
                                    <button
                                        @click="restoreCustomer(customer.id)"
                                        class="text-green-600 hover:text-green-900 mr-3 transition"
                                        title="Restore"
                                    >
                                        <ArrowPathIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="forceDeleteCustomer(customer.id)"
                                        class="text-red-600 hover:text-red-900 transition"
                                        title="Permanently Delete"
                                    >
                                        <ExclamationTriangleIcon class="h-5 w-5" />
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!customers?.data?.length">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">
                                    {{ showDeleted ? 'No archived customers' : 'No customers found' }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ showDeleted ? 'Archived customers will appear here.' : 'Get started by adding a new customer.' }}
                                </p>
                                <div v-if="!showDeleted" class="mt-6">
                                    <button
                                        @click="openAddModal"
                                        class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 transition"
                                    >
                                        <PlusIcon class="h-5 w-5 mr-2" />
                                        Add Customer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="customers.meta" label="customers" @navigate="goToPage" />
        </div><!-- /Customers Table -->

        <!-- Modal Overlay -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                    <!-- Modal Panel -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="showModal"
                            class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                        >
                            <!-- Delete Confirmation -->
                            <template v-if="modalMode === 'delete'">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Customer</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">
                                                    Are you sure you want to delete
                                                    <strong>{{ customerToDelete?.customer_name }}</strong>?
                                                    This customer will be moved to trash and can be restored later.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        @click="confirmDelete"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Delete
                                    </button>
                                    <button
                                        @click="closeModal"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </template>

                            <!-- Add / Edit Form -->
                            <template v-else>
                                <form @submit.prevent="submitForm">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                                        <div class="flex items-center justify-between mb-6">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                                {{ modalMode === 'add' ? 'Add Customer' : 'Edit Customer' }}
                                            </h3>
                                            <button type="button" @click="closeModal" class="text-gray-400 hover:text-gray-600 transition">
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Customer Name -->
                                            <div>
                                                <label for="customer_name" class="block text-sm font-medium text-gray-700">
                                                    Customer Name <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    id="customer_name"
                                                    v-model="form.customer_name"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                                    :class="{ 'border-red-500': form.errors.customer_name }"
                                                    placeholder="e.g., Juan Dela Cruz"
                                                />
                                                <p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-600">{{ form.errors.customer_name }}</p>
                                            </div>

                                            <!-- Email -->
                                            <div>
                                                <label for="email" class="block text-sm font-medium text-gray-700">
                                                    Email <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    id="email"
                                                    v-model="form.email"
                                                    type="email"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                                    :class="{ 'border-red-500': form.errors.email }"
                                                    placeholder="customer@example.com"
                                                />
                                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                                            </div>

                                            <!-- Phone -->
                                            <div>
                                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                                    Phone <span class="text-red-500">*</span>
                                                </label>
                                                <div class="mt-1 flex">
                                                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-600 sm:text-sm font-medium select-none">
                                                        63+
                                                    </span>
                                                    <input
                                                        id="phone"
                                                        :value="phoneDisplay"
                                                        @input="onPhoneInput"
                                                        type="text"
                                                        inputmode="numeric"
                                                        maxlength="12"
                                                        class="block w-full border-gray-300 rounded-r-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                                        :class="{ 'border-red-500': form.errors.phone }"
                                                        placeholder="912 345 6789"
                                                    />
                                                </div>
                                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                                            </div>

                                            <!-- Address -->
                                            <div>
                                                <label for="address" class="block text-sm font-medium text-gray-700">
                                                    Address
                                                </label>
                                                <textarea
                                                    id="address"
                                                    v-model="form.address"
                                                    rows="3"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                                    placeholder="Full address..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                        >
                                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ modalMode === 'add' ? 'Create Customer' : 'Save Changes' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeModal"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:w-auto sm:text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
