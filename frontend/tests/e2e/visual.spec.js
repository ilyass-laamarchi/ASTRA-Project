// Responsive visual smoke checks ensure key pages and image assets render cleanly.
import { test, expect } from '@playwright/test'
import path from 'node:path'

const viewports = [[1440, 900], [1920, 1080], [768, 1024], [390, 844]]

test('captures the reference-composition viewports without broken media', async ({ page }) => {
  const browserErrors = []
  page.on('console', message => {
    if (message.type() !== 'error') return
    // Chromium can cancel an in-flight resource while the same page is being
    // reopened at the next viewport. Media URLs and rendered dimensions are
    // verified explicitly below, so only actionable console errors belong here.
    if (/Failed to load resource: net::ERR_(CONNECTION_RESET|ABORTED)/.test(message.text())) return
    browserErrors.push(message.text())
  })
  page.on('pageerror', error => browserErrors.push(error.message))

  for (const [width, height] of viewports) {
    await page.setViewportSize({ width, height })
    await page.goto('/')
    await page.waitForLoadState('networkidle')
    await expect(page.getByRole('heading', { name: /L'excellence/i })).toBeVisible()
    await page.evaluate(async () => {
      for (let y = 0; y < document.documentElement.scrollHeight; y += window.innerHeight) {
        window.scrollTo(0, y)
        await new Promise(resolve => setTimeout(resolve, 60))
      }
      window.scrollTo(0, 0)
    })
    const imageSources = await page.locator('img').evaluateAll(images => [...new Set(images.map(image => image.src).filter(Boolean))])
    const unavailableSources = (await Promise.all(imageSources.map(async source => ({ source, ok: (await page.request.get(source)).ok() })))).filter(result => !result.ok)
    expect(unavailableSources).toEqual([])
    const broken = await page.locator('img').evaluateAll(images => images.filter(image => image.complete && image.naturalWidth === 0).map(image => image.src))
    expect(broken).toEqual([])
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBe(true)
    await page.screenshot({ path: path.resolve('../docs/screenshots', `home-${width}x${height}.png`), fullPage: true })
  }
  expect(browserErrors).toEqual([])
})
