<?php

namespace DevSarfo\LaraPhone;

use DevSarfo\LaraPhone\Enums\PhoneFormat;
use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use libphonenumber\PhoneNumberFormat;

class LaraPhone
{
    /**
     * @param  string|array<int, string>  $countryCodes
     */
    public function make(?string $phone, string|array $countryCodes = []): PhoneNumber
    {
        return new PhoneNumber($phone, $countryCodes);
    }

    /**
     * @param  string|array<int, string>  $countryCodes
     */
    public function isValid(?string $phone, string|array $countryCodes = []): bool
    {
        return $this->make($phone, $countryCodes)->isValid();
    }

    /**
     * @param  string|array<int, string>  $countryCodes
     *
     * @throws PhoneNumberParseException
     */
    public function format(?string $phone, string|array $countryCodes = [], PhoneFormat|PhoneNumberFormat|int $format = PhoneFormat::E164): string
    {
        return $this->make($phone, $countryCodes)->format($format);
    }

    public function countryName(string $countryCode): string
    {
        return CountryUtil::name($countryCode);
    }

    /**
     * ISO 3166-1 alpha-2 codes supported by libphonenumber.
     *
     * @return array<int, string>
     */
    public function supportedCountries(): array
    {
        return CountryUtil::getCountryCodes();
    }
}
