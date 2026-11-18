<?php

use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Facades\LaraPhone;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberFormat;

describe('PhoneNumber parse errors', function () {
    it('reports a country mismatch when country codes were given', function () {
        try {
            (new PhoneNumber('abc', 'gh'))->formatE164();
            $this->fail('Expected exception');
        } catch (PhoneNumberParseException $e) {
            expect($e->getMessage())->toBe('The phone number does not match GH.')
                ->and($e->getCountryCodes())->toBe(['gh'])
                ->and($e->getPhone())->toBe('abc');
        }
    });

    it('reports a missing country when none was given', function () {
        expect(fn () => (new PhoneNumber('0244123456'))->formatE164())
            ->toThrow(PhoneNumberParseException::class, 'The phone number requires a country code to be specified.');
    });
});

describe('PhoneNumber safety', function () {
    it('returns false from isValid for an unsupported country code', function () {
        expect((new PhoneNumber('0244123456', 'XX'))->isValid())->toBeFalse();
    });

    it('restricts isValid to the given countries, like the validation rule', function (string $phone, string|array $countries, bool $expected) {
        expect((new PhoneNumber($phone, $countries))->isValid())->toBe($expected)
            ->and(LaraPhone::isValid($phone, $countries))->toBe($expected)
            ->and(Validator::make(['p' => $phone], ['p' => new PhoneNumberRule($countries)])->passes())->toBe($expected);
    })->with([
        'international, allowed' => ['+4722334455', 'NO', true],
        'international, not allowed' => ['+4722334455', 'GH', false],
        'international, one of several' => ['+4722334455', ['GH', 'NO'], true],
        'national, allowed' => ['0244123456', 'GH', true],
        'shared +1 code, wrong country' => ['+16135550123', 'US', false],
        'shared +1 code, right country' => ['+16135550123', ['US', 'CA'], true],
        'unsupported country code' => ['+233244123456', 'XX', false],
    ]);

    it('serializes invalid numbers without throwing', function () {
        $restored = unserialize(serialize(new PhoneNumber('abc', 'GH')));

        expect((string) $restored)->toBe('abc');
    });

    it('keeps country codes across serialization', function () {
        $restored = unserialize(serialize(new PhoneNumber('0244123456', 'GH')));

        expect($restored->formatE164())->toBe('+233244123456');
    });

    it('unserializes payloads from the previous format', function () {
        $legacy = 'O:37:"DevSarfo\LaraPhone\Models\PhoneNumber":1:{s:5:"phone";s:13:"+233244123456";}';

        expect(unserialize($legacy)->formatNational())->toBe('024 412 3456');
    });

    it('json encodes valid numbers as E.164 and invalid ones as raw input', function () {
        expect(json_encode(['a' => new PhoneNumber('0244123456', 'GH'), 'b' => new PhoneNumber('abc')]))
            ->toBe('{"a":"+233244123456","b":"abc"}');
    });
});

describe('phone() helper', function () {
    it('accepts an integer format', function () {
        expect(phone('0244123456', 'GH', PhoneNumberFormat::INTERNATIONAL->value))->toBe('+233 24 412 3456')
            ->and(phone('0244123456', 'GH', PhoneNumberFormat::NATIONAL))->toBe('024 412 3456');
    });
});

describe('PhoneNumberRule input handling', function () {
    it('treats a null country code as international validation', function () {
        $validator = Validator::make(['phone' => '+233244123456'], ['phone' => new PhoneNumberRule(null)]);

        expect($validator->passes())->toBeTrue();
    });

    it('rejects array values', function (string|PhoneNumberRule $rule) {
        expect(Validator::make(['phone' => ['+233244123456']], ['phone' => $rule])->fails())->toBeTrue();
    })->with([
        'string rule' => 'phone',
        'class rule' => fn () => new PhoneNumberRule,
    ]);

    it('rejects integers without an international prefix', function () {
        expect(Validator::make(['phone' => 233244123456], ['phone' => 'phone'])->fails())->toBeTrue();
    });

    it('validates integers against a country', function () {
        expect(Validator::make(['phone' => 244123456], ['phone' => 'phone:GH'])->passes())->toBeTrue();
    });

    it('fails on an unsupported country code regardless of its position', function () {
        $validator = Validator::make(['phone' => '+233244123456'], ['phone' => 'phone:GH,XX']);

        expect($validator->errors()->first('phone'))->toBe('The phone has an invalid country code XX.');
    });

    it('normalizes lowercase and duplicate country codes', function () {
        expect((new PhoneNumberRule(['gh', 'GH', ' no ']))->countryNames())->toBe('Ghana, Norway');
    });

    it('supports Rule::phone() with an array argument', function () {
        expect(Validator::make(['phone' => '22334455'], ['phone' => Rule::phone(['GH', 'NO'])])->passes())->toBeTrue();
    });
});

