# LaraPhone

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devsarfo/laraphone.svg?style=flat-square)](https://packagist.org/packages/devsarfo/laraphone)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/devsarfo/laraphone/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/devsarfo/laraphone/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/devsarfo/laraphone/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/devsarfo/laraphone/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/devsarfo/laraphone.svg?style=flat-square)](https://packagist.org/packages/devsarfo/laraphone)

Laravel Phone Number Package based on the [libphonenumber for PHP (Lite)](https://github.com/giggsey/libphonenumber-for-php-lite). It is a simple Laravel package for validating, formatting, and parsing phone numbers based on the PHP port of Google’s libphonenumber library, providing robust support for international phone number handling.

## Installation

You can install the package via composer using the following command. The command will install the latest applicable version of the package.

```bash
composer require devsarfo/laraphone
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laraphone-config"
```

Optionally, you can publish the translations with :

```bash
php artisan vendor:publish --tag="laraphone-translations"
```

## Usage

Use the `phone` keyword in your validation rules array or use the `DevSarfo\LaraPhone\Rules\PhoneNumberRule` rule class to define the rule in an expressive way.

To put constraints on the allowed originating countries, you can explicitly specify the allowed country codes.

```php
'phone' => 'phone:NO,GH',
// 'phone' => new PhoneNumberRule(['NO', 'GH'])
```

You can pass the country code from another field in the request. For example, to require a phone number to match the user's country.

```php
'country_code' => 'required',
'phone' => ['required', new PhoneNumberRule($this->country_code)],
```

The country codes should be [*ISO 3166-1 alpha-2 compliant*](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements).

### Validation Message

We provide validation for various cases out of the box. However, to enable the custom phone validation message, please add the following line to the `validation.php` language file in your `resources/lang/{language}/` directory (e.g., `resources/lang/en/validation.php`):

```php
'phone' => 'The :attribute field must be a valid phone number.',
```

## PhoneNumber Utility class

You can use the `DevSarfo\LaraPhone\Models\PhoneNumber` class to handle various phone number operations, such as formatting, validating, and manipulating phone numbers. It provides an easy-to-use interface for working with phone numbers in different formats, and can be safely referenced in views or when saving to the database.

```php
use DevSarfo\LaraPhone\Models\PhoneNumber;

(string) new PhoneNumber('+4722334455');    // +4722334455
(string) new PhoneNumber('22334455', 'NO'); // +4722334455
```

Alternatively you can use the `phone()` function found in the `Helper.php`. It returns a `DevSarfo\LaraPhone\Models\PhoneNumber` instance or the formatted string if `$format` was provided:

```php
phone('+233244123456');             // PhoneNumber instance
phone('0244123456', 'GH');          // PhoneNumber instance
phone('0244123456', 'GH', $format); // string
```

### Formatting
A PhoneNumber can be formatted in various ways:

```php
$phone = new PhoneNumber('0244123456', 'GH');

$phone->format($format);       // See libphonenumber\PhoneNumberFormat
$phone->formatE164();          // +233244123456
$phone->formatInternational(); // +233 24 412 3456
$phone->formatRFC3966();       // tel:+233-24-412-3456
$phone->formatNational();      // 024 412 3456
```

### Database

Store phone numbers in the **E.164** format in your database. This format globally uniquely identifies a phone number, ensuring consistency and simplifying validation.

For example:
- **User input**: `0244123456` (GH number)
- **Database storage**: `+233244123456`

### Why E.164 Format?

- **Consistency**: E.164 format uniquely identifies a phone number globally.
- **Flexibility**: You can format the number for display purposes (e.g., national or international formats).

### Example Workflow:

1. **User Input**: `0244123456`
2. **Format to E.164**: `+233244123456`
3. **Save in Database**: Store as `+233244123456`
4. **Display**: Format it as needed, e.g., `0244 123 456` for Ghana.

This ensures unique, globally recognizable phone numbers that can be displayed differently based on user needs.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Bernard Sarfo Twumasi](https://github.com/devsarfo)
- [Joshua Gigg - libphonenumber for PHP (Lite)](https://github.com/giggsey/libphonenumber-for-php-lite)
- [Google - libphonenumber](https://github.com/google/libphonenumber)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
