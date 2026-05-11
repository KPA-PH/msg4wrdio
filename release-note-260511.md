# Release Notes

## ⚠️ Breaking changes

- **Demo routes are off by default.** `/msg4wrd`, `/msg4wrd/send`, and `/msg4wrd/send-with-options` were previously auto-registered on every consuming app. They are now gated behind `MSG4wrdIO_EXPOSE_DEMO_ROUTES=true`. Set the env var to re-enable them (recommended: local environments only).
- **`MSG4wrdIO_DOMAIN` semantics changed.** It is now the **live (production)** URL only. The default was `https://sms-backend-api-rpnlb.msg4wrd.io`; it is now `https://api.msg4wrd.io`. If you were pinning a custom URL via this env var, double-check it.
- **Helper method renamed.** `Helper::chechNumberCountryCode()` → `Helper::checkNumberCountryCode()`. Update any direct callers.
- **Number format is stricter.** Only `+63` (PH mobile) and `+1` (US/CA) numbers are accepted. Local PH formats like `09171234567` are rejected — use international `+639171234567`. NANP numbers must have a valid area code and exchange code (both `2–9`).
- **Service-provider registration changed.** Auto-discovery is now wired up — **remove** the manual `KPAPH\MSG4wrdIO\MSG4wrdIOServiceProvider::class` entry from `config/app.php` if you had one.
- **Default token is empty.** The placeholder `[SECRET-TOKEN]` default in the published config has been removed.

## 🐛 Bug fixes

- **`Country::US` no longer silently falls through to PH.** `Helper::checkCountry()` was comparing an int-backed enum to the string `"US"` and always taking the default branch. The country option now actually works.
- **Service-provider register/boot lifecycle corrected.** `mergeConfigFrom()` moved from `boot()` into `register()` (where it belongs), and the eager `$this->app->make(SampleController::class)` call has been removed — controllers are resolved by the router on demand.
- **HTTP `Authorization` header malformed.** The header value had a stray leading space (`' Bearer '`), which some proxies reject. Fixed to `Bearer <token>`.
- **Success was hard-coded to HTTP 200.** Any 2xx response is now treated as success; non-2xx responses surface the upstream status and body instead of being collapsed to a generic `Sending failed.` message.
- **Dead imports removed.** `Guzzle\Http\Exception\BadResponseException`, `ClientErrorResponseException`, `ServerErrorResponseException` are Guzzle 3 classes that do not exist in Guzzle 7 — removed.
- **Brittle PHP version check removed.** `(int)phpversion() < 8` cURL fallback is gone (we require PHP `^8.1` now anyway, and Guzzle 7 handles everything).

## 🔒 Security

- **Unauthenticated SMS-sending routes disabled by default** (see breaking change above). Previously any consuming app exposed publicly-reachable endpoints that could trigger SMS sends billed to the token owner.
- Removed the `[SECRET-TOKEN]` placeholder default from the published config.
- Removed tracked `.DS_Store` files and added a `.gitignore`.

## ✨ New features

- **Live / sandbox environment switch.** The config now exposes separate `live` and `sandbox` base URLs. Toggle via `MSG4wrdIO_DEV_MODE`:
  - `MSG4wrdIO_DEV_MODE=false` → live (`MSG4wrdIO_DOMAIN`, default `https://api.msg4wrd.io`)
  - `MSG4wrdIO_DEV_MODE=true` → sandbox (`MSG4wrdIO_DEVELOPER`, default `https://staging.msg4wrd.io`)
  - Parsed with `FILTER_VALIDATE_BOOLEAN`, so `"true"`, `"1"`, `"on"`, `"yes"` all enable sandbox.
- **Laravel package auto-discovery.** Added `extra.laravel.providers` to `composer.json`. Manually registering the service provider in `config/app.php` is no longer required.
- **New public helpers** on the `Helper` trait:
  - `normalizeNumber(string): ?string` — strips `+`, spaces, dashes, dots, and parentheses; returns digits-only string or `null` if anything else was present.
  - `detectCountry(string): ?Country` — returns the inferred `Country` enum (`PH` or `US`) for a valid +63 / +1 number, otherwise `null`.
- **`checkCountry()` accepts string aliases.** `PH` / `PHL` / `PHILIPPINES` → `Country::PH`; `US` / `USA` / `CA` / `CAN` / `CANADA` → `Country::US`. Enum, int, and string-alias inputs all resolve consistently. Case-insensitive.
- **`Send()` accepts partial `$options`.** `$options` now merges over sensible defaults, so you can pass just the keys you care about (e.g. only `country`).
- **`sendername` accepts custom brand IDs** as plain strings (e.g. `"GOOGLESMS"`) alongside the `SenderName` enum cases.

