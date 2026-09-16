const {defineConfig} = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/E2E',
  use: {
    baseURL: process.env.NAVIGATING_BASE_URL || 'http://127.0.0.1:8000',
  },
});
