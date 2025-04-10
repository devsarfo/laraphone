<?php

namespace DevSarfo\LaraPhone\Tests;

use DevSarfo\LaraPhone\Exceptions\CountryCodeException;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;

class StringBasedValidationTest extends TestCase
{
    #[Test]
    public function it_validates_a_valid_phone_number_for_without_country()
    {
        // Norwegian number (valid)
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => 'phone',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_a_valid_phone_number_for_with_wrong_valid_country()
    {
        // Norwegian number (valid)
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => 'phone:GH',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Ghana.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_a_valid_phone_number_for_norway()
    {
        // Norwegian number (valid)
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => 'phone:NO',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_a_valid_phone_number_for_ghana()
    {
        // GH number (valid)
        $data = ['phone' => '+233244123456'];

        $validator = Validator::make($data, [
            'phone' => 'phone:GH',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_a_phone_number_for_invalid_country_code()
    {
        // Invalid country code test (XX)
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => 'phone:XX', // XX is an invalid country code
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone has an invalid country code XX.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_a_phone_number_in_international_format()
    {
        // Valid international phone number (Norwegian)
        $data = ['phone' => '+4722222222'];

        $validator = Validator::make($data, [
            'phone' => 'phone',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_a_phone_number_for_invalid_country_code_for_norway()
    {
        // Invalid Norwegian phone number with wrong country code
        $data = ['phone' => '+XX22334455'];

        $validator = Validator::make($data, [
            'phone' => 'phone:NO',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Norway.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_invalidates_a_phone_number_for_invalid_country_code_for_ghana()
    {
        // Invalid Norwegian phone number with wrong country code
        $data = ['phone' => '+XXX244123456'];

        $validator = Validator::make($data, [
            'phone' => 'phone:GH',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Ghana.', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_country_codes_in_parse_method()
    {
        // Test valid country code (NO)
        $validCountryCode = 'NO';
        $invalidCountryCode = 'XX';

        $result = CountryUtil::parse($validCountryCode);
        $this->assertEquals('NO', $result);

        // Test invalid country code (this should throw an exception)
        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('The country code XX is invalid.');

        CountryUtil::parse($invalidCountryCode);
    }

    #[Test]
    public function it_parses_multiple_country_codes()
    {
        // Parse multiple country codes (NO, GH)
        $validCountryCodes = ['NO', 'GH'];

        $result = CountryUtil::parse($validCountryCodes);
        $this->assertEquals(['NO', 'GH'], $result);
    }

    #[Test]
    public function it_throws_exception_for_invalid_country_code()
    {
        // Test invalid country code (ZZ)
        $invalidCountryCode = 'ZZ';

        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('The country code ZZ is invalid.');

        throw new CountryCodeException($invalidCountryCode);
    }

    #[Test]
    public function it_validates_phone_number_can_be_null()
    {
        // Valid case: phone number is null
        $data = ['phone' => null];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone',  // 'nullable' allows null, 'phone' validates the format when not null
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_phone_number_when_present_but_invalid()
    {
        // Invalid case: phone number is present but invalid
        $data = ['phone' => '+47@22334455'];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('validation.phone', $validator->errors()->first('phone'));
    }

    #[Test]
    public function it_validates_phone_number_when_present_and_valid()
    {
        // Invalid case: phone number is present but invalid
        $data = ['phone' => '+4722334455'];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_phone_number_when_using_national_format_and_country_code()
    {
        // Invalid case: phone number is present but invalid
        $data = ['phone' => '0244123456'];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone:GH',
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_invalidates_phone_with_valid_country_code_but_invalid_national_format()
    {
        $data = ['phone' => '024412345'];

        $validator = Validator::make($data, [
            'phone' => 'nullable|phone:GH',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('The phone must be a valid phone number for Ghana.', $validator->errors()->first('phone'));
    }
}
