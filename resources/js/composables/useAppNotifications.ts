import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import echo from '@/echo'

// ─── Types ───────────────────────────────────────────────────

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

// ─── Reactive State ─────────────────────────────────────────

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
const dropdownOpen = ref(false)
const wsConnected = ref(false)

// ─── Internal State ─────────────────────────────────────────

let mountCount = 0
let pollTimer: ReturnType<typeof setTimeout> | null = null
let currentBackoff = 2000
const MAX_BACKOFF = 30000
const MIN_BACKOFF = 2000

let subscribedUserId: number | null = null

// ─── API ────────────────────────────────────────────────────

async function fetchNotifications(): Promise<void> {
    try {
        loading.value = true
        const res = await axios.get('/notifications')

        if (res.data?.success) {
            notifications.value = res.data.data
            unreadCount.value = res.data.unread_count
        }
    } catch (err) {
        console.warn('[Notifications] Fetch failed:', err)
    } finally {
        loading.value = false
    }
}

async function markAsRead(id: number): Promise<void> {
    const notif = notifications.value.find(n => n.id === id)

    if (notif && !notif.is_read) {
        notif.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    }

    try {
        await axios.post(`/notifications/${id}/read`)
    } catch {
        if (notif) {
            notif.is_read = false
            unreadCount.value += 1
        }
    }
}

async function markAllAsRead(): Promise<void> {
    const backup = notifications.value.map(n => ({ id: n.id, was: n.is_read }))
    const backupCount = unreadCount.value

    notifications.value.forEach(n => (n.is_read = true))
    unreadCount.value = 0

    try {
        await axios.post('/notifications/read-all')
    } catch {
        backup.forEach(b => {
            const n = notifications.value.find(x => x.id === b.id)
            if (n) n.is_read = b.was
        })
        unreadCount.value = backupCount
    }
}

async function clearAll(): Promise<void> {
    const backup = [...notifications.value]
    const backupCount = unreadCount.value

    notifications.value = []
    unreadCount.value = 0

    try {
        await axios.delete('/notifications/clear')
    } catch {
        notifications.value = backup
        unreadCount.value = backupCount
    }
}

// ─── UI ─────────────────────────────────────────────────────

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value
}

function closeDropdown() {
    dropdownOpen.value = false
}

// ─── Polling Fallback ───────────────────────────────────────

function startPolling() {
    stopPolling()

    pollTimer = setTimeout(async () => {
        await fetchNotifications()

        currentBackoff = Math.min(currentBackoff * 2, MAX_BACKOFF)

        if (!wsConnected.value) {
            startPolling()
        }
    }, currentBackoff)
}

function stopPolling() {
    if (pollTimer) clearTimeout(pollTimer)
    pollTimer = null
    currentBackoff = MIN_BACKOFF
}

// ─── WebSocket (REVERB SAFE VERSION) ────────────────────────

function subscribe(userId: number) {
    if (subscribedUserId === userId) return

    if (!echo) {
        wsConnected.value = false
        startPolling()
        subscribedUserId = userId
        return
    }

    try {
        const channel = echo.private(`notifications.${userId}`)

        channel.listen('.NotificationPushed', (e: any) => {
            notifications.value.unshift(e.notification)
            unreadCount.value += 1

            if (notifications.value.length > 50) {
                notifications.value = notifications.value.slice(0, 50)
            }
        })

        // SAFE connection access (NO pusher dependency)
        const connector: any = (echo as any).connector
        const connection = connector?.connection

        if (connection) {
            connection.bind('connected', () => {
                wsConnected.value = true
                stopPolling()
                fetchNotifications()
            })

            connection.bind('disconnected', () => {
                wsConnected.value = false
                startPolling()
            })

            connection.bind('unavailable', () => {
                wsConnected.value = false
                startPolling()
            })

            if (connection.state === 'connected') {
                wsConnected.value = true
            } else {
                startPolling()
            }
        } else {
            startPolling()
        }

        subscribedUserId = userId
    } catch (err) {
        console.warn('[Notifications] WS failed, fallback polling:', err)
        wsConnected.value = false
        startPolling()
    }
}

function unsubscribe(userId: number) {
    try {
        echo?.leave(`notifications.${userId}`)
    } catch {}
}

// ─── Lifecycle ─────────────────────────────────────────────

function connect(userId: number) {
    mountCount++

    if (mountCount === 1) {
        fetchNotifications()
        subscribe(userId)
    }
}

function disconnect(userId: number) {
    mountCount = Math.max(0, mountCount - 1)

    if (mountCount === 0) {
        unsubscribe(userId)
        stopPolling()
    }
}

// ─── Composable ────────────────────────────────────────────

export function useAppNotifications() {
    const page = usePage()

    onMounted(() => {
        const userId = (page.props as any)?.auth?.user?.id

        if (userId) {
            connect(userId)
        } else {
            mountCount++
            if (mountCount === 1) {
                fetchNotifications()
                startPolling()
            }
        }
    })

    onUnmounted(() => {
        const userId = (usePage().props as any)?.auth?.user?.id
        if (userId) disconnect(userId)
    })

    return {
        notifications,
        unreadCount,
        loading,
        dropdownOpen,
        wsConnected,

        fetchNotifications,
        markAsRead,
        markAllAsRead,
        clearAll,
        toggleDropdown,
        closeDropdown,
    }
}
