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
| `node tools/dev/check-schema-drift.mjs` | Compares migrations ⇄ `DATABASE.md` ⇄ Eloquent `$fillable`; catches columns that exist in one layer but not the others. |
| `node tools/dev/check-mysql.mjs` | Asserts the project stays MySQL-only: no SQLite path, SRID 4326 + `axis-order=long-lat`, spatial indexes on NOT NULL, native ENUM/SET, CHECK constraints, InnoDB. |
| `node tools/dev/check-seeders.mjs` | Asserts seeders match the docs: 24 categories, 12 permissions, 8 settings keys, idempotency, UUID morph key for Spatie. |
| `node tools/dev/check-services.mjs` | Asserts service-layer guarantees: OTP hashing/TTL/attempt limit, two-way broadcast matching, state-machine finality, phone masking in logs. |
| `node tools/dev/check-http.mjs` | Asserts every documented endpoint has a route, auth/throttle middleware is applied, resources do not leak private columns, and Blade escaping stays on. |
| `node tools/dev/check-admin-menu.mjs` | Asserts every `@can` in the admin sidebar matches the `permission:` middleware on the route it links to, that Datatables JSON endpoints are guarded too, that the Datatables i18n file is self-hosted and valid, and renders every admin page as both `admin` and `super-admin`. |
| `node tools/dev/check-peta.mjs` | Asserts all 24 Pasuruan districts are present with in-bounds coordinates, Leaflet is self-hosted with its licence, OpenStreetMap attribution is kept, the map container has an explicit height, store popups use `textContent` (never string-built HTML), and the 50-store seeder is deterministic and clamped to the regency. |
| `./tools/dev/php tools/dev/render-admin.php` | Renders all admin Blade pages under two permission sets without MySQL. Catches missing route names, missing view variables, and menu items that leak across roles. |
| `./tools/dev/php tools/dev/check-relations.php` | Verifies every eager-loaded relation (`with`/`withCount`/`load`) actually exists on its model. Eager loading is lazy, so `with('user')` on a model whose relation is `owner()` throws only when rows are fetched — `toSql()` will not reveal it. |
| `./tools/dev/php tools/dev/check-datatables.php` | Builds each DataTables query for real and checks that every column the Blade view requests exists in the SELECT list or as `addColumn`/`editColumn`. Also enforces `select()` before `withCount()`. |
| `node tools/dev/recolor-modernize.mjs` | Rewrites the vendored Modernize CSS from its stock blue `#5D87FF` to Seekitar green `#168A4A` (164 replacements) and strips the Tabler `@font-face` down to woff2. Idempotent — re-run it after every template update. |
| `./tools/dev/php tools/dev/run-seeders.php` | **Actually runs every seeder** against an in-memory SQLite mirror of the schema, then asserts row counts, FK wiring, uniqueness, `axis-order=long-lat` on every POINT, recomputed store ratings, and that each ENUM status is represented. Set `SKALA=1.0` for full volume. Called by `check-seeders.mjs`. |

All checkers exit non-zero on failure, so they work as CI/pre-commit steps:

```bash
for c in versions structure datamodel api backend mobile brand prd terms security deploy dbperf docs schema-drift mysql seeders services http admin-menu; do
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
   (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `zip`, `gd`, `curl`,
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
- **No database server.** Seekitar is MySQL-only, and MySQL cannot be installed
  here (`dev.mysql.com` is blocked). So `artisan migrate`, `./tools/dev/test`
  and `./tools/dev/serve` all fail at the point they open a connection. See
  below for what *can* be verified offline.

## Verifying the MySQL schema without MySQL

The schema depends on `POINT SRID 4326`, `SPATIAL INDEX`, `SET`, and `CHECK`
constraints. None of that can be exercised here, but the **generated DDL** can:

```bash
./tools/dev/ddl                  # every CREATE TABLE / ALTER TABLE, in order
node tools/dev/check-mysql.mjs   # asserts SRID, spatial indexes, ENUM/SET, CHECK, InnoDB
```

`dump-ddl.php` uses Laravel's `pretend()`, which runs the migrations through
the MySQL grammar and captures the SQL **without ever opening a connection**.
That catches the class of bug that matters most here — DDL that is silently
wrong — while still requiring a real MySQL 8.0.34+ server for behaviour tests.

Seeders can be inspected the same way:

```bash
./tools/dev/php tools/dev/dump-seed.php 'Database\Seeders\CategorySeeder'
node tools/dev/check-seeders.mjs
```

`pretend()` alone is not enough for seeders: idempotent seeders run a `SELECT`
first (`firstOrCreate`), which `pretend()` never executes — Laravel then still
reaches for PDO and the script dies. So `dump-seed.php` swaps in a connection
subclass that answers every read with an empty set and records every write.

> What that proves: the seeder runs to completion, targets columns that exist,
> and its SQL is assembled by the MySQL grammar. What it does **not** prove:
> constraint and uniqueness behaviour, or anything depending on auto-increment
> ids — foreign keys come back `NULL` because nothing is really inserted.
> Those still need a real MySQL server.

> ⚠️ MySQL reads `SRID 4326` WKT as **(latitude longitude)**, per EPSG. Every
> Indonesian longitude (95°–141°E) is outside latitude's ±90 range, so
> `ST_GeomFromText('POINT(lng lat)', 4326)` fails with
> `ERROR 3617: Latitude ... is out of range`. All spatial SQL therefore passes
> `'axis-order=long-lat'`; `check-mysql.mjs` fails the build if it is dropped.

## Files

| File | Role |
| :-- | :-- |
| `install-vendor.mjs` | Fetches `composer.lock` packages from GitHub. |
| `boot-autoload.php` | Minimal PSR-4/classmap loader used only to start Composer itself. |
| `ddl` / `dump-ddl.php` | Prints the MySQL DDL the migrations would emit, without a server. |
| `dump-seed.php` | Runs a seeder against a recording stub connection and prints the writes it would perform. |
| `package.json` | Pins `@php-wasm/cli`. |

`node_modules/`, `.composer-src/` and `.composer-home/` are generated and
git-ignored.
