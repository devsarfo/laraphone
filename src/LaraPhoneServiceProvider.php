<?php

namespace DevSarfo\LaraPhone;

use DevSarfo\LaraPhone\Rules\PhoneNumberRule;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberUtil;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaraPhoneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laraphone')
            ->hasConfigFile()
            ->hasTranslations();

        // Register the libphonenumber Library as singleton
        $this->app->singleton('libphonenumber', function ($app) {
            return PhoneNumberUtil::getInstance();
        });

        $this->app->alias('libphonenumber', PhoneNumberUtil::class);

        // Register the validation rule globally
        $this->callAfterResolving('validator', function (Factory $validator) {
            $validator->extendDependent('phone', function ($attribute, $value, array $parameters, $validator) {
                // Create the PhoneNumberRule object
                $rule = new PhoneNumberRule($parameters);

                // Validate using the custom rule's `validate` method
                $failClosure = function ($message) use ($validator, $attribute) {
                    $validator->errors()->add($attribute, $message);

                    return $message;
                };

                $rule->validate($attribute, $value, $failClosure);

                // Return true if validation passed, false otherwise
                return ! $validator->errors()->has($attribute);
            });
        });

        Rule::macro('phone', function (...$countryCodes) {
            return new PhoneNumberRule($countryCodes);
        });
    }
}
