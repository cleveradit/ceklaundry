const { chromium } = require('playwright');
const { expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const fixture = JSON.parse(fs.readFileSync(process.env.BROWSER_FIXTURE || 'test-results/browser-fixture.json', 'utf8'));
const base = process.env.BROWSER_URL || 'http://127.0.0.1:8089';
const output = path.resolve('test-results'); fs.mkdirSync(output,{recursive:true});
const password = 'Qa!'+crypto.randomBytes(18).toString('hex');
const ownerEmail='owner-'+Date.now()+'@example.test', adminEmail='admin-'+Date.now()+'@example.test';
(async()=>{
 const browser=await chromium.launch({headless:true, ...(process.env.CHROME_PATH ? {executablePath:process.env.CHROME_PATH} : {})});
 const context=await browser.newContext({viewport:{width:1440,height:1000},locale:'id-ID',timezoneId:'Asia/Jakarta'});
 const page=await context.newPage(); const errors=[];
 page.on('pageerror',e=>errors.push(e.message)); page.on('dialog',d=>d.accept());
 async function go(url){await page.goto(base+url);await page.waitForLoadState('networkidle');}
 async function login(email,pw){await go('/login');await page.getByLabel('Email',{exact:true}).fill(email);await page.getByLabel('Password',{exact:true}).fill(pw);await page.getByRole('button',{name:'Masuk ke panel'}).click();await page.waitForLoadState('networkidle');}
 async function change(old,pw){await expect(page).toHaveURL(/password\/change/);await page.getByLabel('Password saat ini').fill(old);await page.getByLabel('Password baru',{exact:true}).fill(pw);await page.getByLabel('Ulangi password baru').fill(pw);await page.getByRole('button',{name:'Ganti password',exact:true}).click();await page.waitForLoadState('networkidle');}
 async function add(name){await page.getByRole('button',{name,exact:true}).first().click();await expect(page.getByRole('dialog')).toBeVisible();}
 async function save(){await page.getByRole('button',{name:'Simpan perubahan',exact:true}).click();await expect(page.getByRole('dialog')).toHaveCount(0);}
 async function logout(){await page.getByRole('button',{name:'Keluar',exact:true}).click();await expect(page).toHaveURL(/login/);}
 try {
  await go('/'); expect(await page.locator('script[src*="app-"]').count()).toBe(0);
  await login(fixture.email,fixture.password);await change(fixture.password,password);await expect(page).toHaveURL(/\/dev$/);
  await add('Tambah bisnis');await page.getByLabel('Nama bisnis',{exact:true}).fill('Laundry Melati');await page.getByLabel('Masa aktif sampai (WIB)').fill('2030-12-31');await page.getByLabel('Nama owner').fill('Pemilik Melati');await page.getByLabel('Email owner').fill(ownerEmail);await page.getByLabel('Password awal').fill(password);await save();await expect(page.getByText('Laundry Melati',{exact:true})).toBeVisible();await logout();
  await login(ownerEmail,password);await change(password,password+'x');await expect(page).toHaveURL(/\/owner$/);
  await page.screenshot({path:path.join(output,'owner-desktop.png'),fullPage:true});
  await go('/owner/branches');await add('Tambah cabang');await page.getByLabel('Nama cabang',{exact:true}).fill('Melati Pusat');await page.getByLabel('Alamat cabang').fill('Jalan Melati 12, Jakarta');await page.getByLabel('Nomor telepon').fill('081234567890');await save();
  await go('/owner/admins');await add('Tambah admin');await page.getByLabel('Nama admin',{exact:true}).fill('Sari');await page.getByLabel('Email admin').fill(adminEmail);await page.getByLabel('Cabang tugas').selectOption({label:'Melati Pusat'});await page.getByLabel('Password awal').fill(password);await save();
  await go('/owner/masters');await add('Tambah layanan');await page.getByLabel('Nama layanan',{exact:true}).fill('Cuci Lipat');await page.getByLabel('Harga per satuan (Rp)').fill('7000');await page.getByLabel('Berat minimum').fill('2');await save();
  await go('/owner/sync');await page.getByLabel('Melati Pusat',{exact:true}).check();await page.getByRole('button',{name:'Lihat pratinjau'}).click();await expect(page.getByRole('button',{name:'Konfirmasi & terapkan'})).toBeVisible();await page.screenshot({path:path.join(output,'sync-desktop.png'),fullPage:true});
  // Edit in another tab: the old preview must fail atomically.
  const tab=await context.newPage();await tab.goto(base+'/owner/masters');await tab.getByRole('button',{name:'Ubah Cuci Lipat',exact:true}).click();await tab.getByLabel('Harga per satuan (Rp)').fill('8000');await tab.getByRole('button',{name:'Simpan perubahan',exact:true}).click();await expect(tab.getByRole('dialog')).toHaveCount(0);await tab.close();
  await page.getByRole('button',{name:'Konfirmasi & terapkan'}).click();await expect(page.getByText('Data berubah',{exact:false}).first()).toBeVisible();
  await go('/owner/sync');await page.getByLabel('Melati Pusat',{exact:true}).check();await page.getByRole('button',{name:'Lihat pratinjau'}).click();await page.getByRole('button',{name:'Konfirmasi & terapkan'}).click();await expect(page.getByRole('status')).toContainText('berhasil disinkronkan');
  await page.setViewportSize({width:390,height:844});await go('/owner/branches');await page.screenshot({path:path.join(output,'branches-mobile.png'),fullPage:true});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
  await page.getByRole('link',{name:'Layanan',exact:true}).click();await expect(page.getByText('Cuci Lipat',{exact:true})).toBeVisible();
  await logout();await page.goBack();await page.waitForTimeout(400);await expect(page.getByText('Cuci Lipat',{exact:true})).toHaveCount(0);
  await login(adminEmail,password);await change(password,password+'a');await expect(page).toHaveURL(/\/app$/);await expect(page.getByRole('heading',{name:'Operasional cabang'})).toBeVisible();
  await page.screenshot({path:path.join(output,'admin-mobile.png'),fullPage:true});await go('/owner/branches');await expect(page.getByText('Anda tidak memiliki akses',{exact:false}).first()).toBeVisible();
  expect(errors).toEqual([]);console.log('PASS: developer → owner → admin; forced passwords, CRUD, two-tab stale sync, mobile, logout history, role isolation; '+await browser.version());
 } catch(error){await page.screenshot({path:path.join(output,'failure.png'),fullPage:true});throw error;} finally {await browser.close();}
})().catch(error=>{console.error(error.message);process.exitCode=1;});
