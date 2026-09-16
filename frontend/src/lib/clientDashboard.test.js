// Unit tests for client-only dashboard derivations and action eligibility.
import { describe, expect, it } from 'vitest'
import { clientSummary, nextClientReservation } from './clientDashboard'

describe('client dashboard data', () => {
  const ownReservations = [
    { id: 71, status: 'confirmed', start_date: '2026-08-19', end_date: '2026-08-21', payments: [] },
    { id: 72, status: 'pending', start_date: '2026-08-19', end_date: '2026-08-20', payments: [] },
    { id: 60, status: 'completed', start_date: '2026-07-01', end_date: '2026-07-03', payments: [{ status: 'paid' }] },
  ]

  it('builds personal counts without agency metrics', () => {
    expect(clientSummary(ownReservations, [], '2026-08-19')).toEqual({
      total: 3,
      upcoming: 2,
      pendingPayments: 1,
      completed: 1,
    })
  })

  it('selects the confirmed reservation first when dates match', () => {
    expect(nextClientReservation(ownReservations, '2026-08-19')?.id).toBe(71)
  })
})
