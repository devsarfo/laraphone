<?php

use DevSarfo\LaraPhone\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

uses(TestCase::class)->in(__DIR__);

function validatePhone(mixed $phone, mixed $rule): Illuminate\Validation\Validator
{
    return Validator::make(['phone' => $phone], ['phone' => $rule]);
}
