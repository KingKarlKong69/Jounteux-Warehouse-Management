<script setup lang="ts">
/**
 * ThemeToggle — Animated Dark/Light mode toggle switch.
 */
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'

const { theme, toggleTheme } = useTheme()

const isDark = computed(() => theme.value === 'dark')
</script>

<template>
    <button
        @click="toggleTheme"
        class="relative p-2 rounded-xl transition-all duration-300
               text-gray-500 hover:text-gray-700 hover:bg-gray-100
               dark:text-gray-400 dark:hover:text-yellow-400 dark:hover:bg-gray-700/50
               focus:outline-none focus:ring-2 focus:ring-orange-400/50"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="rotate-90 scale-0 opacity-0"
            enter-to-class="rotate-0 scale-100 opacity-100"
            leave-active-class="transition-all duration-300 absolute"
            leave-from-class="rotate-0 scale-100 opacity-100"
            leave-to-class="-rotate-90 scale-0 opacity-0"
            mode="out-in"
        >
            <MoonIcon v-if="!isDark" class="h-5 w-5" />
            <SunIcon v-else class="h-5 w-5 text-yellow-400" />
        </Transition>
    </button>
</template>
