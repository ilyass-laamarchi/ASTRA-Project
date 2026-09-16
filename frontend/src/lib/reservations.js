/** Shared client booking endpoint and payload builder used by vehicle pages. */
export const CLIENT_RESERVATION_ENDPOINT = '/my-reservations'

/** Builds the only client-supplied reservation fields; Laravel calculates prices. */
export function buildReservationPayload({ carId, startDate, endDate }) {
  return {
    car_id: carId,
    start_date: startDate,
    end_date: endDate,
  }
}
