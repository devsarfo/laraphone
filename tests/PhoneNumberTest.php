<?php

namespace DevSarfo\LaraPhone\Tests;

use DevSarfo\LaraPhone\Models\PhoneNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[Test]
    public function it_initializes_with_phone_and_country_codes()
    {
        $phone = '+4722334455';
        $countryCodes = ['NO', 'GH'];

        $phoneNumber = new PhoneNumber($phone, $countryCodes);

        $this->assertEquals($phone, $this->getClassProperty($phoneNumber, 'phone'));
        $this->assertEquals($countryCodes, $this->getClassProperty($phoneNumber, 'countryCodes'));
    }

    #[Test]
    public function it_checks_valid_local_phone_number_format_norway()
    {
        $phoneNumber = new PhoneNumber('22334455', 'NO');

        // Assert valid number is formatted in E164 format
        $this->assertEquals('+4722334455', $phoneNumber->formatE164());

        // Assert valid number is formatted in International format
        $this->assertEquals('+47 22 33 44 55', $phoneNumber->formatInternational());

        // Assert valid number is formatted in National format
        $this->assertEquals('22 33 44 55', $phoneNumber->formatNational());

        // Assert valid number is formatted in RFC3966 format
        $this->assertEquals('tel:+47-22-33-44-55', $phoneNumber->formatRFC3966());
    }

    #[Test]
    public function it_checks_valid_local_phone_number_format_ghana()
    {
        $phoneNumber = new PhoneNumber('0244123456', 'GH');

        // Assert valid number is formatted in E164 format
        $this->assertEquals('+233244123456', $phoneNumber->formatE164());

        // Assert valid number is formatted in International format
        $this->assertEquals('+233 24 412 3456', $phoneNumber->formatInternational());

        // Assert valid number is formatted in National format
        $this->assertEquals('024 412 3456', $phoneNumber->formatNational());

        // Assert valid number is formatted in RFC3966 format
        $this->assertEquals('tel:+233-24-412-3456', $phoneNumber->formatRFC3966());
    }

    #[Test]
    public function it_checks_phone_number_validity()
    {
        $validPhoneNumber = new PhoneNumber('+4722334455', 'NO');
        $invalidPhoneNumber = new PhoneNumber('+1234567890', 'NO');

        // Assert valid phone number returns true
        $this->assertTrue($validPhoneNumber->isValid());

        // Assert invalid phone number returns false
        $this->assertFalse($invalidPhoneNumber->isValid());
    }

    #[Test]
    public function it_checks_if_country_code_is_valid()
    {
        $phoneNumber = new PhoneNumber('+4722334455', ['NO', 'GH']);

        // Assert that the country code is valid for 'NO'
        $this->assertTrue($phoneNumber->checkCountryCode('NO'));

        // Assert that the country code is not valid for 'US'
        $this->assertFalse($phoneNumber->checkCountryCode('US'));
    }

    #[Test]
    public function it_serializes_and_unserializes_properly()
    {
        $phoneNumber = new PhoneNumber('+4722334455', 'NO');

        // Test serialization
        $serialized = serialize($phoneNumber);
        $unserialized = unserialize($serialized);

        $this->assertEquals($phoneNumber->formatE164(), $unserialized->formatE164());
    }

    #[Test]
    public function it_can_be_converted_to_string()
    {
        $phoneNumber = new PhoneNumber('+4722334455', 'NO');

        // Test __toString
        $this->assertEquals('+4722334455', (string) $phoneNumber);
    }

    // Utility method to get private/protected class properties for testing
    protected function getClassProperty(object $object, string $property)
    {
        $property = (new \ReflectionClass($object))->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
