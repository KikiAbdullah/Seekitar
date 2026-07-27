# Seekitar Dev Toolchain (sandbox)

Helper scripts that make the Laravel backend runnable inside this workspace,
which has **no system PHP** and a restricted network.

## Quick start

```bash
./tools/dev/setup      # one-shot: runtime + packages + .env + migrations
./tools/dev/serve      # http://127.0.0.1:8080
```

Verified working: Laravel 13.22.0 on PHP 8.5.8, migrations applied, PHPUnit
green, and the app served over HTTP (`/` and `/up` both return 200).

## Commands

| Command | Purpose |
| :-- | :-- |
| `./tools/dev/setup` | Full bootstrap. Safe to re-run (idempotent). |
| `./tools/dev/serve [port]` | Dev web server on `public/`. Default port 8080. |
| `./tools/dev/artisan <cmd>` | Any artisan command, e.g. `migrate`, `make:model Store -m`, `route:list`. |
| `./tools/dev/test [args]` | PHPUnit suite, e.g. `--filter=StoreTest`. |
| `./tools/dev/composer <cmd>` | Composer, e.g. `dump-autoload --optimize`, `show`. |
| `./tools/dev/php <file>` | Raw PHP 8.5 runtime. |
| `node tools/dev/check-versions.mjs` | Guards doc version consistency (Laravel 13 / PHP 8.3 / Riverpod 3). |
| `node tools/dev/check-structure.mjs` | Guards project-structure sections and Enum-vs-schema consistency. |
| `node tools/dev/check-datamodel.mjs` | Guards schema columns, design decisions, and DB↔API alignment. |
| `node tools/dev/check-api.mjs` | Guards API endpoint coverage, contract details, and cross-doc drift. |
| `node tools/dev/check-backend.mjs` | Guards Laravel implementation guide: middleware, policies, jobs, deployment. |
| `node tools/dev/check-mobile.mjs` | Guards Flutter guide: Riverpod 3 patterns, routing, FCM, deep links. |
| `node tools/dev/check-brand.mjs` | Guards brand guideline: design tokens, badge data sources, legal contacts. |
| `node tools/dev/check-prd.mjs` | Guards PRD: promised schema exists, KPI formulas, status mapping. |
| `node tools/dev/check-terms.mjs` | Guards cross-layer terminology (UI ↔ DB ↔ API) and enum value spelling. |
| `node tools/dev/check-security.mjs` | Guards PRD §11 security promises against their §18A implementation. |
| `node tools/dev/check-deploy.mjs` | Guards deployment guides: Redis, S3, TLS, backup, signing, monitoring. |
| `node tools/dev/check-dbperf.mjs` | Guards composite indexes, fulltext parser choice, and spatial query pattern. |
| `node tools/dev/check-docs.mjs` | Guards UI/UX, queue, testing, notification, geospatial, and doc-version sections. |

All checkers exit non-zero on failure, so they work as CI/pre-commit steps:

```bash
for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs; do
  node tools/dev/check-$c.mjs || exit 1
done
```

## Why this exists

The sandbox network only reaches **github.com** and **registry.npmjs.org**.
Blocked: `deb.debian.org` (no `apt install php`), `packagist.org` (no normal
`composer install`), `pub.dev` and `storage.googleapis.com` (no Flutter/Dart).

Two workarounds make the backend run anyway:

1. **PHP runtime** — [`@php-wasm/cli`](https://www.npmjs.com/package/@php-wasm/cli)
   from npm provides a WebAssembly PHP 8.5.8 with the extensions Laravel needs
   (`pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `zip`, `gd`, `curl`,
   `bcmath`, `fileinfo`, `dom`, ...).
2. **Composer packages** — every `dist.url` in `composer.lock` points at
   `api.github.com`, which *is* reachable. `install-vendor.mjs` downloads those
   110 zips directly, lays them out under `vendor/`, and writes
   `vendor/composer/installed.json`. Real Composer then generates the
   optimized autoloader (6,726 classes).

## Known limitations

- **`composer require` / `composer update` do not work** — resolving new
  versions needs packagist. To change a dependency: edit `composer.json` and
  `composer.lock`, then re-run `node tools/dev/install-vendor.mjs`.
- **No subprocesses.** WASM PHP cannot fork, so `artisan test`, `artisan serve`
  and `artisan pail` fail. Use `./tools/dev/test` and `./tools/dev/serve`,
  which call PHPUnit and the built-in server in-process instead.
- **Dev server is single-threaded** — one request at a time; fine for
  development, and it logs some harmless `Failed to poll event` lines on
  connection close.
- **Flutter/Dart cannot be installed here.** `seekitar_mobile` can be edited and
  reviewed, but not compiled or run in this sandbox — build it on a local
  machine with the Flutter SDK.
- **Database is SQLite.** The PRD targets MySQL 8.0 with spatial types
  (`POINT`, `ST_Distance_Sphere`, `SPATIAL INDEX`), which SQLite does not
  support. Plan geospatial work accordingly — see the note below.

## Note on the MySQL spatial requirement

`PRD.md` §7.3 and `DATABASE.md` rely on MySQL spatial functions. MySQL is not
installable in this sandbox, so for local work either:

- keep spatial columns/queries behind a small repository/service class so the
  SQLite path can use a bounding-box or Haversine fallback, or
- treat spatial features as "write here, verify on a MySQL environment".

Worth deciding before the store/listing radius search gets built, since it
affects how migrations and queries are written.

## Files

| File | Role |
| :-- | :-- |
| `install-vendor.mjs` | Fetches `composer.lock` packages from GitHub. |
| `boot-autoload.php` | Minimal PSR-4/classmap loader used only to start Composer itself. |
| `package.json` | Pins `@php-wasm/cli`. |

`node_modules/`, `.composer-src/` and `.composer-home/` are generated and
git-ignored.
