<script setup lang="ts">
/**
 * ─────────────────────────────────────────────────────────────
 * NotificationToast — Global Stacked Toast Renderer
 * ─────────────────────────────────────────────────────────────
 *
 * Renders the reactive `notifications` array from useNotification.
 * Mount once in AuthenticatedLayout — works across all pages.
 *
 * Desktop: fixed top-right, stacked downward, slide-from-right.
 * Mobile: fixed top-center, full-width, slide-down.
 */

import { useNotification } from '@/composables/useNotification'
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'
import type { NotificationType } from '@/composables/useNotification'

const { notifications, dismiss } = useNotification()

const iconMap: Record<NotificationType, any> = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
}

const colorMap: Record<NotificationType, { bg: string; border: string; text: string; icon: string }> = {
    success: {
        bg: 'bg-green-50',
        border: 'border-green-200',
        text: 'text-green-800',
        icon: 'text-green-500',
    },
    error: {
        bg: 'bg-red-50',
        border: 'border-red-200',
        text: 'text-red-800',
        icon: 'text-red-500',
    },
    warning: {
        bg: 'bg-yellow-50',
        border: 'border-yellow-200',
        text: 'text-yellow-800',
        icon: 'text-yellow-500',
    },
    info: {
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        text: 'text-blue-800',
        icon: 'text-blue-500',
    },
}
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="polite"
            aria-atomic="true"
            class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none sm:right-6"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0 sm:translate-x-full sm:translate-y-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
                move-class="transition-all duration-300"
            >
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    :class="[
                        colorMap[notification.type].bg,
                        colorMap[notification.type].border,
                        'pointer-events-auto border rounded-lg shadow-lg p-4 flex items-start gap-3',
                    ]"
                    role="alert"
                >
                    <!-- Icon -->
                    <component
                        :is="iconMap[notification.type]"
                        :class="['h-5 w-5 flex-shrink-0 mt-0.5', colorMap[notification.type].icon]"
                    />

                    <!-- Message -->
                    <p :class="['text-sm font-medium flex-1', colorMap[notification.type].text]">
                        {{ notification.message }}
                    </p>

                    <!-- Dismiss -->
                    <button
                        @click="dismiss(notification.id)"
                        :class="['flex-shrink-0 rounded-md p-1 hover:bg-black/5 transition-colors', colorMap[notification.type].text]"
                        aria-label="Dismiss notification"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
