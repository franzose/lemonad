<?php
declare(strict_types=1);

namespace Lemonad;

function optional($value): Optional
{
    return Optional::ofNullable($value);
}

function maybe($value): Maybe
{
    return Maybe::of($value);
}
