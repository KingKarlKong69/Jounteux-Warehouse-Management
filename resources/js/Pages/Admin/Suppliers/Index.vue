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
    BuildingStorefrontIcon,
    EnvelopeIcon,
    PhoneIcon,
    UserIcon,
    ArchiveBoxIcon,
    DocumentArrowDownIcon,
    TableCellsIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    suppliers: Object,
    filters: Object,
    trashedCount: Number,
});

// Filters
const search = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sort || 'company_name');
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
    if (type === 'csv') return route('admin.suppliers.export.csv') + '?' + params.toString();
    if (type === 'excel') return route('admin.suppliers.export.excel') + '?' + params.toString();
    if (type === 'report') return route('admin.suppliers.report') + '?' + params.toString();
    return '#';
};

// Notification system
const { success, error, confirm } = useNotification();
const { isNavigating } = useRouteLoading();

// Modal state
const showModal = ref(false);
const modalMode = ref('add'); // 'add' | 'edit' | 'delete'
const editingSupplierId = ref(null);
const supplierToDelete = ref(null);

// Form for supplier
const form = useForm({
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
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
    router.get(route('admin.suppliers.index'), {
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
    // Strip everything except digits
    let digits = e.target.value.replace(/\D/g, '');
    // Strip leading zeros
    digits = digits.replace(/^0+/, '');
    // Limit to 10 digits
    digits = digits.slice(0, 10);
    // Store raw digits in form
    form.phone = digits;
    // Format for display
    phoneDisplay.value = formatPhoneForDisplay(digits);
    // Update input value
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

const openEditModal = (supplier) => {
    modalMode.value = 'edit';
    editingSupplierId.value = supplier.id;
    form.company_name = supplier.company_name;
    form.contact_person = supplier.contact_person || '';
    form.email = supplier.email;
    form.phone = supplier.phone;
    phoneDisplay.value = formatPhoneForDisplay(supplier.phone);
    form.address = supplier.address || '';
    form.notes = supplier.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const openDeleteModal = (supplier) => {
    modalMode.value = 'delete';
    supplierToDelete.value = supplier;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editingSupplierId.value = null;
    supplierToDelete.value = null;
};

const submitForm = () => {
    if (modalMode.value === 'add') {
        form.post(route('admin.suppliers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                success('Supplier created successfully.');
            },
        });
    } else if (modalMode.value === 'edit') {
        form.put(route('admin.suppliers.update', editingSupplierId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                success('Supplier updated successfully.');
            },
        });
    }
};

const confirmDelete = () => {
    if (!supplierToDelete.value) return;
    router.delete(route('admin.suppliers.destroy', supplierToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            success('Supplier deleted successfully.');
        },
        onError: (errors) => {
            closeModal();
            error(errors.delete || 'Failed to delete supplier.');
        },
    });
};

const restoreSupplier = (id) => {
    confirm({
        title: 'Restore Supplier',
        message: 'Are you sure you want to restore this supplier?',
        confirmLabel: 'Restore',
        onConfirm: () => {
            router.put(route('admin.suppliers.restore', id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    success('Supplier restored successfully.');
                },
            });
        },
    });
};

const forceDeleteSupplier = (id) => {
    confirm({
        title: 'Permanent Delete',
        message: 'This action cannot be undone! The supplier will be permanently removed.',
        confirmLabel: 'Delete Forever',
        destructive: true,
        onConfirm: () => {
            router.delete(route('admin.suppliers.force-delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    success('Supplier permanently deleted.');
                },
                onError: (errors) => {
                    error(errors.delete || 'Failed to permanently delete supplier.');
                },
            });
        },
    });
};

