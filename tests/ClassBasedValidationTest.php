<?php

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;

it('validates a valid phone number for a given country code', function () {
    expect(validatePhone('+4722334455', new PhoneNumberRule('NO'))->passes())->toBeTrue();
});

it('invalidates an invalid phone number for a given country code', function () {
    $validator = validatePhone('+47XXXXXX', new PhoneNumberRule('NO'));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid phone number for Norway.');
});

it('validates a phone number in international format without a country', function () {
    expect(validatePhone('+4722334455', [new PhoneNumberRule])->passes())->toBeTrue();
});

it('invalidates a phone number with an invalid country code', function () {
    $validator = validatePhone('+4722334455', new PhoneNumberRule('XX'));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toBe('The phone has an invalid country code XX.');
});

it('allows a nullable phone number', function () {
    expect(validatePhone(null, ['nullable', new PhoneNumberRule])->passes())->toBeTrue();
});

it('invalidates an invalid phone format', function () {
    $validator = validatePhone('invalid-phone-number', new PhoneNumberRule);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toBe('The phone field must be a valid phone number.');
});

it('validates against multiple country codes', function () {
    expect(validatePhone('+4722334455', new PhoneNumberRule(['NO', 'GH']))->passes())->toBeTrue();
});

it('lists every country when none of multiple country codes match', function () {
    $validator = validatePhone('+4722334455', new PhoneNumberRule(['GH', 'NG']));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid phone number for Ghana, Nigeria.');
});
