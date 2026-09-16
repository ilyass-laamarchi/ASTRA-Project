/** Lazily creates one Echo connection; pages subscribe and unsubscribe through composables. */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher
let echo

export function getEcho() {
  if (echo || !import.meta.env.VITE_REVERB_APP_KEY) return echo
  const token = localStorage.getItem('astra_token')
  const configuredHost = import.meta.env.VITE_REVERB_HOST
  const configuredOrigin = import.meta.env.VITE_API_ORIGIN
  const wsHost = !configuredHost || configuredHost === 'same-origin' ? window.location.hostname : configuredHost
  const authOrigin = !configuredOrigin || configuredOrigin === 'same-origin' ? window.location.origin : configuredOrigin
  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${authOrigin}/broadcasting/auth`,
    auth: { headers: token ? { Authorization: `Bearer ${token}` } : {} },
  })
  window.Echo = echo
  return echo
}
