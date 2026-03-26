<script setup>
import { ref, watch, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import { useRouteLoading } from '@/composables/useRouteLoading';
import { TableSkeleton } from '@/Components/Skeletons';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    XMarkIcon,
    TagIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ChevronUpDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    categories: Array,
    filters: Object,
});

const page = usePage();
const { isNavigating } = useRouteLoading();

// Notification system — replaces Inertia flash props
const { success: notifySuccess, error: notifyError } = useNotification();
const flash = computed(() => page.props.flash || {});

// Bridge: convert Inertia server-side flash to composable toasts
if (flash.value.success) {
    notifySuccess(flash.value.success);
}
if (page.props.errors?.delete) {
    notifyError(page.props.errors.delete);
}

// Search
const search = ref(props.filters?.search || '');
let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.categories.index'), {
            search: search.value,
            sort: sortField.value,
            direction: sortDirection.value,
        }, { preserveState: true, replace: true });
    }, 300);
});

// Sort
const sortField = ref(props.filters?.sort || 'name');
const sortDirection = ref(props.filters?.direction || 'asc');

const toggleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    router.get(route('admin.categories.index'), {
        search: search.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

// Modal state
const showModal = ref(false);
const modalMode = ref('add'); // 'add' | 'edit' | 'delete'
const editingCategory = ref(null);

const form = useForm({
    id: '',
    name: '',
});

const openAdd = () => {
    modalMode.value = 'add';
    form.reset();
    form.clearErrors();
    editingCategory.value = null;
    showModal.value = true;
};

const openEdit = (cat) => {
    modalMode.value = 'edit';
    form.clearErrors();
    editingCategory.value = cat;
    form.id = cat.id;
    form.name = cat.name;
    showModal.value = true;
};

const openDelete = (cat) => {
    modalMode.value = 'delete';
    form.clearErrors();
    editingCategory.value = cat;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editingCategory.value = null;
};

const submit = () => {
    if (modalMode.value === 'add') {
        form.transform((data) => ({
            ...data,
            id: data.id.toUpperCase(),
        })).post(route('admin.categories.store'), {
            onSuccess: () => {
                closeModal();
                notifySuccess('Category created successfully.');
            },
            preserveScroll: true,
        });
    } else if (modalMode.value === 'edit') {
        form.transform((data) => ({
            ...data,
            id: data.id.toUpperCase(),
        })).put(route('admin.categories.update', editingCategory.value.id), {
            onSuccess: () => {
                closeModal();
                notifySuccess('Category updated successfully.');
            },
            preserveScroll: true,
        });
    } else if (modalMode.value === 'delete') {
        router.delete(route('admin.categories.destroy', editingCategory.value.id), {
            onSuccess: () => {
                closeModal();
                notifySuccess('Category deleted successfully.');
            },
            onError: (errors) => {
                closeModal();
                notifyError(errors.delete || 'Failed to delete category.');
            },
            preserveScroll: true,
        });
    }
};

const sortIcon = (field) => {
    if (sortField.value !== field) return ChevronUpDownIcon;
    return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon;
};
</script>

<template>
    <Head title="Categories" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <TagIcon class="h-7 w-7 mr-2 text-customOrange" />
                        Category Management
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Manage product categories for your warehouse.</p>
                </div>
                <button
                    @click="openAdd"
                    class="inline-flex items-center px-4 py-2 bg-customOrange text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-orange-600 transition"
                >
                    <PlusIcon class="h-5 w-5 mr-1" />
                    Add Category
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative max-w-md">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by ID or name..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-customOrange focus:border-customOrange"
                />
                <button
                    v-if="search"
                    @click="search = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>

            <!-- Skeleton Loading -->
            <TableSkeleton v-if="isNavigating" :columns="6" :rows="6" :show-actions="true" :show-pagination="false" />

            <!-- Table -->
            <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 transition"
                                @click="toggleSort('id')"
                            >
                                <div class="flex items-center gap-1">
                                    ID
                                    <component :is="sortIcon('id')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 transition"
                                @click="toggleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Name
                                    <component :is="sortIcon('name')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Products
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 transition"
                                @click="toggleSort('created_at')"
                            >
                                <div class="flex items-center gap-1">
                                    Created At
                                    <component :is="sortIcon('created_at')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 transition"
                                @click="toggleSort('updated_at')"
                            >
                                <div class="flex items-center gap-1">
                                    Updated At
                                    <component :is="sortIcon('updated_at')" class="h-4 w-4" />
                                </div>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="cat in categories"
                            :key="cat.id"
                            class="hover:bg-gray-50 transition"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ cat.id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ cat.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        cat.products_count > 0
                                            ? 'bg-blue-50 text-blue-700'
                                            : 'bg-gray-100 text-gray-500',
                                    ]"
                                >
                                    {{ cat.products_count }} {{ cat.products_count === 1 ? 'product' : 'products' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ cat.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ cat.updated_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="openEdit(cat)"
                                        class="inline-flex items-center p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="openDelete(cat)"
                                        class="inline-flex items-center p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="Delete"
                                        :disabled="cat.products_count > 0"
                                        :class="{ 'opacity-30 cursor-not-allowed': cat.products_count > 0 }"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="categories.length === 0" class="py-12 text-center">
                    <TagIcon class="mx-auto h-12 w-12 text-gray-300" />
                    <h3 class="mt-3 text-sm font-medium text-gray-900">No categories found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ search ? 'Try adjusting your search.' : 'Get started by creating a new category.' }}
                    </p>
                    <button
                        v-if="!search"
                        @click="openAdd"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-customOrange text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition"
                    >
                        <PlusIcon class="h-5 w-5 mr-1" />
                        Add Category
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <TagIcon class="h-6 w-6 mr-2 text-customOrange" />
                            <span v-if="modalMode === 'add'">Add Category</span>
                            <span v-else-if="modalMode === 'edit'">Edit Category</span>
                            <span v-else>Delete Category</span>
                        </h2>
                        <button @click="closeModal" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Add / Edit Form -->
                    <form v-if="modalMode !== 'delete'" @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Category ID <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.id"
                                type="text"
                                :placeholder="modalMode === 'add' ? 'e.g., AG' : ''"
                                maxlength="10"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange uppercase"
                                @input="form.id = form.id.toUpperCase()"
                            />
                            <p v-if="form.errors.id" class="mt-1 text-xs text-red-600">{{ form.errors.id }}</p>
                            <p v-else class="mt-1 text-xs text-gray-500">Uppercase letters only (e.g., AG for Aggregates)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                :placeholder="modalMode === 'add' ? 'e.g., Aggregates' : ''"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.id.trim() || !form.name.trim()"
                                :class="[
                                    'px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-50 transition',
                                    modalMode === 'add'
                                        ? 'bg-customOrange hover:bg-orange-600'
                                        : 'bg-amber-600 hover:bg-amber-700',
                                ]"
                            >
                                {{ form.processing ? 'Saving...' : (modalMode === 'add' ? 'Add Category' : 'Update Category') }}
                            </button>
                        </div>
                    </form>

                    <!-- Delete Confirmation -->
                    <div v-else class="space-y-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm text-red-800">
                                Are you sure you want to delete category
                                <strong>"{{ editingCategory?.name }}"</strong>
                                (<span class="font-mono">{{ editingCategory?.id }}</span>)?
                            </p>
                            <p class="text-xs text-red-600 mt-2">This action cannot be undone.</p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                            >
                                Delete Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
