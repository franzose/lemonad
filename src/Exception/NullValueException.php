<?php
declare(strict_types=1);

namespace Lemonad\Exception;

final class NullValueException extends \Exception
{
    public static function create(): self
    {
        return new static('Value must not be null.');
    }
}
