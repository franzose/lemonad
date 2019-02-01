# Lemonad
It is a small repository containing implementations of some monads.

## Optional
`Optional` is useful when you don‘t exactly sure if you‘re dealing with an empty value or some meaningful one. It provides a clean and expressive API.

```php
<?php
declare(strict_types=1);

use Lemonad\Optional;
use function Lemonad\optional;

$empty = Optional::empty();
$empty->isPresent(); // false
$empty->isAbsent(); // true

$optOf42 = Optional::of(42);
$optOf42->isPresent(); // true
$optOf42->isAbsent(); // false

$null = optional(null);
$null->equals(Optional::empty()); // true
$null->isAbsent(); // true
$null->isPresent(); // false

$present = Optional::of(42)->filter(function (int $value) {
    return 42 === $value;
});

$present->isPresent(); // true
$present->isAbsent(); // false

$absent = Optional::of(42)->filter(function (int $value) {
    return 43 === $value;
});

$absent->isPresent(); // false
$absent->isAbsent(); // true

optional(42)->map(function (int $value) {
    return $value + 1;
})->get(); // 43

Optional::of(42)->flatMap(function (int $value) {
    return Optional::of($value + 1);
})->get(); // 43

Optional::of(42)->ifPresent(function (int $value) {
    echo $value;
}); // should output "42"

Optional::ofNullable(null)->ifPresentOrElse(function (int $value) {
    echo $value;
}, function () {
    echo '999';
}); // should output "999"

Optional::ofNullable(null)->or(function () {
    return Optional::of(42);
})->get(); // 42

Optional::ofNullable(null)->orElse(42); // 42

Optional::ofNullable(null)->orElseGet(function () {
    return 42;
}); // 42

Optional::ofNullable(null)->orElseThrow(function () {
    return new \InvalidArgumentException('No! No! No!');
}); // it will throw \InvalidArgumentException exception
```

## Maybe
Here are some examples of the Maybe monad:

```php
<?php
declare(strict_types=1);

use Lemonad\Maybe;
use function Lemonad\maybe;

$unknown = Maybe::unknown(); // unknown forever
$unknown->isKnown(); // false

$known = Maybe::definitely(42);
$known->isKnown(); // true

Maybe::of(null)->isKnown(); // false
Maybe::of(42)->isKnown(); // true
Maybe::of(42)->or(999); // 42
Maybe::of(42)->orElse(Maybe::definitely(999)); // same instance with value of 42
Maybe::of(42)->to(function (int $value) {
    return $value + 1;
}); // Maybe with value of 43

maybe(42)->query(function (int $value) {
    return 42 === $value;
}); // Maybe with value of boolean true

Maybe::of(42)->query(function (int $value) {
    return 43 === $value;
}); // Maybe with value of boolean false

Maybe::of(null)->or(42); // 42
Maybe::of(null)->orElse(Maybe::definitely(42)); // Maybe with value of 42
Maybe::of(null)->to(function () {}); // always a new instance of 'unknown' Maybe
Maybe::of(null)->query(function () {}); // always a new instance of 'unknown' Maybe
Maybe::of(null)->equals(Maybe::unknown()); // equals is always false

echo Maybe::definitely('Brian')->to(function (string $name) {
    return $name . ' Adams';
}); // will output "Brian Adams"
```

## Try
A Try represents a computation that may either throw an exception or return a value. As `try` is a reserved keyword in PHP and class names are case insensitive, I could not use `Try` for the class name, so I named it `LetsTry`.

