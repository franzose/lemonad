<?php
declare(strict_types=1);

namespace Lemonad\Tests;

use Lemonad\Exception\NoSuchValueException;
use Lemonad\Exception\NullValueException;
use Lemonad\Optional;
use PHPUnit\Framework\TestCase;

final class OptionalTests extends TestCase
{
    public function testOfShouldNotAllowNull(): void
    {
        $this->expectException(NullValueException::class);

        Optional::of(null);
    }

    public function testOfShouldReturnNewInstances(): void
    {
        $optOne = Optional::of(42);
        $optTwo = Optional::of(42);

        static::assertNotSame($optOne, $optTwo);
        static::assertTrue($optTwo->equals($optOne));
    }

    public function testOfNullableShouldReturnEmptyOptional(): void
    {
        static::assertTrue(Optional::ofNullable(null)->isAbsent());
        static::assertFalse(Optional::ofNullable(null)->isPresent());
    }

    public function testOfNullableShouldReturnAnOptional(): void
    {
        static::assertTrue(Optional::ofNullable(42)->isPresent());
        static::assertFalse(Optional::ofNullable(42)->isAbsent());
    }

    /**
     * @dataProvider filterTestDataProvider
     *
     * @param mixed $value
     * @param callable $filter
     */
    public function testFilterShouldReturnEmptyOptional($value, callable $filter): void
    {
        static::assertTrue(Optional::ofNullable($value)->filter($filter)->isAbsent());
    }

    public function filterTestDataProvider(): array
    {
        return [
            [
                null,
                function () {
                    //
                }
            ],
            [
                42,
                function (int $value) {
                    return 43 === $value;
                }
            ]
        ];
    }

    public function testFilterShouldReturnOriginalOptional(): void
    {
        $optionalOf42 = Optional::of(42);
        $filteredOptional = $optionalOf42->filter(function (int $value) {
            return 42 === $value;
        });

        static::assertTrue($filteredOptional->isPresent());
        static::assertSame($optionalOf42, $filteredOptional);
    }

    public function testMapShouldReturnEmptyOptional(): void
    {
        $optional = Optional::ofNullable(null)->map(function () {
            return 42;
        });

        static::assertTrue($optional->isAbsent());
    }

    public function testMapShouldReturnNewOptional(): void
    {
        $optional = Optional::of(42)->map(function (int $value) {
            return $value + 1;
        });

        static::assertTrue($optional->isPresent());
        static::assertTrue($optional->equals(Optional::of(43)));
    }

    public function testFlatMapShouldReturnEmptyOptional(): void
    {
        $optional = Optional::ofNullable(null)->flatMap(function () {
            return Optional::of(42);
        });

        static::assertTrue($optional->isAbsent());
    }

    public function testFlatMapShouldReturnNewOptional(): void
    {
        $optional = Optional::of(42)->flatMap(function (int $value) {
            return Optional::of($value + 1);
        });

        static::assertTrue($optional->isPresent());
        static::assertTrue($optional->equals(Optional::of(43)));
    }

    public function testGetShouldThrowIfOptionalIsEmpty(): void
    {
        $this->expectException(NoSuchValueException::class);

        Optional::ofNullable(null)->get();
    }

    public function testGetShouldReturnUnderlyingValue(): void
    {
        static::assertEquals(42, Optional::of(42)->get());
    }

    /**
     * @dataProvider ifPresentValueDataProvider
     *
     * @param int $initial
     * @param mixed $value
     * @param int $expected
     */
    public function testIfPresent(int $initial, $value, int $expected): void
    {
        $foo = $initial;

        Optional::ofNullable($value)->ifPresent(function ($value) use (&$foo) {
            $foo = $value;
        });

        static::assertEquals($expected, $foo);
    }

    public function ifPresentValueDataProvider(): array
    {
        return [
            [43, null, 43],
            [43, 42, 42]
        ];
    }

    /**
     * @dataProvider ifPresentOrElseDataProvider
     *
     * @param mixed $value
     * @param int $expected
     */
    public function testIfPresentOrElse($value, int $expected): void
    {
        $foo = null;

        $consumer = function ($value) use (&$foo) {
            $foo = $value;
        };

        $action = function () use (&$foo) {
            $foo = 43;
        };

        Optional::ofNullable($value)->isPresentOrElse($consumer, $action);

        static::assertEquals($expected, $foo);
    }

    public function ifPresentOrElseDataProvider(): array
    {
        return [
            [null, 43],
            [42, 42]
        ];
    }

    /**
     * @dataProvider orDataProvider
     *
     * @param mixed $value
     * @param callable $supplier
     * @param int $expected
     *
     * @throws NoSuchValueException
     */
    public function testOr($value, callable $supplier, int $expected): void
    {
        static::assertEquals($expected, Optional::ofNullable($value)->or($supplier)->get());
    }

    public function orDataProvider(): array
    {
        $supplier = function () {
            return Optional::of(42);
        };

        return [
            [null, $supplier, 42],
            [999, $supplier, 999]
        ];
    }

    /**
     * @dataProvider orElseDataProvider
     *
     * @param mixed $value
     * @param mixed $other
     * @param mixed $expected
     */
    public function testOrElse($value, $other, $expected): void
    {
        static::assertEquals($expected, Optional::ofNullable($value)->orElse($other));
    }

    public function orElseDataProvider(): array
    {
        return [
            [null, 42, 42],
            [84, 42, 84]
        ];
    }

    /**
     * @dataProvider orElseGetDataProvider
     *
     * @param mixed $value
     * @param callable $supplier
     * @param mixed $expected
     */
    public function testOrElseGet($value, callable $supplier, $expected): void
    {
        static::assertEquals($expected, Optional::ofNullable($value)->orElseGet($supplier));
    }

    public function orElseGetDataProvider(): array
    {
        $supplier = function () {
            return 42;
        };

        return [
            [null, $supplier, 42],
            [84, $supplier, 84]
        ];
    }

    public function testOrElseThrowShouldReturnValue(): void
    {
        $value = Optional::of(42)->orElseThrow(function () {
            return new \InvalidArgumentException('Argh!');
        });

        static::assertEquals(42, $value);
    }

    public function testOrElseThrowShouldThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argh!');

        Optional::ofNullable(null)->orElseThrow(function () {
            return new \InvalidArgumentException('Argh!');
        });
    }
}
