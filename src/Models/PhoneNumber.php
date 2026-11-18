<?php

namespace DevSarfo\LaraPhone\Models;

use DevSarfo\LaraPhone\Enums\PhoneFormat;
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

    public function getCountryCode(): ?string
    {
        // Attempt parsing phone to get country code
        try {
            return PhoneNumberUtil::getInstance()->getRegionCodeForNumber(PhoneNumberUtil::getInstance()->parse($this->phone, 'ZZ'));
        } catch (NumberParseException) {
            // do nothing
        }

        $countryCodes = CountryUtil::parse($this->countryCodes);

        foreach ($countryCodes as $countryCode) {
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
     * Passing libphonenumber\PhoneNumberFormat is deprecated and will be
     * removed in 2.0; use PhoneFormat instead.
     *
     * @throws PhoneNumberParseException
     * @throws \ValueError for an unknown integer format
     */
    public function format(PhoneFormat|PhoneNumberFormat|int $format = PhoneFormat::E164): string
    {
        return PhoneNumberUtil::getInstance()->format(
            $this->toPhoneNumber(),
            PhoneFormat::resolve($format)->toLibPhoneNumber()
        );
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
            if ($countryCode === null || (! empty($allowed) && ! in_array($countryCode, $allowed, true))) {
                return false;
            }

            return PhoneNumberUtil::getInstance()->isValidNumberForRegion($this->toPhoneNumber(), $countryCode);
        } catch (NumberParseException|CountryCodeException) {
            return false;
        }
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
     */
    /**
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
            empty($this->countryCodes)
                ? throw PhoneNumberParseException::countryRequired($this->phone)
                : throw PhoneNumberParseException::countryMismatch($this->phone, $this->countryCodes);
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
}
