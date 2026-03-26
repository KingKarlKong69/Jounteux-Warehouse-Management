/**
 * ─────────────────────────────────────────────────────────────
 * useAppNotifications — Real-Time Notification Service
 * ─────────────────────────────────────────────────────────────
 *
 * Production-grade notification composable with:
 *
 *   1. PRIMARY: WebSocket via Laravel Echo / Reverb
 *      - Private channel per user: `notifications.{userId}`
 *      - Instant push — zero latency
 *      - Auto-reconnect built into Echo/Pusher client
 *
 *   2. FALLBACK: Exponential-backoff HTTP polling
 *      - Kicks in when WebSocket fails to connect
 *      - Backoff: 2s → 4s → 8s → 16s → 30s (capped)
 *      - Resets to instant when WS recovers
 *
 *   3. STATE: Singleton reactive store (module-level refs)
 *      - Shared across all components & modules
 *      - Survives Inertia page navigation
 *      - Optimistic UI updates on mark-read / clear
 *
 * Architecture:
 *   - The composable is consumed by NotificationDropdown.vue
 *   - Any component can `useAppNotifications()` to access
 *     the same reactive state without tight coupling
 *   - Echo listener prepends new notifications to the array
 *     and increments unread count — no full refetch needed
 *
 * @see App\Events\NotificationPushed (backend event)
 * @see resources/js/echo.ts (Echo singleton)
 */

import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import echo from '@/echo'

// ─── TypeScript Interface ───────────────────────────────────

export interface AppNotification {
    id?: number
    type: string
    message: string
    user_name: string
    user_role: string
    resource_type: string | null
    resource_id: number | null
    resource_label: string | null
    redirect_url: string | null
    is_read: boolean
    created_at: string
    time_ago: string
}

// ─── Singleton Reactive State ───────────────────────────────
// Module-level refs survive component mount/unmount cycles and
// are shared across all consumers. This is intentional.

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
const dropdownOpen = ref(false)

/** True when WebSocket is the active transport */
const wsConnected = ref(false)

// ─── Connection Tracking ────────────────────────────────────

/** How many components have called useAppNotifications() and are mounted */
let mountCount = 0

/** Polling state */
let pollTimer: ReturnType<typeof setTimeout> | null = null
let currentBackoff = 2000
const MAX_BACKOFF = 30000
const MIN_BACKOFF = 2000

/** Echo channel reference (for cleanup) */
let echoChannel: any = null
let subscribedUserId: number | null = null

// ─── API Layer ──────────────────────────────────────────────

async function fetchNotifications(): Promise<void> {
    try {
        loading.value = true
        const response = await axios.get('/notifications')
        if (response.data?.success) {
            notifications.value = response.data.data
            unreadCount.value = response.data.unread_count
        }
    } catch (err) {
        console.warn('[Notifications] Fetch failed:', err)
    } finally {
        loading.value = false
    }
}

async function markAsRead(id: number): Promise<void> {
    // Optimistic update
    const notif = notifications.value.find(n => n.id === id)
    if (notif && !notif.is_read) {
        notif.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    }

    try {
        await axios.post(`/notifications/${id}/read`)
    } catch (err) {
        // Revert optimistic on failure
        if (notif) {
            notif.is_read = false
            unreadCount.value += 1
        }
        console.warn('[Notifications] Mark-read failed:', err)
    }
}

async function markAllAsRead(): Promise<void> {
    const previousStates = notifications.value.map(n => ({ id: n.id, was: n.is_read }))
    const previousCount = unreadCount.value

    // Optimistic
    notifications.value.forEach(n => (n.is_read = true))
    unreadCount.value = 0

    try {
        await axios.post('/notifications/read-all')
    } catch (err) {
        // Revert
        previousStates.forEach(s => {
            const n = notifications.value.find(x => x.id === s.id)
            if (n) n.is_read = s.was
        })
        unreadCount.value = previousCount
        console.warn('[Notifications] Mark-all-read failed:', err)
    }
}

async function clearAll(): Promise<void> {
    const backup = [...notifications.value]
    const backupCount = unreadCount.value

    // Optimistic
    notifications.value = []
    unreadCount.value = 0

    try {
        await axios.delete('/notifications/clear')
    } catch (err) {
        notifications.value = backup
        unreadCount.value = backupCount
        console.warn('[Notifications] Clear failed:', err)
    }
}