// Pagination
const goToPage = (page) => {
    router.get(route('admin.suppliers.index'), {
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
    <Head title="Suppliers" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Suppliers</h1>
                <p class="mt-2 text-sm text-gray-600">Manage your supplier directory</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                <!-- Add Supplier Button -->
                <button
                    v-if="!showDeleted"
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-customOrange border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-black active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add Supplier
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
                            ? 'border-customOrange text-customOrange'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <BuildingStorefrontIcon :class="['mr-2 h-5 w-5', !showDeleted ? 'text-customOrange' : 'text-gray-400 group-hover:text-gray-500']" />
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

        <!-- Search Bar -->
        <div class="mb-6 bg-white shadow rounded-lg p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by company name, contact, email, or phone..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
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

        <!-- Suppliers Table -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                @click="toggleSort('company_name')"
                            >
                                <div class="flex items-center gap-1">
                                    Company Name
                                    <component :is="getSortIcon('company_name')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact Person
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
                                POs
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="supplier in suppliers?.data"
                            :key="supplier.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                        <BuildingStorefrontIcon class="h-5 w-5 text-gray-700" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ supplier.company_name }}</div>
                                        <div v-if="supplier.address" class="text-xs text-gray-500 max-w-[200px] truncate">
                                            {{ supplier.address }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="supplier.contact_person" class="flex items-center text-sm text-gray-700">
                                    <UserIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                    {{ supplier.contact_person }}
                                </div>
                                <span v-else class="text-sm text-gray-400 italic">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-700">
                                    <EnvelopeIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                    {{ supplier.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-700">
                                    <PhoneIcon class="h-4 w-4 mr-1.5 text-gray-400" />
                                    <span class="text-gray-500 font-medium">63+</span>{{ supplier.phone_formatted || supplier.phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ supplier.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        (supplier.purchase_orders_count || 0) > 0
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-gray-100 text-gray-600'
                                    ]"
                                >
                                    {{ supplier.purchase_orders_count || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <!-- Active supplier actions -->
                                <template v-if="!showDeleted">
                                    <button
                                        @click="openEditModal(supplier)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3 transition"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="openDeleteModal(supplier)"
                                        :class="[
                                            'transition',
                                            (supplier.purchase_orders_count || 0) > 0
                                                ? 'text-gray-300 cursor-not-allowed'
                                                : 'text-red-600 hover:text-red-900'
                                        ]"
                                        :disabled="(supplier.purchase_orders_count || 0) > 0"
                                        :title="(supplier.purchase_orders_count || 0) > 0 ? 'Cannot delete — has linked POs' : 'Delete'"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </template>

                                <!-- Trashed supplier actions -->
                                <template v-else>
                                    <button
                                        @click="restoreSupplier(supplier.id)"
                                        class="text-green-600 hover:text-green-900 mr-3 transition"
                                        title="Restore"
                                    >
                                        <ArrowPathIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="forceDeleteSupplier(supplier.id)"
                                        class="text-red-600 hover:text-red-900 transition"
                                        title="Permanently Delete"
                                    >
                                        <ExclamationTriangleIcon class="h-5 w-5" />
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!suppliers?.data?.length">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <BuildingStorefrontIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">
                                    {{ showDeleted ? 'No archived suppliers' : 'No suppliers found' }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ showDeleted ? 'Archived suppliers will appear here.' : 'Get started by adding a new supplier.' }}
                                </p>
                                <div v-if="!showDeleted" class="mt-6">
                                    <button
                                        @click="openAddModal"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                    >
                                        <PlusIcon class="h-5 w-5 mr-2" />
                                        Add Supplier
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="suppliers.meta" label="suppliers" @navigate="goToPage" />
        </div><!-- /Suppliers Table -->

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
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Supplier</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">
                                                    Are you sure you want to delete
                                                    <strong>{{ supplierToDelete?.company_name }}</strong>?
                                                    This supplier will be moved to trash and can be restored later.
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
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
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
                                                {{ modalMode === 'add' ? 'Add Supplier' : 'Edit Supplier' }}
                                            </h3>
                                            <button type="button" @click="closeModal" class="text-gray-400 hover:text-gray-600 transition">
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Company Name -->
                                            <div>
                                                <label for="company_name" class="block text-sm font-medium text-gray-700">
                                                    Company Name <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    id="company_name"
                                                    v-model="form.company_name"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    :class="{ 'border-red-500': form.errors.company_name }"
                                                    placeholder="e.g., Acme Construction Supply"
                                                />
                                                <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
                                            </div>

                                            <!-- Contact Person -->
                                            <div>
                                                <label for="contact_person" class="block text-sm font-medium text-gray-700">
                                                    Contact Person
                                                </label>
                                                <input
                                                    id="contact_person"
                                                    v-model="form.contact_person"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    placeholder="e.g., John Doe"
                                                />
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
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    :class="{ 'border-red-500': form.errors.email }"
                                                    placeholder="supplier@example.com"
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
                                                        class="block w-full border-gray-300 rounded-r-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
                                                    rows="2"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    placeholder="Full address..."
                                                ></textarea>
                                            </div>

                                            <!-- Notes -->
                                            <div>
                                                <label for="notes" class="block text-sm font-medium text-gray-700">
                                                    Notes
                                                </label>
                                                <textarea
                                                    id="notes"
                                                    v-model="form.notes"
                                                    rows="2"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-customOrange focus:border-customOrange sm:text-sm"
                                                    placeholder="Additional notes about this supplier..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-customOrange text-base font-medium text-white hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                        >
                                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ modalMode === 'add' ? 'Create Supplier' : 'Save Changes' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeModal"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
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
