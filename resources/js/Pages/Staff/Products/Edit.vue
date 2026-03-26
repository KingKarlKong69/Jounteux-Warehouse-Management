<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useNotification } from '@/composables/useNotification';

const { success, error } = useNotification();

const props = defineProps({
    product: Object,
});

const form = useForm({
    _method: 'put',
    name: props.product.name || '',
    description: props.product.description || '',
    unit_price: props.product.unit_price_raw ?? props.product.unit_price ?? 0,
    current_stock: props.product.current_stock ?? 0,
    image: null,
});

const imagePreview = ref(props.product.image_url ?? null);

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    const fileInput = document.getElementById('image');
    if (fileInput) fileInput.value = '';
};

const submit = () => {
    form.post(
        route('staff.products.update', { product: props.product.id }),
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                success('Product updated successfully.');
            },
            onError: () => {
                error('Failed to update product. Please check the form.');
            },
        }
    );
};
</script>

<template>
    <Head title="Edit Product" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <Link
                :href="route('staff.products.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
            >
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Back to Products
            </Link>
            <h1 class="text-3xl font-bold text-gray-900">Edit Product</h1>
            <p class="mt-2 text-sm text-gray-600">Update product information</p>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg p-6 max-w-2xl">
            <form @submit.prevent="submit">
                <!-- SKU (Read-Only) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-mono">
                        {{ product.sku }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500">SKU is a permanent identifier and cannot be changed.</p>
                </div>

                <!-- Category (Read-Only) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
                        {{ product.category?.name || 'Uncategorized' }}
                        <span v-if="product.category" class="text-xs text-gray-400 font-mono ml-1">({{ product.category.id }})</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Category cannot be changed. To reassign, create a new product under the correct category and archive this one.</p>
                </div>

                <!-- Product Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.description }"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Product Image -->
                <div class="mb-6">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Image
                    </label>
                    <div v-if="imagePreview != null" class="mb-4 relative inline-block">
                        <img :src="imagePreview" alt="Preview" class="w-48 h-48 object-cover rounded-lg border-2 border-gray-300" />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <div v-else class="flex items-center justify-center w-full">
                        <label for="image" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <PhotoIcon class="w-12 h-12 mb-3 text-gray-400" />
                                <p class="mb-2 text-sm text-gray-500">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF or WEBP (MAX. 2MB)</p>
                            </div>
                            <input
                                id="image"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleImageUpload"
                            />
                        </label>
                    </div>
                    <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                </div>

                <!-- Unit Price -->
                <div class="mb-6">
                    <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Unit Price <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                        <input
                            id="unit_price"
                            v-model="form.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors.unit_price }"
                        />
                    </div>
                    <p v-if="form.errors.unit_price" class="mt-1 text-sm text-red-600">{{ form.errors.unit_price }}</p>
                </div>

                <!-- Current Stock (Read-Only) -->
                <div class="mb-6">
                    <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Stock
                    </label>
                    <input
                        id="current_stock"
                        v-model="form.current_stock"
                        type="number"
                        disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-700"
                    />
                    <p class="mt-1 text-sm text-gray-500">Stock is managed via Purchase Orders and Sales Orders</p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('staff.products.index')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Product' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
