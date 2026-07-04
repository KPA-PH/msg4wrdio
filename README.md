## About MSG4wrd
MSG4wrd is an SMS Gateway and Message Forwarder API formerly known as PTXT4wrd.

From 2005 to 2012, in the Philippines, some networks only allowed text messaging within the same network. Promos were available for unlimited texting to the same network, such as SMART to SMART or GLOBE to GLOBE only.

To address this issue, PTXT4wrd was invented, which allows users to send messages to other networks by forwarding their texts from their own network.

Example command for sending a message to other networks:

> PTXT{space}OtherNetworkNumber{space}YourMessage then send to Gateway.

> PTXT 09171234567 Hello world! Then, send to gateway number.

Gateways - SMART / GLOBE / SUN
If you are smart, you will use SMART Gateway, identical to the other networks.

## Requirements

- PHP `^8.1`
- Laravel `8.x` – `13.x`

## Installation

> composer require kpaph/msg4wrdio

The service provider is auto-discovered by Laravel — no manual registration in `config/app.php` is required.

Publish the config file (`config/msg4wrdio.php`):

> php artisan vendor:publish --tag=msg4wrdio-config

Add your token to `.env`. Get a token at [MSG4wrd.io](https://www.msg4wrd.io/):

```
MSG4wrdIO_TOKEN=YOUR-TOKEN-HERE
```

### Demo routes (optional, disabled by default)

The package ships with demo routes (`/msg4wrd`, `/msg4wrd/send`, `/msg4wrd/send-with-options`) for smoke-testing. They are **unauthenticated** and can trigger SMS sends billed to your token, so they are disabled by default. To enable them in local environments only:

```
MSG4wrdIO_EXPOSE_DEMO_ROUTES=true
```

With the demo routes enabled you can check the install:

> http://localhost:8000/msg4wrd

And trigger a test send:

> http://localhost:8000/msg4wrd/send?number=63XXXXXXXXXX

Note: Only `+63` (Philippines mobile) and `+1` (US / Canada) numbers are accepted. A leading `+` and common separators (spaces, dashes, dots, parentheses) are stripped automatically. Examples of valid input: `+63 917 123 4567`, `639171234567`, `+1 (202) 664-5435`, `12026645435`.

## Usage

Create a controller, e.g. `SMSController`:

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KPAPH\MSG4wrdIO\Enums\SenderName;
use KPAPH\MSG4wrdIO\Enums\Country;
use KPAPH\MSG4wrdIO\Services\MSG4wrd;

class SMSController extends Controller
{
    // $options = [
    //     "sendername" => SenderName::Default | SenderName::MSG4wrd | "YourBrandID",
    //     "priority"   => 0 | 1,
    //     "country"    => Country::PH | Country::US,
    // ]
    //
    // sendername => Default     = Typical Number / SIM-based / whatever is available
    // sendername => MSG4wrd     = Uses the MSG4wrd sender name (charges more)
    // sendername => "BRANDID"   = Your own brand ID, e.g. "GOOGLESMS", "YAHOOMSG"
    //
    // priority => 0 = Normal
    // priority => 1 = High (charges more)
    //
    // country => Country::PH = Philippines only
    // country => Country::US = US, Canada, and Philippines (charges more)

    public function SMSSendNormal()
    {
        return MSG4wrd::Send("US-PH-Number-Here", "Your-Message-Here");
    }

    public function SMSSendWithOptions()
    {
        $options = [
            "sendername" => SenderName::Default,
            "priority"   => 0,
            "country"    => Country::PH,
        ];

        return MSG4wrd::Send("US-PH-Number-Here", "Your-Message-Here", $options);
    }
}
```

## Configuration reference

| Env variable                     | Default                          | Purpose                                                                          |
| -------------------------------- | -------------------------------- | -------------------------------------------------------------------------------- |
| `MSG4wrdIO_TOKEN`                | *(empty)*                        | Bearer token from [MSG4wrd.io](https://www.msg4wrd.io/).                     |
| `MSG4wrdIO_DOMAIN`               | `https://api.msg4wrd.io`         | Live (production) API base URL.                                                  |
| `MSG4wrdIO_DEVELOPER`            | `https://staging.msg4wrd.io`     | Sandbox / staging API base URL, used when `dev_mode=true`.                       |
| `MSG4wrdIO_DEV_MODE`             | `false`                          | When `true`, requests go to the sandbox URL; when `false`, they go to live.      |
| `MSG4wrdIO_DEFAULT_COUNTRY`      | `PH`                             | Default country if `$options["country"]` is omitted.                             |
| `MSG4wrdIO_EXPOSE_DEMO_ROUTES`   | `false`                          | Opt-in to register the unauthenticated `/msg4wrd*` demo routes.                  |

## Changelog

### v2026.7.1 — Laravel 13 support

- **Laravel 13 support.** `illuminate/support` now allows `^13.0`, so the package installs cleanly on Laravel 13. Verified against `laravel/framework v13.18.1` on PHP 8.4. Existing Laravel 8–12 apps are unaffected.
- **No code or config changes.** This is a dependency-constraint-only release — nothing in your app needs to change. Just bump to `v2026.7.1` (see [Upgrading](#upgrading)).

### Fixes

- **Country option now actually works.** `checkCountry()` was comparing the `Country` int-backed enum to the string `"US"`, so `Country::US` silently fell through to `Country::PH`. The helper now accepts a `Country` enum, an int matching the enum value, or the string `"US"`/`"PH"`.
- **Number validation.** Renamed `chechNumberCountryCode` → `checkNumberCountryCode` and tightened the rules: only `+63` (PH mobile) and `+1` (US/CA) numbers are accepted. PH numbers must start with `9` after the country code; NANP numbers must have valid area + exchange codes (2-9). A leading `+` and common separators (space, dash, dot, parentheses) are stripped automatically. New helpers `normalizeNumber()` and `detectCountry()` are exposed alongside `checkNumberCountryCode()`.
- **`checkCountry()` aliases.** Accepts a `Country` enum, the int value, or string aliases — `PH`/`PHL`/`PHILIPPINES` resolve to `Country::PH`, and `US`/`USA`/`CA`/`CAN`/`CANADA` resolve to `Country::US`.
- **HTTP client.** Removed dead Guzzle 3 exception imports, removed the brittle `(int)phpversion() < 8` cURL fallback, fixed the stray space in `Authorization: ' Bearer '`, added a 30s timeout, and any 2xx response is now treated as success. Non-2xx responses surface the upstream body and status instead of being collapsed to a generic `Sending failed.` message.
- **Live / sandbox switch.** The config now exposes separate `live` and `sandbox` URLs. Set `MSG4wrdIO_DEV_MODE=true` to route requests to the sandbox (`MSG4wrdIO_DEVELOPER`); leave it `false` for the live URL (`MSG4wrdIO_DOMAIN`).
- **Service provider.** Moved `mergeConfigFrom()` out of `boot()` into `register()` where it belongs, removed the eager `app->make(SampleController::class)` call from `register()` (controllers should be resolved by the router), and the config publish is now gated behind `runningInConsole()` and tagged as `msg4wrdio-config`.
- **`Send()` ergonomics.** `$options` now merges over defaults, so you can pass just the keys you care about. `sendername` accepts either a `SenderName` enum or a custom brand-ID string. `priority` is cast to `int`. A leading `+` is stripped from the mobile before sending.

### Security

- **Demo routes are off by default.** `/msg4wrd`, `/msg4wrd/send`, and `/msg4wrd/send-with-options` were previously auto-registered on every consuming app — anyone who could reach the app could trigger SMS sends billed to your token. They are now gated behind `MSG4wrdIO_EXPOSE_DEMO_ROUTES=true`. **This is a breaking change** if you relied on those routes — set the env var to re-enable them.
- Removed the placeholder `[SECRET-TOKEN]` default from `config/msg4wrdio.php`; the token now defaults to an empty string.
- Removed tracked `.DS_Store` files and added a `.gitignore`.

### Packaging

- **Package auto-discovery.** Added `extra.laravel.providers` to `composer.json`. You no longer need to manually add `MSG4wrdIOServiceProvider::class` to `config/app.php`.
- **Explicit constraints.** `composer.json` now declares `php: ^8.1` and `illuminate/support: ^8.0|^9.0|^10.0|^11.0|^12.0|^13.0`, so Composer can resolve the right Laravel version on install.
- Switched `minimum-stability` to `stable` with `prefer-stable: true`.

### Upgrading

**Upgrading to Laravel 13?** Just update the package — no app changes needed:

```
composer require kpaph/msg4wrdio:^2026.7
```

If you are upgrading from a much earlier version:

1. Remove the manual `KPAPH\MSG4wrdIO\MSG4wrdIOServiceProvider::class` entry from `config/app.php` — auto-discovery handles it now.
2. If you relied on the `/msg4wrd*` demo routes, add `MSG4wrdIO_EXPOSE_DEMO_ROUTES=true` to your `.env` (recommended: local environments only).
3. Re-publish the config: `php artisan vendor:publish --tag=msg4wrdio-config --force`.
4. If you called `MSG4wrd::chechNumberCountryCode(...)` directly, rename it to `checkNumberCountryCode`.

