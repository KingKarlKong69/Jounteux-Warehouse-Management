/**
 * ─────────────────────────────────────────────────────────────
 * useNotification — Global Notification Composable
 * ─────────────────────────────────────────────────────────────
 *
 * Provides a unified API for success/error/warning toasts and
 * styled confirmation dialogs. Replaces:
 *   - All native `confirm()` calls
 *   - All `flashSuccess`/`flashError` ref patterns
 *   - All `console.log` success handlers
 *   - Inertia flash prop reading
 *
 * Usage:
 *   import { useNotification } from '@/composables/useNotification'
 *   const { success, error, warning, confirm } = useNotification()
 *
 *   success('Product created successfully.')
 *   error('Failed to delete supplier.')
 *   confirm({
 *       title: 'Delete Product',
 *       message: 'Are you sure? You can restore it later.',
 *       confirmLabel: 'Delete',
 *       destructive: true,
 *       onConfirm: () => router.delete(...)
 *   })
 */

import { reactive, readonly } from 'vue'

// ─── Types ───────────────────────────────────────────────────

export type NotificationType = 'success' | 'error' | 'warning' | 'info'

export interface Notification {
    id: number
    type: NotificationType
    message: string
    duration: number
}

export interface ConfirmOptions {
    title: string
    message: string
    confirmLabel?: string
    cancelLabel?: string
    destructive?: boolean
    onConfirm: () => void
    onCancel?: () => void
}

export interface ConfirmState {
    visible: boolean
    title: string
    message: string
    confirmLabel: string
    cancelLabel: string
    destructive: boolean
    onConfirm: (() => void) | null
    onCancel: (() => void) | null
}

// ─── Shared Reactive State (singleton across app) ────────────

let nextId = 0

const notifications = reactive<Notification[]>([])

const confirmState = reactive<ConfirmState>({
    visible: false,
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    destructive: false,
    onConfirm: null,
    onCancel: null,
})

// ─── Internal Helpers ────────────────────────────────────────

function addNotification(type: NotificationType, message: string, duration = 4000) {
    const id = ++nextId
    notifications.push({ id, type, message, duration })

    if (duration > 0) {
        setTimeout(() => removeNotification(id), duration)
    }
}

function removeNotification(id: number) {
    const idx = notifications.findIndex(n => n.id === id)
    if (idx !== -1) notifications.splice(idx, 1)
}

// ─── Public API ──────────────────────────────────────────────

export function useNotification() {
    return {
        /** Reactive list of active notifications (for the toast renderer) */
        notifications: readonly(notifications) as readonly Notification[],

        /** Reactive confirm dialog state (for the confirm renderer) */
        confirmState: readonly(confirmState) as Readonly<ConfirmState>,

        /** Show a success toast (auto-dismiss 4s) */
        success(message: string) {
            addNotification('success', message, 4000)
        },

        /** Show an error toast (auto-dismiss 6s) */
        error(message: string) {
            addNotification('error', message, 6000)
        },

        /** Show a warning toast (auto-dismiss 5s) */
        warning(message: string) {
            addNotification('warning', message, 5000)
        },

        /** Show an info toast (auto-dismiss 4s) */
        info(message: string) {
            addNotification('info', message, 4000)
        },

        /** Dismiss a specific notification by ID */
        dismiss(id: number) {
            removeNotification(id)
        },

        /** Open a styled confirmation dialog (replaces native confirm()) */
        confirm(options: ConfirmOptions) {
            confirmState.visible = true
            confirmState.title = options.title
            confirmState.message = options.message
            confirmState.confirmLabel = options.confirmLabel ?? 'Confirm'
            confirmState.cancelLabel = options.cancelLabel ?? 'Cancel'
            confirmState.destructive = options.destructive ?? false
            confirmState.onConfirm = () => {
                confirmState.visible = false
                options.onConfirm()
            }
            confirmState.onCancel = () => {
                confirmState.visible = false
                options.onCancel?.()
            }
        },

        /** Close confirm dialog without action */
        closeConfirm() {
            confirmState.visible = false
            confirmState.onConfirm = null
            confirmState.onCancel = null
        },
    }
}
