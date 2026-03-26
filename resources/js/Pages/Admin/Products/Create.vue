<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useNotification } from '@/composables/useNotification';

const { success, error } = useNotification();

const props = defineProps({
    categories: Array,
});

const form = useForm({
    name: '',
    description: '',
    category_id: '',
    unit_price: '',
    current_stock: 0,
    image: null,
});

const imagePreview = ref(null);

// SKU preview: shows what the generated SKU will look like
const skuPreview = computed(() => {
    if (!form.category_id) return '—';
    return `${form.category_id}-XXXX`;
});

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
    form.post(route('admin.products.store'), {
        forceFormData: true,
        onSuccess: () => {
            success('Product created successfully.');
            form.reset();
            imagePreview.value = null;
            router.get(route('admin.products.index'));
        },
        onError: () => {
            error('Failed to create product. Please check the form.');
        },
    });
};
</script>

<template>
    <Head title="Create Product" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <Link
                :href="route('admin.products.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
            >
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Back to Products
            </Link>
            <h1 class="text-3xl font-bold text-gray-900">Create Product</h1>
            <p class="mt-2 text-sm text-gray-600">Add a new product to your inventory</p>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg p-6 max-w-2xl">
            <form @submit.prevent="submit">
                <!-- Category (Required - determines SKU) -->
                <div class="mb-6">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange bg-white"
                        :class="{ 'border-red-500': form.errors.category_id }"
                    >
                        <option value="">Select a category</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }} ({{ cat.id }})
                        </option>
                    </select>
                    <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
                    <p class="mt-1 text-xs text-gray-500">Category cannot be changed after product creation.</p>
                </div>

                <!-- SKU Preview (Auto-generated) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        SKU <span class="text-xs text-gray-400 font-normal">(auto-generated)</span>
                    </label>
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-600 font-mono">
                        {{ skuPreview }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500">SKU is automatically generated based on the selected category.</p>
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.name }"
                        placeholder="e.g., Laptop Dell Inspiron 15"
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.description }"
                        placeholder="Product description..."
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Product Image -->
                <div class="mb-6">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Image
                    </label>
                    
                    <!-- Image Preview -->
                    <div v-if="imagePreview" class="mb-4 relative inline-block">
                        <img :src="imagePreview" alt="Preview" class="w-48 h-48 object-cover rounded-lg border-2 border-gray-300" />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                        >
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Upload Button -->
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
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                            :class="{ 'border-red-500': form.errors.unit_price }"
                            placeholder="0.00"
                        />
                    </div>
                    <p v-if="form.errors.unit_price" class="mt-1 text-sm text-red-600">{{ form.errors.unit_price }}</p>
                </div>

                <!-- Current Stock -->
                <div class="mb-6">
                    <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-2">
                        Initial Stock
                    </label>
                    <input
                        id="current_stock"
                        v-model="form.current_stock"
                        type="number"
                        min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.current_stock }"
                        placeholder="0"
                    />
                    <p v-if="form.errors.current_stock" class="mt-1 text-sm text-red-600">{{ form.errors.current_stock }}</p>
                    <p class="mt-1 text-sm text-gray-500">Leave as 0 if you'll add stock via Purchase Orders</p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('admin.products.index')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-customOrange border border-transparent rounded-lg text-sm font-medium text-white hover:bg-black disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Product' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
