// Unit tests for timezone-safe date-only helpers and half-open durations.
import { describe, expect, it } from 'vitest'
import { addIsoDays, formatDateFr, fromIsoDate, rangeIntersectsPeriods, todayIso, toIsoDate } from './dates'

describe('ASTRA calendar dates', () => {
  it('keeps ISO calendar arithmetic free of timezone shifts', () => {
    expect(addIsoDays('2026-03-29', 1)).toBe('2026-03-30')
    expect(formatDateFr('2026-08-04')).toBe('04/08/2026')
    expect(todayIso()).toMatch(/^\d{4}-\d{2}-\d{2}$/)
    expect(toIsoDate({
      getFullYear: () => 2026,
      getMonth: () => 7,
      getDate: () => 31,
      toISOString: () => '2026-08-30T23:00:00.000Z',
    })).toBe('2026-08-31')
    expect(toIsoDate(fromIsoDate('2026-08-31'))).toBe('2026-08-31')
    expect(fromIsoDate('2026-02-30')).toBeNull()
  })

  it('uses half-open unavailable periods', () => {
    const periods = [{ start_date: '2026-07-10', end_date: '2026-07-15' }]
    expect(rangeIntersectsPeriods('2026-07-12', '2026-07-18', periods)).toBe(true)
    expect(rangeIntersectsPeriods('2026-07-15', '2026-07-18', periods)).toBe(false)
  })
})
