const { chromium, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const output = path.resolve('test-results'); fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
  const options = { viewport: { width: 390, height: 844 }, locale: 'id-ID', timezoneId: 'Asia/Jakarta', serviceWorkers: 'allow' };
  const contextA = await browser.newContext(options);
  const contextB = await browser.newContext(options);
  const a = await contextA.newPage();
  const b = await contextB.newPage();
  const errors = []; a.on('pageerror', error => errors.push(error.message)); b.on('pageerror', error => errors.push(error.message));
  async function demo(page) {
    await page.goto(base + '/');
    await page.getByRole('button', { name: 'Coba Demo' }).click();
    await expect(page).toHaveURL(/\/owner$/);
    await expect(page.getByText('MODE DEMO')).toBeVisible();
  }
  try {
    const manifestResponse = await contextA.request.get(base + '/manifest.webmanifest');
    expect(manifestResponse.status()).toBe(200);
    const manifest = await manifestResponse.json();
    expect(manifest.name).toBe('CekLaundry');
    expect(manifest.icons.map(icon => icon.sizes)).toEqual(['192x192', '512x512']);
    for (const icon of manifest.icons) expect((await contextA.request.get(base + icon.src)).status()).toBe(200);

    await demo(a);
    await demo(b);
    await a.goto(base + '/app/transactions');
    const transactions = a.locator('a[href^="/app/transactions/"]');
    await expect(transactions.first()).toBeVisible();
    const href = (await transactions.evaluateAll(links => links.map(link => link.getAttribute('href')))).find(value => /^\/app\/transactions\/\d+$/.test(value || ''));
    expect(href).toBeTruthy();
    const id = Number(href.split('/').at(-1));
    await b.goto(base + `/app/transactions/${id}`);
    await expect(b.getByText('Halaman tidak ditemukan.')).toBeVisible();

    await a.goto(base + `/app/transactions/${id}`);
    const acceptUnknown = dialog => dialog.accept();
    a.on('dialog', acceptUnknown);
    const [preview] = await Promise.all([
      contextA.waitForEvent('page'),
      a.getByRole('button', { name: 'Buka resi via WhatsApp' }).click(),
    ]);
    a.off('dialog', acceptUnknown);
    await expect(preview.getByRole('heading', { name: 'Pratinjau pesan demo' })).toBeVisible();
    expect(preview.url()).not.toContain('wa.me');
    await preview.close();
    await a.getByRole('button', { name: 'Lihat sebagai Admin' }).click();
    await expect(a).toHaveURL(/\/app$/);
    await a.goto(base + '/owner');
    await expect(a.getByText('Anda tidak memiliki akses', { exact: false }).first()).toBeVisible();
    await a.goto(base + '/app');
    await a.getByRole('button', { name: 'Kembali sebagai Owner' }).click();
    await expect(a).toHaveURL(/\/owner$/);
    await a.screenshot({ path: path.join(output, 'm6-demo-mobile.png'), fullPage: true });

    await a.waitForFunction(() => navigator.serviceWorker?.controller, undefined, { timeout: 15000 });
    await a.goto(base + '/owner/branches');
    await expect(a.getByText('Cabang Kedua')).toBeVisible();
    await a.getByRole('button', { name: 'Tambah cabang' }).first().click();
    await a.getByLabel('Nama cabang').fill('Perubahan belum disimpan');
    await a.evaluate(() => window.dispatchEvent(new Event('pwa-controller-changed')));
    await expect(a.getByText('Pembaruan CekLaundry tersedia')).toBeVisible();
    await expect(a.getByLabel('Nama cabang')).toHaveValue('Perubahan belum disimpan');
    await a.getByRole('button', { name: 'Tutup formulir' }).click();
    a.once('dialog', dialog => dialog.dismiss());
    await a.getByRole('button', { name: 'Muat versi terbaru' }).click();
    await expect(a).toHaveURL(/\/owner\/branches$/);
    const cached = await a.evaluate(async () => {
      const keys = await caches.keys();
      return (await Promise.all(keys.map(async key => (await (await caches.open(key)).keys()).map(request => new URL(request.url).pathname)))).flat();
    });
    expect(cached).toContain('/offline.html');
    expect(cached.every(url => url === '/offline.html' || /^\/build\/assets\/.+-[A-Za-z0-9_-]{8,}\./.test(url))).toBe(true);
    await contextA.setOffline(true);
    await a.goto(base + '/owner');
    await expect(a.getByRole('heading', { name: 'Anda sedang offline' })).toBeVisible();
    await contextA.setOffline(false);
    await a.goto(base + '/owner');
    await a.getByRole('button', { name: 'Keluar' }).click();
    await expect(a).toHaveURL(/\/login$/);
    await demo(a);
    await a.goto(base + `/app/transactions/${id}`);
    await expect(a.getByText('Halaman tidak ditemukan.')).toBeVisible();
    const dynamicCached = await a.evaluate(async () => {
      const keys = await caches.keys();
      return (await Promise.all(keys.map(async key => (await (await caches.open(key)).keys()).map(request => new URL(request.url).pathname)))).flat();
    });
    expect(dynamicCached).not.toContain('/owner');
    expect(dynamicCached).not.toContain(`/app/transactions/${id}`);
    expect(errors).toEqual([]);
    console.log('PASS: M6 two demos, role switch, outbound preview, install metadata, static cache, offline fallback, tenant switch; ' + await browser.version());
  } catch (error) {
    await a.screenshot({ path: path.join(output, 'm6-failure.png'), fullPage: true });
    throw error;
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
