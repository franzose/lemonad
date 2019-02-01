<?php
declare(strict_types=1);

namespace Lemonad;

use Lemonad\Exception\NoSuchValueException;
use Lemonad\Exception\NullValueException;
use Throwable;

/**
 * A Try represents a computation that may either throw an exception or return a value.
 * A Try will either be successful wrapping a value or a failed which wraps an exception.
 *
 * @package Lemonad
 */
abstract class LetsTry
{
    /**
     * If `Success`, applies the mapper function to the contained
     * value and uses the result to perform a new Try,
     * otherwise returns a new instance of `Failure`.
     *
     * @param callable $mapper
     *
     * @return LetsTry
     */
    abstract public function map(callable $mapper): LetsTry;

    /**
     * If `Success`, passes the contained value to the mapper function
     * and returns its result (which must be a Try), otherwise
     * returns a new instance of `Failure`.
     *
     * @param callable $mapper
     *
     * @return LetsTry
     */
    abstract public function flatMap(callable $mapper): LetsTry;

    /**
     * If `Failure`, applies the given function to the contained exception
     * and performs another Try using the result of that application,
     * otherwise returns the current instance unchanged.
     *
     * @param callable $supplier
     *
     * @return LetsTry
     */
    abstract public function recover(callable $supplier): LetsTry;

    /**
     * If `Failure`, applies the given function to the contained exception
     * and returns the result (which must be another Try) directly,
     * otherwise returns the current instance unchanged.
     *
     * @param callable $supplier
     *
     * @return LetsTry
     */
    abstract public function recoverWith(callable $supplier): LetsTry;

    /**
     * If `Success`, returns the contained value,
     * otherwise calls the supplier and returns its value.
     *
     * @param callable $supplier
     *
     * @return mixed
     */
    abstract public function getOrElse(callable $supplier);

    /**
     * If `Success`, returns the same `Success`,
     * otherwise returns a Try provided by the callable.
     *
     * @param callable $try
     *
     * @return LetsTry
     */
    abstract public function orElse(callable $try): LetsTry;

    /**
     * Returns a `Success` if this is a `Success` and the
     * contained values satisfies the given predicate.
     *
     * If this is a `Success` but the predicate is not satisfied,
     * returns a `Failure` with the value (exception instance)
     * provided by the supplier.
     *
     * If this is a `Failure`, returns a new `Failure` with the contained value.
     *
     * @param callable $predicate
     * @param callable $exceptionSupplier
     *
     * @return LetsTry
     */
    abstract public function filterOrElse(callable $predicate, callable $exceptionSupplier): LetsTry;

    /**
     * Applies either $failure or $success if this is a `Failure` or `Success` accordingly.
     *
     * @param callable $failure The function applied if this is a `Failure`
     * @param callable $success The function applied if this is a `Success`
     *
     * @return mixed
     */
    abstract public function fold(callable $failure, callable $success);

    /**
     * Creates an Optional from this Try.
     *
     * @return Optional
     */
    abstract public function toOptional(): Optional;

    /**
     * Perform the given consumer for the contained value of the `Success` Try.
     *
     * @param callable $consumer
     */
    abstract public function forEach(callable $consumer): void;

    /**
     * Compares this Try to another one.
     *
     * @param LetsTry $try
     *
     * @return bool
     */
    abstract public function equals(LetsTry $try): bool;

    /**
     * Returns true if this is a `Failure`, otherwise returns false.
     *
     * @return bool
     */
    abstract public function isFailure(): bool;

    /**
     * Returns true if this is a `Success`, otherwise returns false.
     *
     * @return bool
     */
    abstract public function isSuccess(): bool;

    /**
     * Returns a success wrapping all of the values if all of the arguments
     * were a success, otherwise returns the first failure.
     *
     * @param iterable|LetsTry[] $trys
     *
     * @return LetsTry
     * @throws NullValueException
     */
    public static function sequence(iterable $trys): LetsTry
    {
        $seq = [];

        $supplier = static function (): void {
            throw new NoSuchValueException();
        };

        foreach ($trys as $try) {
            if ($try->isFailure()) {
                return static::failure($try->fold(identity(), $supplier));
            }

            $seq[] = $try->fold($supplier, identity());
        }

        return static::successful($seq);
    }

