<?php

namespace DevSarfo\LaraPhone\Rules;

use DevSarfo\LaraPhone\Utils\CountryUtil;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberRule implements ValidationRule
{
    protected array $countryCodes;

    public function __construct(string|array $countryCodes = [])
    {
        if (is_string($countryCodes)) {
            $countryCodes = [$countryCodes];
        }

        $this->countryCodes = array_map('strtoupper', $countryCodes);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $util = PhoneNumberUtil::getInstance();

        // Case 1: Validate as international if no country code is specified
        if (empty($this->countryCodes)) {
            try {
                $phone = $util->parse($value); // Should be in international format
                if (! $util->isValidNumber($phone) || ! str_starts_with($value, '+')) {
                    $fail($this->message($attribute));
                }

                return;
            } catch (\Throwable $e) {
                $fail($this->message($attribute));

                return;
            }
        }

        // Case 2: Try validating against provided country code(s)
        foreach ($this->countryCodes as $countryCode) {
            try {
                // Check if country code is valid
                if (! in_array($countryCode, CountryUtil::getCountryCodes())) {
                    $fail(trans('laraphone::validation.field_country_code_invalid', [
                        'attribute' => $attribute,
                        'country' => $countryCode,
                    ]));

                    return;
                }

                $phone = $util->parse($value, $countryCode);
                if ($util->isValidNumberForRegion($phone, $countryCode)) {
                    return;
                }
            } catch (NumberParseException) {
                // If parsing fails, try the next country code
            }
        }

        $countries = array_map(fn ($countryCode) => CountryUtil::name($countryCode), $this->countryCodes);
        $list = implode(', ', $countries);

        $fail(trans('laraphone::validation.field_phone_country_code', [
            'attribute' => $attribute,
            'country' => $list,
        ]));
    }

    protected function message(string $attribute): string
    {
        // Default message (Laravel validation message)
        return trans('validation.phone', ['attribute' => $attribute]);
    }

    public function setCountryCode(string|array $countryCodes): static
    {
        $countryCodes = is_array($countryCodes) ? $countryCodes : func_get_args();

        $this->countryCodes = array_merge($this->countryCodes, $countryCodes);

        return $this;
    }
}
