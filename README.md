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

The country codes should be [*ISO 3166-1 alpha-2 compliant*](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements).

#### Country from another field

Name another input field instead of a country code to take the country from the request, e.g. to require the phone number to match the user's selected country:

```php
'country' => 'required',
'phone' => 'required|phone:country',
// 'phone' => ['required', new PhoneNumberRule('country')],

// Nested and array fields work too
'contacts.*.phone' => 'phone:contacts.*.country',
```

When the field is empty, the number must be in international format.

#### Number types

Add a number type to accept only mobile, fixed-line or other kinds of numbers:

```php
'phone' => 'phone:GH,mobile',
'phone' => 'phone:GH,mobile,fixed_line', // either
// 'phone' => (new PhoneNumberRule('GH'))->mobile(),
// 'phone' => (new PhoneNumberRule('GH'))->type(PhoneType::TOLL_FREE),
```

Available types are the values of `DevSarfo\LaraPhone\Enums\PhoneType`: `mobile`, `fixed_line`, `toll_free`, `premium_rate`, `shared_cost`, `voip`, `personal_number`, `pager`, `uan`, `voicemail` and more. In some countries (such as the US) mobile and fixed-line numbers can't be told apart; those numbers satisfy both `mobile` and `fixed_line`.

Each parameter is read as a country code first, then as a number type, then as a field name.

### Validation Message

Validation messages are provided out of the box, e.g. *"The phone must be a valid phone number for Ghana."* Publish the translations to change them.

You can also override the message the usual Laravel ways. Custom messages may use the `:country` and `:type` placeholders:

```php
// Per validator
Validator::make($data, ['phone' => 'phone:GH,NO'], [
    'phone.phone' => 'Enter a valid :country number.',
]);

// Application-wide, in lang/{locale}/validation.php
'phone' => 'The :attribute field must be a valid phone number.',
```

An application-wide `validation.phone` line only replaces the generic message (no country given). Country-specific messages such as *"…for Ghana."* still come from the package unless you set an inline or `validation.custom` message.

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
phone('0244123456', 'GH', PhoneFormat::NATIONAL); // string
```

### Facade

The `LaraPhone` facade offers the same operations without creating objects yourself:

```php
use DevSarfo\LaraPhone\Enums\PhoneFormat;
use DevSarfo\LaraPhone\Facades\LaraPhone;

LaraPhone::make('0244123456', 'GH');                          // PhoneNumber instance
LaraPhone::isValid('0244123456', 'GH');                       // true
LaraPhone::format('0244123456', 'GH');                        // +233244123456
LaraPhone::format('0244123456', 'GH', PhoneFormat::NATIONAL); // 024 412 3456
LaraPhone::countryName('GH');                                 // Ghana
LaraPhone::supportedCountries();                              // ['AC', 'AD', 'AE', ...]
```

### Formatting
A PhoneNumber can be formatted in various ways:

```php
use DevSarfo\LaraPhone\Enums\PhoneFormat;

$phone = new PhoneNumber('0244123456', 'GH');

$phone->format(PhoneFormat::NATIONAL); // 024 412 3456
$phone->formatE164();          // +233244123456
$phone->formatInternational(); // +233 24 412 3456
$phone->formatRFC3966();       // tel:+233-24-412-3456
$phone->formatNational();      // 024 412 3456
```

> Passing `libphonenumber\PhoneNumberFormat` still works but is deprecated and will be removed in 2.0. Use `DevSarfo\LaraPhone\Enums\PhoneFormat` instead.

For anything the package doesn't wrap, `$phone->toPhoneNumber()` returns the underlying `libphonenumber\PhoneNumber`.

### Number type

```php
use DevSarfo\LaraPhone\Enums\PhoneType;

$phone = new PhoneNumber('0244123456', 'GH');

$phone->getType();                 // PhoneType::MOBILE
$phone->isOfType('mobile');        // true
$phone->isOfType(PhoneType::FIXED_LINE, PhoneType::VOIP); // false
```

### Exceptions

Every exception the package throws implements `DevSarfo\LaraPhone\Exceptions\LaraPhoneException`:

```php
use DevSarfo\LaraPhone\Exceptions\LaraPhoneException;

try {
    $e164 = phone($input, 'GH')->formatE164();
} catch (LaraPhoneException $e) {
    // the number couldn't be parsed, or a country code is unsupported
}
```

### Eloquent cast

Cast a model attribute with `AsPhoneNumber` to store numbers in E.164 format and get a `PhoneNumber` back:

```php
use DevSarfo\LaraPhone\Casts\AsPhoneNumber;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'phone' => AsPhoneNumber::class.':GH',        // national numbers are Ghanaian
            // 'phone' => AsPhoneNumber::class.':country', // country from the "country" attribute
        ];
    }
}

$user->phone = '024 412 3456'; // stored as +233244123456
$user->phone->formatNational(); // 024 412 3456
$user->toArray()['phone'];      // +233244123456
```

Invalid numbers are stored exactly as given. Validate input with the `phone` rule before saving. When the country comes from another attribute, set that attribute first.

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

## Configuration

Publish the config file (`php artisan vendor:publish --tag="laraphone-config"`) to set:

```php
// config/laraphone.php
'default_country' => 'GH',  // parse national numbers without a country: phone('0244123456')
'default_format' => 'E164', // used by format() without an argument
```

`default_country` is only a parsing hint for `PhoneNumber`, `phone()`, the facade and the cast. It doesn't restrict valid countries, and the `phone` validation rule doesn't use it.

Country names in messages come from PHP's `intl` extension in the application's locale when it's installed (*"Alemania"* in Spanish), with built-in English names as the fallback.

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
