<?php

namespace DevSarfo\LaraPhone\Casts;

use DevSarfo\LaraPhone\Models\PhoneNumber;
use DevSarfo\LaraPhone\Utils\CountryUtil;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Stringable;

/**
 * Stores valid numbers in E.164 format and reads them back as PhoneNumber
 * objects. Invalid numbers are stored exactly as given; validating input is
 * the job of the "phone" validation rule.
 *
 * Parameters are country codes used to parse numbers in national format, or
 * the names of other attributes holding the country code:
 *
 *     'phone' => AsPhoneNumber::class.':GH',
 *     'phone' => AsPhoneNumber::class.':country',
 *
 * When the country comes from another attribute, set that attribute before the
 * phone number. Without parameters, the "laraphone.default_country" config
 * value is used for national numbers.
 *
 * @implements CastsAttributes<PhoneNumber, mixed>
 */
class AsPhoneNumber implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * @var array<int, string>
     */
    protected array $parameters;

    public function __construct(string ...$parameters)
    {
        $this->parameters = array_values(array_filter(array_map('trim', $parameters), fn ($parameter) => $parameter !== ''));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        if (is_null($value) || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return new PhoneNumber((string) $value, $this->countryCodes($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value) || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (! $value instanceof PhoneNumber) {
            if (! is_string($value) && ! is_int($value) && ! $value instanceof Stringable) {
                throw new InvalidArgumentException("The [{$key}] attribute must be a phone number string or PhoneNumber instance.");
            }

            $value = new PhoneNumber((string) $value, $this->countryCodes($attributes));
        }

        return $value->isValid() ? $value->formatE164() : $value->getRawNumber();
    }

    /**
     * Value used by toArray() and toJson(): E.164 for valid numbers, the stored
     * value otherwise.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof PhoneNumber ? (string) $value : $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    protected function countryCodes(array $attributes): array
    {
        $codes = [];

        foreach ($this->parameters as $parameter) {
            if (! CountryUtil::isValid($parameter) && array_key_exists($parameter, $attributes)) {
                $country = $attributes[$parameter];

                if (is_string($country) && trim($country) !== '') {
                    $codes[] = strtoupper(trim($country));
                }

                continue;
            }

            $codes[] = strtoupper($parameter);
        }

        return array_values(array_unique($codes));
    }
}
