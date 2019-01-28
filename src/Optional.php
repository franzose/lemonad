<?php
declare(strict_types=1);

namespace Lemonad;

use Lemonad\Exception\NoSuchValueException;
use Lemonad\Exception\NullValueException;

/**
 * A container object which may or may not contain a non-null value.
 *
 * @package Lemonad
 */
final class Optional
{
    private $value;

    private function __construct($value = null)
    {
        $this->value = $value;
    }

    public static function empty(): self
    {
        return new static();
    }

    /**
     * Returns an Optional with the specified present non-null value.
     *
     * @param mixed $value
     *
     * @return Optional
     * @throws NullValueException
     */
    public static function of($value): self
    {
        if (null === $value) {
            throw NullValueException::create();
        }

        return new static($value);
    }

    public static function ofNullable($value): self
    {
        return null === $value
            ? static::empty()
            : static::of($value);
    }

    public function equals(Optional $optional): bool
    {
        return $this === $optional || $this->value === $optional->value;
    }

    /**
     * If a value is absent or the value does not match the given predicate,
     * returns an empty Optional, otherwise returns an Optional describing the value.
     *
     * @param callable $predicate
     *
     * @return Optional
     */
    public function filter(callable $predicate): self
    {
        if ($this->isAbsent() || false === $predicate($this->value)) {
            return static::empty();
        }

        return $this;
    }

    /**
     * If a value is present, applies the provided mapping function to it,
     * and if the result is non-null, returns an Optional describing the result.
     *
     * @param callable $mapper
     *
     * @return Optional
     */
    public function map(callable $mapper): self
    {
        if ($this->isAbsent()) {
            return static::empty();
        }

        return static::ofNullable($mapper($this->value));
    }

    /**
     * If a value is present, applies the provided Optional-bearing mapping function to it,
     * returns that result, otherwise return an empty Optional.
     *
     * @param callable $mapper
     *
     * @return Optional
     */
    public function flatMap(callable $mapper): self
    {
        if ($this->isAbsent()) {
            return static::empty();
        }

        return $mapper($this->value);
    }

    public function get()
    {
        if ($this->isAbsent()) {
            throw NoSuchValueException::create();
        }

        return $this->value;
    }

    /**
     * If a value is present, invokes the specified consumer with the value, otherwise does nothing.
     *
     * @param callable $consumer
     */
    public function ifPresent(callable $consumer): void
    {
        if ($this->isPresent()) {
            $consumer($this->value);
        }
    }

    /**
     * If a value is present, invokes the specified consumer with the value,
     * otherwise invokes the specified action.
     *
     * @param callable $consumer
     * @param callable $action
     */
    public function isPresentOrElse(callable $consumer, callable $action): void
    {
        $this->isPresent()
            ? $consumer($this->value)
            : $action();
    }

    public function isPresent(): bool
    {
        return null !== $this->value;
    }

    public function isAbsent(): bool
    {
        return !$this->isPresent();
    }

    /**
     * If a value is present, returns itself, otherwise invokes supplier
     * and returns the result of that invocation.
     *
     * @param callable $supplier
     *
     * @return Optional
     */
    public function or(callable $supplier): self
    {
        return $this->isPresent() ? $this : $supplier();
    }

    /**
     * If a value is present, returns that value, otherwise returns the given value.
     *
     * @param mixed $other
     *
     * @return mixed
     */
    public function orElse($other)
    {
        return $this->isPresent() ? $this->value : $other;
    }

    /**
     * If a value is present, returns that value, otherwise invokes the supplier
     * and returns the result of that invocation.
     *
     * @param callable $supplier
     *
     * @return mixed
     */
    public function orElseGet(callable $supplier)
    {
        return $this->isPresent() ? $this->value : $supplier();
    }

    /**
     * If a value is present, returns that value, otherwise throws
     * the exception to be created by the provider supplier.
     *
     * @param callable $exceptionSupplier
     *
     * @return mixed
     */
    public function orElseThrow(callable $exceptionSupplier)
    {
        if ($this->isPresent()) {
            return $this->value;
        }

        throw $exceptionSupplier();
    }
}
