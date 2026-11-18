<?php

namespace DevSarfo\LaraPhone;

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use libphonenumber\PhoneNumberUtil;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaraPhoneServiceProvider extends PackageServiceProvider
{
    /**
     * Fallback message for the string-based "phone" rule. When Laravel resolves
     * to this value (no custom message defined), the replacer substitutes the
     * specific package message for the failure that occurred.
     */
    public const FALLBACK_MESSAGE = 'laraphone::validation.phone';

    public function configurePackage(Package $package): void
    {
        $package->name('laraphone')
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('libphonenumber', fn () => PhoneNumberUtil::getInstance());
        $this->app->alias('libphonenumber', PhoneNumberUtil::class);

        $this->app->singleton(LaraPhone::class);
    }

    public function packageBooted(): void
    {
        $this->callAfterResolving('validator', function (Factory $factory) {
            // Dependent, so that "*" in field-name parameters (phone:items.*.country)
            // is replaced with the index of the attribute being validated.
            $factory->extendDependent('phone', function ($attribute, $value, array $parameters, Validator $validator) {
                return (new PhoneNumberRule($parameters))->setData($validator->getData())->failure($value) === null;
            }, self::FALLBACK_MESSAGE);

            $factory->replacer('phone', function ($message, $attribute, $rule, array $parameters, Validator $validator) {
                $phoneRule = (new PhoneNumberRule($parameters))->setData($validator->getData());
                $displayable = $validator->getDisplayableAttribute($attribute);

                // An application-wide "validation.phone" line only replaces the
                // generic message; country-specific failures keep the package
                // messages. Inline and "validation.custom" messages always win.
                $isDefault = $message === self::FALLBACK_MESSAGE
                    || (trans()->has('validation.phone') && $message === trans('validation.phone', ['attribute' => $displayable]));

                if ($isDefault) {
                    $failure = $phoneRule->failure(Arr::get($validator->getData(), $attribute));

                    if ($failure !== null && $failure['key'] !== 'validation.phone') {
                        $message = trans($failure['key'], $failure['replace'] + ['attribute' => $displayable]);
                    }
                }

                // Custom messages may still use the :country and :type placeholders.
                return str_replace(
                    [':country', ':type'],
                    [$phoneRule->countryNames(), $phoneRule->typeNames()],
                    $message
                );
            });
        });

        Rule::macro('phone', function (...$countryCodes) {
            return new PhoneNumberRule(Arr::flatten($countryCodes));
        });
    }
}
