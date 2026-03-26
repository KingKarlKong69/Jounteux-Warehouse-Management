<script setup lang="ts">
/**
 * ─────────────────────────────────────────────────────────────
 * AuthenticatedLayout — Premium Sidebar + Header Layout
 * ─────────────────────────────────────────────────────────────
 *
 * Features:
 * - Collapsible desktop sidebar with icon-only mode
 * - Mobile slide-in drawer with overlay
 * - User profile section in sidebar with avatar
 * - Logout placed in sidebar
 * - Header: greeting, notification bell, dark/light toggle
 * - No logout in header
 * - Smooth theme transitions
 */
import { router } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import NotificationToast from '@/Components/NotificationToast.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import NotificationDropdown from '@/Components/NotificationDropdown.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import { useGreeting } from '@/composables/useGreeting'
import { useTheme } from '@/composables/useTheme'
import {
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ArrowLeftOnRectangleIcon,
} from '@heroicons/vue/24/outline'
import { buildNavigation, DASHBOARD_ROUTES } from '@/config/navigation'
import type { ResolvedNavItem } from '@/config/navigation'

interface AuthUser {
    id: number
    name: string
    email: string
    role: string
    profile_photo_url?: string
}

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const page = usePage()
const { greeting, currentTime, currentDate } = useGreeting()
const { theme } = useTheme()

/**
 * Safe computed user
 */
const user = computed<AuthUser | null>(() => {
    return (page.props as any).auth?.user ?? null
})

/**
 * User initials for avatar fallback
 */
const userInitials = computed(() => {
    if (!user.value?.name) return '?'
    const parts = user.value.name.split(' ')
    return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2)
})

/**
 * Compute dashboard route safely
 */
const dashboardRoute = computed(() => {
    if (!user.value?.role) return 'login'
    return DASHBOARD_ROUTES[user.value.role] ?? 'login'
})

/**
 * Configuration-driven navigation
 */
const navigation = computed<ResolvedNavItem[]>(() => {
    if (!user.value?.role) return []
    return buildNavigation(user.value.role, route)
})

router.on('navigate', () => {
    sidebarOpen.value = false
})

/**
 * Persist sidebar collapsed state
 */
onMounted(() => {
    const stored = localStorage.getItem('wms_sidebar_collapsed')
    if (stored === 'true') sidebarCollapsed.value = true
})

const toggleSidebarCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('wms_sidebar_collapsed', String(sidebarCollapsed.value))
}

/**
 * Dynamic sidebar width
 */
