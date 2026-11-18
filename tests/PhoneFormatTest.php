<?php

use DevSarfo\LaraPhone\Enums\PhoneFormat;
use DevSarfo\LaraPhone\Exceptions\CountryCodeException;
use DevSarfo\LaraPhone\Exceptions\LaraPhoneException;
use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Facades\LaraPhone;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use libphonenumber\PhoneNumberFormat;

it('mirrors every libphonenumber format', function () {
    expect(array_map(fn ($case) => [$case->name, $case->value], PhoneFormat::cases()))
        ->toBe(array_map(fn ($case) => [$case->name, $case->value], PhoneNumberFormat::cases()));
});

it('formats the same with PhoneFormat, PhoneNumberFormat and int', function (PhoneFormat $format, string $expected) {
    $phone = new PhoneNumber('0244123456', 'GH');

    expect($phone->format($format))->toBe($expected)
        ->and($phone->format($format->toLibPhoneNumber()))->toBe($expected)
        ->and($phone->format($format->value))->toBe($expected)
        ->and(phone('0244123456', 'GH', $format))->toBe($expected)
        ->and(LaraPhone::format('0244123456', 'GH', $format))->toBe($expected);
})->with([
    [PhoneFormat::E164, '+233244123456'],
    [PhoneFormat::INTERNATIONAL, '+233 24 412 3456'],
    [PhoneFormat::NATIONAL, '024 412 3456'],
    [PhoneFormat::RFC3966, 'tel:+233-24-412-3456'],
]);

it('defaults to E.164', function () {
    expect((new PhoneNumber('0244123456', 'GH'))->format())->toBe('+233244123456');
});

it('rejects an unknown integer format', function () {
    (new PhoneNumber('0244123456', 'GH'))->format(99);
})->throws(ValueError::class);

it('lets callers catch every package exception through LaraPhoneException', function (Closure $throw, string $class) {
    try {
        $throw();
        $this->fail('Expected exception');
    } catch (LaraPhoneException $e) {
        expect($e)->toBeInstanceOf($class);
    }
})->with([
    'parse failure' => [fn () => phone('abc', 'GH')->formatE164(), PhoneNumberParseException::class],
    'unsupported country' => [fn () => phone('0244123456', 'XX')->formatE164(), CountryCodeException::class],
]);
