/**
 * Combines Reverb with deterministic fallbacks. Each caller gets one timer,
 * one focus listener and one channel subscription, all removed on unmount.
 */
import { onMounted, onUnmounted, watch } from 'vue'
import { getEcho } from '../lib/realtime'

export function useAvailabilitySync({ carId, refresh, interval = 30000 }) {
  let timer
  let channel
  let privateChannel
  let stopCarWatch
  let activeCarId
  /** Refreshes after the tab becomes visible again. */
  const onVisible = () => { if (document.visibilityState === 'visible') refresh() }
  /** Refreshes after the browser window regains focus. */
  const onFocus = () => refresh()

  onMounted(() => {
    const echo = getEcho()
    // Detail data is loaded asynchronously, so subscribe when the reactive car
    // id appears rather than only checking it during the mount callback.
    if (echo && carId) {
      stopCarWatch = watch(() => carId.value, id => {
        if (activeCarId) echo.leave(`cars.${activeCarId}.availability`)
        activeCarId = id
        channel = id ? echo.channel(`cars.${id}.availability`).listen('.CarAvailabilityChanged', refresh) : null
      }, { immediate: true })
    }
    // Authenticated dashboards receive private workflow updates; public views
    // still stay fresh through focus/visibility events and the bounded poll.
    const user = JSON.parse(localStorage.getItem('astra_user') || 'null')
    if (echo && user?.id) {
      const privateName = ['owner', 'admin'].includes(user.role) ? 'operations' : `users.${user.id}`
      privateChannel = echo.private(privateName)
        .listen('.ReservationStatusChanged', refresh)
        .listen('.PaymentStatusChanged', refresh)
    }
    window.addEventListener('focus', onFocus)
    document.addEventListener('visibilitychange', onVisible)
    timer = window.setInterval(() => { if (document.visibilityState === 'visible') refresh() }, interval)
  })

  onUnmounted(() => {
    if (timer) window.clearInterval(timer)
    window.removeEventListener('focus', onFocus)
    document.removeEventListener('visibilitychange', onVisible)
    stopCarWatch?.()
    if (channel && activeCarId) getEcho()?.leave(`cars.${activeCarId}.availability`)
    if (privateChannel) {
      const user = JSON.parse(localStorage.getItem('astra_user') || 'null')
      const privateName = ['owner', 'admin'].includes(user?.role) ? 'operations' : `users.${user?.id}`
      getEcho()?.leave(privateName)
    }
  })
}
