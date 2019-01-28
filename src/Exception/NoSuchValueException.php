<?php
declare(strict_types=1);

namespace Lemonad\Exception;

final class NoSuchValueException extends \Exception
{
    public static function create(): self
    {
        return new static('Optional is empty.');
    }
}
