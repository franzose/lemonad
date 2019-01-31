<?php
declare(strict_types=1);

use Lemonad\Optional;

/**
 * Provides a callable that returns the given value.
 *
 * @param mixed $value
 *
 * @return callable
 */
function supplier($value): callable
{
    return
        /** @return mixed */
        function () use ($value) {
            return $value;
        };
}

/**
 * Provides a callable that returns an Optional containing the given value.
 *
 * @param mixed $value
 *
 * @return callable
 */
function optional_supplier($value): callable
{
    return function () use ($value): Optional {
        return Optional::ofNullable($value);
    };
}

/**
 * Provides a callable that checks inner value to be equal to the given one.
 *
 * @param mixed $expected
 *
 * @return callable
 */
function equality_supplier($expected): callable
{
    return
        /**
         * @param mixed $value
         * @return boolean
         */
        function ($value) use ($expected): bool {
            return $value === $expected;
        };
}

/**
 * Provides a callable that mutates a variable to the given value.
 *
 * @param mixed $old A value to change
 * @param mixed $new A new value that to be set
 *
 * @return callable
 */
function mutation_supplier(&$old, $new): callable
{
    return function () use (&$old, $new): void {
        $old = $new;
    };
}
