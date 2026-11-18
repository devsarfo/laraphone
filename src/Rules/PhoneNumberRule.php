<?php

namespace DevSarfo\LaraPhone\Rules;

use Closure;
use DevSarfo\LaraPhone\Enums\PhoneType;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as LibPhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Stringable;

/**
 * Each parameter is read as, in order of precedence:
 *  1. a supported ISO 3166-1 alpha-2 country code ("GH"),
 *  2. a number type ("mobile", "fixed_line", see PhoneType),
 *  3. the name of another input field holding the country code(s),
 *  4. otherwise an (unsupported) country code, which fails validation.
 */
class PhoneNumberRule implements DataAwareRule, ValidationRule
{
    /**
     * Raw parameters, trimmed and de-duplicated (case is kept for field names).
     *
     * @var array<int, string>
     */
    protected array $parameters = [];

    /**
     * @var array<int, PhoneType>
     */
    protected array $types = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param  string|array<int, string|null>|null  $parameters  country codes, number types or field names
     */
    public function __construct(string|array|null $parameters = [])
    {
        $this->addParameters(Arr::wrap($parameters));
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
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
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

        $countryCodes = $this->countryCodes();

        // Reject the configuration before checking the number, so the result
        // does not depend on the order of the country codes.
        foreach ($countryCodes as $countryCode) {
            if (! CountryUtil::isValid($countryCode)) {
                return [
                    'key' => 'laraphone::validation.field_country_code_invalid',
                    'replace' => ['country' => $countryCode],
                ];
            }
        }

        $phone = empty($countryCodes)
            ? $this->parseInternational($value)
            : $this->parseForCountries($value, $countryCodes);

        if ($phone === null) {
            return empty($countryCodes) ? $this->genericFailure() : [
                'key' => 'laraphone::validation.field_phone_country_code',
                'replace' => ['country' => $this->countryNames()],
            ];
        }

        if (! empty($this->types) && ! $this->matchesType($phone)) {
            return [
                'key' => 'laraphone::validation.field_phone_type',
                'replace' => ['type' => $this->typeNames()],
            ];
        }

        return null;
    }

    /**
     * Add country codes, number types or field names.
     *
     * @param  string|array<int, string|null>  $countryCodes
     */
    public function setCountryCode(string|array $countryCodes): static
    {
        return $this->addParameters(is_array($countryCodes) ? $countryCodes : func_get_args());
    }

    /**
     * Only accept numbers of the given type(s).
     */
    public function type(PhoneType|string ...$types): static
    {
        foreach ($types as $type) {
            $resolved = PhoneType::resolve($type)
                ?? throw new \InvalidArgumentException("Unknown phone number type [{$type}].");

            if (! in_array($resolved, $this->types, true)) {
                $this->types[] = $resolved;
            }
        }

        return $this;
    }

    public function mobile(): static
    {
        return $this->type(PhoneType::MOBILE);
    }

    public function fixedLine(): static
    {
        return $this->type(PhoneType::FIXED_LINE);
    }

    /**
     * The effective country codes, with field names replaced by their values.
     *
     * @return array<int, string>
     */
    public function countryCodes(): array
    {
        $codes = [];

        foreach ($this->parameters as $parameter) {
            if (! CountryUtil::isValid($parameter) && Arr::has($this->data, $parameter)) {
                foreach (Arr::wrap(Arr::get($this->data, $parameter)) as $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $codes[] = strtoupper(trim($value));
                    }
                }

                continue;
            }

            $codes[] = strtoupper($parameter);
        }

        return array_values(array_unique($codes));
    }

    /**
     * @return array<int, PhoneType>
     */
    public function types(): array
    {
        return $this->types;
    }

    public function countryNames(): string
    {
        return implode(', ', array_map(fn ($countryCode) => CountryUtil::name($countryCode), $this->countryCodes()));
    }

    public function typeNames(): string
    {
        return implode(' or ', array_map(fn (PhoneType $type) => $type->label(), $this->types));
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    protected function addParameters(array $parameters): static
    {
        foreach ($parameters as $parameter) {
            if (! is_string($parameter) || trim($parameter) === '') {
                continue;
            }

            $parameter = trim($parameter);

            if (! CountryUtil::isValid($parameter) && ($type = PhoneType::resolve($parameter)) !== null) {
                $this->type($type);

                continue;
            }

            if (! in_array($parameter, $this->parameters, true)) {
                $this->parameters[] = $parameter;
            }
        }

        return $this;
    }

    protected function parseInternational(string $value): ?LibPhoneNumber
    {
        $util = PhoneNumberUtil::getInstance();

        try {
            $phone = $util->parse($value);
        } catch (NumberParseException) {
            return null;
        }

        return str_starts_with(trim($value), '+') && $util->isValidNumber($phone) ? $phone : null;
    }

    /**
     * @param  array<int, string>  $countryCodes
     */
    protected function parseForCountries(string $value, array $countryCodes): ?LibPhoneNumber
    {
        $util = PhoneNumberUtil::getInstance();

        foreach ($countryCodes as $countryCode) {
            try {
                $phone = $util->parse($value, $countryCode);

                if ($util->isValidNumberForRegion($phone, $countryCode)) {
                    return $phone;
                }
            } catch (NumberParseException) {
                // try the next country code
            }
        }

        return null;
    }

    protected function matchesType(LibPhoneNumber $phone): bool
    {
        $type = PhoneType::fromLibPhoneNumber(PhoneNumberUtil::getInstance()->getNumberType($phone));

        foreach ($this->types as $requested) {
            if ($type->satisfies($requested)) {
                return true;
            }
        }

        return false;
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
}
