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
