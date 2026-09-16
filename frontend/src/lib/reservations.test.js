// Unit tests for canonical client reservation endpoints and payloads.
import { describe, expect, it } from 'vitest'
import { CLIENT_RESERVATION_ENDPOINT, buildReservationPayload } from './reservations'

describe('client reservation request', () => {
  it('uses the authenticated Laravel creation route', () => {
    expect(CLIENT_RESERVATION_ENDPOINT).toBe('/my-reservations')
  })

  it('sends only server-authorized booking fields', () => {
    expect(buildReservationPayload({ carId: 8, startDate: '2026-08-18', endDate: '2026-08-20' })).toEqual({
      car_id: 8,
      start_date: '2026-08-18',
      end_date: '2026-08-20',
    })
  })
})
