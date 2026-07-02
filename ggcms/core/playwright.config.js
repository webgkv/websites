// @ts-check
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: 'tests',
  fullyParallel: false,
  timeout: 15000,
  webServer: {
    command: 'npx serve . -p 8765',
    url: 'http://localhost:8765',
    reuseExistingServer: !process.env.CI,
    timeout: 12000,
  },
  use: {
    baseURL: 'http://localhost:8765',
    trace: 'on-first-retry',
  },
});
