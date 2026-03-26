/**
 * ─────────────────────────────────────────────────────────────
 * useTheme — Dark / Light Mode Composable
 * ─────────────────────────────────────────────────────────────
 *
 * Manages theme state globally with smooth CSS transitions.
 * Persists preference in localStorage.
 * Uses CSS variables + Tailwind's `dark` class strategy.
 */

import { ref, watch, readonly } from 'vue'

export type Theme = 'light' | 'dark'

const STORAGE_KEY = 'wms_theme_preference'

function getStoredTheme(): Theme {
    try {
        const stored = localStorage.getItem(STORAGE_KEY) as Theme | null
        if (stored === 'light' || stored === 'dark') return stored
    } catch {}
    // Default to light
    return 'light'
}

const currentTheme = ref<Theme>(getStoredTheme())

function applyTheme(theme: Theme) {
    const root = document.documentElement

    // Add transition class for smooth theme switching
    root.classList.add('theme-transitioning')

    if (theme === 'dark') {
        root.classList.add('dark')
    } else {
        root.classList.remove('dark')
    }

    // Remove transition class after animation completes
    setTimeout(() => {
        root.classList.remove('theme-transitioning')
    }, 500)

    try {
        localStorage.setItem(STORAGE_KEY, theme)
    } catch {}
}

// Apply on load
if (typeof document !== 'undefined') {
    applyTheme(currentTheme.value)
}

// Watch for changes
watch(currentTheme, (newTheme) => {
    applyTheme(newTheme)
})

export function useTheme() {
    const toggleTheme = () => {
        currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light'
    }

    const setTheme = (theme: Theme) => {
        currentTheme.value = theme
    }

    const isDark = () => currentTheme.value === 'dark'

    return {
        theme: readonly(currentTheme),
        toggleTheme,
        setTheme,
        isDark,
    }
}