    /**
     * Performs an action and returns a successful Try if there were no exceptions thrown,
     * otherwise returns a failure try wrapping thrown exception.
     *
     * @param callable $action
     *
     * @return LetsTry
     */
    public static function perform(callable $action): LetsTry
    {
        try {
            return static::successful($action());
        } catch (Throwable $e) {
            return static::failure($e);
        }
    }

    /**
     * Creates a new `Success`.
     *
     * @param mixed $value A value to wrap
     *
     * @return LetsTry
     * @throws NullValueException
     */
    public static function successful($value): LetsTry
    {
        if (null === $value) {
            throw NullValueException::create();
        }

        return new class($value) extends LetsTry
        {
            /**
             * @var mixed
             */
            private $value;

            /**
             * @param mixed $value
             */
            public function __construct($value)
            {
                $this->value = $value;
            }

            public function map(callable $mapper): LetsTry
            {
                /** @psalm-suppress MissingClosureReturnType */
                return LetsTry::perform(function () use ($mapper) {
                    return $mapper($this->value);
                });
            }

            public function flatMap(callable $mapper): LetsTry
            {
                return $mapper($this->value);
            }

            public function recover(callable $supplier): LetsTry
            {
                return $this;
            }

            public function recoverWith(callable $supplier): LetsTry
            {
                return $this;
            }

            public function getOrElse(callable $supplier)
            {
                return $this->value;
            }

            public function orElse(callable $try): LetsTry
            {
                return $this;
            }

            public function filterOrElse(callable $predicate, callable $exceptionSupplier): LetsTry
            {
                /** @psalm-suppress MissingClosureReturnType */
                return LetsTry::perform(function () use ($predicate, $exceptionSupplier) {
                    if (true === $predicate($this->value)) {
                        return $this->value;
                    }

                    throw $exceptionSupplier();
                });
            }

            /**
             * @param callable $failure
             * @param callable $success
             *
             * @return mixed
             */
            public function fold(callable $failure, callable $success)
            {
                return $success($this->value);
            }

            public function toOptional(): Optional
            {
                return Optional::of($this->value);
            }

            public function forEach(callable $consumer): void
            {
                $consumer($this->value);
            }

            public function equals(LetsTry $try): bool
            {
                if ($this === $try) {
                    return true;
                }

                return $this->value === $try->getOrElse(noop());
            }

            public function isFailure(): bool
            {
                return false;
            }

            public function isSuccess(): bool
            {
                return true;
            }
        };
    }

    /**
     * Creates a new `Failure`.
     *
     * @param Throwable $exception An exception to wrap
     *
     * @return LetsTry
     */
    public static function failure(Throwable $exception): LetsTry
    {
        return new class($exception) extends LetsTry
        {
            /**
             * @var Throwable
             */
            private $exception;

            public function __construct(Throwable $exception)
            {
                $this->exception = $exception;
            }

            public function map(callable $mapper): LetsTry
            {
                return LetsTry::failure($this->exception);
            }

            public function flatMap(callable $mapper): LetsTry
            {
                return LetsTry::failure($this->exception);
            }

            public function recover(callable $supplier): LetsTry
            {
                /** @psalm-suppress MissingClosureReturnType */
                return LetsTry::perform(function () use ($supplier) {
                    return $supplier($this->exception);
                });
            }

            public function recoverWith(callable $supplier): LetsTry
            {
                return $supplier($this->exception);
            }

            public function getOrElse(callable $supplier)
            {
                return $supplier();
            }

            public function orElse(callable $try): LetsTry
            {
                return $try();
            }

            public function filterOrElse(callable $predicate, callable $exceptionSupplier): LetsTry
            {
                return LetsTry::failure($this->exception);
            }

            /**
             * @param callable $failure
             * @param callable $success
             *
             * @return mixed
             */
            public function fold(callable $failure, callable $success)
            {
                return $failure($this->exception);
            }

            public function toOptional(): Optional
            {
                return Optional::empty();
            }

            public function forEach(callable $consumer): void
            {
            }

            public function equals(LetsTry $try): bool
            {
                if ($this === $try) {
                    return true;
                }

                /** @psalm-suppress UndefinedPropertyFetch */
                return property_exists($try, 'exception') &&
                       $this->exception == $try->exception;
            }

            public function isFailure(): bool
            {
                return true;
            }

            public function isSuccess(): bool
            {
                return false;
            }
        };
    }
}
