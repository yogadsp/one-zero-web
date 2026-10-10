import { chromium } from '/Users/mca_m4/.npm/_npx/31e32ef8478fbf80/node_modules/playwright-core/index.mjs';

const browser = await chromium.launch({
  headless: true,
  executablePath: '/Users/mca_m4/Library/Caches/ms-playwright/chromium-1243/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing',
});
const page = await browser.newPage({ viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 });
await page.goto('http://localhost:8000/login', { waitUntil: 'networkidle' });
console.log('login-url', page.url(), 'inputs', await page.locator('input').count(), 'body', (await page.locator('body').innerText()).slice(0,500));
await page.locator('input').nth(0).fill('codex-screenshot-qa-20260919@localhost.test');
await page.locator('input[type="password"]').fill('ScreenshotQa-2026');
await page.locator('button[type="submit"]').click();
await page.waitForTimeout(2500);
console.log('after-login', page.url(), (await page.locator('body').innerText()).slice(0,400));

const pages = [
  ['01-admin-dashboard', 'http://localhost:8000/admin/dashboard'],
  ['02-question-bank', 'http://localhost:8000/admin/question-titles'],
  ['03-question-list', 'http://localhost:8000/admin/question-titles/b66780a4-d080-5951-87f0-b383468e2ed4/questions'],
  ['04-import-docx-pdf', 'http://localhost:8000/admin/question-titles/b66780a4-d080-5951-87f0-b383468e2ed4/questions/import'],
];
for (const [name, url] of pages) {
  try { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 }); } catch (error) { console.log(`${name} navigation: ${error.message}`); }
  await page.waitForTimeout(1200);
  await page.screenshot({ path: `output/playwright/${name}.png`, fullPage: true });
  console.log(`${name}: ${page.url()} title=${await page.title()}`);
}
await browser.close();
