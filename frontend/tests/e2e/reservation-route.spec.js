// End-to-end booking check proves public selections reach the client reservation route.
import { test, expect } from '@playwright/test'

const apiOrigin = process.env.ASTRA_API_ORIGIN || 'http://localhost:8000'

/** Returns an ISO calendar date a fixed number of days from today. */
function futureIso(days) {
  const date = new Date(Date.now() + days * 86_400_000)
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Africa/Casablanca', year: 'numeric', month: '2-digit', day: '2-digit',
  }).format(date)
}

/** Finds an available interval and authoritative server quote for car 8. */
async function findAvailableQuote(request) {
  for (let offset = 2; offset <= 120; offset += 4) {
    const dates = { start_date: futureIso(offset), end_date: futureIso(offset + 2) }
    const response = await request.get(`${apiOrigin}/api/cars/8/availability`, { params: dates })
    if (response.ok()) {
      const quote = await response.json()
      if (quote.available) return quote
    }
  }
  return null
}

test('Kia Picanto booking uses the authenticated client route end to end', async ({ page, request }) => {
  test.setTimeout(300_000)
  const quote = await findAvailableQuote(request)
  expect(quote).toBeTruthy()
  const payload = { car_id: 8, start_date: quote.start_date, end_date: quote.end_date }

  await page.goto('/login')
  await page.locator('input[name="email"]').fill('client@astra.ma')
  await page.locator('input[name="password"]').fill('Password123!')
  await page.getByRole('button', { name: /Se connecter/i }).click()
  await expect(page).toHaveURL(/\/client\/dashboard$/)

  await page.goto(`/cars/8?start_date=${quote.start_date}&end_date=${quote.end_date}`)
  await expect(page.getByRole('heading', { name: 'Réservation' })).toBeVisible({ timeout: 60_000 })
  await page.locator(`[data-date="${quote.start_date}"]`).click()
  await page.locator(`[data-date="${quote.end_date}"]`).click()
  await expect(page.getByText(`${Number(quote.daily_price).toLocaleString('fr-MA')} MAD x ${quote.rental_days} jours`)).toBeVisible()
  await expect(page.getByText(`${Number(quote.total_amount).toLocaleString('fr-MA')} MAD`).first()).toBeVisible()

  const responsePromise = page.waitForResponse(response =>
    response.request().method() === 'POST' && response.url().endsWith('/api/my-reservations'),
  )
  const requestPromise = page.waitForRequest(requestItem =>
    requestItem.method() === 'POST' && requestItem.url().endsWith('/api/my-reservations'),
  )
  await page.getByRole('button', { name: /Confirmer la réservation/i }).click()
  const [bookingRequest, bookingResponse] = await Promise.all([requestPromise, responsePromise])

  expect(bookingRequest.url()).toBe('http://localhost:8000/api/my-reservations')
  expect(bookingRequest.postDataJSON()).toEqual(payload)
  expect(bookingResponse.status()).toBe(201)
  const created = (await bookingResponse.json()).data
  expect(created).toMatchObject({
    car: { id: 8 },
    rental_days: quote.rental_days,
    total_amount: quote.total_amount,
    status: 'pending',
  })

  await expect(page).toHaveURL(/\/client\/dashboard$/)
  await page.goto('/client/reservations')
  await expect(page.getByText(`#${created.id}`, { exact: true })).toBeVisible()
  await expect(page.getByText(/Kia Picanto/i).first()).toBeVisible()

  const token = await page.evaluate(() => localStorage.getItem('astra_token'))
  const overlap = await request.post('http://localhost:8000/api/my-reservations', {
    headers: { Authorization: `Bearer ${token}` },
    data: payload,
  })
  expect(overlap.status()).toBe(409)

  const invalidDates = await request.post('http://localhost:8000/api/my-reservations', {
    headers: { Authorization: `Bearer ${token}` },
    data: { car_id: 8, start_date: '2026-08-20', end_date: '2026-08-18' },
  })
  expect(invalidDates.status()).toBe(422)

  const unauthenticated = await request.post('http://localhost:8000/api/my-reservations', { data: payload })
  expect(unauthenticated.status()).toBe(401)
})
