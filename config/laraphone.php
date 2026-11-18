<?php

// config for DevSarfo/LaraPhone
return [

    /*
    |--------------------------------------------------------------------------
    | Default country
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 alpha-2 code used to parse numbers in national format when no
    | country is given, e.g. phone('0244123456') with 'GH'. It is only a
    | parsing hint: it does not restrict which countries are valid, and the
    | "phone" validation rule does not use it.
    |
    */

    'default_country' => null,

    /*
    |--------------------------------------------------------------------------
    | Default format
    |--------------------------------------------------------------------------
    |
    | Format used by format() when called without an argument. One of: E164,
    | INTERNATIONAL, NATIONAL, RFC3966. Casting to string, JSON and the
    | Eloquent cast always use E164.
    |
    */

    'default_format' => 'E164',

];
