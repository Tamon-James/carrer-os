import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const base=process.env.DEMO_BASE_URL||'http://127.0.0.1:8765';
const email=process.env.DEMO_EMAIL;
const password=process.env.DEMO_PASSWORD;
if(!email||!password)throw new Error('DEMO_EMAIL and DEMO_PASSWORD are required.');
const output=new URL('../public/assets/images/guide/',import.meta.url);
await fs.mkdir(output,{recursive:true});
const screenshotPath=name=>path.join(fileURLToPath(output),name);

const browser=await chromium.launch({headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1000},deviceScaleFactor:1});
await page.goto(`${base}/login.php`);
await page.getByLabel('メールアドレス').fill(email);
await page.getByLabel('パスワード').fill(password);
await page.getByRole('button',{name:'ログイン'}).click();
await page.waitForLoadState('networkidle');

await page.screenshot({path:screenshotPath('dashboard.png'),fullPage:true});

await page.goto(`${base}/companyFile.php`);
await page.waitForLoadState('networkidle');
await page.screenshot({path:screenshotPath('companies.png'),fullPage:true});

await page.getByText('アーク戦略パートナーズ株式会社',{exact:true}).click();
await page.waitForLoadState('networkidle');
await page.screenshot({path:screenshotPath('company-workspace.png'),fullPage:true});

await page.getByRole('main').getByRole('link',{name:'面接モード'}).click();
await page.waitForLoadState('networkidle');
await page.screenshot({path:screenshotPath('interview-mode.png'),fullPage:false});

await page.getByRole('button',{name:'日程調整'}).click();
await page.waitForTimeout(350);
await page.screenshot({path:screenshotPath('interview-scheduling.png'),fullPage:false});

await fs.mkdir(new URL('./artifacts/',import.meta.url),{recursive:true});
await page.goto(`${base}/guide.php`);
await page.waitForLoadState('networkidle');
await page.screenshot({path:fileURLToPath(new URL('./artifacts/guide-desktop.png',import.meta.url)),fullPage:false});
await page.locator('#quick-access').screenshot({path:fileURLToPath(new URL('./artifacts/guide-quick-access.png',import.meta.url))});
await page.setViewportSize({width:390,height:844});
await page.goto(`${base}/guide.php`);
await page.waitForLoadState('networkidle');
await page.screenshot({path:fileURLToPath(new URL('./artifacts/guide-mobile.png',import.meta.url)),fullPage:false});
await page.locator('#quick-access').screenshot({path:fileURLToPath(new URL('./artifacts/guide-quick-access-mobile.png',import.meta.url))});

console.log('Demo screenshots captured.');
await browser.close();
