// Browser checks prove the client dashboard contains only personal live data.
import { expect, test } from '@playwright/test'

const token = process.env.ASTRA_REAL_CLIENT_TOKEN
const apiOrigin = process.env.ASTRA_API_ORIGIN || 'http://localhost:8000'

test.skip(!token, 'ASTRA_REAL_CLIENT_TOKEN is required for real-account verification')
test.setTimeout(180_000)

test('real client sees a personal portal without admin metrics', async ({ page, request }) => {
  const meResponse = await request.get(`${apiOrigin}/api/me`, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } })
  expect(meResponse.ok()).toBeTruthy()
  const { user } = await meResponse.json()
  await page.addInitScript(({ authToken, currentUser }) => {
    localStorage.setItem('astra_token', authToken)
    localStorage.setItem('astra_user', JSON.stringify(currentUser))
  }, { authToken: token, currentUser: user })

  await page.goto('/client/dashboard')
  await expect(page.getByRole('heading', { name: `Bonjour, ${user.first_name}` })).toBeVisible({ timeout: 120_000 })
  await expect(page.getByText('Ma prochaine réservation')).toBeVisible()
  await expect(page.getByText('Mes dernières réservations')).toBeVisible()
  await expect(page.getByText('Actions rapides')).toBeVisible()
  await expect(page.getByText('Véhicules disponibles')).toBeVisible()
  await expect(page.getByText('AST-260819-FZTFB9').first()).toBeVisible()
  await expect(page.getByText('Opel Corsa').first()).toBeVisible()

  for (const forbidden of ['Clients', 'Revenu Total', "Taux d'occupation", 'Répartition par catégorie', 'Revenus par véhicule']) {
    await expect(page.getByText(forbidden, { exact: true })).toHaveCount(0)
  }
})
