<?php
declare(strict_types=1);

namespace Lemonad;

use Lemonad\Exception\NullValueException;

/**
 * Maybe represents a possibility of a value.
 *
 * @package Lemonad
 */
abstract class Maybe
{
    /**
     * Returns true if the value is present, false otherwise.
     *
     * @return bool
     */
    abstract public function isKnown(): bool;

    /**
     * Returns the given value if the actual value is absent, original otherwise.
     *
     * @param mixed $other
     *
     * @return mixed
     */
    abstract public function or($other);

    /**
     * Returns the given Maybe if the actual value is absent,
     * otherwise returns the original Maybe.
     *
     * @param Maybe $other
     *
     * @return Maybe
     */
    abstract public function orElse(Maybe $other): Maybe;

    /**
     * If a value is present, applies the given mapping function
     * to it and returns a new definite Maybe, otherwise
     * returns a Maybe with an absent value.
     *
     * @param callable $mapper
     *
     * @return Maybe
     */
    abstract public function to(callable $mapper): Maybe;

    /**
     * If a value is present, applies the given predicate function
     * to it and returns a new Maybe with a predicate's boolean value,
     * otherwise returns a Maybe with an absent value.
     *
     * @param callable $predicate
     *
     * @return Maybe
     */
    abstract public function query(callable $predicate): Maybe;

    /**
     * Checks whether current Maybe is equal to another Maybe.
     *
     * @param Maybe $other
     *
     * @return bool
     */
    abstract public function equals(Maybe $other): bool;

    /**
     * Creates Maybe containing either definite or unknown value.
     *
     * @param mixed $value
     *
     * @return Maybe
     */
    public static function of($value): Maybe
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return null === $value
            ? static::unknown()
            : static::definitely($value);
    }

    /**
     * Creates an empty Maybe.
     *
     * @return Maybe
     */
    public static function unknown(): Maybe
    {
        return new class extends Maybe
        {
            public function isKnown(): bool
            {
                return false;
            }

            public function or($other)
            {
                return \is_callable($other) ? $other() : $other;
            }

            public function orElse(Maybe $other): Maybe
            {
                return $other;
            }

            public function to(callable $mapper): Maybe
            {
                return Maybe::unknown();
            }

            public function query(callable $predicate): Maybe
            {
                return Maybe::unknown();
            }

            public function equals(Maybe $other): bool
            {
                return false;
            }

            public function __toString(): string
            {
                return 'unknown';
            }
        };
    }

    /**
     * Creates a Maybe with a non-empty value.
     *
     * @param mixed $value
     *
     * @return Maybe
     * @throws NullValueException
     */
    public static function definitely($value): Maybe
    {
        if (null === $value) {
            throw NullValueException::create();
        }

        return new class($value) extends Maybe
        {
            /**
             * @var mixed
             */
            private $value;

            /**
             * Creates a new, definite implementation of Maybe.
             *
             * @param mixed $value
             */
            public function __construct($value)
            {
                $this->value = $value;
            }

            public function isKnown(): bool
            {
                return true;
            }

            public function or($other)
            {
                return $this->value;
            }

            public function orElse(Maybe $other): Maybe
            {
                return $this;
            }

            public function to(callable $mapper): Maybe
            {
                return Maybe::definitely($mapper($this->value));
            }

            public function query(callable $predicate): Maybe
            {
                return Maybe::definitely((bool) $predicate($this->value));
            }

            public function equals(Maybe $other): bool
            {
                if (!$other->isKnown()) {
                    return false;
                }

                return $this === $other || $this->value === $other->or($this->value);
            }

            public function __toString(): string
            {
                return (string) $this->value;
            }
        };
    }
}
