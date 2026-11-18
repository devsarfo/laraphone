<?php

use DevSarfo\LaraPhone\Enums\PhoneFormat;
use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Facades\LaraPhone;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use DevSarfo\LaraPhone\Utils\CountryUtil;

describe('default country', function () {
    beforeEach(fn () => config(['laraphone.default_country' => 'GH']));

    it('parses national numbers without a country', function () {
        expect(phone('0244123456')->formatE164())->toBe('+233244123456')
            ->and(phone('0244123456')->isValid())->toBeTrue()
            ->and(LaraPhone::isValid('0244123456'))->toBeTrue();
    });

    it('does not restrict numbers from other countries', function () {
        expect(phone('+4722334455')->isValid())->toBeTrue();
    });

    it('is ignored when a country is given', function () {
        expect(phone('0244123456', 'NO')->isValid())->toBeFalse();
    });

    it('names the default country in parse errors', function () {
        phone('abc')->formatE164();
    })->throws(PhoneNumberParseException::class, 'The phone number does not match GH.');

    it('is not used by the validation rule', function () {
        expect(validatePhone('0244123456', 'phone')->fails())->toBeTrue();
    });
});

it('requires a country for national numbers without a default', function () {
    config(['laraphone.default_country' => null]);

    expect(phone('0244123456')->isValid())->toBeFalse();
});

it('uses the default format for format() without an argument', function (string $config, string $expected) {
    config(['laraphone.default_format' => $config]);

    $phone = new PhoneNumber('0244123456', 'GH');

    expect($phone->format())->toBe($expected)
        ->and(LaraPhone::format('0244123456', 'GH'))->toBe($expected)
        ->and((string) $phone)->toBe('+233244123456')
        ->and(json_encode($phone))->toBe('"+233244123456"');
})->with([
    ['E164', '+233244123456'],
    ['national', '024 412 3456'],
    ['International', '+233 24 412 3456'],
    ['not-a-format', '+233244123456'],
]);

it('accepts a PhoneFormat in the config', function () {
    config(['laraphone.default_format' => PhoneFormat::NATIONAL]);

    expect(PhoneFormat::default())->toBe(PhoneFormat::NATIONAL);
});

describe('country names', function () {
    it('uses the application locale when intl is available', function () {
        app()->setLocale('es');

        expect(CountryUtil::name('DE'))->toBe('Alemania')
            ->and(validatePhone('abc', 'phone:DE')->errors()->first('phone'))->toContain('Alemania');
    })->skip(! extension_loaded('intl'), 'intl extension not loaded');

    it('accepts an explicit locale', function () {
        expect(CountryUtil::name('GH', 'fr'))->toBe('Ghana')
            ->and(CountryUtil::name('DE', 'fr'))->toBe('Allemagne');
    })->skip(! extension_loaded('intl'), 'intl extension not loaded');

    it('has a built-in English name for every supported region', function () {
        $missing = array_filter(CountryUtil::getCountryCodes(), fn ($code) => CountryUtil::builtinName($code) === null);

        expect($missing)->toBe([]);
    });

    it('falls back to the code for unknown regions', function () {
        expect(CountryUtil::name('qq'))->toBe('QQ');
    });
});
