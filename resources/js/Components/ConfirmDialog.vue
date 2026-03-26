<script setup lang="ts">
/**
 * ─────────────────────────────────────────────────────────────
 * ConfirmDialog — Global Styled Confirmation Modal
 * ─────────────────────────────────────────────────────────────
 *
 * Replaces all native `confirm()` calls with a styled modal.
 * Mount once in AuthenticatedLayout — triggered from any page
 * via `useNotification().confirm(...)`.
 *
 * Supports a `destructive` variant with red confirm button.
 * Closes on Escape key and overlay click.
 */

import { watch, ref, nextTick } from 'vue'
import { useNotification } from '@/composables/useNotification'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const { confirmState } = useNotification()
const confirmButtonRef = ref<HTMLButtonElement | null>(null)

// Auto-focus the confirm button when dialog opens
watch(
    () => confirmState.visible,
    async (visible) => {
        if (visible) {
            await nextTick()
            confirmButtonRef.value?.focus()
        }
    },
)

const handleConfirm = () => {
    confirmState.onConfirm?.()
}

const handleCancel = () => {
    confirmState.onCancel?.()
}

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        handleCancel()
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmState.visible"
                class="fixed inset-0 z-[10000] flex items-center justify-center"
                @keydown="handleKeydown"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
                    @click="handleCancel"
                />

                <!-- Dialog Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="confirmState.visible"
                        class="relative w-full max-w-md mx-4 bg-white rounded-xl shadow-2xl overflow-hidden"
                        role="alertdialog"
                        aria-modal="true"
                        :aria-labelledby="'confirm-title'"
                        :aria-describedby="'confirm-message'"
                    >
                        <div class="p-6">
                            <!-- Icon + Title -->
                            <div class="flex items-start gap-4">
                                <div
                                    :class="[
                                        'flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full',
                                        confirmState.destructive ? 'bg-red-100' : 'bg-orange-100',
                                    ]"
                                >
                                    <ExclamationTriangleIcon
                                        :class="[
                                            'h-5 w-5',
                                            confirmState.destructive ? 'text-red-600' : 'text-orange-600',
                                        ]"
                                    />
                                </div>
                                <div class="flex-1">
                                    <h3
                                        id="confirm-title"
                                        class="text-base font-semibold text-gray-900"
                                    >
                                        {{ confirmState.title }}
                                    </h3>
                                    <p
                                        id="confirm-message"
                                        class="mt-2 text-sm text-gray-600"
                                    >
                                        {{ confirmState.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                            <button
                                @click="handleCancel"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
                            >
                                {{ confirmState.cancelLabel }}
                            </button>
                            <button
                                ref="confirmButtonRef"
                                @click="handleConfirm"
                                :class="[
                                    'inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-lg shadow-sm focus:outline-none focus:ring-2 transition-colors',
                                    confirmState.destructive
                                        ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                                        : 'bg-customOrange hover:bg-orange-600 focus:ring-orange-500',
                                ]"
                            >
                                {{ confirmState.confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
