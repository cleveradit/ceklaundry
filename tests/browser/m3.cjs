const { chromium } = require('playwright');
const { expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const fixture = JSON.parse(fs.readFileSync(process.env.M2_BROWSER_FIXTURE || 'test-results/m2-browser-fixture.json', 'utf8'));
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
    await page.getByLabel('Password', { exact: true }).fill(fixture.password);
    await page.getByRole('button', { name: 'Masuk ke panel' }).click(); await page.waitForLoadState('networkidle');
    await expect(page).not.toHaveURL(/\/login$/);
  }
  try {
    await login(fixture.ownerEmail);
    await go('/owner/settings/notifications');
    await expect(page.getByRole('heading', { name: 'Pengaturan notifikasi' })).toBeVisible();
    await page.getByLabel('Pengingat pertama setelah siap (hari)').fill('1');
    await page.getByLabel('Batas WA per bulan (kosong = tanpa batas)').fill('0');
    await page.getByRole('button', { name: 'Simpan pengaturan' }).click();
    await expect(page.getByLabel('Pengingat pertama setelah siap (hari)')).toHaveValue('1');
    await page.screenshot({ path: path.join(output, 'm3-owner-settings-mobile.png'), fullPage: true });
    await page.getByRole('button', { name: 'Keluar' }).click();
    await login(fixture.adminEmail);
    await go('/app/transactions/create');
    await page.getByLabel('Nama', { exact: true }).fill('Pelanggan M3');
    await page.getByLabel('Nomor HP').fill('081299988877');
    await page.getByLabel('Email (opsional)').fill('awal@example.test');
    await page.getByLabel('Berat aktual (kg)').fill('2');
    await page.getByRole('button', { name: 'Hitung & periksa harga' }).click();
    await page.getByRole('button', { name: 'Simpan transaksi' }).click();
    await expect(page).toHaveURL(/\/app\/transactions\/\d+$/);
    const code = (await page.getByRole('heading', { name: /^Resi / }).textContent()).replace('Resi ', '').trim();
    const publicContext = await browser.newContext({ javaScriptEnabled: false, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
    const publicPage = await publicContext.newPage();
    await publicPage.goto(base + '/t/' + code);
    await expect(publicPage.getByRole('heading', { name: 'Notifikasi email' })).toBeVisible();
    await publicPage.getByLabel('Alamat email').fill('baru@example.test');
    await publicPage.getByRole('button', { name: 'Kirim tautan konfirmasi' }).click();
    await expect(publicPage).toHaveURL(new RegExp('/t/' + code + '$'));
    await expect(publicPage.getByRole('status')).toContainText('tautan konfirmasi');
    await expect(publicPage.locator('body')).not.toContainText('awal@example.test');
    await expect(publicPage.locator('body')).not.toContainText('baru@example.test');
    await publicContext.close();
    await page.reload();
    await page.getByRole('button', { name: 'Pindah ke DIPROSES' }).click();
    await page.getByRole('button', { name: 'Pindah ke SIAP DIAMBIL' }).click();
    await expect(page.getByRole('heading', { name: 'SIAP DIAMBIL' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Riwayat notifikasi' })).toBeVisible();
    await expect(page.getByText('awal@example.test · tertunda')).toBeVisible();
    await page.getByRole('button', { name: 'Kirim email pengingat' }).click();
    await expect(page.getByText('Permintaan email dicatat')).toBeVisible();
    await page.screenshot({ path: path.join(output, 'm3-notifications-mobile.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(errors).toEqual([]);
    console.log('PASS: M3 settings, no-JS public email request, ready reservation and manual reminder UI; ' + await browser.version());
  } catch (error) {
    await page.screenshot({ path: path.join(output, 'm3-failure.png'), fullPage: true });
    throw error;
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
