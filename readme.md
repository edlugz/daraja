# Daraja

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

Laravel package for Safaricom **M-Pesa Daraja**: STK Push (C2B), B2C, B2B (BuyGoods/PayBill), Account Balance, Reversal, Transaction Status, **Tax remittance**, and **MNO Lookup** — with Laravel-12 friendly service bindings, logging, and callback helpers.

## Requirements

- PHP **8.3+**
- Laravel **12**
- `ext-openssl`
- `guzzlehttp/guzzle` **^7.8**

## Installation

```bash
composer require edlugz/daraja
```

### Quick start (one command)

```bash
php artisan daraja:install --force
```

This publishes the config and migrations (optional) and runs `php artisan migrate`.

### Manual publish (optional)

Publish everything from this provider:
```bash
php artisan vendor:publish --provider="EdLugz\Daraja\DarajaServiceProvider"
```

Or publish specific tags:
```bash
# Config
php artisan vendor:publish --provider="EdLugz\Daraja\DarajaServiceProvider" --tag=daraja-config

# Migrations
php artisan vendor:publish --provider="EdLugz\Daraja\DarajaServiceProvider" --tag=daraja-migrations
```

> Migrations are also auto-loaded from the package, so `php artisan migrate` will pick them up even without publishing.

## Environment

> **Note:** Consumer key/secret/shortcode can be injected dynamically per tenant via `ClientCredential`. Env vars below are optional defaults.

```dotenv
# Mode / endpoints
DARAJA_MODE= #uat|live
# Optional override (leave empty to auto-derive from mode)
DARAJA_BASE_URL=

# (Optional) Credentials if you prefer env-based defaults
DARAJA_CONSUMER_KEY=
DARAJA_CONSUMER_SECRET=
DARAJA_INITIATOR_NAME=
DARAJA_INITIATOR_PASSWORD=
DARAJA_PASS_KEY=
DARAJA_SHORTCODE=

# Default callback URLs (you may also pass per call)
DARAJA_STK_RESULT_URL=
DARAJA_BALANCE_RESULT_URL=
DARAJA_MOBILE_RESULT_URL=
DARAJA_TILL_RESULT_URL=
DARAJA_PAYBILL_RESULT_URL=
DARAJA_REVERSAL_RESULT_URL=
DARAJA_TRANSACTION_QUERY_RESULT_URL=
DARAJA_REVERSAL_QUERY_RESULT_URL=
DARAJA_FUNDS_TRANSFER_RESULT_URL=
DARAJA_TIMEOUT_URL=

# Logging
DARAJA_LOG_ENABLED=true
DARAJA_LOG_LEVEL=DEBUG


```

## Usage

### 1) Build credentials dynamically
```php
use EdLugz\Daraja\Data\ClientCredential;

$cred = new ClientCredential(
    accountId:          (string) $accountId,
    consumerKey:        (string) $consumerKey,
    consumerSecret:     (string) $consumerSecret,
    shortcode:          (string) $shortcode,
    initiator:          (string) $initiatorName,
    password:           (string) $initiatorPassword,
    passkey:            (string) $stkPasskey,
    use_b2c_validation: '0' // or '1'
);
```

### 2) Make a request

**STK Push (C2B)**
```php
use EdLugz\Daraja\Requests\C2B;

$c2b = new C2B($cred); // result URL defaults to config('daraja.stk_result_url')
$tx  = $c2b->send('0712345678', '150', 'INV-12345');
```

**B2C (Send to mobile)**
```php
use EdLugz\Daraja\Requests\B2C;

$b2c = new B2C($cred, route('daraja.mobile.result'));
$tx  = $b2c->pay('0712345678', 100);
```

**B2B (BuyGoods / PayBill)**
```php
use EdLugz\Daraja\Requests\B2B;

$b2b = new B2B($cred);
$buyGoods = $b2b->till(recipient: '123456', requester: '0712345678', amount: 100);
$paybill  = $b2b->paybill(recipient: '600000', requester: '0712345678', amount: 100, accountReference: 'ACC-1');
```

More flows: Balance, Reversal, Transaction Status, Tax remittance, and MNO Lookup are provided via their respective request classes.

Ensure these endpoints are publicly reachable and validate incoming payloads.

## Change log

Please see the [changelog](changelog.md) for recent changes.

## Contributing

See [contributing.md](contributing.md) for details and the todo list.

## Security

If you discover any security-related issues, please email **eddy.lugaye@gmail.com** instead of using the issue tracker.

## Credits

- [Eddy Lugaye][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/edlugz/daraja.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/edlugz/daraja.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/edlugz/daraja
[link-downloads]: https://packagist.org/packages/edlugz/daraja
[link-author]: https://github.com/edlugz
[link-contributors]: ../../contributors