## 🔧 Improvements

- **Stricter, more correct number validation.** PH numbers must be `63` + 10 digits starting with `9` (the PH mobile prefix). NANP numbers must be `1` + 10 digits with the area code and exchange code both `2–9`, per NANP rules. Common separators (` `, `-`, `.`, `(`, `)`) are stripped before validation.
- **Mobile number normalized in the payload.** The `mobile` field sent upstream is consistently digits-only, regardless of how the caller formatted the input.
- **Defensive type handling in `Send()`.** `priority` is cast to `int`; `sendername` is unwrapped to its enum value when applicable.
- **Guzzle client timeout.** Added a 30s timeout so callers don't hang on unreachable hosts.
- **Cleaner `MSG4wrd::Send` validation flow.** Single guard via `checkNumberCountryCode()`, then a guaranteed-non-null `normalizeNumber()`.
- **Config publish gated by `runningInConsole()`** and tagged as `msg4wrdio-config`, so `php artisan vendor:publish --tag=msg4wrdio-config` works cleanly.

## 📦 Packaging

- `composer.json` now declares:
  - `php: ^8.1` (required for enums).
  - `illuminate/support: ^8.0 | ^9.0 | ^10.0 | ^11.0 | ^12.0` — **supports Laravel 8 through Laravel 12**.
  - `guzzlehttp/guzzle: ^7.2` (unchanged).
- `minimum-stability` switched from `dev` to `stable`, with `prefer-stable: true`.
- Added `extra.laravel.providers` for auto-discovery (see New features).

## 📝 Documentation

- README rewritten:
  - Added a **Requirements** section (PHP `^8.1`, Laravel `8.x`–`12.x`).
  - Documented the auto-discovery flow (no more `config/app.php` step).
  - Documented the new **demo-route opt-in** (`MSG4wrdIO_EXPOSE_DEMO_ROUTES`).
  - Added a **Configuration reference** table for every env var.
  - Documented the **live / sandbox** switch and the new `MSG4wrdIO_DEV_MODE` / `MSG4wrdIO_DEVELOPER` env vars.
  - Documented the supported phone-number formats and accepted separators.
  - Added a full **Changelog** with Fixes / Security / Packaging / Upgrading subsections.
- Fixed README typos (e.g. "Installtion" → "Installation").

## 🚚 Upgrading

If you are upgrading from an earlier version:

1. **Remove** the manual `KPAPH\MSG4wrdIO\MSG4wrdIOServiceProvider::class` line from `config/app.php` — auto-discovery handles it.
2. **Re-publish the config** to get the new keys (`live`, `sandbox`, `dev_mode`, `expose_demo_routes`):
   ```
   php artisan vendor:publish --tag=msg4wrdio-config --force
   ```
3. If you relied on the `/msg4wrd*` demo routes, add to `.env`:
   ```
   MSG4wrdIO_EXPOSE_DEMO_ROUTES=true
   ```
4. If you point at the sandbox during development, set:
   ```
   MSG4wrdIO_DEV_MODE=true
   ```
5. If you were calling `MSG4wrd::chechNumberCountryCode(...)` directly, rename it to `checkNumberCountryCode`.
6. If you were sending local-format PH numbers (`09171234567`), convert them to international format (`+639171234567` or `639171234567`).

## 📋 Supported environment variables

| Env variable | Default | Purpose |
| --- | --- | --- |
| `MSG4wrdIO_TOKEN` | *(empty)* | Bearer token from [MSG4wrd.io](https://www.msg4wrd.io/). |
| `MSG4wrdIO_DOMAIN` | `https://api.msg4wrd.io` | Live (production) API base URL. |
| `MSG4wrdIO_DEVELOPER` | `https://staging.msg4wrd.io` | Sandbox API base URL, used when `dev_mode=true`. |
| `MSG4wrdIO_DEV_MODE` | `false` | When `true`, requests go to the sandbox URL; when `false`, they go to live. |
| `MSG4wrdIO_DEFAULT_COUNTRY` | `PH` | Default country if `$options["country"]` is omitted. |
| `MSG4wrdIO_EXPOSE_DEMO_ROUTES` | `false` | Opt-in to register the unauthenticated `/msg4wrd*` demo routes. |

---

**Suggested release title:** `v2.0.0 — Laravel 8–12 support, sandbox/live switch, security & validation fixes`

**Suggested tag:** `v2.0.0` (the breaking changes and renamed helper warrant a major bump from the prior tag).
