const { chromium } = require('playwright');
const { expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const account = JSON.parse(fs.readFileSync(process.env.M2_BROWSER_FIXTURE || 'test-results/m2-browser-fixture.json', 'utf8'));
const fixture = JSON.parse(fs.readFileSync(process.env.M4_BROWSER_FIXTURE || 'test-results/m4-browser-fixture.json', 'utf8'));
const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const output = path.resolve('test-results'); fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
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
    await go('/owner/settings/loyalty');
    await expect(page.getByRole('heading', { name: 'Program stempel' })).toBeVisible();
    await expect(page.getByLabel('Aktifkan program stempel')).toBeChecked();
    await go('/owner/promos');
    await expect(page.getByText('Promo M4')).toBeVisible();
    await page.getByRole('button', { name: 'Keluar' }).click();

    await login(account.adminEmail);
    await go('/app/transactions/create');
    await expect(page.getByLabel('Baris hadiah (opsional)')).toHaveCount(0);
    await page.getByLabel('Pelanggan terdaftar').selectOption(String(fixture.customerId));
    await expect(page.getByLabel('Baris hadiah (opsional)')).toBeVisible();
    await page.getByLabel('Berat aktual (kg)').fill('2');
    await page.getByLabel('Baris hadiah (opsional)').selectOption('0');
    await page.getByLabel('Promo (opsional)').selectOption(String(fixture.promoId));
    await page.getByRole('button', { name: 'Hitung & periksa harga' }).click();
    await expect(page.getByText('−Rp7.000')).toBeVisible();
    await expect(page.getByText('−Rp700')).toBeVisible();
    await expect(page.getByText('Rp6.300')).toBeVisible();
    await page.getByRole('button', { name: 'Simpan transaksi' }).click();
    await expect(page).toHaveURL(/\/app\/transactions\/\d+$/);
    const code = (await page.getByRole('heading', { name: /^Resi / }).textContent()).replace('Resi ', '').trim();
    await expect(page.getByText('Promo M4')).toBeVisible();
    await page.screenshot({ path: path.join(output, 'm4-transaction-mobile.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

    const publicContext = await browser.newContext({ javaScriptEnabled: false, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
    const publicPage = await publicContext.newPage();
    await publicPage.goto(base + '/t/' + code);
    await expect(publicPage.getByRole('heading', { name: 'Stempel Anda: 0/1' })).toBeVisible();
    await expect(publicPage.getByText('Promo M4')).toBeVisible();
    await expect(publicPage.locator('body')).not.toContainText('Pelanggan M4');
    await publicPage.goto(base + '/t/' + code + '/print');
    await expect(publicPage.getByText('Promo M4')).toBeVisible();
    await publicContext.close();
    expect(errors).toEqual([]);
    console.log('PASS: M4 owner settings, mobile redemption with promo, no-JS public saldo and receipt; ' + await browser.version());
  } catch (error) {
    await page.screenshot({ path: path.join(output, 'm4-failure.png'), fullPage: true });
    throw error;
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
