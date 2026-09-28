const { chromium } = require('playwright');
const { expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const jsQR = require('jsqr');
const { PNG } = require('pngjs');

const fixture = JSON.parse(fs.readFileSync(process.env.M2_BROWSER_FIXTURE || 'test-results/m2-browser-fixture.json', 'utf8'));
const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const output = path.resolve('test-results'); fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  async function go(url) { await page.goto(base + url); await page.waitForLoadState('networkidle'); }
  async function login(email) {
    await go('/login');
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill(fixture.password);
    await page.getByRole('button', { name: 'Masuk ke panel' }).click();
    await page.waitForLoadState('networkidle');
  }
  try {
    await login(fixture.ownerEmail);
    await expect(page).toHaveURL(/\/owner$/);
    await go('/owner/settings/payment');
    await page.getByRole('checkbox', { name: 'Izinkan DP baru' }).check();
    await page.getByRole('button', { name: 'Simpan pengaturan' }).click();
    await expect(page.getByRole('checkbox', { name: 'Izinkan DP baru' })).toBeChecked();
    await page.getByRole('button', { name: 'Keluar' }).click();
    await login(fixture.adminEmail);
    await expect(page).toHaveURL(/\/app$/);
    await go('/app/transactions/create');
    await page.getByLabel('Nama', { exact: true }).fill('Sinta Permata');
    await page.getByLabel('Nomor HP', { exact: true }).fill('081298765432');
    await page.getByLabel('Berat aktual (kg)').fill('6');
    await page.getByRole('button', { name: 'Tambah layanan' }).click();
    const setrikaId = await page.getByLabel('Layanan 2').locator('option').filter({ hasText: 'Setrika' }).getAttribute('value');
    await page.getByLabel('Layanan 2').selectOption(setrikaId);
    await page.getByLabel('Jumlah item').fill('13');
    await page.getByRole('button', { name: 'Hitung & periksa harga' }).click();
    await expect(page.getByText('Rp74.500', { exact: true }).first()).toBeVisible();
    await page.getByLabel('Pembayaran saat masuk (opsional)').fill('30000');
    await page.getByRole('button', { name: 'Simpan transaksi' }).click();
    await expect(page).toHaveURL(/\/app\/transactions\/\d+$/);
    const id = Number(new URL(page.url()).pathname.split('/').at(-1));
    const code = (await page.getByRole('heading', { name: /^Resi / }).textContent()).replace('Resi ', '').trim();
    await expect(page.getByText('Rp44.500', { exact: true }).first()).toBeVisible();
    await page.getByLabel('Perkiraan jumlah baju · Cuci Lipat').fill('8');
    await page.getByRole('button', { name: 'Simpan perubahan' }).click();
    await expect(page.getByText('Transaksi diperbarui.')).toBeVisible();
    await expect(page.getByLabel('Perkiraan jumlah baju · Cuci Lipat')).toHaveValue('8');
    expect(await page.locator('.stat-card').evaluateAll(cards => cards.some(card => {
      const number = card.querySelector('.stat-number');
      const label = card.querySelector('span');
      return number && label && number.getBoundingClientRect().bottom > label.getBoundingClientRect().top;
    }))).toBe(false);
    await page.screenshot({ path: path.join(output, 'm2-transaction-mobile.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

    await page.getByRole('button', { name: 'Pindah ke DIPROSES' }).click();
    await expect(page.getByRole('heading', { name: 'DIPROSES' })).toBeVisible();
    await page.getByRole('button', { name: 'Pindah ke SIAP DIAMBIL' }).click();
    await expect(page.getByRole('heading', { name: 'SIAP DIAMBIL' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Pindah ke SUDAH DIAMBIL' })).toBeDisabled();
    await page.getByLabel('Jumlah (Rp)').fill('44500');
    await page.getByRole('button', { name: 'Catat pembayaran' }).click();
    await expect(page.getByText('LUNAS', { exact: false }).first()).toBeVisible();
    await page.getByRole('button', { name: 'Pindah ke SUDAH DIAMBIL' }).click();
    await expect(page.getByRole('heading', { name: 'SUDAH DIAMBIL' })).toBeVisible();

    await go('/app/transactions/create');
    await page.getByLabel('Cari pelanggan lama').fill('081298765432');
    await expect(page.getByLabel('Pelanggan terdaftar').locator('option')).toHaveCount(2);
    await expect(page.getByLabel('Pelanggan terdaftar')).toContainText('Sinta Permata');

    await go('/app/transactions/' + id + '/print');
    await expect(page.getByText(code, { exact: false }).first()).toBeVisible();
    await expect(page.locator('svg')).toHaveCount(1);
    await page.screenshot({ path: path.join(output, 'm2-thermal-receipt.png'), fullPage: true });
    await page.pdf({ path: path.join(output, 'm2-thermal-receipt.pdf'), printBackground: true, preferCSSPageSize: true });
    const qr = PNG.sync.read(await page.locator('.qr').screenshot());
    expect(jsQR(new Uint8ClampedArray(qr.data), qr.width, qr.height)?.data).toBe(base + '/t/' + code);

    const publicContext = await browser.newContext({ viewport: { width: 390, height: 844 }, javaScriptEnabled: false, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
    const publicPage = await publicContext.newPage();
    await publicPage.goto(base + '/');
    await publicPage.getByLabel('Kode resi').fill(code);
    await publicPage.getByRole('button', { name: 'Cek Status' }).click();
    await expect(publicPage).toHaveURL(new RegExp('/t/' + code + '$'));
    await expect(publicPage.getByRole('heading', { name: 'SUDAH DIAMBIL' })).toBeVisible();
    await expect(publicPage.getByText('Rp74.500').first()).toBeVisible();
    await publicPage.screenshot({ path: path.join(output, 'm2-public-mobile.png'), fullPage: true });
    expect(await publicPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await publicPage.goto(base + '/t/ZZZZZZ');
    await expect(publicPage.getByText('Resi tidak ditemukan', { exact: false }).first()).toBeVisible();
    await publicContext.close();
    await page.setViewportSize({ width: 1440, height: 1000 });
    await go('/app');
    await expect(page.getByText('Berat aktual hari ini')).toBeVisible();
    await page.screenshot({ path: path.join(output, 'm2-dashboard-desktop.png'), fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(errors).toEqual([]);
    console.log('PASS: M2 mobile create Rp74.500, DP Rp30.000, payoff, pickup guard, thermal print, no-JS public lookup; ' + await browser.version());
  } catch (error) {
    await page.screenshot({ path: path.join(output, 'm2-failure.png'), fullPage: true });
    throw error;
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
