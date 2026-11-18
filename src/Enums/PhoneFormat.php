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
     * The "laraphone.default_format" config value, or E164 when it is unset
     * or not a valid format name.
     */
    public static function default(): self
    {
        try {
            $configured = config('laraphone.default_format');
        } catch (\Throwable) {
            return self::E164;
        }

        if ($configured instanceof self) {
            return $configured;
        }

        foreach (self::cases() as $case) {
            if (is_string($configured) && strcasecmp($case->name, trim($configured)) === 0) {
                return $case;
            }
        }

        return self::E164;
    }

    /**
     * @internal
     */
    public function toLibPhoneNumber(): PhoneNumberFormat
    {
        return PhoneNumberFormat::from($this->value);
    }
}
