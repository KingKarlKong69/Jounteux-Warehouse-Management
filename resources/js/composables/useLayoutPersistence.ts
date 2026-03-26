import { ref, type Ref } from 'vue'

const STORAGE_KEY_ORDER = 'wms_report_layout'
const STORAGE_KEY_SIZES = 'wms_report_sizes'

export interface PersistedCardSize {
    width: number | null
    height: number
    colSpan: number
}

/**
 * Persist card order AND per-card dimensions in localStorage, keyed by role.
 */
export function useLayoutPersistence(role: string) {
    const orderKey = `${STORAGE_KEY_ORDER}_${role}`
    const sizesKey = `${STORAGE_KEY_SIZES}_${role}`

    // ─── Order ────────────────────────────────────────────────
    const loadOrder = (): string[] => {
        try {
            const raw = localStorage.getItem(orderKey)
            return raw ? JSON.parse(raw) : []
        } catch {
            return []
        }
    }

    const savedOrder = ref<string[]>(loadOrder())

    const saveOrder = (ids: string[]) => {
        savedOrder.value = ids
        try {
            localStorage.setItem(orderKey, JSON.stringify(ids))
        } catch { /* ignore */ }
    }

    // ─── Sizes ────────────────────────────────────────────────
    const loadSizes = (): Record<string, PersistedCardSize> => {
        try {
            const raw = localStorage.getItem(sizesKey)
            return raw ? JSON.parse(raw) : {}
        } catch {
            return {}
        }
    }

    const savedSizes = ref<Record<string, PersistedCardSize>>(loadSizes())

    const saveCardSize = (id: string, size: PersistedCardSize) => {
        savedSizes.value[id] = size
        try {
            localStorage.setItem(sizesKey, JSON.stringify(savedSizes.value))
        } catch { /* ignore */ }
    }

    return { savedOrder, saveOrder, savedSizes, saveCardSize }
}
