<?php

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Support\Facades\Validator;

it('reads the country from another field', function (array $data, string|PhoneNumberRule $rule, bool $passes) {
    expect(Validator::make($data, ['phone' => $rule])->passes())->toBe($passes);
})->with([
    'string rule, matches' => [['phone' => '0244123456', 'country' => 'GH'], 'phone:country', true],
    'string rule, lowercase value' => [['phone' => '0244123456', 'country' => 'gh'], 'phone:country', true],
    'string rule, wrong country' => [['phone' => '0244123456', 'country' => 'NO'], 'phone:country', false],
    'class rule' => [['phone' => '0244123456', 'country' => 'GH'], fn () => new PhoneNumberRule('country'), true],
    'class rule, wrong country' => [['phone' => '0244123456', 'country' => 'NO'], fn () => new PhoneNumberRule('country'), false],
    'nested field' => [['phone' => '0244123456', 'address' => ['country' => 'GH']], 'phone:address.country', true],
    'field plus fixed code' => [['phone' => '22334455', 'country' => 'GH'], 'phone:country,NO', true],
    'field holding several codes' => [['phone' => '22334455', 'countries' => ['GH', 'NO']], 'phone:countries', true],
    'empty field falls back to international' => [['phone' => '+4722334455', 'country' => null], 'phone:country', true],
    'empty field rejects national numbers' => [['phone' => '22334455', 'country' => ''], 'phone:country', false],
    'with a type' => [['phone' => '0302123456', 'country' => 'GH'], 'phone:country,mobile', false],
]);

it('names the country from the field in the message', function () {
    $errors = Validator::make(['phone' => '0244123456', 'country' => 'NO'], ['phone' => 'phone:country'])->errors();

    expect($errors->first('phone'))->toBe('The phone must be a valid phone number for Norway.');
});

it('reports an unsupported country code from the field', function () {
    $errors = Validator::make(['phone' => '0244123456', 'country' => 'zz'], ['phone' => 'phone:country'])->errors();

    expect($errors->first('phone'))->toBe('The phone has an invalid country code ZZ.');
});

it('prefers a country code over a field with the same name', function () {
    // "GH" is a supported country code, so it is not read as a field name.
    $data = ['phone' => '0244123456', 'GH' => 'NO'];

    expect(Validator::make($data, ['phone' => 'phone:GH'])->passes())->toBeTrue();
});

it('resolves wildcard fields per array item', function () {
    $data = ['contacts' => [
        ['phone' => '0244123456', 'country' => 'GH'],
        ['phone' => '22334455', 'country' => 'NO'],
        ['phone' => '22334455', 'country' => 'GH'],
    ]];

    $errors = Validator::make($data, ['contacts.*.phone' => 'phone:contacts.*.country'])->errors();

    expect($errors->keys())->toBe(['contacts.2.phone'])
        ->and($errors->first('contacts.2.phone'))->toBe('The contacts.2.phone must be a valid phone number for Ghana.');
});
