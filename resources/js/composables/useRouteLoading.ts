import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Composable to track Inertia route navigation loading state.
 *
 * Tied directly to Inertia's router lifecycle — no arbitrary timeouts.
 * Use this in any page where data arrives via Inertia props and
 * filter/pagination/sort changes trigger `router.get()`.
 */
const MIN_DISPLAY_MS = 1500

export function useRouteLoading() {
    const isNavigating = ref(false)

    let removeStart: (() => void) | null = null
    let removeFinish: (() => void) | null = null
    let startedAt = 0
    let timer: ReturnType<typeof setTimeout> | null = null

    onMounted(() => {
        removeStart = router.on('start', () => {
            if (timer) { clearTimeout(timer); timer = null }
            startedAt = Date.now()
            isNavigating.value = true
        })
        removeFinish = router.on('finish', () => {
            const elapsed = Date.now() - startedAt
            const remaining = Math.max(0, MIN_DISPLAY_MS - elapsed)

            if (remaining === 0) {
                isNavigating.value = false
            } else {
                timer = setTimeout(() => {
                    isNavigating.value = false
                    timer = null
                }, remaining)
            }
        })
    })

    onUnmounted(() => {
        removeStart?.()
        removeFinish?.()
        if (timer) { clearTimeout(timer); timer = null }
    })

    return { isNavigating }
}
