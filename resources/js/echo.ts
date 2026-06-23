import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

;(window as any).Pusher = Pusher

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080)
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '')

const echo = reverbKey
    ? new Echo({
        broadcaster: 'reverb',

        key: reverbKey,

        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,

        forceTLS: reverbScheme === 'https',

        enabledTransports: ['ws', 'wss'],

        authEndpoint: '/broadcasting/auth',
    })
    : null

export default echo
