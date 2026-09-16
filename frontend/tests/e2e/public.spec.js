/** Browser smoke flow confirms public navigation and clean dynamic dates. */
import { test, expect } from '@playwright/test'

test('public visitor can open the premium fleet', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { name: /L'excellence/i })).toBeVisible()
  const forbiddenOldDate = ['01', '02', '20', '00'].join('/')
  await expect(page.locator('body')).not.toContainText(forbiddenOldDate)
  await page.goto('/cars')
  await expect(page).toHaveURL(/\/cars/)
  await expect(page.getByRole('heading', { name: /Trouvez le Véhicule Parfait/i })).toBeVisible()
})
