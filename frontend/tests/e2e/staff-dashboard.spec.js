// Browser checks for the distinct administrator and Responsable dashboards.
import { expect, test } from '@playwright/test'

const apiBase = process.env.PLAYWRIGHT_API_URL || 'http://localhost:8000/api/'
const browserApiBase = process.env.PLAYWRIGHT_BROWSER_API_URL

// Authenticate a fixture staff account through the real API.
async function signIn(request, email) {
  const response = await request.post(`${apiBase}login`, { data: { email, password: 'Password123!' } })
  expect(response.ok()).toBe(true)
  const payload = await response.json()
  return { token: payload.token, user: payload.user }
}

// Redirect browser API traffic when Playwright runs across Docker boundaries.
async function bridgeDockerApi(page) {
  if (!browserApiBase) return
  await page.route('http://localhost:8000/**', async route => {
    const response = await route.fetch({ url: route.request().url().replace('http://localhost:8000', browserApiBase) })
    await route.fulfill({ response })
  })
}

test('admin receives strategic paid-revenue analytics', async ({ page, request }) => {
  const session = await signIn(request, 'admin@astra.ma')
  await bridgeDockerApi(page)
  await page.addInitScript(value => { localStorage.setItem('astra_token', value.token); localStorage.setItem('astra_user', JSON.stringify(value.user)) }, session)
  const analytics = page.waitForResponse(response => response.url().includes('/api/admin/dashboard') && response.status() === 200)
  await page.goto('/admin/dashboard')
  expect((await (await analytics).json()).data.dashboard_type).toBe('admin')
  await expect(page.getByRole('heading', { name: 'Performance globale ASTRA' })).toBeVisible()
  await expect(page.getByText('Évolution du chiffre d’affaires')).toBeVisible()
  await expect(page.getByText('Performance des véhicules')).toBeVisible()
})

test('responsable receives operational analytics without strategic sections', async ({ page, request }) => {
  const session = await signIn(request, 'owner@astra.test')
  await bridgeDockerApi(page)
  await page.addInitScript(value => { localStorage.setItem('astra_token', value.token); localStorage.setItem('astra_user', JSON.stringify(value.user)) }, session)
  const analytics = page.waitForResponse(response => response.url().includes('/api/owner/dashboard') && response.status() === 200)
  await page.goto('/owner/dashboard')
  const payload = (await (await analytics).json()).data
  expect(payload.dashboard_type).toBe('operations')
  expect(payload.revenue_series).toBeUndefined()
  await expect(page.getByRole('heading', { name: 'Opérations quotidiennes' })).toBeVisible()
  await expect(page.getByText('Planning des départs et retours')).toBeVisible()
  await expect(page.getByText('Évolution du chiffre d’affaires')).toHaveCount(0)
})
