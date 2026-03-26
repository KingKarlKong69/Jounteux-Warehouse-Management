<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

// Check if inputs have values for floating label animation
const emailHasValue = computed(() => form.email.length > 0);
const passwordHasValue = computed(() => form.password.length > 0);
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <!-- Title Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white text-shadow mb-2">
                Welcome Back
            </h1>
            <p class="text-white/80 text-sm">
                Sign in to access your warehouse dashboard
            </p>
        </div>

        <!-- Success Message -->
        <div 
            v-if="status" 
            class="mb-6 p-4 rounded-lg bg-green-500/20 border border-green-400/50 backdrop-blur-sm animate-slide-up"
        >
            <p class="text-sm font-medium text-green-100 text-center">
                {{ status }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email Input Field -->
            <div class="relative">
                <div class="relative">
                    <TextInput
                        id="email"
                        type="email"
                        class="w-full px-4 pt-6 pb-2 text-base bg-white/10 border-2 rounded-lg transition-all duration-300 focus:outline-none focus:bg-white/20 backdrop-blur-sm text-white placeholder-transparent"
                        :class="{
                            'border-warehouse-orange focus:border-warehouse-orange': !form.errors.email,
                            'border-red-500 shake': form.errors.email,
                        }"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Email"
                    />
                    <label 
                        for="email" 
                        class="absolute left-4 transition-all duration-300 pointer-events-none text-white/70"
                        :class="{
                            'text-xs top-2 text-warehouse-orange': emailHasValue || form.email,
                            'text-base top-1/2 -translate-y-1/2': !emailHasValue && !form.email
                        }"
                    >
                        📧 Email Address
                    </label>
                </div>

                <InputError 
                    class="mt-2 text-red-300 text-sm font-medium animate-slide-up" 
                    :message="form.errors.email" 
                />
            </div>

            <!-- Password Input Field -->
            <div class="relative">
                <div class="relative">
                    <TextInput
                        id="password"
                        type="password"
                        class="w-full px-4 pt-6 pb-2 text-base bg-white/10 border-2 rounded-lg transition-all duration-300 focus:outline-none focus:bg-white/20 backdrop-blur-sm text-white placeholder-transparent"
                        :class="{
                            'border-warehouse-concrete/30 focus:border-warehouse-orange': !form.errors.password,
                            'border-red-500 shake': form.errors.password,
                        }"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <label 
                        for="password" 
                        class="absolute left-4 transition-all duration-300 pointer-events-none text-white/70"
                        :class="{
                            'text-xs top-2 text-warehouse-orange': passwordHasValue || form.password,
                            'text-base top-1/2 -translate-y-1/2': !passwordHasValue && !form.password
                        }"
                    >
                        🔒 Password
                    </label>
                </div>

                <InputError 
                    class="mt-2 text-red-300 text-sm font-medium animate-slide-up" 
                    :message="form.errors.password" 
                />
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center justify-between">
                <label class="flex items-center group cursor-pointer">
                    <div class="relative">
                        <Checkbox 
                            name="remember" 
                            v-model:checked="form.remember"
                            class="transition-all duration-200 group-hover:scale-110"
                        />
                    </div>
                    <span class="ms-3 text-sm text-white/80 group-hover:text-white transition-colors duration-200">
                        Remember me
                    </span>
                </label>

                <!-- Forgot Password Link -->
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-warehouse-yellow hover:text-warehouse-safety transition-colors duration-200 hover:underline font-medium"
                >
                    Forgot password?
                </Link>
            </div>

            <!-- Login Button -->
            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center text-base py-3 font-bold tracking-wide"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing" class="flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Sign In
                    </span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Signing In...
                    </span>
                </PrimaryButton>
            </div>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/20"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-transparent text-white/60">
                        Secure Access
                    </span>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="text-center">
                <p class="text-xs text-white/60">
                    Protected by enterprise-grade security
                </p>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
/* Input focus glow effect */
input:focus {
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2), 
                0 0 20px rgba(255, 107, 53, 0.15);
}

/* Error shake animation */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
    20%, 40%, 60%, 80% { transform: translateX(8px); }
}

.shake {
    animation: shake 0.5s;
}
</style>
