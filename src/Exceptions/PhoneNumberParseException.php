<?php

namespace DevSarfo\LaraPhone\Exceptions;

use Illuminate\Support\Arr;
use libphonenumber\NumberParseException;

class PhoneNumberParseException extends NumberParseException
{
    protected string $phone;

    protected array $countryCodes = [];

    public static function countryRequired(string $phone): static
    {
        /** @phpstan-ignore-next-line */
        $exception = new static(
            NumberParseException::INVALID_COUNTRY_CODE,
            trans('laraphone::validation.country_code_required')
        );

        $exception->phone = $phone;

        return $exception;
    }

    public static function countryMismatch(string $phone, array|string $countryCodes): static
    {
        $countries = array_filter(Arr::wrap($countryCodes));

        /** @phpstan-ignore-next-line */
        $exception = new static(
            NumberParseException::INVALID_COUNTRY_CODE,
            trans('laraphone::validation.country_code_invalid', [
                'country_code' => implode(', ', $countries),
            ])
        );

        $exception->phone = $phone;
        $exception->countryCodes = $countryCodes;

        return $exception;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCountryCodes(): array
    {
        return $this->countryCodes;
    }
}
