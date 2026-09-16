// Browser security checks cover public registration roles and guarded workspaces.
import { test, expect } from '@playwright/test'

test('public registration creates only a client and denies staff workspaces', async ({ page, request }) => {
  test.setTimeout(240_000)
  const email = process.env.ASTRA_AUTH_TEST_EMAIL || `public-client-${Date.now()}@astra.test`

  await page.goto('/register')
  await page.locator('input[name="first_name"]').fill('Public')
  await page.locator('input[name="last_name"]').fill('Security')
  await page.locator('input[name="email"]').fill(email)
  await page.locator('input[name="phone"]').fill('0612345678')
  await page.locator('input[name="password"]').fill('BrowserSecurity123!')
  await page.locator('input[name="password_confirmation"]').fill('BrowserSecurity123!')
  await page.locator('#terms').check()
  const registrationResponse = page.waitForResponse(response =>
    response.request().method() === 'POST' && response.url().endsWith('/api/register'),
  )
  await page.getByRole('button', { name: /Créer mon compte/i }).click()
  expect((await registrationResponse).status()).toBe(201)

  await expect(page).toHaveURL(/\/client\/dashboard$/, { timeout: 60_000 })
  await expect(page.getByText('Espace Client', { exact: true })).toBeVisible()

  const token = await page.evaluate(() => localStorage.getItem('astra_token'))
  expect(token).toBeTruthy()
  for (const path of ['/api/admin/staff', '/api/admin/settings', '/api/admin/cars']) {
    const response = await request.get(`http://localhost:8000${path}`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    expect(response.status()).toBe(403)
  }

  await page.goto('/admin/dashboard')
  await expect(page).toHaveURL(/\/client\/dashboard$/)
  await page.goto('/owner/dashboard')
  await expect(page).toHaveURL(/\/client\/dashboard$/)
})
