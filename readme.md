# Daraja

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

```bash
composer require edlugz/daraja
```

## Publish Migration Files

```bash
php artisan vendor:publish --provider="EdLugz\Daraja\DarajaServiceProvider" --tag="migrations"
```

Fill in all the details you will be requiring for your application. Here are the env variables for quick copy paste.

```bash
DARAJA_STK_RESULT_URL=
DARAJA_BALANCE_RESULT_URL=
DARAJA_MOBILE_RESULT_URL=
DARAJA_TILL_RESULT_URL=
DARAJA_PAYBILL_RESULT_URL=
DARAJA_REVERSAL_RESULT_URL=
DARAJA_TRANSACTION_QUERY_MOBILE_RESULT_URL=
DARAJA_TRANSACTION_QUERY_TILL_RESULT_URL=
DARAJA_TRANSACTION_QUERY_PAYBILL_RESULT_URL=
DARAJA_TIMEOUT_URL=
DARAJA_BALANCE_URL=
```

## Usage

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.



## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email eddy.lugaye@gmail.com instead of using the issue tracker.

## Credits

- [Eddy Lugaye][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/edlugz/daraja.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/edlugz/daraja.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/edlugz/daraja/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/edlugz/daraja
[link-downloads]: https://packagist.org/packages/edlugz/daraja
[link-travis]: https://travis-ci.org/edlugz/daraja
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/edlugz
[link-contributors]: ../../contributors
