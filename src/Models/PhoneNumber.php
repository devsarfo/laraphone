<?php

namespace DevSarfo\LaraPhone\Models;

use DevSarfo\LaraPhone\Enums\PhoneFormat;
use DevSarfo\LaraPhone\Enums\PhoneType;
use DevSarfo\LaraPhone\Exceptions\CountryCodeException;
use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Throwable;

class PhoneNumber implements JsonSerializable
{
    use Macroable;

    protected ?string $phone;

    /**
     * @var array<int, string>
     */
    protected array $countryCodes;

    /**
     * @param  string|array<int, string>  $countryCodes
     */
    public function __construct(?string $phone, array|string $countryCodes = [])
    {
        $this->phone = is_null($phone) ? '' : $phone;
        $this->countryCodes = Arr::wrap($countryCodes);
    }

    /**
     * The input exactly as it was given.
     */
    public function getRawNumber(): string
    {
        return (string) $this->phone;
    }

    public function getCountryCode(): ?string
    {
        // Numbers in international format carry their own country
        try {
            return PhoneNumberUtil::getInstance()->getRegionCodeForNumber(PhoneNumberUtil::getInstance()->parse($this->phone, 'ZZ'));
        } catch (NumberParseException) {
            // do nothing
        }

        foreach ($this->parsingCountries() as $countryCode) {
            try {
                $phoneNumber = PhoneNumberUtil::getInstance()->parse($this->phone, $countryCode);
            } catch (NumberParseException) {
                continue;
            }

            if (PhoneNumberUtil::getInstance()->isValidNumberForRegion($phoneNumber, $countryCode)) {
                return PhoneNumberUtil::getInstance()->getRegionCodeForNumber($phoneNumber);
            }
        }

        return null;
    }

    /**
     * @param  string|array<int, string>  $countryCode
     */
    public function checkCountryCode(array|string $countryCode): bool
    {
        $countryCodes = CountryUtil::parse(Arr::wrap($countryCode));

        $instance = clone $this;
        $instance->countryCodes = $countryCodes;

        return in_array($instance->getCountryCode(), $countryCodes);
    }

    /**
     * Format the number. Without an argument, uses the "laraphone.default_format"
     * config value (E164 unless changed).
     *
     * Passing libphonenumber\PhoneNumberFormat is deprecated and will be
     * removed in 2.0; use PhoneFormat instead.
     *
     * @throws PhoneNumberParseException
     * @throws \ValueError for an unknown integer format
     */
    public function format(PhoneFormat|PhoneNumberFormat|int|null $format = null): string
    {
        $format = is_null($format) ? PhoneFormat::default() : PhoneFormat::resolve($format);

        return PhoneNumberUtil::getInstance()->format($this->toPhoneNumber(), $format->toLibPhoneNumber());
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatInternational(): string
    {
        return $this->format(PhoneFormat::INTERNATIONAL);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatNational(): string
    {
        return $this->format(PhoneFormat::NATIONAL);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatE164(): string
    {
        return $this->format(PhoneFormat::E164);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatRFC3966(): string
    {
        return $this->format(PhoneFormat::RFC3966);
    }

    public function isValid(): bool
    {
        try {
            $countryCode = $this->getCountryCode();
            $allowed = CountryUtil::parse($this->countryCodes);

            // Like the validation rule, the given country codes restrict the
            // allowed countries, including for numbers in international format.
            // The configured default country is only a parsing hint.
            if ($countryCode === null || (! empty($allowed) && ! in_array($countryCode, $allowed, true))) {
                return false;
            }

            return PhoneNumberUtil::getInstance()->isValidNumberForRegion($this->toPhoneNumber(), $countryCode);
        } catch (NumberParseException|CountryCodeException) {
            return false;
        }
    }

    /**
     * The number's type, e.g. PhoneType::MOBILE, or null if it can't be parsed.
     */
    public function getType(): ?PhoneType
    {
        try {
            return PhoneType::fromLibPhoneNumber(
                PhoneNumberUtil::getInstance()->getNumberType($this->toPhoneNumber())
            );
        } catch (NumberParseException|CountryCodeException) {
            return null;
        }
    }

    /**
     * Whether the number is any of the given types. Numbers that can't be told
     * apart as fixed-line or mobile (PhoneType::FIXED_LINE_OR_MOBILE) match both.
     *
     * @throws \InvalidArgumentException for an unknown type name
     */
    public function isOfType(PhoneType|string ...$types): bool
    {
        $type = $this->getType();

        if ($type === null) {
            return false;
        }

        foreach ($types as $requested) {
            $resolved = PhoneType::resolve($requested)
                ?? throw new \InvalidArgumentException("Unknown phone number type [{$requested}].");

            if ($type->satisfies($resolved)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The E.164 representation, or the raw input when it cannot be parsed.
     */
    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    /**
     * Keep the raw input and country codes so that invalid numbers can be
     * serialized (e.g. inside queued jobs) without throwing.
     *
     * @return array{phone: string, countryCodes: array<int, string>}
     */
    public function __serialize(): array
    {
        return [
            'phone' => $this->phone,
            'countryCodes' => $this->countryCodes,
        ];
    }

    /**
     * @param  array{phone?: string, countryCodes?: array<int, string>}  $serialized
     */
    public function __unserialize(array $serialized): void
    {
        $this->phone = $serialized['phone'] ?? '';
        $this->countryCodes = $serialized['countryCodes'] ?? [];
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function toPhoneNumber(): \libphonenumber\PhoneNumber
    {
        try {
            return PhoneNumberUtil::getInstance()->parse($this->phone, $this->getCountryCode());
        } catch (NumberParseException) {
            $countries = $this->parsingCountries();

            empty($countries)
                ? throw PhoneNumberParseException::countryRequired($this->phone)
                : throw PhoneNumberParseException::countryMismatch($this->phone, $countries);
        }
    }

    public function __toString()
    {
        try {
            return $this->formatE164();
        } catch (Exception $e) {
            return (string) $this->phone;
        }
    }

    /**
     * Countries used to parse numbers in national format: the given country
     * codes, or else the configured default country.
     *
     * @return array<int, string>
     *
     * @throws CountryCodeException
     */
    protected function parsingCountries(): array
    {
        if (! empty($this->countryCodes)) {
            return CountryUtil::parse($this->countryCodes);
        }

        $default = static::defaultCountry();

        return $default === null ? [] : [CountryUtil::parse($default)];
    }

    protected static function defaultCountry(): ?string
    {
        try {
            $country = config('laraphone.default_country');
        } catch (Throwable) {
            return null;
        }

        return is_string($country) && trim($country) !== '' ? trim($country) : null;
    }
}
