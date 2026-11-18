<?php

namespace DevSarfo\LaraPhone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \DevSarfo\LaraPhone\Models\PhoneNumber make(?string $phone, string|array<int, string> $countryCodes = [])
 * @method static bool isValid(?string $phone, string|array<int, string> $countryCodes = [])
 * @method static string format(?string $phone, string|array<int, string> $countryCodes = [], \libphonenumber\PhoneNumberFormat|int $format = \libphonenumber\PhoneNumberFormat::E164)
 * @method static string countryName(string $countryCode)
 * @method static array<int, string> supportedCountries()
 *
 * @see \DevSarfo\LaraPhone\LaraPhone
 */
class LaraPhone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DevSarfo\LaraPhone\LaraPhone::class;
    }
}
