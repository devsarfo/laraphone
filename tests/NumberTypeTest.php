<?php

use DevSarfo\LaraPhone\Enums\PhoneType;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberType;

it('maps every libphonenumber type', function () {
    foreach (PhoneNumberType::cases() as $type) {
        expect(PhoneType::fromLibPhoneNumber($type)->name)->toBe($type->name);
    }
});

describe('PhoneNumber', function () {
    it('reports the number type', function (string $phone, ?string $country, ?PhoneType $type) {
        expect((new PhoneNumber($phone, $country ?? []))->getType())->toBe($type);
    })->with([
        'GH mobile' => ['0244123456', 'GH', PhoneType::MOBILE],
        'GH fixed line' => ['0302123456', 'GH', PhoneType::FIXED_LINE],
        'GB toll free' => ['0800123456', 'GB', PhoneType::TOLL_FREE],
        'US either' => ['+16502530000', null, PhoneType::FIXED_LINE_OR_MOBILE],
        'unparseable' => ['abc', 'GH', null],
    ]);

    it('checks types, treating fixed_line_or_mobile as both', function () {
        $mobile = new PhoneNumber('0244123456', 'GH');
        $us = new PhoneNumber('+16502530000');

        expect($mobile->isOfType('mobile'))->toBeTrue()
            ->and($mobile->isOfType(PhoneType::FIXED_LINE))->toBeFalse()
            ->and($mobile->isOfType('fixed_line', 'MOBILE'))->toBeTrue()
            ->and($us->isOfType('mobile'))->toBeTrue()
            ->and($us->isOfType('fixed_line'))->toBeTrue()
            ->and($us->isOfType('toll_free'))->toBeFalse()
            ->and((new PhoneNumber('abc', 'GH'))->isOfType('mobile'))->toBeFalse();
    });

    it('rejects unknown type names', function () {
        (new PhoneNumber('0244123456', 'GH'))->isOfType('cellular');
    })->throws(InvalidArgumentException::class, 'Unknown phone number type [cellular].');
});

describe('validation', function () {
    it('accepts numbers of the requested type', function (string $phone, string|PhoneNumberRule $rule) {
        expect(validatePhone($phone, $rule)->passes())->toBeTrue();
    })->with([
        'string rule' => ['0244123456', 'phone:GH,mobile'],
        'type first' => ['0244123456', 'phone:mobile,GH'],
        'several types' => ['0302123456', 'phone:GH,mobile,fixed_line'],
        'no country' => ['+447400123456', 'phone:mobile'],
        'US either' => ['+16502530000', 'phone:US,mobile'],
        'fluent mobile()' => ['0244123456', fn () => (new PhoneNumberRule('GH'))->mobile()],
        'fluent type()' => ['0800123456', fn () => (new PhoneNumberRule('GB'))->type(PhoneType::TOLL_FREE)],
        'constructor' => ['0244123456', fn () => new PhoneNumberRule(['GH', 'mobile'])],
        'Rule::phone()' => ['0244123456', fn () => Rule::phone('GH', 'mobile')],
    ]);

    it('rejects numbers of another type with a type message', function (string $phone, string|PhoneNumberRule $rule, string $message) {
        expect(validatePhone($phone, $rule)->errors()->get('phone'))->toBe([$message]);
    })->with([
        'string rule' => ['0302123456', 'phone:GH,mobile', 'The phone must be a mobile number.'],
        'class rule' => ['0302123456', fn () => (new PhoneNumberRule('GH'))->mobile(), 'The phone must be a mobile number.'],
        'several types' => ['0800123456', 'phone:GB,mobile,fixed_line', 'The phone must be a mobile or fixed line number.'],
    ]);

    it('still reports an invalid number before its type', function () {
        expect(validatePhone('abc', 'phone:GH,mobile')->errors()->first('phone'))
            ->toBe('The phone must be a valid phone number for Ghana.');
    });

    it('supports :type in custom messages', function () {
        $validator = Validator::make(
            ['phone' => '0302123456'],
            ['phone' => 'phone:GH,mobile'],
            ['phone.phone' => 'Need a :country :type number.'],
        );

        expect($validator->errors()->first('phone'))->toBe('Need a Ghana mobile number.');
    });

    it('rejects unknown types in the fluent API', function () {
        (new PhoneNumberRule('GH'))->type('cellular');
    })->throws(InvalidArgumentException::class);
});
