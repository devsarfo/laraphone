<?php

namespace DevSarfo\LaraPhone\Exceptions;

use Illuminate\Support\Arr;
use libphonenumber\NumberParseException;

class PhoneNumberParseException extends NumberParseException
{
    protected string $phone;

    /**
     * @var array<int, string>
     */
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

    /**
     * @param  string|array<int, string>  $countryCodes
     */
    public static function countryMismatch(string $phone, array|string $countryCodes): static
    {
        $countries = array_filter(Arr::wrap($countryCodes));

        /** @phpstan-ignore-next-line */
        $exception = new static(
            NumberParseException::INVALID_COUNTRY_CODE,
            trans('laraphone::validation.country_code_invalid', [
                'country' => implode(', ', array_map('strtoupper', $countries)),
            ])
        );

        $exception->phone = $phone;
        $exception->countryCodes = array_values($countries);

        return $exception;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return array<int, string>
     */
    public function getCountryCodes(): array
    {
        return $this->countryCodes;
    }
}
