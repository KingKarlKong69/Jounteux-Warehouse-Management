import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo — Real-time WebSocket client (Reverb).
 * Importing here ensures the singleton is created once on app boot.
 * Composables import echo.ts directly for channel subscriptions.
 */
import './echo';
