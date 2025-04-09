<?php

namespace DevSarfo\LaraPhone\Exceptions;

use Exception;

class CountryCodeException extends Exception
{
    protected string $countryCode;

    public function __construct(string $countryCode)
    {
        $this->countryCode = strtoupper($countryCode);

        parent::__construct(trans('laraphone::validation.exception_country_code', ['country' => $this->countryCode]));
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }
}