```php
<?php
declare(strict_types=1);

use Lemonad\LetsTry;
use function Lemonad\lets_try;
use function Lemonad\noop;
use RuntimeException;

LetsTry::perform(function () {
    return 42;
})->getOrElse(noop()); // 42

lets_try(function () {
    throw new RuntimeException('Argh!');
})->getOrElse(function () {
    return 42;
}); // 42

LetsTry::successful(42)->getOrElse(noop()); // 42
LetsTry::successful(null); // will throw Lemonad\Exception\NullValueException

LetsTry::failure(new RuntimeException('Argh!'))
    ->getOrElse(function () {
        return 42;
    }); // 42

// Successful, do mapping
lets_try(function () {
    return 42;
})->map(function (int $value) {
    return $value + 1;
})->getOrElse(noop()); // 43

lets_try(function () {
    throw new RuntimeException('Argh!');
})->map(function () {
    // this callback will not be called,
    // new instance of `Failure` will be returned instead
});

// Successful, do mapping
lets_try(function () {
    return 42;
})->flatMap(function (int $value) {
    return lets_try(function () use ($value) {
        return $value + 1;
    });
})->getOrElse(noop()); // 43

lets_try(function () {
    throw new RuntimeException('Argh!');
})->flatMap(function () {
    // again, this callback will not be called,
    // new instance of `Failure` will be returned instead
});

// There's nothing to recover,
// so the callback will not be called
lets_try(function () {
    return 42;
})->recover(function () {
    return 43;
})->getOrElse(noop()); // 42

// Again, there's nothing to recover,
// so the callback will not be called
lets_try(function () {
    return 42;
})->recoverWith(function () {
    return lets_try(function () {
        return 43;
    });
})->getOrElse(noop()); // 42

// Here, exception was thrown,
// so we need to recover from that
lets_try(function () {
    throw new RuntimeException('Argh!');
})->recover(function (RuntimeException $exception) {
    return $exception->getMessage();
}); // Argh!

// Again, exception was thrown,
// so we need to recover from that
lets_try(function () {
    throw new RuntimeException('Argh!');
})->recoverWith(function (RuntimeException $exception) {
    return lets_try(function () use ($exception) {
        return $exception->getMessage();
    });
}); // Argh!

lets_try(function () {
    return 42;
})->orElse(function () {
    return lets_try(function () {
        return 43;
    });
})->getOrElse(noop()); // 42 as the Try is `Success`

lets_try(function () {
    throw new RuntimeException('Argh!');
})->orElse(function () {
    return lets_try(function () {
        return 43;
    });
})->getOrElse(noop()); // 43 as the Try was `Failure`

// Will perform another Try
lets_try(function () {
    return 42;
})->filterOrElse(
    function (int $value) {
        return 42 === $value;
    },
    function () {
        return new RuntimeException('Argh!');
    }
);

// Will throw provided RuntimeException,
// as the predicate is unsatisfied
lets_try(function () {
    return 42;
})->filterOrElse(
    function (int $value) {
        return 99 === $value;
    },
    function () {
        return new RuntimeException('Argh!');
    }
);

// Will just return another `Failure` Try
lets_try(function () {
    throw new RuntimeException('Argh!');
})->filterOrElse(
    function (int $value) {
        return 99 === $value;
    },
    function () {
        return new RuntimeException('Argh!');
    }
);

lets_try(function () {
    return 42;
})->fold(noop(), function (int $value) {
    return $value + 1;
}); // 43

lets_try(function () {
    throw new RuntimeException('Argh!');
})->fold(
    function (RuntimeException $exception) {
        return $exception->getMessage();
    },
    function (int $value) {
        return $value + 1;
    }
); // Argh!

lets_try(function () {
    return 42;
})->toOptional(); // Optional which contains value of 42

lets_try(function () {
    throw new RuntimeException('Argh!');
})->toOptional(); // An empty Optional

lets_try(function () {
    return 42;
})->forEach(function (int $value) {
    // do something
});

lets_try(function () {
    throw new RuntimeException('Argh!');
})->forEach(function () {
    // for `Failure`s it is a noop
});

lets_try(function () {
    return 42;
})->isSuccess(); // true

lets_try(function () {
    throw new RuntimeException('Argh!');
})->isFailure(); // true
```