// ─── Dropdown ───────────────────────────────────────────────

function toggleDropdown(): void {
    dropdownOpen.value = !dropdownOpen.value
}

function closeDropdown(): void {
    dropdownOpen.value = false
}

// ─── Fallback Polling (Exponential Backoff) ──────────────────

function startFallbackPolling(): void {
    stopFallbackPolling()
    schedulePoll()
}

function schedulePoll(): void {
    pollTimer = setTimeout(async () => {
        await fetchNotifications()
        // Increase backoff up to cap
        currentBackoff = Math.min(currentBackoff * 2, MAX_BACKOFF)
        // Only continue polling if WS is still disconnected
        if (!wsConnected.value) {
            schedulePoll()
        }
    }, currentBackoff)
}

function stopFallbackPolling(): void {
    if (pollTimer) {
        clearTimeout(pollTimer)
        pollTimer = null
    }
    currentBackoff = MIN_BACKOFF
}

// ─── WebSocket (Echo / Reverb) ──────────────────────────────

function subscribeToChannel(userId: number): void {
    if (subscribedUserId === userId && echoChannel) return

    unsubscribeFromChannel()

    try {
        echoChannel = echo.private(`notifications.${userId}`)

        // Listen for real-time notification pushes
        echoChannel.listen('.NotificationPushed', (data: { notification: AppNotification }) => {
            // Prepend new notification to the list
            notifications.value.unshift(data.notification)
            unreadCount.value += 1

            // Cap the local list at 50 items (same as server limit)
            if (notifications.value.length > 50) {
                notifications.value = notifications.value.slice(0, 50)
            }
        })

        // Track connection state via Pusher events
        const pusher = (echo.connector as any).pusher

        if (pusher) {
            pusher.connection.bind('connected', () => {
                wsConnected.value = true
                // WS recovered — stop polling, reset backoff, do one fresh fetch
                stopFallbackPolling()
                fetchNotifications()
            })

            pusher.connection.bind('disconnected', () => {
                wsConnected.value = false
                // WS dropped — engage fallback polling
                startFallbackPolling()
            })

            pusher.connection.bind('unavailable', () => {
                wsConnected.value = false
                startFallbackPolling()
            })

            // Check if already connected
            if (pusher.connection.state === 'connected') {
                wsConnected.value = true
            } else {
                // Not connected yet — start polling in parallel
                startFallbackPolling()
            }
        } else {
            // No pusher instance — go straight to polling
            startFallbackPolling()
        }

        subscribedUserId = userId
    } catch (err) {
        console.warn('[Notifications] Echo subscribe failed, using polling fallback:', err)
        wsConnected.value = false
        startFallbackPolling()
    }
}

function unsubscribeFromChannel(): void {
    if (subscribedUserId !== null) {
        try {
            echo.leave(`notifications.${subscribedUserId}`)
        } catch { /* ignore */ }
        echoChannel = null
        subscribedUserId = null
    }
}

// ─── Lifecycle Management ───────────────────────────────────

function connect(userId: number): void {
    mountCount++

    if (mountCount === 1) {
        // First consumer mounting — bootstrap everything
        fetchNotifications()
        subscribeToChannel(userId)
    }
}

function disconnect(): void {
    mountCount = Math.max(0, mountCount - 1)

    if (mountCount === 0) {
        // Last consumer unmounted — clean up
        unsubscribeFromChannel()
        stopFallbackPolling()
    }
}

// ─── Composable Export ──────────────────────────────────────

export function useAppNotifications() {
    const page = usePage()

    onMounted(() => {
        const userId: number | undefined = (page.props as any)?.auth?.user?.id

        if (userId) {
            connect(userId)
        } else {
            // No user ID available yet — fallback to polling only
            mountCount++
            if (mountCount === 1) {
                fetchNotifications()
                startFallbackPolling()
            }
        }
    })

    onUnmounted(() => {
        disconnect()
    })

    return {
        // Reactive state
        notifications,
        unreadCount,
        loading,
        dropdownOpen,
        wsConnected,

        // Actions
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        clearAll,
        toggleDropdown,
        closeDropdown,
    }
}

