/** Browser, responsive, and two-context MySQL checks against the local stack. */
import { defineConfig, devices } from '@playwright/test'

const frontendOrigin = process.env.ASTRA_FRONTEND_ORIGIN || 'http://127.0.0.1:5173'
const chromiumPath = process.env.ASTRA_PLAYWRIGHT_CHROMIUM
const httpCredentials = process.env.ASTRA_HTTP_USERNAME && process.env.ASTRA_HTTP_PASSWORD
  ? { username: process.env.ASTRA_HTTP_USERNAME, password: process.env.ASTRA_HTTP_PASSWORD }
  : undefined

export default defineConfig({
  testDir: './tests/e2e',
  workers: 1,
  timeout: 240_000,
  expect: { timeout: 60_000 },
  webServer: process.env.ASTRA_SKIP_WEB_SERVER ? undefined : {
    command: 'npm run dev -- --host 127.0.0.1',
    url: frontendOrigin,
    reuseExistingServer: true,
  },
  use: { baseURL: frontendOrigin, trace: 'on-first-retry', ...(httpCredentials ? { httpCredentials } : {}) },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'], launchOptions: chromiumPath ? { executablePath: chromiumPath } : {} } }],
})
