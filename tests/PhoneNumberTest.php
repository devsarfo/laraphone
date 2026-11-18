<?php

use DevSarfo\LaraPhone\Models\PhoneNumber;

it('stores the phone and country codes', function () {
    $phoneNumber = new PhoneNumber('+4722334455', ['NO', 'GH']);

    expect((new ReflectionProperty($phoneNumber, 'phone'))->getValue($phoneNumber))->toBe('+4722334455')
        ->and((new ReflectionProperty($phoneNumber, 'countryCodes'))->getValue($phoneNumber))->toBe(['NO', 'GH']);
});

it('formats a national number', function (string $phone, string $country, array $expected) {
    $phoneNumber = new PhoneNumber($phone, $country);

    expect([
        $phoneNumber->formatE164(),
        $phoneNumber->formatInternational(),
        $phoneNumber->formatNational(),
        $phoneNumber->formatRFC3966(),
    ])->toBe($expected);
})->with([
    'Norway' => ['22334455', 'NO', ['+4722334455', '+47 22 33 44 55', '22 33 44 55', 'tel:+47-22-33-44-55']],
    'Ghana' => ['0244123456', 'GH', ['+233244123456', '+233 24 412 3456', '024 412 3456', 'tel:+233-24-412-3456']],
]);

it('checks validity', function () {
    expect((new PhoneNumber('+4722334455', 'NO'))->isValid())->toBeTrue()
        ->and((new PhoneNumber('+1234567890', 'NO'))->isValid())->toBeFalse();
});

it('checks the country code', function () {
    $phoneNumber = new PhoneNumber('+4722334455', ['NO', 'GH']);

    expect($phoneNumber->checkCountryCode('NO'))->toBeTrue()
        ->and($phoneNumber->checkCountryCode('US'))->toBeFalse();
});

it('round-trips through serialization', function () {
    $phoneNumber = new PhoneNumber('+4722334455', 'NO');

    expect(unserialize(serialize($phoneNumber))->formatE164())->toBe($phoneNumber->formatE164());
});

it('casts to an E.164 string', function () {
    expect((string) new PhoneNumber('+4722334455', 'NO'))->toBe('+4722334455');
});
