/** Converts Laravel storage paths into browser-accessible URLs on the API host. */
const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const apiOrigin = apiUrl.startsWith('/') ? '' : apiUrl.replace(/\/api\/?$/, '')

/** Resolve API-owned public media without coupling it to the Vite host. */
export function publicMediaUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) {
    const url = new URL(path)
    // Development rows can contain localhost storage URLs. Use the current
    // same-origin gateway when the frontend is running through an online host.
    if (['localhost', '127.0.0.1'].includes(url.hostname) && url.pathname.startsWith('/storage/')) {
      return `${apiOrigin}${url.pathname}`
    }
    return path
  }
  return `${apiOrigin}/${String(path).replace(/^\/+/, '')}`
}
