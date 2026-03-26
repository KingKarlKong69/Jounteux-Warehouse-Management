import { ref, type Ref } from 'vue'
import axios from 'axios'

export interface ReportState<T = any> {
    data: Ref<T | null>
    loading: Ref<boolean>
    error: Ref<string | null>
    fetch: (params?: Record<string, any>) => Promise<void>
}

/**
 * Composable for independent report fetching.
 * Each report card gets its own loading/error/data state.
 */
const MIN_DISPLAY_MS = 1500

export function useReport<T = any>(url: string): ReportState<T> {
    const data    = ref<T | null>(null) as Ref<T | null>
    const loading = ref(false)
    const error   = ref<string | null>(null)

    const fetch = async (params?: Record<string, any>) => {
        loading.value = true
        error.value   = null

        const startedAt = Date.now()

        try {
            const response = await axios.get(url, { params })

            if (response.data?.success) {
                data.value = response.data.data
            } else {
                error.value = 'Unexpected response format'
            }
        } catch (err: any) {
            if (err.response?.status === 403) {
                error.value = 'Access denied'
            } else {
                error.value = err.response?.data?.message || err.message || 'Failed to load report'
            }
        } finally {
            const elapsed = Date.now() - startedAt
            const remaining = Math.max(0, MIN_DISPLAY_MS - elapsed)

            if (remaining === 0) {
                loading.value = false
            } else {
                await new Promise(resolve => setTimeout(resolve, remaining))
                loading.value = false
            }
        }
    }

    return { data, loading, error, fetch }
}
