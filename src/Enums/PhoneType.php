<?php

namespace DevSarfo\LaraPhone\Enums;

use libphonenumber\PhoneNumberType;

enum PhoneType: string
{
    case FIXED_LINE = 'fixed_line';
    case MOBILE = 'mobile';

    /** Used where fixed-line and mobile numbers can't be told apart (e.g. US, CA). */
    case FIXED_LINE_OR_MOBILE = 'fixed_line_or_mobile';
    case TOLL_FREE = 'toll_free';
    case PREMIUM_RATE = 'premium_rate';
    case SHARED_COST = 'shared_cost';
    case VOIP = 'voip';
    case PERSONAL_NUMBER = 'personal_number';
    case PAGER = 'pager';
    case UAN = 'uan';
    case VOICEMAIL = 'voicemail';
    case EMERGENCY = 'emergency';
    case SHORT_CODE = 'short_code';
    case STANDARD_RATE = 'standard_rate';
    case UNKNOWN = 'unknown';

    /**
     * Accepts a PhoneType or its value, case-insensitively ("mobile", "FIXED_LINE").
     */
    public static function resolve(self|string $type): ?self
    {
        return $type instanceof self ? $type : self::tryFrom(strtolower(trim($type)));
    }

    /**
     * Whether a number of this type satisfies the requested type. Numbers that
     * could be either fixed-line or mobile satisfy both.
     */
    public function satisfies(self $requested): bool
    {
        return $this === $requested
            || ($this === self::FIXED_LINE_OR_MOBILE && in_array($requested, [self::FIXED_LINE, self::MOBILE], true));
    }

    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }

    /**
     * @internal
     */
    public static function fromLibPhoneNumber(PhoneNumberType $type): self
    {
        return constant(self::class.'::'.$type->name);
    }
}
