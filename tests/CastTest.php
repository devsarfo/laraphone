<?php

use DevSarfo\LaraPhone\Casts\AsPhoneNumber;
use DevSarfo\LaraPhone\Models\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CastContact extends Model
{
    protected $table = 'cast_contacts';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'phone' => AsPhoneNumber::class.':GH',
            'any_phone' => AsPhoneNumber::class,
            'local_phone' => AsPhoneNumber::class.':country',
        ];
    }
}

beforeEach(function () {
    config(['database.default' => 'testing']);

    Schema::create('cast_contacts', function (Blueprint $table) {
        $table->id();
        $table->string('country')->nullable();
        $table->string('phone')->nullable();
        $table->string('any_phone')->nullable();
        $table->string('local_phone')->nullable();
    });
});

it('stores valid numbers as E.164', function (mixed $input) {
    $contact = CastContact::create(['phone' => $input]);

    expect($contact->getRawOriginal('phone'))->toBe('+233244123456')
        ->and(CastContact::find($contact->id)->getRawOriginal('phone'))->toBe('+233244123456');
})->with([
    'national' => '0244123456',
    'spaced' => '024 412 3456',
    'international' => '+233 24 412 3456',
    'PhoneNumber' => fn () => new PhoneNumber('0244123456', 'GH'),
]);

it('reads numbers back as PhoneNumber objects', function () {
    $contact = CastContact::find(CastContact::create(['phone' => '0244123456'])->id);

    expect($contact->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($contact->phone->formatNational())->toBe('024 412 3456')
        ->and($contact->phone->isValid())->toBeTrue()
        ->and($contact->toArray()['phone'])->toBe('+233244123456');
});

it('stores invalid numbers exactly as given', function (string $input) {
    $contact = CastContact::create(['phone' => $input]);

    expect($contact->getRawOriginal('phone'))->toBe($input)
        ->and(CastContact::find($contact->id)->phone->isValid())->toBeFalse()
        ->and((string) CastContact::find($contact->id)->phone)->toBe($input);
})->with([
    'garbage' => 'not a phone',
    'too short' => '024412345',
]);

it('keeps numbers from other countries as given when not allowed', function () {
    // The cast is restricted to GH, so a Norwegian number is not valid for it.
    expect(CastContact::create(['phone' => '+4722334455'])->getRawOriginal('phone'))->toBe('+4722334455');
});

it('stores null and blank as null', function (mixed $input) {
    $contact = CastContact::create(['phone' => $input]);

    expect($contact->getRawOriginal('phone'))->toBeNull()
        ->and(CastContact::find($contact->id)->phone)->toBeNull();
})->with([null, '', '   ']);

it('reads the country from another attribute', function () {
    $contact = CastContact::create(['country' => 'NO', 'local_phone' => '22334455']);

    expect($contact->getRawOriginal('local_phone'))->toBe('+4722334455')
        ->and(CastContact::find($contact->id)->local_phone->formatNational())->toBe('22 33 44 55');
});

it('uses the default country when the cast has none', function () {
    config(['laraphone.default_country' => 'GH']);

    expect(CastContact::create(['any_phone' => '0244123456'])->getRawOriginal('any_phone'))->toBe('+233244123456');
});

it('requires international format without a country or default', function () {
    expect(CastContact::create(['any_phone' => '0244123456'])->getRawOriginal('any_phone'))->toBe('0244123456')
        ->and(CastContact::create(['any_phone' => '+233244123456'])->getRawOriginal('any_phone'))->toBe('+233244123456');
});

it('rejects values that are not phone numbers', function () {
    CastContact::create(['phone' => ['0244123456']]);
})->throws(InvalidArgumentException::class, 'The [phone] attribute must be a phone number string or PhoneNumber instance.');
