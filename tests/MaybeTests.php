<?php
declare(strict_types=1);

namespace Lemonad\Tests;

use Lemonad\Exception\NullValueException;
use Lemonad\Maybe;
use PHPUnit\Framework\TestCase;

final class MaybeTests extends TestCase
{
    public static function testOf(): void
    {
        static::assertFalse(Maybe::of(null)->isKnown());
        static::assertTrue(Maybe::of(42)->isKnown());
    }

    public static function testUnknown(): void
    {
        $unknown = Maybe::unknown();
        $maybe42 = Maybe::definitely(42);

        $supplier = function () {
            return 42;
        };

        $callable = function () {
            //
        };

        static::assertFalse($unknown->isKnown());
        static::assertEquals(42, $unknown->or(42));
        static::assertEquals(42, $unknown->or($supplier));
        static::assertTrue($unknown->orElse($maybe42)->equals($maybe42));
        static::assertFalse($unknown->equals(Maybe::unknown()));
        static::assertFalse($unknown->equals($maybe42));
        static::assertFalse($unknown->to($callable)->isKnown());
        static::assertFalse($unknown->query($callable)->isKnown());
    }

    public function testDefinitelyShouldNotAcceptNullValue(): void
    {
        $this->expectException(NullValueException::class);

        Maybe::definitely(null);
    }

    public function testDefinitely(): void
    {
        $maybe = Maybe::definitely(42);
        $maybe84 = Maybe::definitely(84);
        $mapper = function (int $value) {
            return $value + 1;
        };

        $predicate = function (int $value) {
            return 42 === $value;
        };

        static::assertTrue($maybe->isKnown());
        static::assertTrue($maybe->equals(Maybe::definitely(42)));
        static::assertFalse($maybe->equals(Maybe::definitely(43)));
        static::assertFalse($maybe->equals(Maybe::unknown()));
        static::assertEquals(42, $maybe->or(84));
        static::assertSame($maybe, $maybe->orElse($maybe84));
        static::assertEquals(43, $maybe->to($mapper)->or(84));
        static::assertTrue($maybe->query($predicate)->or(false));
        static::assertFalse(Maybe::definitely(43)->query($predicate)->or(true));
    }
}
