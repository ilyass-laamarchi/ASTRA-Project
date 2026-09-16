// Browser checks cover the public fleet catalogue and vehicle details.
import { test, expect } from '@playwright/test'

const addedModels = [
  'Sandero',
  '208',
  'Corsa',
  'Logan',
  'Kardian',
  'Tucson',
  'Sportage',
  'Tiguan',
]

const apiOrigin = process.env.ASTRA_API_ORIGIN || 'http://localhost:8000'

test('public and admin catalogues expose the same persistent Tanger fleet', async ({ page, request }) => {
  test.setTimeout(180_000)
  const publicResponse = await request.get(`${apiOrigin}/api/cars`)
  expect(publicResponse.ok()).toBe(true)
  const publicPayload = await publicResponse.json()
  expect(publicPayload.meta.total).toBe(20)

  await page.goto('/cars')
  await expect(page.getByRole('heading', { name: /Trouvez le Véhicule Parfait/i })).toBeVisible({ timeout: 60_000 })
  const search = page.getByPlaceholder('ex: Peugeot')
  for (const model of addedModels) {
    await search.fill(model)
    await page.waitForTimeout(800)
    await expect(page.getByText(new RegExp(model, 'i')).first()).toBeVisible({ timeout: 15_000 })
  }
  await search.fill('')
  const brokenPublicImages = await page.locator('img').evaluateAll(images =>
    images.filter(image => image.complete && image.naturalWidth === 0).map(image => image.src),
  )
  expect(brokenPublicImages).toEqual([])

  const loginResponse = await request.post(`${apiOrigin}/api/login`, {
    data: { email: 'admin@astra.ma', password: 'Password123!' },
  })
  expect(loginResponse.ok()).toBe(true)
  const session = await loginResponse.json()

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('astra_token', token)
    localStorage.setItem('astra_user', JSON.stringify(user))
  }, session)
  await page.goto('/admin/cars')
  await expect(page.getByText(/Gestion des véhicules/i).first()).toBeVisible({ timeout: 60_000 })
  await expect(page.getByText('Chargement...', { exact: true })).toHaveCount(0, { timeout: 60_000 })
  const adminSearch = page.getByPlaceholder('Rechercher (Marque, Modèle, Immat...)')
  for (const model of addedModels) {
    await adminSearch.fill(model)
    await expect(page.getByText(new RegExp(model, 'i')).first()).toBeVisible()
  }
  await adminSearch.fill('')

  const adminResponse = await request.get(`${apiOrigin}/api/admin/cars`, {
    headers: { Authorization: `Bearer ${session.token}` },
  })
  expect(adminResponse.ok()).toBe(true)
  const adminPayload = await adminResponse.json()
  expect(adminPayload.data).toHaveLength(publicPayload.meta.total)
  expect(adminPayload.data.map(car => car.model)).toEqual(expect.arrayContaining(addedModels))
})
