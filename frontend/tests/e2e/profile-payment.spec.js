// Optional real-account verification for avatar persistence and payment readiness.
import { expect, test } from '@playwright/test'
import { fileURLToPath } from 'node:url'

const token = process.env.ASTRA_REAL_CLIENT_TOKEN
const apiOrigin = process.env.ASTRA_API_ORIGIN || 'http://localhost:8000'
const avatarFixture = fileURLToPath(new URL('../../public/favicon.png', import.meta.url))

test.skip(!token, 'ASTRA_REAL_CLIENT_TOKEN is required for real-account verification')

// Seed browser storage from a validated real-client token.
async function authenticate(page, request) {
  const response = await request.get(`${apiOrigin}/api/me`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  })
  expect(response.ok()).toBeTruthy()
  const { user } = await response.json()
  await page.addInitScript(({ authToken, currentUser }) => {
    localStorage.setItem('astra_token', authToken)
    localStorage.setItem('astra_user', JSON.stringify(currentUser))
  }, { authToken: token, currentUser: user })
}

test('real client avatar uploads, renders twice, and survives refresh', async ({ page, request }) => {
  await authenticate(page, request)
  await page.goto('/client/profile')
  await page.locator('input[type="file"]').setInputFiles(avatarFixture)
  await expect(page.getByText('Photo mise à jour.')).toBeVisible()

  const avatars = page.locator('img[alt="Photo de profil"]')
  await expect(avatars).toHaveCount(2)
  await expect(avatars.first()).toHaveAttribute('src', /^http:\/\/localhost:8000\/storage\/avatars\//)
  await expect.poll(() => avatars.evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0))).toBe(true)

  await page.reload()
  await expect(page.locator('img[alt="Photo de profil"]')).toHaveCount(2)
  await expect.poll(() => page.locator('img[alt="Photo de profil"]').evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0))).toBe(true)
})

test('reservation 71 stays payable only when the provider is ready', async ({ page, request }) => {
  await authenticate(page, request)
  await page.goto('/client/reservations')
  const row = page.locator('tr').filter({ hasText: 'Opel Corsa' }).filter({ hasText: '71' })
  await expect(row).toBeVisible()
  await expect(row.getByRole('button', { name: 'Paiement temporairement indisponible' })).toBeDisabled()
})
