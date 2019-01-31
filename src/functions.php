<?php
declare(strict_types=1);

namespace Lemonad;

/**
 * Creates a new Optional with the given value.
 *
 * @param mixed $value
 *
 * @return Optional
 */
function optional($value): Optional
{
    return Optional::ofNullable($value);
}

/**
 * Creates a new Maybe with the given value.
 *
 * @param mixed $value
 *
 * @return Maybe
 */
function maybe($value): Maybe
{
    return Maybe::of($value);
}

/**
 * Creates a new callable which returns a given value untouched.
 *
 * @return callable
 */
function identity(): callable
{
    return
        /**
         * @param mixed $value
         * @return mixed
         */
        function ($value) {
            return $value;
        };
}
