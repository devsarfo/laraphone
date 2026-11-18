# Changelog

All notable changes to `LaraPhone` will be documented in this file.

## 1.3 - 2026-11-18

### 🐛 Bug Fixes

**Validation**

- The `phone` rule no longer shows the raw `validation.phone` key. LaraPhone now ships a default message: *"The phone field must be a valid phone number."*
- The string `phone` rule no longer adds two errors for one failure.
- Messages now use readable field names (*"mobile no"* instead of `mobile_no`) and your custom attribute names.
- Arrays and other non-string values now fail validation. Integers are checked as phone numbers.
- `new PhoneNumberRule(null)` no longer throws. It validates the number in international format instead.
- Country codes are case-insensitive, and duplicates are ignored. An unsupported code now always fails, wherever it appears in the list.

**PhoneNumber**

- Parse errors now report the right reason. Before, every error said *"requires a country code"*, even when you gave one.
- `isValid()` now checks the country codes you pass, the same way the `phone` rule does.
- `serialize()` and `json_encode()` no longer throw for invalid numbers, so they're safe to use in queued jobs and API responses.

### ✨ New

- **`LaraPhone` facade:** `make()`, `isValid()`, `format()`, `countryName()` and `supportedCountries()`.
- **`PhoneFormat` enum:** format numbers without importing libphonenumber classes:
  ```php
  $phone->format(PhoneFormat::NATIONAL); // 024 412 3456
  
  ```
- **`LaraPhoneException` interface:** implemented by every exception LaraPhone throws, so one `catch` covers all of them.
- **Custom messages** can use a `:country` placeholder.
- **`Rule::phone()`** accepts an array of country codes.
- **Country names:** added Ascension Island (AC), Tristan da Cunha (TA) and Kosovo (XK).

### ⚠️ Behaviour Changes

Check these when upgrading:

- **Default message:** the generic error is now *"The phone field must be a valid phone number."* instead of `validation.phone`.
- **Your own `validation.phone` line** (if you added one, as the old README said to) is now only used when no country is given. Rules like `phone:GH` show the more specific *"…for Ghana."* message. Inline and `validation.custom` messages still take priority.
- **`PhoneNumber::isValid()`** returns `false` for numbers outside the country codes you pass. For example, `new PhoneNumber('+4722334455', 'GH')` used to be valid.
- **`jsonSerialize()`** returns the original input for numbers it can't parse, instead of throwing.
- **Calling `PhoneNumberRule::validate()` directly** (outside Laravel's validator) now gives messages that still contain `:attribute`. Laravel's validator replaces it with the field name, as it does for any rule.

### 🔜 Deprecations

- Passing `libphonenumber\PhoneNumberFormat` to `format()` still works, but it will be removed in 2.0. Use `DevSarfo\LaraPhone\Enums\PhoneFormat` instead.

### 📦 Requirements

- PHP 8.2 or newer (Laravel 11 already required it)
- Laravel 11, 12 or 13
- `giggsey/libphonenumber-for-php-lite` 9.0.43 or newer, for the latest numbering-plan data

**Full details:** #12

## 1.2 - 2026-05-01

### 🚀 Laravel 13 Support

We’ve updated LaraPhone to fully support Laravel 13! This release aligns the package with the latest framework features and PHP 8.3/8.4 standards.

### ⚠️ Breaking Changes

To keep the codebase modern and maintainable, we are officially dropping support for:

- Laravel 10 (now End-of-Life)
- PHP 8.1

Users on these legacy versions should continue using the v1.1

## LaraPhone v1.0.0 – Initial Stable Release - 2025-04-09

### v1.0.0 – Initial Release 🎉

LaraPhone is a Laravel package that provides powerful and intuitive tools for validating, formatting, and parsing phone numbers in your application.

#### ✨ Features

- ✅ **Phone Number Validation** – Validate phone numbers using international standards.
- 🌍 **International Support** – Handle phone numbers from virtually any country.
- 🧰 **Convenient Helpers** – Simplify common phone number operations with clean helper methods.

#### ⚠️ Known Issues

- 🔧 Some edge cases in phone number formatting may still require handling.
- 📚 Documentation is still in progress — feel free to contribute or share feedback.

## 1.0.0-beta - 2025-04-09

### v1.0.0-beta

This is the first beta release of LaraPhone. It includes the basic phone number validation, formatting, and parsing features.

#### Features:

- Phone number validation.
- Support for international phone numbers.
- Helper functions for easy use.

#### Known Issues:

- Some edge cases for formatting may not work as expected.
- Documentation might still be a work in progress.