describe('validation messages', function () {
    it('adds exactly one message per failure', function (string|PhoneNumberRule $rule, string $expected) {
        $errors = Validator::make(['phone' => 'abc'], ['phone' => $rule])->errors()->get('phone');

        expect($errors)->toBe([$expected]);
    })->with([
        'string, no country' => ['phone', 'The phone field must be a valid phone number.'],
        'string, country' => ['phone:GH', 'The phone must be a valid phone number for Ghana.'],
        'class, no country' => [fn () => new PhoneNumberRule, 'The phone field must be a valid phone number.'],
        'class, country' => [fn () => new PhoneNumberRule('GH'), 'The phone must be a valid phone number for Ghana.'],
    ]);

    it('uses displayable and custom attribute names', function (string|PhoneNumberRule $rule) {
        $data = ['mobile_no' => 'abc', 'work_phone' => 'abc'];
        $rules = ['mobile_no' => $rule, 'work_phone' => $rule];

        $errors = Validator::make($data, $rules, [], ['work_phone' => 'office number'])->errors();

        expect($errors->first('mobile_no'))->toBe('The mobile no must be a valid phone number for Ghana.')
            ->and($errors->first('work_phone'))->toBe('The office number must be a valid phone number for Ghana.');
    })->with([
        'string rule' => 'phone:GH',
        'class rule' => fn () => new PhoneNumberRule('GH'),
    ]);

    it('resolves the specific message for nested attributes', function () {
        $errors = Validator::make(
            ['contacts' => [['phone' => '0244123456'], ['phone' => 'abc']]],
            ['contacts.*.phone' => 'phone:GH'],
        )->errors();

        expect($errors->keys())->toBe(['contacts.1.phone'])
            ->and($errors->first('contacts.1.phone'))->toBe('The contacts.1.phone must be a valid phone number for Ghana.');
    });

    it('honours inline custom messages with a :country placeholder', function () {
        $validator = Validator::make(
            ['phone' => 'abc'],
            ['phone' => 'phone:GH,NO'],
            ['phone.phone' => ':attribute is not a :country number.'],
        );

        expect($validator->errors()->first('phone'))->toBe('phone is not a Ghana, Norway number.');
    });

    it('uses an application validation.phone line only for the generic message', function (string|PhoneNumberRule $generic, string|PhoneNumberRule $country, string|PhoneNumberRule $badCountry) {
        app('translator')->addLines(['validation.phone' => 'Bad :attribute.'], 'en');

        $first = fn ($rule) => Validator::make(['phone' => 'abc'], ['phone' => $rule])->errors()->get('phone');

        expect($first($generic))->toBe(['Bad phone.'])
            ->and($first($country))->toBe(['The phone must be a valid phone number for Ghana.'])
            ->and($first($badCountry))->toBe(['The phone has an invalid country code XX.']);
    })->with([
        'string rule' => ['phone', 'phone:GH', 'phone:XX'],
        'class rule' => [fn () => new PhoneNumberRule, fn () => new PhoneNumberRule('GH'), fn () => new PhoneNumberRule('XX')],
    ]);

    it('lets validation.custom messages override country-specific messages', function () {
        app('translator')->addLines([
            'validation.phone' => 'Bad :attribute.',
            'validation.custom.phone.phone' => 'Need a :country number for :attribute.',
        ], 'en');

        expect(Validator::make(['phone' => 'abc'], ['phone' => 'phone:GH'])->errors()->first('phone'))
            ->toBe('Need a Ghana number for phone.');
    });
});
