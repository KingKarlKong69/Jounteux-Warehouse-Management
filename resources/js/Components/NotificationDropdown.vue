<script setup lang="ts">
/**
 * NotificationDropdown — Facebook-like notification bell & dropdown.
 * Displays unread count, scrollable list, mark-read, clear-all.
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAppNotifications, type AppNotification } from '@/composables/useAppNotifications'
import {
    BellIcon,
    CheckIcon,
    TrashIcon,
    ShoppingCartIcon,
    CubeIcon,
    UserIcon,
    ClipboardDocumentListIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import { BellAlertIcon } from '@heroicons/vue/24/solid'

const {
    notifications,
    unreadCount,
    dropdownOpen,
    markAsRead,
    markAllAsRead,
    clearAll,
    toggleDropdown,
    closeDropdown,
} = useAppNotifications()

const dropdownRef = ref<HTMLElement | null>(null)

function getNotifIcon(type: string) {
    switch (type) {
        case 'sales_order': return ShoppingCartIcon
        case 'product': return CubeIcon
        case 'user': return UserIcon
        case 'purchase_order': return ClipboardDocumentListIcon
        default: return InformationCircleIcon
    }
}

function getNotifColor(type: string) {
    switch (type) {
        case 'sales_order': return 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400'
        case 'product': return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
        case 'user': return 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400'
        case 'purchase_order': return 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400'
        default: return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
    }
}

function handleNotifClick(notif: AppNotification) {
    if (!notif.is_read && notif.id) markAsRead(notif.id)
    if (notif.redirect_url) {
        closeDropdown()
        router.visit(notif.redirect_url)
    }
}

// Close dropdown when clicking outside
function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside, true)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside, true)
})
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Bell Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 rounded-xl transition-all duration-200
                   text-gray-500 hover:text-gray-700 hover:bg-gray-100
                   dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700/50
                   focus:outline-none focus:ring-2 focus:ring-orange-400/50"
            :aria-label="`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ''}`"
        >
            <BellAlertIcon v-if="unreadCount > 0" class="h-6 w-6 text-orange-500 animate-pulse" />
            <BellIcon v-else class="h-6 w-6" />

            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1
                       text-[10px] font-bold text-white bg-red-500 rounded-full
                       ring-2 ring-white dark:ring-gray-800
                       animate-bounce"
                style="animation-duration: 2s;"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-show="dropdownOpen"
                class="absolute right-0 top-full mt-2 w-80 sm:w-96
                       bg-white dark:bg-gray-800
                       border border-gray-200 dark:border-gray-700
                       rounded-2xl shadow-2xl
                       overflow-hidden z-50"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700
                            bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-750">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Notifications</h3>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="text-xs text-orange-600 hover:text-orange-700 dark:text-orange-400 font-medium
                                   px-2 py-1 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/20 transition-colors"
                        >
                            <CheckIcon class="h-3.5 w-3.5 inline mr-0.5" />
                            Mark all read
                        </button>
                        <button
                            v-if="notifications.length > 0"
                            @click="clearAll"
                            class="text-xs text-gray-400 hover:text-red-500 dark:text-gray-500
                                   p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            title="Clear all"
                        >
                            <TrashIcon class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Notification List -->
                <div class="max-h-80 overflow-y-auto overscroll-contain divide-y divide-gray-50 dark:divide-gray-700/50">
                    <template v-if="notifications.length === 0">
                        <div class="px-4 py-8 text-center">
                            <BellIcon class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">No notifications yet</p>
                        </div>
                    </template>

                    <button
                        v-for="notif in notifications"
                        :key="notif.id"
                        @click="handleNotifClick(notif)"
                        class="w-full flex items-start gap-3 px-4 py-3 text-left transition-colors duration-150
                               hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        :class="!notif.is_read ? 'bg-orange-50/50 dark:bg-orange-900/10' : ''"
                    >
                        <!-- Icon -->
                        <div class="shrink-0 mt-0.5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="getNotifColor(notif.type)">
                                <component :is="getNotifIcon(notif.type)" class="h-4.5 w-4.5" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-gray-200 leading-snug"
                               :class="!notif.is_read ? 'font-semibold' : 'font-normal'">
                                {{ notif.message }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ notif.time_ago }}
                            </p>
                        </div>

                        <!-- Unread dot -->
                        <div v-if="!notif.is_read" class="shrink-0 mt-2">
                            <span class="block w-2 h-2 rounded-full bg-orange-500"></span>
                        </div>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
