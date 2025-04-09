<?php

namespace DevSarfo\LaraPhone\Tests;

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class PhoneNumberRuleTest extends TestCase
{
    #[Test]
    public function it_defaults_to_empty_country_codes()
    {
        $rule = new PhoneNumberRule;
        $this->assertEquals([], $this->getClassProperty($rule, 'countryCodes'));
    }

    #[Test]
    public function it_sets_single_country_code()
    {
        $rule = (new PhoneNumberRule)->setCountryCode('NO');
        $this->assertEquals(['NO'], $this->getClassProperty($rule, 'countryCodes'));
    }

    #[Test]
    public function it_sets_multiple_country_codes()
    {
        $rule = (new PhoneNumberRule)->setCountryCode('NO', 'GH');
        $this->assertEquals(['NO', 'GH'], $this->getClassProperty($rule, 'countryCodes'));
    }

    #[Test]
    public function it_sets_country_codes_from_array()
    {
        $rule = (new PhoneNumberRule)->setCountryCode(['NO', 'GH']);
        $this->assertEquals(['NO', 'GH'], $this->getClassProperty($rule, 'countryCodes'));
    }

    #[Test]
    public function it_validates_international_phone_number()
    {
        $rule = new PhoneNumberRule;

        $fail = function ($message) {
            // You can assert error message here
            $this->assertEquals('validation.phone', $message);
        };

        // Test with a valid international number format
        $rule->validate('phone', '+1234567890', $fail);

        // Test with an invalid international number
        $rule->validate('phone', '1234567890', $fail);
    }

    #[Test]
    public function it_validates_phone_number_for_country_code()
    {
        $rule = (new PhoneNumberRule)->setCountryCode('GH');

        $fail = function ($message) {
            // You can assert error message here
            $this->assertEquals('The phone must be a valid phone number for Ghana.', $message);
        };

        // Test valid phone number for Ghana country code
        $rule->validate('phone', '+233244123456', $fail);

        // Test invalid phone number for Ghana country code
        $rule->validate('phone', '+23324412345', $fail);
    }

    #[Test]
    public function it_fails_on_invalid_country_code()
    {
        $rule = (new PhoneNumberRule)->setCountryCode('XYZ');

        $fail = function ($message) {
            // Assert the expected error message
            $this->assertEquals('The phone has an invalid country code XYZ.', $message);
        };

        // Test with an invalid country code
        $rule->validate('phone', '+1234567890', $fail);
    }

    #[Test]
    public function it_checks_valid_country_codes()
    {
        // Set country codes for validation
        $rule = (new PhoneNumberRule)->setCountryCode('NO', 'GH');

        // Define the failure callback
        $fail = function ($message) {
            // Assert that no failure message is triggered for valid phone numbers
            $this->assertEmpty($message, "Failed to validate phone number: $message");
        };

        // Test valid phone number for the NO country code
        $result = $rule->validate('phone', '+4722334455', $fail);
        $this->assertNull($result, 'Validation result should be null for valid phone number (NO)');

        // Test valid phone number for the GH country code
        $result = $rule->validate('phone', '+233244123456', $fail);
        $this->assertNull($result, 'Validation result should be null for valid phone number (GH)');
    }

    #[Test]
    public function it_provides_error_message_for_invalid_phone()
    {
        $rule = (new PhoneNumberRule)->setCountryCode('GH');

        $fail = function ($message) {
            $this->assertEquals('The phone must be a valid phone number for Ghana.', $message);
        };

        // Trigger failure
        $rule->validate('phone', 'invalid_phone', $fail);
    }

    protected function getClassProperty(object $object, string $property)
    {
        $property = (new ReflectionClass($object))->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
