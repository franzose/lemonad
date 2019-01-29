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
