#!/usr/bin/env node
/**
 * Install a Composer `vendor/` tree straight from composer.lock, without
 * needing packagist.org.
 *
 * Why: this workspace's network only reaches github.com + registry.npmjs.org.
 * Every dist URL in the Laravel lockfile points at api.github.com, so we can
 * fetch each package zip directly and lay it out the way Composer would.
 *
 * Usage:  node install-vendor.mjs [projectRoot]
 */
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(process.argv[2] || path.join(import.meta.dirname, '../../seekitar-server'));
const VENDOR = path.join(ROOT, 'vendor');
const CACHE = path.join(os.tmpdir(), 'seekitar-composer-cache');
const CONCURRENCY = 8;

function githubToken() {
  if (process.env.GITHUB_TOKEN) return process.env.GITHUB_TOKEN.trim();
  if (process.env.GH_TOKEN) return process.env.GH_TOKEN.trim();
  try {
    return execFileSync('gh', ['auth', 'token'], { encoding: 'utf8' }).trim();
  } catch {
    return null; // public repos usually work unauthenticated, just rate-limited
  }
}

const TOKEN = githubToken();
const lockPath = path.join(ROOT, 'composer.lock');
if (!fs.existsSync(lockPath)) {
  console.error(`composer.lock not found at ${lockPath}`);
  process.exit(1);
}

const lock = JSON.parse(fs.readFileSync(lockPath, 'utf8'));
const prod = (lock.packages || []).map(p => ({ ...p, __dev: false }));
const dev = (lock['packages-dev'] || []).map(p => ({ ...p, __dev: true }));
const all = [...prod, ...dev];

fs.mkdirSync(CACHE, { recursive: true });
fs.mkdirSync(VENDOR, { recursive: true });

async function download(pkg) {
  const safe = pkg.name.replace(/\//g, '__');
  const ref = (pkg.dist?.reference || pkg.version).slice(0, 12);
  const zip = path.join(CACHE, `${safe}-${ref}.zip`);
  if (fs.existsSync(zip) && fs.statSync(zip).size > 0) return zip;

  const headers = { 'User-Agent': 'seekitar-dev-toolchain' };
  if (TOKEN) headers.Authorization = `Bearer ${TOKEN}`;
  const res = await fetch(pkg.dist.url, { headers });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  fs.writeFileSync(zip, Buffer.from(await res.arrayBuffer()));
  return zip;
}

function extract(pkg, zip) {
  const dest = path.join(VENDOR, ...pkg.name.split('/'));
  const stamp = path.join(dest, '.composer-bootstrap');
  const ref = pkg.dist?.reference || pkg.version;
  if (fs.existsSync(stamp) && fs.readFileSync(stamp, 'utf8') === ref) return false;

  fs.rmSync(dest, { recursive: true, force: true });
  const tmp = fs.mkdtempSync(path.join(CACHE, 'x-'));
  execFileSync('unzip', ['-qq', '-o', zip, '-d', tmp]);
  const entries = fs.readdirSync(tmp);
  const inner = entries.length === 1 ? path.join(tmp, entries[0]) : tmp;
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.renameSync(inner, dest);
  fs.rmSync(tmp, { recursive: true, force: true });
  fs.writeFileSync(stamp, ref);
  return true;
}

/** Composer reads vendor/composer/installed.json to know what is present. */
function writeInstalledJson() {
  const toEntry = p => {
    const { __dev, ...rest } = p;
    return { ...rest, 'install-path': '../' + p.name };
  };
  const payload = {
    packages: all.map(toEntry),
    dev: true,
    'dev-package-names': dev.map(p => p.name),
  };
  fs.mkdirSync(path.join(VENDOR, 'composer'), { recursive: true });
  fs.writeFileSync(
    path.join(VENDOR, 'composer', 'installed.json'),
    JSON.stringify(payload, null, 4)
  );
}

console.log(`Installing ${all.length} packages into ${path.relative(process.cwd(), VENDOR) || VENDOR}`);
const queue = [...all];
let done = 0;
let fetched = 0;

await Promise.all(
  Array.from({ length: CONCURRENCY }, async () => {
    while (queue.length) {
      const pkg = queue.shift();
      let lastErr;
      for (let attempt = 1; attempt <= 3; attempt++) {
        try {
          if (extract(pkg, await download(pkg))) fetched++;
          lastErr = null;
          break;
        } catch (e) {
          lastErr = e;
          await new Promise(r => setTimeout(r, 800 * attempt));
        }
      }
      if (lastErr) {
        console.error(`FAILED ${pkg.name}: ${lastErr.message}`);
        process.exitCode = 1;
      }
      done++;
      if (done % 25 === 0) console.log(`  ...${done}/${all.length}`);
    }
  })
);

writeInstalledJson();
console.log(`Done: ${done} packages present (${fetched} newly extracted).`);
console.log('Next: ./tools/dev/composer dump-autoload --optimize');
