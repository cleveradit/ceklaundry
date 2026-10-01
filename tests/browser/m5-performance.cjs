const { chromium, expect } = require('@playwright/test');
const fs = require('fs');
const account = JSON.parse(fs.readFileSync(process.env.M2_BROWSER_FIXTURE || 'test-results/m2-browser-fixture.json', 'utf8'));
const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const capacity = JSON.parse(fs.readFileSync(process.env.M5_CAPACITY_FIXTURE || 'test-results/m5-capacity-fixture.json', 'utf8'));
expect(capacity.businesses).toBe(200);
expect(capacity.transactions).toBe(50000);
(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'id-ID', timezoneId: 'Asia/Jakarta' });
  const page = await context.newPage();
  try {
    await page.goto(base + '/login');
    await page.getByLabel('Email', { exact: true }).fill(account.ownerEmail);
    await page.getByLabel('Password', { exact: true }).fill(account.password);
    await page.getByRole('button', { name: 'Masuk ke panel' }).click();
    await expect(page).toHaveURL(/\/owner$/);
    const cdp = await context.newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 4 * 1000 * 1000 / 8, uploadThroughput: 1000 * 1000 / 8 });
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    const values = [];
    for (let i = 0; i < 20; i++) {
      const start = performance.now();
      await page.goto(base + '/owner', { waitUntil: 'domcontentloaded' });
      await expect(page.getByTestId('receivables')).toBeVisible();
      await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
      values.push(performance.now() - start);
    }
    values.sort((a, b) => a - b);
    const p95 = values[Math.ceil(values.length * 0.95) - 1];
    fs.writeFileSync('test-results/m5-performance.json', JSON.stringify({ samples_ms: values, p95_ms: p95, capacity, profile: '4Mbps/1Mbps, RTT150ms, CPU4x' }, null, 2));
    expect(p95).toBeLessThan(2500);
    console.log('PASS: M5 dashboard P95=' + Math.round(p95) + 'ms, 20 navigations at DAT-04 capacity; ' + await browser.version());
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
