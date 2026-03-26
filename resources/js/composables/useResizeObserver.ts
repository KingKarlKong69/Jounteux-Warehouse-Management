import { onMounted, onUnmounted, type Ref } from 'vue'

/**
 * Composable for observing element resize via ResizeObserver.
 * Debounces callbacks to prevent layout thrashing.
 *
 * Charts use this to detect container dimension changes
 * and trigger non-destructive re-renders.
 */
export function useResizeObserver(
    target: Ref<HTMLElement | null>,
    callback: (entry: ResizeObserverEntry) => void,
    options?: { debounce?: number }
) {
    let observer: ResizeObserver | null = null
    let timeout: ReturnType<typeof setTimeout> | null = null
    const debounceMs = options?.debounce ?? 150

    const debouncedCallback = (entry: ResizeObserverEntry) => {
        if (timeout) clearTimeout(timeout)
        timeout = setTimeout(() => callback(entry), debounceMs)
    }

    const observe = () => {
        if (!observer) {
            observer = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    debouncedCallback(entry)
                }
            })
        }
        if (target.value) {
            observer.observe(target.value)
        }
    }

    const disconnect = () => {
        if (timeout) clearTimeout(timeout)
        observer?.disconnect()
        observer = null
    }

    onMounted(() => {
        observe()
    })

    onUnmounted(() => {
        disconnect()
    })

    return { observe, disconnect }
}
