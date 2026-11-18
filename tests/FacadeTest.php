<?php

use DevSarfo\LaraPhone\Facades\LaraPhone;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use libphonenumber\PhoneNumberFormat;

it('makes a PhoneNumber', function () {
    expect(LaraPhone::make('0244123456', 'GH'))
        ->toBeInstanceOf(PhoneNumber::class)
        ->formatE164()->toBe('+233244123456');
});

it('checks validity', function () {
    expect(LaraPhone::isValid('0244123456', 'GH'))->toBeTrue()
        ->and(LaraPhone::isValid('abc', 'GH'))->toBeFalse()
        ->and(LaraPhone::isValid('0244123456', 'XX'))->toBeFalse();
});

it('formats numbers', function () {
    expect(LaraPhone::format('0244123456', 'GH'))->toBe('+233244123456')
        ->and(LaraPhone::format('0244123456', 'GH', PhoneNumberFormat::NATIONAL))->toBe('024 412 3456');
});

it('names countries', function () {
    expect(LaraPhone::countryName('gh'))->toBe('Ghana')
        ->and(LaraPhone::countryName('XK'))->toBe('Kosovo');
});

it('names every supported country', function () {
    $unnamed = array_filter(LaraPhone::supportedCountries(), fn ($code) => LaraPhone::countryName($code) === $code);

    expect($unnamed)->toBe([]);
});
