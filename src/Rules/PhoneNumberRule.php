<?php

namespace DevSarfo\LaraPhone\Rules;

use Closure;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Stringable;

class PhoneNumberRule implements ValidationRule
{
    /**
     * @var array<int, string>
     */
    protected array $countryCodes;

    /**
     * @param  string|array<int, string|null>|null  $countryCodes
     */
    public function __construct(string|array|null $countryCodes = [])
    {
        $this->countryCodes = static::normalizeCountryCodes($countryCodes);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $failure = $this->failure($value);

        if ($failure !== null) {
            // ":attribute" is left in place so the validator can substitute
            // the displayable (custom) attribute name.
            $fail(trans($failure['key'], $failure['replace']));
        }
    }

    /**
     * Determine why the given value fails, or null when it passes.
     *
     * @return array{key: string, replace: array<string, string>}|null
     */
    public function failure(mixed $value): ?array
    {
        if (is_null($value) || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_int($value) || $value instanceof Stringable) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return $this->genericFailure();
        }

        $util = PhoneNumberUtil::getInstance();

        // Validate as international if no country code is specified
        if (empty($this->countryCodes)) {
            try {
                $phone = $util->parse($value);

                if (str_starts_with(trim($value), '+') && $util->isValidNumber($phone)) {
                    return null;
                }
            } catch (NumberParseException) {
                // fall through
            }

            return $this->genericFailure();
        }

        // Reject the configuration before checking the number, so the result
        // does not depend on the order of the country codes.
        foreach ($this->countryCodes as $countryCode) {
            if (! CountryUtil::isValid($countryCode)) {
                return [
                    'key' => 'laraphone::validation.field_country_code_invalid',
                    'replace' => ['country' => $countryCode],
                ];
            }
        }

        foreach ($this->countryCodes as $countryCode) {
            try {
                if ($util->isValidNumberForRegion($util->parse($value, $countryCode), $countryCode)) {
                    return null;
                }
            } catch (NumberParseException) {
                // try the next country code
            }
        }

        return [
            'key' => 'laraphone::validation.field_phone_country_code',
            'replace' => ['country' => $this->countryNames()],
        ];
    }

    /**
     * @param  string|array<int, string|null>  $countryCodes
     */
    public function setCountryCode(string|array $countryCodes): static
    {
        $countryCodes = is_array($countryCodes) ? $countryCodes : func_get_args();

        $this->countryCodes = static::normalizeCountryCodes(
            array_merge($this->countryCodes, $countryCodes)
        );

        return $this;
    }

    public function countryNames(): string
    {
        return implode(', ', array_map(fn ($countryCode) => CountryUtil::name($countryCode), $this->countryCodes));
    }

    /**
     * @return array{key: string, replace: array<string, string>}
     */
    protected function genericFailure(): array
    {
        // Prefer the application's own "validation.phone" line when defined.
        return [
            'key' => trans()->has('validation.phone') ? 'validation.phone' : 'laraphone::validation.phone',
            'replace' => [],
        ];
    }

    /**
     * @param  string|array<int, mixed>|null  $countryCodes
     * @return array<int, string>
     */
    protected static function normalizeCountryCodes(string|array|null $countryCodes): array
    {
        $codes = array_filter(Arr::wrap($countryCodes), fn ($code) => is_string($code) && trim($code) !== '');

        return array_values(array_unique(array_map(fn ($code) => strtoupper(trim($code)), $codes)));
    }
}
