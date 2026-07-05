// Captures product-tour screenshots of the local VowNook app with headless
// Chrome at 2x scale. Outputs PNGs to ./tour-out/.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8080';
const OUT = path.join(__dirname, 'tour-out');

// Kill entrance animations so nothing is caught mid-fade, and hide scrollbars.
const FREEZE_CSS = `
  *, *::before, *::after { transition: none !important; animation: none !important; }
  [style*="opacity"] { opacity: 1 !important; transform: none !important; }
  ::-webkit-scrollbar { display: none !important; }
`;

const SHOTS = [
  { name: 'dashboard', url: '/dashboard' },
  { name: 'guests', url: '/guests' },
  { name: 'budget', url: '/budget' },
  { name: 'checklist', url: '/checklist' },
  { name: 'seating', url: '/seating' },
  { name: 'timeline', url: '/timeline' },
  { name: 'website-editor', url: '/website' },
  { name: 'registry', url: '/registry' },
  { name: 'quotes', url: '/vendors/quotes' },
  { name: 'quotes-compare', url: '/vendors/quotes/compare' },
  { name: 'marketplace', url: '/marketplace' },
  {
    name: 'wedding-site',
    url: '/w/amelia-and-julian',
    // The public invitation opens behind a tap-to-open cover.
    action: async (page) => {
      const btn = await page.$$eval('button', (els) => {
        const b = els.find((e) => /open invitation/i.test(e.textContent));
        if (b) b.click();
        return !!b;
      }).catch(() => false);
      if (btn) await new Promise((r) => setTimeout(r, 2500));
      // Pause the background music the open tap may have started.
      await page.evaluate(() => document.querySelectorAll('audio').forEach((a) => a.pause()));
    },
  },
  {
    name: 'shop-personalizer',
    url: '/shop/customize.html',
    extraWait: 3500, // canvas fonts + design thumbnails
  },
];

(async () => {
  fs.mkdirSync(OUT, { recursive: true });

  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: 'shell',
    args: ['--no-first-run', '--hide-scrollbars', '--force-color-profile=srgb'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

  // Log in as the demo couple.
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0', timeout: 60000 });
  await page.type('input[type="email"]', 'couple@vownook.test');
  await page.type('input[type="password"]', 'preview-pass-123');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 60000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await new Promise((r) => setTimeout(r, 2000));
  const where = await page.evaluate(() => location.pathname);
  if (where !== '/dashboard') throw new Error(`login failed, on ${where}`);
  console.log('logged in');

  const only = process.argv.slice(2);
  for (const shot of SHOTS.filter((s) => only.length === 0 || only.includes(s.name))) {
    try {
      await page.goto(`${BASE}${shot.url}`, { waitUntil: 'networkidle0', timeout: 60000 });
      await page.addStyleTag({ content: FREEZE_CSS });
      await new Promise((r) => setTimeout(r, shot.extraWait ?? 1600));
      if (shot.action) await shot.action(page);
      await page.screenshot({ path: path.join(OUT, `${shot.name}.png`), type: 'png' });
      console.log(`ok ${shot.name}`);
    } catch (e) {
      console.log(`FAIL ${shot.name}: ${e.message}`);
    }
  }

  await browser.close();
  console.log('done');
})();
