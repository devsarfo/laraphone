<?php

use DevSarfo\LaraPhone\Exceptions\PhoneNumberParseException;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use libphonenumber\PhoneNumberFormat;

if (! function_exists('phone')) {
    /**
     * @throws PhoneNumberParseException
     */
    function phone(?string $phone, array|string $country = [], ?PhoneNumberFormat $format = null): PhoneNumber|string
    {
        $instance = new PhoneNumber($phone, $country);

        if (! is_null($format)) {
            return $instance->format($format);
        }

        return $instance;
    }
}
