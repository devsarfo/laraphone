<?php

namespace DevSarfo\LaraPhone\Tests;

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;

class ClassBasedValidationTest extends TestCase
{
    #[Test]
    public function it_validates_a_valid_phone_number_for_given_country_code()
    {
        // Valid Norwegian phone number
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule('NO'),  // Norway country code
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_an_invalid_phone_number_for_given_country_code()
    {
        // Invalid Norwegian phone number with wrong format
        $data = ['phone' => '+47XXXXXX'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule('NO'),  // Norway country code
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Norway.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_phone_number_in_international_format_without_country()
    {
        // Valid international format
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => [new PhoneNumberRule],  // No country code, so it uses international validation
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_phone_number_with_invalid_country_code()
    {
        // Invalid country code (XX)
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule('XX'),  // XX is an invalid country code
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone has an invalid country code XX.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_allows_nullable_phone_number()
    {
        // Phone number can be null
        $data = ['phone' => null];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone',  // 'nullable' allows null
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_invalid_phone_format()
    {
        // Invalid phone number format
        $data = ['phone' => 'invalid-phone-number'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule,  // No country code specified
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('validation.phone', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_multiple_country_codes()
    {
        // Valid phone number for multiple country codes
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule(['NO', 'GH']), // Norway and Ghana
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_phone_number_for_invalid_country_code_in_multiple_country_codes()
    {
        // Invalid phone number with wrong country code for both
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => new PhoneNumberRule(['GH', 'NG']), // Ghana and Nigeria
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Ghana, Nigeria.', $validator->errors()->first('phone'));
    }
}
