<?php
declare(strict_types=1);

namespace Lemonad\Tests;

use Lemonad\Exception\NullValueException;
use Lemonad\LetsTry;
use function Lemonad\noop;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LetsTryTest extends TestCase
{
    public function testSequenceShouldReturnTheFirstFailure(): void
    {
        $try = LetsTry::sequence([
            LetsTry::successful(1),
            static::failure('foo'),
            static::failure('bar'),
            LetsTry::successful(2),
            LetsTry::successful(3),
        ]);

        static::assertTrue($try->isFailure());
        static::assertEquals('foo', $try->fold(function (RuntimeException $exception): string {
            return $exception->getMessage();
        }, noop()));
    }

    public function testSequenceShouldBeSuccessful(): void
    {
        $try = LetsTry::sequence([
            LetsTry::successful(1),
            LetsTry::successful(2),
            LetsTry::successful(3),
        ]);

        static::assertTrue($try->isSuccess());
        static::assertEquals([1, 2, 3], $try->fold(noop(), function (array $value): array {
            return $value;
        }));
    }

    public function testToPerform(): void
    {
        $tryOf42 = LetsTry::perform(supplier(42));
        $tryOfException = LetsTry::perform(function (): void {
            throw new RuntimeException('');
        });

        static::assertTrue($tryOf42->isSuccess());
        static::assertFalse($tryOf42->isFailure());
        static::assertFalse($tryOfException->isSuccess());
        static::assertTrue($tryOfException->isFailure());
    }

    public function testSuccessfulShouldNotAllowNullValue(): void
    {
        $this->expectException(NullValueException::class);

        LetsTry::successful(null);
    }

    public function testMap(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $supplier = supplier(43);

        $actual = $successful->map($supplier)->getOrElse(noop());

        static::assertEquals(43, $actual);
        static::assertTrue($failed->map($supplier)->isFailure());
    }

    public function testFlatMap(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();

        static::assertEquals(43, $successful->flatMap(function (int $value): LetsTry {
            return LetsTry::successful($value + 1);
        })->getOrElse(noop()));

        static::assertTrue($failed->flatMap(noop())->isFailure());
    }

    public function testRecover(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $supplier = supplier(99);

        static::assertEquals(42, $successful->recover($supplier)->getOrElse(noop()));
        static::assertEquals(99, $failed->recover($supplier)->getOrElse(noop()));
    }

    public function testRecoverWith(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $supplier = supplier(LetsTry::successful(99));

        static::assertEquals(42, $successful->recoverWith($supplier)->getOrElse(noop()));
        static::assertEquals(99, $failed->recoverWith($supplier)->getOrElse(noop()));
    }

    public function testGetOrElse(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $supplier = supplier(99);

        static::assertEquals(42, $successful->getOrElse($supplier));
        static::assertEquals(99, $failed->getOrElse($supplier));
    }

    public function testOrElse(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $supplier = supplier(LetsTry::successful(99));

        static::assertSame($successful, $successful->orElse($supplier));
        static::assertEquals(99, $failed->orElse($supplier)->getOrElse(noop()));
    }

    public function testFilterOrElse(): void
    {
        $successful = LetsTry::successful(42);

        $shouldBe42 = $successful
            ->filterOrElse(equality_supplier(42), static::exception())
            ->getOrElse(noop());

        static::assertEquals(42, $shouldBe42);
    }

    /**
     * @dataProvider filterOrElseDataProvider
     *
     * @param LetsTry $try
     */
    public function testFilterOrElseShouldThrowException(LetsTry $try): void
    {
        $filter = equality_supplier(99);

        $shouldBeFailure = $try->filterOrElse($filter, static::exception())->isFailure();

        static::assertTrue($shouldBeFailure);
    }

    public function filterOrElseDataProvider(): array
    {
        return [
            [LetsTry::successful(42)],
            [static::failure()]
        ];
    }

    public function testFold(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();
        $onFailure = supplier('failure');
        $onSuccess = supplier('success');

        static::assertEquals('success', $successful->fold($onFailure, $onSuccess));
        static::assertEquals('failure', $failed->fold($onFailure, $onSuccess));
    }

    public function testToOptional(): void
    {
        $successful = LetsTry::successful(42);
        $failed = static::failure();

        static::assertTrue($successful->toOptional()->isPresent());
        static::assertTrue($failed->toOptional()->isAbsent());
    }

    public function testForEach(): void
    {
        $foo = 42;
        $successful = LetsTry::successful($foo);

        $successful->forEach(mutation_supplier($foo, 99));

        static::assertEquals(99, $foo);
    }

    public function testEquals(): void
    {
        $successful = LetsTry::successful(42);

        static::assertTrue($successful->equals(LetsTry::successful(42)));
        static::assertFalse($successful->equals(static::failure()));
        static::assertTrue(static::failure()->equals(static::failure()));
    }

    private static function failure(string $message = ''): LetsTry
    {
        return LetsTry::failure(new RuntimeException($message));
    }

    private static function exception(): callable
    {
        return function (): RuntimeException {
            return new RuntimeException('');
        };
    }
}
