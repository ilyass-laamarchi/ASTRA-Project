// Real-browser regression for the admin client directory synchronization flow.
import { test, expect, request } from '@playwright/test'

const adminToken = process.env.ASTRA_REAL_ADMIN_TOKEN
const testEmail = process.env.ASTRA_SYNC_TEST_EMAIL
const apiBase = `${process.env.ASTRA_API_ORIGIN || 'http://127.0.0.1:8000'}/api/`

test('new public registration appears in the admin client directory automatically', async ({ page }) => {
  test.skip(!adminToken || !testEmail, 'Requires an isolated admin token and temporary client email.')
  test.setTimeout(90_000)

  const adminApi = await request.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: { Authorization: `Bearer ${adminToken}` },
  })
  const publicApi = await request.newContext({ baseURL: apiBase })
  const meResponse = await adminApi.get('me')
  expect(meResponse.ok()).toBe(true)
  const { user: admin } = await meResponse.json()

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('astra_token', token)
    localStorage.setItem('astra_user', JSON.stringify(user))
  }, { token: adminToken, user: admin })

  await page.goto('/admin/clients')
  await expect(page).toHaveURL(/\/admin\/clients$/)
  await expect(page.getByRole('heading', { name: 'Clients', level: 2 })).toBeVisible()

  const registration = await publicApi.post('register', { data: {
    first_name: 'Sync',
    last_name: 'Verification',
    email: testEmail,
    phone: '0600000099',
    password: 'AdminSync123!',
    password_confirmation: 'AdminSync123!',
  } })
  expect(registration.status()).toBe(201)

  // The directory refreshes every 15 seconds without requiring an admin reload.
  await expect(page.getByText(testEmail, { exact: true })).toBeVisible({ timeout: 30_000 })
  await expect(page.getByText(/Page 1 sur \d+ · \d+ clients/)).toBeVisible()

  const search = page.getByRole('textbox', { name: 'Rechercher (Nom, Email...)' })
  await search.fill(testEmail)
  await expect(page.getByText(testEmail, { exact: true })).toBeVisible()
  await expect(page.getByText('1 comptes · synchronisation automatique')).toBeVisible()

  await Promise.all([adminApi.dispose(), publicApi.dispose()])
})
