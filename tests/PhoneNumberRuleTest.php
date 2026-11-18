<?php

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;

/**
 * Run the rule directly and collect the messages it fails with.
 *
 * @return array<int, string>
 */
function ruleMessages(PhoneNumberRule $rule, mixed $value): array
{
    $messages = [];

    $rule->validate('phone', $value, function (string $message) use (&$messages) {
        $messages[] = $message;
    });

    return $messages;
}

function countryCodesOf(PhoneNumberRule $rule): array
{
    return (new ReflectionProperty($rule, 'countryCodes'))->getValue($rule);
}

describe('country codes', function () {
    it('defaults to none', function () {
        expect(countryCodesOf(new PhoneNumberRule))->toBe([]);
    });

    it('sets a single country code', function () {
        expect(countryCodesOf((new PhoneNumberRule)->setCountryCode('NO')))->toBe(['NO']);
    });

    it('sets multiple country codes as arguments', function () {
        expect(countryCodesOf((new PhoneNumberRule)->setCountryCode('NO', 'GH')))->toBe(['NO', 'GH']);
    });

    it('sets country codes from an array', function () {
        expect(countryCodesOf((new PhoneNumberRule)->setCountryCode(['NO', 'GH'])))->toBe(['NO', 'GH']);
    });
});

describe('direct validation', function () {
    // Messages keep ":attribute" so the validator can substitute the display name.

    it('requires an international number when no country is given', function () {
        expect(ruleMessages(new PhoneNumberRule, '+4722334455'))->toBe([])
            ->and(ruleMessages(new PhoneNumberRule, '4722334455'))->toBe(['The :attribute field must be a valid phone number.']);
    });

    it('validates against a country code', function () {
        $rule = (new PhoneNumberRule)->setCountryCode('GH');

        expect(ruleMessages($rule, '+233244123456'))->toBe([])
            ->and(ruleMessages($rule, '+23324412345'))->toBe(['The :attribute must be a valid phone number for Ghana.'])
            ->and(ruleMessages($rule, 'invalid_phone'))->toBe(['The :attribute must be a valid phone number for Ghana.']);
    });

    it('fails on an unsupported country code', function () {
        expect(ruleMessages((new PhoneNumberRule)->setCountryCode('XYZ'), '+1234567890'))
            ->toBe(['The :attribute has an invalid country code XYZ.']);
    });

    it('passes when any of several country codes match', function () {
        $rule = (new PhoneNumberRule)->setCountryCode('NO', 'GH');

        expect(ruleMessages($rule, '+4722334455'))->toBe([])
            ->and(ruleMessages($rule, '+233244123456'))->toBe([]);
    });
});
