<?php

namespace DevSarfo\LaraPhone\Enums;

use libphonenumber\PhoneNumberFormat;

enum PhoneFormat: int
{
    /** +233244123456 */
    case E164 = 0;

    /** +233 24 412 3456 */
    case INTERNATIONAL = 1;

    /** 024 412 3456 */
    case NATIONAL = 2;

    /** tel:+233-24-412-3456 */
    case RFC3966 = 3;

    /**
     * Resolve any accepted format value to a PhoneFormat.
     *
     * @throws \ValueError for an unknown integer
     */
    public static function resolve(self|PhoneNumberFormat|int $format): self
    {
        return match (true) {
            $format instanceof self => $format,
            $format instanceof PhoneNumberFormat => self::from($format->value),
            default => self::from($format),
        };
    }

    /**
     * @internal
     */
    public function toLibPhoneNumber(): PhoneNumberFormat
    {
        return PhoneNumberFormat::from($this->value);
    }
}
