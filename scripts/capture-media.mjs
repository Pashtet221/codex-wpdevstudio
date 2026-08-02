#!/usr/bin/env node
import { chromium } from 'playwright';
import sharp from 'sharp';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const wpScript = path.join(__dirname, 'wp');

function usage(message = '') {
  if (message) console.error(message);
  console.error(`Usage:
  scripts/wp capture URL NAME [options]

Options:
  --selector=CSS          Screenshot only this element
  --mode=page|viewport    Full page (default) or current viewport
  --width=1440            Browser viewport width
  --height=1200           Browser viewport height
  --max-width=1600        Maximum output image width
  --max-height=0          Maximum output height; 0 = unlimited
  --quality=80            WebP quality 1..100
  --post-id=123           Attach media to this WordPress post
  --alt="..."             Media alt text
  --title="..."           Media title
  --caption="..."         Media caption
  --description="..."     Media description
  --set-featured          Set uploaded image as post thumbnail
  --wait-for=CSS          Wait for selector before screenshot
  --delay=1000            Extra delay in milliseconds
  --mobile                Shortcut: viewport 390x844
`);
  process.exit(2);
}

function parseArgs(argv) {
  const [url, name, ...rest] = argv;
  if (!url || !name) usage();
  const opts = {
    url,
    name: name.replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, '') || 'screenshot',
    selector: '',
    mode: 'page',
    width: 1440,
    height: 1200,
    maxWidth: 1600,
    maxHeight: 0,
    quality: 80,
    postId: 0,
    alt: '',
    title: '',
    caption: '',
    description: '',
    setFeatured: false,
    waitFor: '',
    delay: 500,
  };

  for (const arg of rest) {
    if (arg === '--set-featured') opts.setFeatured = true;
    else if (arg === '--mobile') { opts.width = 390; opts.height = 844; opts.mode = 'viewport'; }
    else if (arg.startsWith('--selector=')) opts.selector = arg.slice(11);
    else if (arg.startsWith('--mode=')) opts.mode = arg.slice(7);
    else if (arg.startsWith('--width=')) opts.width = Number(arg.slice(8));
    else if (arg.startsWith('--height=')) opts.height = Number(arg.slice(9));
    else if (arg.startsWith('--max-width=')) opts.maxWidth = Number(arg.slice(12));
    else if (arg.startsWith('--max-height=')) opts.maxHeight = Number(arg.slice(13));
    else if (arg.startsWith('--quality=')) opts.quality = Number(arg.slice(10));
    else if (arg.startsWith('--post-id=')) opts.postId = Number(arg.slice(10));
    else if (arg.startsWith('--alt=')) opts.alt = arg.slice(6);
    else if (arg.startsWith('--title=')) opts.title = arg.slice(8);
    else if (arg.startsWith('--caption=')) opts.caption = arg.slice(10);
    else if (arg.startsWith('--description=')) opts.description = arg.slice(14);
    else if (arg.startsWith('--wait-for=')) opts.waitFor = arg.slice(11);
    else if (arg.startsWith('--delay=')) opts.delay = Number(arg.slice(8));
    else usage(`Unknown option: ${arg}`);
  }

  if (!['page', 'viewport'].includes(opts.mode)) usage('--mode must be page or viewport');
  if (!Number.isFinite(opts.width) || opts.width < 240) usage('Invalid --width');
  if (!Number.isFinite(opts.height) || opts.height < 240) usage('Invalid --height');
  opts.quality = Math.min(100, Math.max(1, Number(opts.quality) || 80));
  return opts;
}

const opts = parseArgs(process.argv.slice(2));
const tempDir = await fs.mkdtemp(path.join(os.tmpdir(), 'cwb-capture-'));
const rawPath = path.join(tempDir, `${opts.name}.png`);
const webpPath = path.join(tempDir, `${opts.name}.webp`);
let browser;

try {
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: opts.width, height: opts.height },
    deviceScaleFactor: 1,
  });

  await page.goto(opts.url, { waitUntil: 'networkidle', timeout: 60000 });
  if (opts.waitFor) await page.locator(opts.waitFor).first().waitFor({ state: 'visible', timeout: 30000 });
  if (opts.delay > 0) await page.waitForTimeout(opts.delay);

  // Reduce noisy overlays that commonly ruin automated case screenshots.
  await page.addStyleTag({ content: `
    [aria-modal="true"].cookie, .cookie-banner, .cookie-consent,
    .cky-consent-container, #cookie-notice, .grecaptcha-badge { display:none !important; }
  `}).catch(() => {});

  if (opts.selector) {
    const target = page.locator(opts.selector).first();
    await target.waitFor({ state: 'visible', timeout: 30000 });
    await target.screenshot({ path: rawPath, animations: 'disabled' });
  } else {
    await page.screenshot({
      path: rawPath,
      fullPage: opts.mode === 'page',
      animations: 'disabled',
    });
  }

  const inputMeta = await sharp(rawPath).metadata();
  let resize = null;
  if ((opts.maxWidth && inputMeta.width > opts.maxWidth) || (opts.maxHeight && inputMeta.height > opts.maxHeight)) {
    resize = {
      width: opts.maxWidth || null,
      height: opts.maxHeight || null,
      fit: 'inside',
      withoutEnlargement: true,
    };
  }

  let pipeline = sharp(rawPath);
  if (resize) pipeline = pipeline.resize(resize);
  await pipeline.webp({ quality: opts.quality, effort: 4 }).toFile(webpPath);

  const uploadArgs = ['media-upload', webpPath, `--source-url=${opts.url}`];
  if (opts.postId) uploadArgs.push(`--post-id=${opts.postId}`);
  if (opts.alt) uploadArgs.push(`--alt=${opts.alt}`);
  if (opts.title) uploadArgs.push(`--title=${opts.title}`);
  if (opts.caption) uploadArgs.push(`--caption=${opts.caption}`);
  if (opts.description) uploadArgs.push(`--description=${opts.description}`);
  if (opts.setFeatured) uploadArgs.push('--set-featured');

  const upload = spawnSync(wpScript, uploadArgs, { encoding: 'utf8', env: process.env });
  if (upload.status !== 0) {
    throw new Error(upload.stderr || upload.stdout || 'WordPress media upload failed');
  }

  const response = JSON.parse(upload.stdout);
  const media = response.media || response;
  const block = media?.id && media?.url
    ? `<!-- wp:image {"id":${media.id},"sizeSlug":"full","linkDestination":"none"} -->\n<figure class="wp-block-image size-full"><img src="${media.url}" alt="${(opts.alt || media.alt || '').replaceAll('"', '&quot;')}" class="wp-image-${media.id}"/></figure>\n<!-- /wp:image -->`
    : '';
  const stat = await fs.stat(webpPath);
  const outputMeta = await sharp(webpPath).metadata();

  console.log(JSON.stringify({
    ok: true,
    source_url: opts.url,
    selector: opts.selector || null,
    viewport: { width: opts.width, height: opts.height },
    image: {
      format: 'webp',
      width: outputMeta.width,
      height: outputMeta.height,
      bytes: stat.size,
      quality: opts.quality,
    },
    duplicate: Boolean(response.duplicate),
    media,
    gutenberg_block: block,
  }, null, 2));
} catch (error) {
  console.error(JSON.stringify({ ok: false, error: error.message }, null, 2));
  process.exitCode = 1;
} finally {
  if (browser) await browser.close().catch(() => {});
  await fs.rm(tempDir, { recursive: true, force: true }).catch(() => {});
}
