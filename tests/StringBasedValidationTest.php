<?php

use DevSarfo\LaraPhone\Exceptions\CountryCodeException;
use DevSarfo\LaraPhone\Utils\CountryUtil;

it('passes valid numbers', function (?string $phone, string $rule) {
    expect(validatePhone($phone, $rule)->passes())->toBeTrue();
})->with([
    'international, no country' => ['+4722334455', 'phone'],
    'international, Norway' => ['+4722334455', 'phone:NO'],
    'international, Ghana' => ['+233244123456', 'phone:GH'],
    'another Norwegian number' => ['+4722222222', 'phone'],
    'null when nullable' => [null, 'nullable|phone'],
    'present when nullable' => ['+4722334455', 'nullable|phone'],
    'national format with country' => ['0244123456', 'nullable|phone:GH'],
]);

it('fails invalid numbers with a specific message', function (string $phone, string $rule, string $message) {
    $validator = validatePhone($phone, $rule);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toBe($message);
})->with([
    'valid number, wrong country' => ['+4722334455', 'phone:GH', 'The phone must be a valid phone number for Ghana.'],
    'unsupported country code' => ['+4722334455', 'phone:XX', 'The phone has an invalid country code XX.'],
    'bad calling code, Norway' => ['+XX22334455', 'phone:NO', 'The phone must be a valid phone number for Norway.'],
    'bad calling code, Ghana' => ['+XXX244123456', 'phone:GH', 'The phone must be a valid phone number for Ghana.'],
    'invalid characters' => ['+47@22334455', 'nullable|phone', 'The phone field must be a valid phone number.'],
    'too short national number' => ['024412345', 'nullable|phone:GH', 'The phone must be a valid phone number for Ghana.'],
]);

describe('CountryUtil::parse', function () {
    it('parses a single country code', function () {
        expect(CountryUtil::parse('NO'))->toBe('NO');
    });

    it('parses multiple country codes', function () {
        expect(CountryUtil::parse(['NO', 'GH']))->toBe(['NO', 'GH']);
    });

    it('throws for an unsupported country code', function () {
        CountryUtil::parse('XX');
    })->throws(CountryCodeException::class, 'The country code XX is invalid.');
});

it('builds the CountryCodeException message', function () {
    $exception = new CountryCodeException('zz');

    expect($exception->getMessage())->toBe('The country code ZZ is invalid.')
        ->and($exception->getCountryCode())->toBe('ZZ');
});
