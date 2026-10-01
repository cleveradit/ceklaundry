const { chromium, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const account = JSON.parse(fs.readFileSync(process.env.M2_BROWSER_FIXTURE || 'test-results/m2-browser-fixture.json', 'utf8'));
const fixture = JSON.parse(fs.readFileSync(process.env.M5_BROWSER_FIXTURE || 'test-results/m5-browser-fixture.json', 'utf8'));
const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const output = path.resolve('test-results'); fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
  const page = await context.newPage();
  const errors = []; page.on('pageerror', error => errors.push(error.message));
  async function go(url) { await page.goto(base + url); await page.waitForLoadState('networkidle'); }
  async function login(email) {
    await go('/login'); await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill(account.password);
    await page.getByRole('button', { name: 'Masuk ke panel' }).click(); await page.waitForLoadState('networkidle');
    await expect(page).not.toHaveURL(/\/login$/);
  }
  try {
    await login(account.ownerEmail);
    await go('/owner');
    await expect(page.getByTestId('today-count')).toBeVisible();
    await page.screenshot({ path: path.join(output, 'm5-dashboard-desktop.png'), fullPage: true });
    await go('/owner/reports/history');
    await page.getByLabel('Tanggal awal').fill('2026-07-01');
    await page.getByLabel('Tanggal akhir').fill('2026-07-31');
    await page.getByRole('button', { name: 'Terapkan filter' }).click(); await page.waitForLoadState('networkidle');
    await expect(page.getByRole('link', { name: fixture.code, exact: true })).toBeVisible();
    const downloadEvent = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Ekspor CSV', exact: true }).click();
    const download = await downloadEvent;
    const csv = fs.readFileSync(await download.path(), 'utf8');
    expect(csv.startsWith('\uFEFF')).toBe(true);
    expect(csv).toContain("'=Pelanggan M5");
    expect(csv).toContain(fixture.code);
    await go('/owner/reports/revenue?period=custom&start=2026-07-01&end=2026-07-31');
    await expect(page.getByTestId('revenue-total')).toContainText('3.000');
    await expect(page.getByRole('img', { name: /Pendapatan/ })).toBeVisible();
    await page.getByText('Angka per periode (31)', { exact: true }).click();
    await expect(page.getByText('2026-07-28', { exact: true })).toBeVisible();
    await page.screenshot({ path: path.join(output, 'm5-revenue-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    for (const url of ['/owner', '/owner/reports/history', '/owner/reports/revenue', '/owner/reports/receivables']) {
      await go(url);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
      await page.screenshot({ path: path.join(output, 'm5-' + url.split('/').at(-1) + '-mobile.png'), fullPage: true });
    }
    await page.getByRole('button', { name: 'Keluar' }).click();
    await login(account.adminEmail);
    await go('/owner/reports/history');
    await expect(page.getByText('Anda tidak memiliki akses', { exact: false }).first()).toBeVisible();
    expect(errors).toEqual([]);
    console.log('PASS: M5 owner dashboard, history filters, revenue/chart, CSV formula protection, mobile layout, admin denial; ' + await browser.version());
  } catch (error) {
    await page.screenshot({ path: path.join(output, 'm5-failure.png'), fullPage: true }); throw error;
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
