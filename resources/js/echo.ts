/**
 * ─────────────────────────────────────────────────────────────
 * Echo — Laravel Echo + Reverb WebSocket Client
 * ─────────────────────────────────────────────────────────────
 *
 * Initializes a single Echo instance connected to the Reverb
 * WebSocket server. Imported by bootstrap.ts and then consumed
 * by composables that need real-time channel subscriptions.
 *
 * The instance is exported so it can be imported directly by
 * any composable or component that needs WebSocket access.
 *
 * @see resources/js/composables/useAppNotifications.ts
 */

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Pusher must be on window for Echo to discover it
;(window as any).Pusher = Pusher

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Reverb runs same-origin, so auth goes through our Laravel session
    authEndpoint: '/broadcasting/auth',
})

export default echo
