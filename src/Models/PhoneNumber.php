<?php

namespace DevSarfo\LaraPhone\Models;

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

    protected array $countryCodes;

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

            if (PhoneNumberUtil::getInstance()->isValidNumberForRegion($phoneNumber, $countryCode ?? 'ZZ')) {
                return PhoneNumberUtil::getInstance()->getRegionCodeForNumber($phoneNumber);
            }
        }

        return null;
    }

    public function checkCountryCode(array|string $countryCode): bool
    {
        $countryCodes = CountryUtil::parse(Arr::wrap($countryCode));

        $instance = clone $this;
        $instance->countryCodes = $countryCodes;

        return in_array($instance->getCountryCode(), $countryCodes);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function format(PhoneNumberFormat|int $format = PhoneNumberFormat::E164): string
    {
        if (is_int($format)) {
            $format = PhoneNumberFormat::from($format);
        }

        return PhoneNumberUtil::getInstance()->format($this->toPhoneNumber(), $format);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatInternational(): string
    {
        return $this->format(PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatNational(): string
    {
        return $this->format(PhoneNumberFormat::NATIONAL);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatE164(): string
    {
        return $this->format(PhoneNumberFormat::E164);
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function formatRFC3966(): string
    {
        return $this->format(PhoneNumberFormat::RFC3966);
    }

    public function isValid(): bool
    {
        try {
            return PhoneNumberUtil::getInstance()->isValidNumberForRegion($this->toPhoneNumber(), $this->getCountryCode() ?? 'ZZ');
        } catch (NumberParseException) {
            return false;
        }
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function jsonSerialize(): string
    {
        return $this->formatE164();
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function __serialize()
    {
        return ['phone' => $this->formatE164()];
    }

    public function __unserialize(array $serialized)
    {
        $this->phone = $serialized['phone'];
    }

    /**
     * @throws PhoneNumberParseException
     */
    public function toPhoneNumber(): \libphonenumber\PhoneNumber
    {
        try {
            return PhoneNumberUtil::getInstance()->parse($this->phone, $this->getCountryCode());
        } catch (NumberParseException) {
            empty($this->countries)
                ? throw PhoneNumberParseException::countryRequired($this->phone)
                : throw PhoneNumberParseException::countryMismatch($this->phone, $this->countries);
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
