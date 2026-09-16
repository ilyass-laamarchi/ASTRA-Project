/** Calendar-only helpers avoid Date parsing at midnight, which can shift days across timezones. */
export const BUSINESS_TIMEZONE = 'Africa/Casablanca'

/** Format a Date as the calendar day represented by its local components. */
export function toIsoDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/** Parse an ISO calendar value without treating it as a UTC timestamp. */
export function fromIsoDate(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
    ? date
    : null
}

/** Returns today's ISO calendar date in the Casablanca business timezone. */
export function todayIso() {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: BUSINESS_TIMEZONE,
    year: 'numeric', month: '2-digit', day: '2-digit',
  }).formatToParts(new Date())
  const value = Object.fromEntries(parts.map(part => [part.type, part.value]))
  return `${value.year}-${value.month}-${value.day}`
}

/** Adds whole calendar days without local midnight or daylight-saving shifts. */
export function addIsoDays(isoDate, amount) {
  const [year, month, day] = isoDate.split('-').map(Number)
  const date = new Date(Date.UTC(year, month - 1, day + amount, 12))
  return date.toISOString().slice(0, 10)
}

/** Formats an ISO date as day/month/year for French UI labels. */
export function formatDateFr(isoDate) {
  if (!isoDate) return '—'
  const [year, month, day] = isoDate.slice(0, 10).split('-')
  return `${day}/${month}/${year}`
}

/** Applies the shared half-open overlap formula to public blocking periods. */
export function rangeIntersectsPeriods(startDate, endDate, periods) {
  return periods.some(period => period.start_date < endDate && period.end_date > startDate)
}
