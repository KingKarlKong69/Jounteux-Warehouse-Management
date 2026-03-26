<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline';

interface TargetUser {
    id: number;
    name: string;
    email: string;
    contact_number: string | null;
    role: string;
    is_blocked: boolean;
}

const props = defineProps<{
    targetUser: TargetUser;
    roles: string[];
}>();

const form = useForm({
    name: props.targetUser.name,
    email: props.targetUser.email,
    contact_number: props.targetUser.contact_number || '',
    role: props.targetUser.role,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.put(route('admin.users.update', props.targetUser.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit User - ${targetUser.name}`" />

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
                <PencilSquareIcon class="h-8 w-8 mr-3 text-customOrange" />
                Edit User
            </h1>
            <p class="mt-1 text-sm text-gray-600">Editing account for <strong>{{ targetUser.name }}</strong></p>
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

                <!-- Password (optional) -->
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-3">Change Password <span class="text-xs text-gray-400">(leave blank to keep current)</span></p>

                    <div class="space-y-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <div class="relative mt-1">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
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
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <div class="relative mt-1">
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirm ? 'text' : 'password'"
                                    class="block w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-customOrange focus:border-customOrange"
                                    placeholder="Re-enter new password"
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
