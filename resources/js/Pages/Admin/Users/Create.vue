<script setup lang="ts">
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    UserPlusIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline';

defineProps<{
    roles: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    contact_number: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6">
            <Link
                :href="route('admin.users.index')"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
            >
                <ArrowLeftIcon class="h-4 w-4 mr-1" />
                Back to Users
            </Link>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <UserPlusIcon class="h-8 w-8 mr-3 text-customOrange" />
                Create New User
            </h1>
            <p class="mt-1 text-sm text-gray-600">Add a new admin or staff account to the system.</p>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.name }"
                        placeholder="John Doe"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.email }"
                        placeholder="john@example.com"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <!-- Contact Number -->
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700">
                        Contact Number
                    </label>
                    <input
                        id="contact_number"
                        v-model="form.contact_number"
                        type="text"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                        :class="{ 'border-red-500': form.errors.contact_number }"
                        placeholder="+63 912 345 6789"
                    />
                    <p v-if="form.errors.contact_number" class="mt-1 text-sm text-red-600">{{ form.errors.contact_number }}</p>
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="role"
                        v-model="form.role"
                        required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange bg-white"
                        :class="{ 'border-red-500': form.errors.role }"
                    >
                        <option v-for="r in roles" :key="r" :value="r">{{ r.charAt(0).toUpperCase() + r.slice(1) }}</option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            class="block w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                            :class="{ 'border-red-500': form.errors.password }"
                            placeholder="Minimum 8 characters"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <EyeSlashIcon v-if="showPassword" class="h-5 w-5" />
                            <EyeIcon v-else class="h-5 w-5" />
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Min 8 characters, must include uppercase, lowercase, and a number.</p>
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            required
                            class="block w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                            placeholder="Re-enter password"
                        />
                        <button
                            type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <EyeSlashIcon v-if="showConfirm" class="h-5 w-5" />
                            <EyeIcon v-else class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link
                        :href="route('admin.users.index')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 text-sm font-medium text-white bg-customOrange rounded-lg hover:bg-black disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Creating...' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
