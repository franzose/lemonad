# Lemonad
It is a small repository containing implementations of some monads.

## Optional
`Optional` is useful when you don‘t exactly sure if you‘re dealing with an empty value of some meaningful one. It provides a clean and expressive API.

```php
<?php
declare(strict_types=1);

use Lemonad\Optional;

$empty = Optional::empty();
$empty->isPresent(); // false
$empty->isAbsent(); // true

$optOf42 = Optional::of(42);
$optOf42->isPresent(); // true
$optOf42->isAbsent(); // false

$null = Optional::ofNullable(null);
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

Optional::of(42)->map(function (int $value) {
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
