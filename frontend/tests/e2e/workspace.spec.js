// End-to-end staff/client workspace transitions using real API records.
import { test, expect, request } from '@playwright/test'

const apiBase = `${process.env.ASTRA_API_ORIGIN || 'http://127.0.0.1:8000'}/api/`

// Return a Casablanca calendar date a fixed number of days in the future.
function futureIso(days) {
  const date = new Date(Date.now() + days * 86_400_000)
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Africa/Casablanca', year: 'numeric', month: '2-digit', day: '2-digit' }).format(date)
}

// Locate a free half-open booking interval for the selected vehicle.
async function findAvailableDates(api, carId, startOffset) {
  for (let offset = startOffset; offset <= startOffset + 80; offset += 5) {
    const dates = { start_date: futureIso(offset), end_date: futureIso(offset + 4) }
    const quote = await api.get(`cars/${carId}/availability`, { params: dates })
    if (quote.ok() && (await quote.json()).available) return dates
  }
  return null
}

test('client reservation table shows rejection reason and safe payment states', async ({ page }) => {
  test.setTimeout(300_000)
  const api = await request.newContext({ baseURL: apiBase })
  const unique = `${Date.now()}-${Math.random().toString(16).slice(2)}`
  const registration = await api.post('register', { data: {
    first_name: 'Client', last_name: 'Workspace', email: `workspace-${unique}@astra.test`, phone: '0600000000',
    password: 'Password123!', password_confirmation: 'Password123!',
  } })
  expect(registration.status()).toBe(201)
  const session = await registration.json()
  const clientApi = await request.newContext({ baseURL: apiBase, extraHTTPHeaders: { Authorization: `Bearer ${session.token}` } })

  const ownerLogin = await api.post('login', { data: { email: 'owner@astra.test', password: 'Password123!' } })
  expect(ownerLogin.ok()).toBe(true)
  const ownerApi = await request.newContext({ baseURL: apiBase, extraHTTPHeaders: { Authorization: `Bearer ${(await ownerLogin.json()).token}` } })
  const cars = (await (await api.get('cars')).json()).data
  expect(cars.length).toBeGreaterThan(0)

  // Create one real pending reservation for the scenario.
  async function createReservation(startOffset, carIndex = 0) {
    const car = cars[carIndex % cars.length]
    const dates = await findAvailableDates(api, car.id, startOffset)
    expect(dates).toBeTruthy()
    const response = await clientApi.post('my-reservations', { data: { car_id: car.id, ...dates } })
    expect(response.status()).toBe(201)
    return (await response.json()).data
  }

  const rejectedFixture = await createReservation(180, 0)
  const rejectedResponse = await ownerApi.patch(`owner/reservations/${rejectedFixture.id}/reject`, {
    data: { internal_note: 'Rejet automatique : fixture E2E.' },
  })
  expect(rejectedResponse.ok()).toBe(true)

  const confirmedFixture = await createReservation(270, 1)
  expect((await ownerApi.patch(`owner/reservations/${confirmedFixture.id}/confirm`)).ok()).toBe(true)
  await createReservation(360, 2)

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('astra_token', token)
    localStorage.setItem('astra_user', JSON.stringify(user))
  }, session)

  await page.goto('/client/reservations')
  await expect(page.locator('h2', { hasText: 'servations' })).toBeVisible()
  const rejected = page.locator('tbody tr').filter({ hasText: 'Refus' }).first()
  await expect(rejected).toContainText('Rejet automatique :')
  await expect(rejected.getByRole('button', { name: 'Annuler' })).toHaveCount(0)

  const confirmed = page.locator('tbody tr').filter({ hasText: 'Confirm' }).first()
  await expect(confirmed.getByRole('button', { name: 'Paiement temporairement indisponible' })).toBeDisabled()

  const pending = page.locator('tbody tr').filter({ hasText: 'En attente' }).first()
  await expect(pending.getByRole('button', { name: 'Annuler' })).toBeVisible()
  await expect(pending.getByText('Payer en ligne')).toHaveCount(0)
  await Promise.all([api.dispose(), clientApi.dispose(), ownerApi.dispose()])
})
