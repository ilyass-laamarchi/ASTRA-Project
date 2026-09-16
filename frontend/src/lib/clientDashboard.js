/** Pure helpers that derive client-only dashboard summaries from owned API records. */
const activeUpcomingStatuses = new Set(['pending', 'confirmed', 'active'])

/** Returns whether a reservation contains a provider-verified paid payment. */
export function isPaid(reservation) {
  return (reservation?.payments || []).some(payment => payment.status === 'paid')
}

/** Calculates personal reservation and payment counters without admin data. */
export function clientSummary(reservations, payments, today) {
  const pendingPaymentReservationIds = new Set(
    payments.filter(payment => ['pending', 'processing'].includes(payment.status)).map(payment => payment.reservation_id),
  )
  reservations.filter(reservation => reservation.status === 'confirmed' && !isPaid(reservation))
    .forEach(reservation => pendingPaymentReservationIds.add(reservation.id))

  return {
    total: reservations.length,
    upcoming: reservations.filter(reservation => activeUpcomingStatuses.has(reservation.status) && reservation.end_date > today).length,
    pendingPayments: pendingPaymentReservationIds.size,
    completed: reservations.filter(reservation => reservation.status === 'completed').length,
  }
}

/** Selects the nearest still-active client reservation using stable status priority. */
export function nextClientReservation(reservations, today) {
  const priority = { confirmed: 0, active: 1, pending: 2 }
  return [...reservations]
    .filter(reservation => activeUpcomingStatuses.has(reservation.status) && reservation.end_date > today)
    .sort((a, b) => a.start_date.localeCompare(b.start_date) || (priority[a.status] ?? 9) - (priority[b.status] ?? 9) || a.id - b.id)[0] || null
}
