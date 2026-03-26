<script setup lang="ts">
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { CameraIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    photo: null as File | null,
    _method: 'PATCH',
});

const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);

const currentPhotoUrl = computed(() => {
    if (photoPreview.value) return photoPreview.value;
    return user.profile_photo_url || null;
});

const userInitials = computed(() => {
    const parts = (user.name || '').split(' ');
    return parts.map(p => p.charAt(0)).join('').toUpperCase().slice(0, 2);
});

const selectPhoto = () => {
    photoInput.value?.click();
};

const onPhotoSelected = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    form.photo = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        photoPreview.value = ev.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const removePhoto = () => {
    if (user.profile_photo_url) {
        router.delete(route('profile.photo.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                photoPreview.value = null;
                form.photo = null;
                clearFileInput();
            },
        });
    } else {
        photoPreview.value = null;
        form.photo = null;
        clearFileInput();
    }
};

const clearFileInput = () => {
    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const submitForm = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            form.photo = null;
            clearFileInput();
        },
        onError: () => {
            if (form.errors.photo) {
                photoPreview.value = null;
                form.photo = null;
                clearFileInput();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information, email address, and photo.
            </p>
        </header>

        <form @submit.prevent="submitForm" class="mt-6 space-y-6">
            <!-- Profile Photo -->
            <div>
                <InputLabel value="Profile Photo" />

                <div class="mt-2 flex items-center gap-4">
                    <!-- Photo preview / initials -->
                    <div class="relative group">
                        <div
                            v-if="currentPhotoUrl"
                            class="w-20 h-20 rounded-full overflow-hidden ring-2 ring-gray-200 shadow-md"
                        >
                            <img
                                :src="currentPhotoUrl"
                                alt="Profile photo"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div
                            v-else
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xl font-bold ring-2 ring-gray-200 shadow-md"
                        >
                            {{ userInitials }}
                        </div>

                        <!-- Upload overlay -->
                        <button
                            type="button"
                            @click="selectPhoto"
                            class="absolute inset-0 w-full h-full rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                        >
                            <CameraIcon class="h-6 w-6 text-white" />
                        </button>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button
                            type="button"
                            @click="selectPhoto"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                        >
                            <CameraIcon class="h-4 w-4 mr-1.5" />
                            {{ currentPhotoUrl ? 'Change Photo' : 'Upload Photo' }}
                        </button>

                        <button
                            v-if="currentPhotoUrl"
                            type="button"
                            @click="removePhoto"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-red-300 rounded-md text-sm font-medium text-red-600 hover:bg-red-50 transition"
                        >
                            <TrashIcon class="h-4 w-4 mr-1.5" />
                            Remove
                        </button>

                        <p class="text-xs text-gray-500">JPG, PNG, or WebP. Max 2MB.</p>
                    </div>

                    <input
                        ref="photoInput"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onPhotoSelected"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.photo" />
            </div>

            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
