// End-to-end proof that realtime holds and database locking prevent double booking.
import { test, expect, request } from '@playwright/test'

const apiOrigin = process.env.ASTRA_API_ORIGIN || 'http://127.0.0.1:8000'
const apiBase = `${apiOrigin}/api/`
const frontendOrigin = process.env.ASTRA_FRONTEND_ORIGIN || 'http://127.0.0.1:5173'

// Return a Casablanca calendar date a fixed number of days in the future.
function futureIso(days) {
  const date = new Date(Date.now() + days * 86_400_000)
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Africa/Casablanca', year: 'numeric', month: '2-digit', day: '2-digit' }).format(date)
}

// Find a deterministic unblocked interval for a real fleet vehicle.
async function findAvailableDates(api, carId, startOffset = 20) {
  for (let offset = startOffset; offset <= startOffset + 80; offset += 5) {
    const candidate = { start_date: futureIso(offset), end_date: futureIso(offset + 4) }
    const quote = await api.get(`cars/${carId}/availability`, { params: candidate })
    if (quote.ok() && (await quote.json()).available) return candidate
  }
  return null
}

// Return an authenticated API context for an existing or new client.
async function authenticatedClient(api, email, register = false) {
  if (register) {
    const response = await api.post('register', { data: {
      first_name: 'Client', last_name: 'Concurrent', email, phone: '0600000000',
      password: 'Password123!', password_confirmation: 'Password123!',
    } })
    expect(response.status()).toBe(201)
    const token = (await response.json()).token
    return request.newContext({ baseURL: apiBase, extraHTTPHeaders: { Authorization: `Bearer ${token}` } })
  }
  const response = await api.post('login', { data: { email, password: 'Password123!' } })
  expect(response.ok()).toBe(true)
  const token = (await response.json()).token
  return request.newContext({ baseURL: apiBase, extraHTTPHeaders: { Authorization: `Bearer ${token}` } })
}

test('two clients receive the hold live and concurrent MySQL creates allow only one pending', async ({ browser }) => {
  test.setTimeout(300_000)
  const api = await request.newContext({ baseURL: apiBase })
  const carsResponse = await api.get('cars')
  expect(carsResponse.ok()).toBe(true)
  const cars = (await carsResponse.json()).data
  const car = cars.find(item => item.brand === 'Dacia' && item.model === 'Duster') || cars[0]
  const dates = await findAvailableDates(api, car.id)
  expect(dates).toBeTruthy()

  const unique = `${Date.now()}-${Math.random().toString(16).slice(2)}`
  const clientA = await authenticatedClient(api, 'client@astra.ma')
  const clientB = await authenticatedClient(api, `concurrent-${unique}@astra.test`, true)
  const contextA = await browser.newContext({ baseURL: frontendOrigin })
  const contextB = await browser.newContext({ baseURL: frontendOrigin })
  const pageA = await contextA.newPage()
  const pageB = await contextB.newPage()
  const browserErrors = []
  for (const page of [pageA, pageB]) {
    page.on('console', message => { if (message.type() === 'error') browserErrors.push(message.text()) })
    page.on('pageerror', error => browserErrors.push(error.message))
  }

  await Promise.all([
    pageA.goto(`/cars/${car.id}?start_date=${dates.start_date}&end_date=${dates.end_date}`),
    pageB.goto(`/cars/${car.id}?start_date=${dates.start_date}&end_date=${dates.end_date}`),
  ])
  await Promise.all([
    expect(pageA.getByText('Disponible pour ces dates')).toBeVisible({ timeout: 90_000 }),
    expect(pageB.getByText('Disponible pour ces dates')).toBeVisible({ timeout: 90_000 }),
    expect.poll(() => pageA.evaluate(() => {
      return window.Echo?.connector?.pusher?.connection?.state;
    }), { timeout: 60_000 }).toBe('connected'),
    expect.poll(() => pageB.evaluate(() => {
      return window.Echo?.connector?.pusher?.connection?.state;
    }), { timeout: 60_000 }).toBe('connected'),
  ])

  const first = await clientA.post('my-reservations', { data: { car_id: car.id, ...dates } })
  expect(first.status()).toBe(201)

  await Promise.all([
    expect(pageA.getByText(/actualis/i)).toBeVisible({ timeout: 90_000 }),
    expect(pageB.getByText(/actualis/i)).toBeVisible({ timeout: 90_000 }),
  ])
  await Promise.all([
    expect(pageA.locator(`[data-date="${dates.start_date}"]`)).toBeDisabled(),
    expect(pageB.locator(`[data-date="${dates.start_date}"]`)).toBeDisabled(),
  ])

  const stale = await clientB.post('my-reservations', { data: { car_id: car.id, ...dates } })
  expect(stale.status()).toBe(409)
  expect((await stale.json()).message).toBe('Cette voiture est déjà réservée ou temporairement bloquée pour tout ou partie de cette période. Veuillez choisir d\u2019autres dates.')

  const raceDates = await findAvailableDates(api, car.id, 105)
  expect(raceDates).toBeTruthy()
  const [attemptA, attemptB] = await Promise.all([
    clientA.post('my-reservations', { data: { car_id: car.id, ...raceDates } }),
    clientB.post('my-reservations', { data: { car_id: car.id, ...raceDates } }),
  ])
  expect([attemptA.status(), attemptB.status()].sort()).toEqual([201, 409])

  const ownerLogin = await api.post('login', { data: { email: 'owner@astra.test', password: 'Password123!' } })
  const ownerToken = (await ownerLogin.json()).token
  const ownerApi = await request.newContext({ baseURL: apiBase, extraHTTPHeaders: { Authorization: `Bearer ${ownerToken}` } })
  const pendingResponse = await ownerApi.get('owner/reservations', { params: { status: 'pending' } })
  const pending = (await pendingResponse.json()).data
  const sameRaceHold = pending.filter(item => item.car?.id === car.id && item.start_date === raceDates.start_date && item.end_date === raceDates.end_date)
  expect(sameRaceHold).toHaveLength(1)
  expect(browserErrors).toEqual([])

  await Promise.all([contextA.close(), contextB.close(), api.dispose(), clientA.dispose(), clientB.dispose(), ownerApi.dispose()])
})
