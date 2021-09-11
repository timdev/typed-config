# timdev/typed-config

## What's This?

A small library to provide strictly typed access to data in an array, intended
for use with a nested array of application configuration data. Nested values
are dereferenced with dotted strings.

## Usage

### Install

Install this package with composer:

```bash
composer require timdev/typed-config
```

### Usage

In a nutshell:

```php
declare(strict_types=1);

// This should already be set in your php.ini, but this library depends on it,
// so you should verify that's set.
ini_set('assert.exception', 1);

// Could come from anywhere.
$configArray = [
    'myapp' => [
        'some-api' => [
            'baseUrl' => 'https://example.com/v1/',
            'timeout' => 30
        ],
        'allowed_ips' => [
            '192.168.0.1',
            '192.168.0.50'
        ],
        'optional_value' => null        
    ]
];

$config = new Config($configArray);

// Methods have narrow return type declarations:
$baseUrl = $config->string('myapp.some-api.baseUrl');
$timeout = $config->int('myapp.some-api.timeout');
$allowed = $config->list('myapp.allowed_ips');

// Attempting to access an undefined value throws.
$port = $config->int('invalid.key');  // Throws \TimDev\TypedConfig\Exception\KeyNotFound

// Note that the main methods do not have nulalble return types.
$optional = $config->bool('myapp.optional_value'); // Also throws!

// Use ->nullable to access valeus that are defined, but might be null:
$optional = $config->nullable->bool('myapp.optional_value'); 
```

## Assumptions and Design

In order to use this library effectively, it's important to understand its
design and the assumptions that inform that design.

### Assumption: All valid configuration values are defined

As demonstrated in example above, client code attempting to access an undefined
value will throw. Our opinion is that a library or application should ship a
complete default configuration. This is always a win for users. This design
means that users are alerted early when they try to access an invalid key due to
a typo or transcription error.

If you think you need an optional configuration parameter, you should instead
provide a default such as `null` or perhaps `[]`.

### Design: Relies on PHP's `assert()`

[assert] is a language construct with a very useful mechanism: they are
optimized out in production. This library uses `assert()` to type-check your
configuration values before they're returned from the various accessor-methods.

If you're unfamiliar with the configuration values that affect how [assert]
works, you should review the documentation and your environment's configuration
before using this library. The short version is that you'll want  to ensure that
`assert.exception = 1` and `zend.assertion = 1` in your development and testing
environments.

Because this library depends on assert(), the type checks are optimized away in
production environments (where `zend.assertion = 0`). This reduces some overhead
but means that configuration access that would throw an `AssertionError` in
development will instead:

1. Throw a `TypeError`, or
2. Not throw anything, in the case of a hash/list mismatch.

## Motivation

We developed this while building an application with [mezzio], where
configuration data are generally pulled from an array. Arrays are nice, but
often cause you to have to:

1. Code defensively whenever you pull some value from your `$config` array.
2. Add assertions or comments to let your static analyzer of choice figure out
   what is happening.

This library is an attempt to improve that situation.

## Alternatives

There are other libraries that approach the problem from different perspectives.

[selective-php/config](https://github.com/selective-php/config) takes a similar 
approach in that it provides strictly typed methods to fetch (possibly nullable)
values. Unlike this library, it enforces types by casting values as it 
returns them. We take a more strict posture, and consider mismatched types to be
an error in the software. Its nullable-typed methods return null if a config key
is not defined, while we consider that to be an error as well.

[setbased/typed-config](https://github.com/SetBased/php-typed-config) is also 
similar. Like the previously discussed library, it will attempt to cast whatever
it finds in the config data to the required type, and is more permissive about
undefined config keys than this library is.

[league/config](https://config.thephpleague.com/) takes an even more strict
approach by helping you define a strict schema for your configuration data. We
really like that approach (at least for some contexts), but are somewhat
bewildered that it provides a single `get(string $key): mixed` accessor.

## To Do

- [ ] Do some benchmarks and determine if our usage of [assert] is actually 
      providing any tangible benefit.


[mezzio]: https://docs.mezzio.dev/
[assert]: https://www.php.net/assert
