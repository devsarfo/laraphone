<?php

namespace DevSarfo\LaraPhone\Exceptions;

use Throwable;

/**
 * Implemented by every exception this package throws, so callers can catch
 * them without depending on libphonenumber's exception classes.
 */
interface LaraPhoneException extends Throwable {}