const sidebarWidth = computed(() => sidebarCollapsed.value ? 'w-20' : 'w-64')
const mainPadding = computed(() => sidebarCollapsed.value ? 'md:pl-20' : 'md:pl-64')
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-surface-950 transition-colors duration-500">

            <!-- ═══ MOBILE SIDEBAR OVERLAY ═══ -->
            <Transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="sidebarOpen"
                    class="fixed inset-0 z-40 md:hidden"
                >
                    <!-- Backdrop -->
                    <div
                        class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                        @click="sidebarOpen = false"
                    ></div>

                    <!-- Mobile sidebar panel -->
                    <Transition
                        enter-active-class="transition ease-in-out duration-300 transform"
                        enter-from-class="-translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transition ease-in-out duration-300 transform"
                        leave-from-class="translate-x-0"
                        leave-to-class="-translate-x-full"
                    >
                        <div v-show="sidebarOpen" class="relative flex w-full max-w-xs flex-col bg-gradient-to-b from-gray-900 via-gray-900 to-gray-950 shadow-2xl">
                            <!-- Close -->
                            <div class="absolute top-0 right-0 -mr-12 pt-3">
                                <button
                                    @click="sidebarOpen = false"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50 transition-colors focus:outline-none focus:ring-2 focus:ring-white"
                                >
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Logo -->
                            <div class="flex items-center gap-3 px-5 pt-6 pb-4">
                                <ApplicationLogo class="h-9 w-auto shrink-0" />
                                <span class="text-lg font-bold text-white tracking-tight">Jounteux</span>
                            </div>

                            <!-- Profile Section (Mobile) -->
                            <div v-if="user" class="mx-4 mb-4 p-3 rounded-2xl bg-white/5 border border-white/10">
                                <Link :href="route('profile.edit')" class="flex items-center gap-3 group">
                                    <div class="shrink-0 w-10 h-10 rounded-xl overflow-hidden shadow-lg">
                                        <img
                                            v-if="user.profile_photo_url"
                                            :src="user.profile_photo_url"
                                            alt="Profile photo"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm"
                                        >
                                            {{ userInitials }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-white truncate group-hover:text-orange-300 transition-colors">{{ user.name }}</p>
                                        <p class="text-xs text-gray-400 truncate capitalize">{{ user.role }}</p>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation (Mobile) -->
                            <nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-1">
                                <template v-for="item in navigation">
                                    <div v-if="item.children" :key="item.name">
                                        <p class="text-gray-500 px-3 py-2 uppercase text-[10px] font-bold tracking-wider">{{ item.name }}</p>
                                        <div class="space-y-0.5 pl-3">
                                            <Link
                                                v-for="child in item.children"
                                                :key="child.name"
                                                :href="child.href"
                                                :class="[
                                                    route().current(child.href)
                                                        ? 'bg-orange-500/20 text-orange-300 border-l-2 border-orange-400'
                                                        : 'text-gray-300 hover:bg-white/5 hover:text-white border-l-2 border-transparent',
                                                    'group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200'
                                                ]"
                                            >
                                                <component :is="child.icon" class="mr-3 h-5 w-5 flex-shrink-0 opacity-70"/>
                                                {{ child.name }}
                                            </Link>
                                        </div>
                                    </div>
                                    <div v-else :key="item.name + '-item'">
                                        <Link
                                            :href="item.href"
                                            :class="[
                                                route().current(item.href)
                                                    ? 'bg-orange-500/20 text-orange-300 shadow-sm'
                                                    : 'text-gray-300 hover:bg-white/5 hover:text-white',
                                                'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200'
                                            ]"
                                        >
                                            <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0 opacity-70"/>
                                            {{ item.name }}
                                        </Link>
                                    </div>
                                </template>
                            </nav>

                            <!-- Logout (Mobile) -->
                            <div class="border-t border-white/10 p-4">
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                                           text-red-300 hover:bg-red-500/10 hover:text-red-200 transition-all duration-200"
                                >
                                    <ArrowLeftOnRectangleIcon class="h-5 w-5 opacity-70" />
                                    Sign Out
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <!-- ═══ DESKTOP SIDEBAR (Collapsible) ═══ -->
            <div
                class="hidden md:fixed md:inset-y-0 md:z-30 md:flex md:flex-col transition-all duration-300 ease-in-out"
                :class="sidebarWidth"
            >
                <div class="flex flex-grow flex-col overflow-hidden bg-gradient-to-b from-gray-900 via-gray-900 to-gray-950 shadow-xl">
                    <!-- Logo + Collapse Toggle -->
                    <div class="flex items-center justify-between px-4 pt-5 pb-3" :class="sidebarCollapsed ? 'flex-col gap-2' : ''">
                        <Link :href="route(dashboardRoute)" class="flex items-center gap-3 shrink-0">
                            <ApplicationLogo class="h-9 w-auto shrink-0" />
                            <Transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0 -translate-x-2"
                                enter-to-class="opacity-100 translate-x-0"
                                leave-active-class="transition-all duration-150"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0 -translate-x-2"
                            >
                                <span v-if="!sidebarCollapsed" class="text-lg font-bold text-white tracking-tight whitespace-nowrap">Jounteux</span>
                            </Transition>
                        </Link>
                        <button
                            @click="toggleSidebarCollapse"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-all duration-200"
                            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        >
                            <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-4 w-4" />
                            <ChevronRightIcon v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Profile Section (Desktop) -->
                    <div v-if="user" class="mx-3 mb-3">
                        <Link :href="route('profile.edit')"
                              class="flex items-center gap-3 p-2.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-200 group"
                              :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                            <div class="shrink-0 w-9 h-9 rounded-xl overflow-hidden shadow-lg group-hover:shadow-orange-500/25 transition-shadow">
                                <img
                                    v-if="user.profile_photo_url"
                                    :src="user.profile_photo_url"
                                    alt="Profile photo"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm"
                                >
                                    {{ userInitials }}
                                </div>
                            </div>
                            <Transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-all duration-150"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="!sidebarCollapsed" class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-white truncate group-hover:text-orange-300 transition-colors">{{ user.name }}</p>
                                    <p class="text-xs text-gray-400 truncate capitalize">{{ user.role }}</p>
                                </div>
                            </Transition>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="flex-1 overflow-y-auto overflow-x-hidden">
                        <nav class="px-3 pb-4 space-y-0.5">
                            <template v-for="item in navigation">
                                <div v-if="item.children" :key="item.name">
                                    <p v-if="!sidebarCollapsed" class="text-gray-500 px-3 py-2 uppercase text-[10px] font-bold tracking-wider">{{ item.name }}</p>
                                    <div v-else class="h-px bg-white/10 mx-2 my-2"></div>
                                    <div class="space-y-0.5" :class="!sidebarCollapsed ? 'pl-3' : ''">
                                        <Link
                                            v-for="child in item.children"
                                            :key="child.name"
                                            :href="child.href"
                                            :class="[
                                                route().current(child.href)
                                                    ? 'bg-orange-500/20 text-orange-300'
                                                    : 'text-gray-400 hover:bg-white/5 hover:text-white',
                                                'group flex items-center rounded-xl text-sm font-medium transition-all duration-200',
                                                sidebarCollapsed ? 'justify-center p-2.5' : 'px-3 py-2'
                                            ]"
                                            :title="sidebarCollapsed ? child.name : undefined"
                                        >
                                            <component :is="child.icon" class="h-5 w-5 flex-shrink-0 opacity-70" :class="!sidebarCollapsed ? 'mr-3' : ''" />
                                            <span v-if="!sidebarCollapsed">{{ child.name }}</span>
                                        </Link>
                                    </div>
                                </div>
                                <div v-else :key="item.name + '-item'">
                                    <Link
                                        :href="item.href"
                                        :class="[
                                            route().current(item.href)
                                                ? 'bg-orange-500/20 text-orange-300 shadow-sm shadow-orange-500/10'
                                                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                                            'group flex items-center rounded-xl text-sm font-medium transition-all duration-200',
                                            sidebarCollapsed ? 'justify-center p-2.5' : 'px-3 py-2.5'
                                        ]"
                                        :title="sidebarCollapsed ? item.name : undefined"
                                    >
                                        <component :is="item.icon" class="h-5 w-5 flex-shrink-0 opacity-70" :class="!sidebarCollapsed ? 'mr-3' : ''" />
                                        <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                                    </Link>
                                </div>
                            </template>
                        </nav>
                    </div>

                    <!-- Logout (Desktop — in sidebar) -->
                    <div class="border-t border-white/10 p-3">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            :class="[
                                'w-full flex items-center gap-3 rounded-xl text-sm font-medium',
                                'text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200',
                                sidebarCollapsed ? 'justify-center p-2.5' : 'px-3 py-2.5'
                            ]"
                            :title="sidebarCollapsed ? 'Sign Out' : undefined"
                        >
                            <ArrowLeftOnRectangleIcon class="h-5 w-5 opacity-70" />
                            <span v-if="!sidebarCollapsed">Sign Out</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ═══ MAIN CONTENT AREA ═══ -->
            <div class="flex flex-1 flex-col transition-all duration-300 ease-in-out" :class="mainPadding">
                <!-- Top Header Bar -->
                <header class="sticky top-0 z-20 backdrop-blur-xl bg-white/80 dark:bg-gray-900/80 border-b border-gray-200/50 dark:border-gray-800/50">
                    <div class="flex h-16 items-center justify-between px-4 sm:px-6">
                        <!-- Left: Hamburger (mobile) + Greeting -->
                        <div class="flex items-center gap-4">
                            <button
                                @click="sidebarOpen = true"
                                class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors md:hidden focus:outline-none focus:ring-2 focus:ring-orange-400/50"
                                aria-label="Open sidebar"
                            >
                                <div class="relative h-5 w-5">
                                    <span class="absolute left-0 top-0.5 h-0.5 w-5 bg-current rounded-full"></span>
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 w-5 bg-current rounded-full"></span>
                                    <span class="absolute left-0 bottom-0.5 h-0.5 w-5 bg-current rounded-full"></span>
                                </div>
                            </button>
                            <div v-if="user" class="hidden sm:block">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">
                                    {{ greeting }}, <span class="text-orange-500">{{ user.name.split(' ')[0] }}</span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ currentDate }} &middot; {{ currentTime }} PHT
                                </p>
                            </div>
                        </div>
                        <!-- Right: Theme Toggle + Notifications -->
                        <div class="flex items-center gap-1">
                            <ThemeToggle />
                            <NotificationDropdown />
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1">
                    <div class="py-6">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Global systems -->
        <NotificationToast />
        <ConfirmDialog />
    </div>
</template>
