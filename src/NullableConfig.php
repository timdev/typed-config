<?php

declare(strict_types=1);

namespace TimDev\TypedConfig;

use LogicException;
use TimDev\TypedConfig\Exception\ContainsDottedKeys;
use TimDev\TypedConfig\Exception\InvalidConfigKey;
use TimDev\TypedConfig\Exception\KeyNotFound;

use function assert;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

class NullableConfig
{
    /** @var array<string,mixed> */
    protected array $config;

    /** @param array<mixed> $config */
    protected function __construct(array $config)
    {
        $this->config = self::validate($config);
    }

    public function string(string $key): ?string
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_string($val));

        return $val;
    }

    public function mixed(string $key): mixed
    {
        $key = trim($key);

        if ('' === $key) {
            throw new InvalidConfigKey('Config key cannot be an empty string.');
        }

        if (str_contains($key, '..')) {
            throw new InvalidConfigKey("Key '$key' is invalid. Keys cannot contain consecutive dots.");
        }

        $parts = explode('.', $key);
        $current = $this->config;

        foreach ($parts as $idx => $k) {
            $path = implode('.', array_slice($parts, 0, $idx + 1));

            if (! array_key_exists($k, $current)) {
                // Dead end: nothing at $k
                throw new KeyNotFound($key, $path);
            }

            // Success condition: we've traversed the entire $path!
            if ($path === $key) {
                return $current[$k];
            }

            if (!is_array($current[$k])) {
                // Dead End: Can't descend lower than $path, because it points at a non-array
                throw new KeyNotFound($key, $path, var_export($current[$k], true));
            }

            // We have to (and can) go deeper!
            $current = $current[$k];
        }
        // @codeCoverageIgnoreStart
        throw new LogicException('This is a bug.');
        // @codeCoverageIgnoreEnd
    }

    public function bool(string $key): ?bool
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_bool($val));
        return $val;
    }

    public function int(string $key): ?int
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_int($val));
        return $val;
    }

    public function float(string $key): ?float
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_float($val));
        return $val;
    }

    /** @return list<mixed>|null */
    public function list(string $key): ?array
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_array($val), "Expected a list at '$key', but it isn't an array");
        assert(array_is_list($val), "Expected a list at '$key', but found a hash");
        /** @var list<mixed> $val */
        return $val;
    }

    public function config(string $key): Config
    {
        $config = new Config([]);
        $config->config = $this->hash($key) ?? [];
        $config->nullable->config = $config->config;
        return $config;
    }

    /** @return array<string,mixed>|null */
    public function hash(string $key): ?array
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_array($val), "Expected a hash at '$key', but it isn't an array");
        self::assertHasOnlyStringKeys($val);
        return $val;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $clone = $this->config;
        unset($clone[self::FLAG_VALID]);
        return $clone;
    }

    /**
     * @param array<mixed> $array
     * @return array<string,mixed>
     */
    private static function dot(array $array, string $delim = '.', string $prepend = ''): array
    {
        /** @var list<array<string,mixed>> $results */
        $results = [];

        /** @var mixed $value */
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results[] = self::dot($value, $delim, $prepend . $key . $delim);
            } else {
                $results[] = [ $prepend . $key => $value ];
//                $results[][$prepend . $key] = $value;
            }
        }

        /** @psalm-var array<string,mixed> */
        return array_merge(...$results);
    }

    /**
     * Returns a flattened array of the configuration, with keys constructed
     * using $delimiter as glue.
     *
     * @param string $delimiter
     * @return array<string,mixed>
     */
    public function toFlatArray(string $delimiter = '.'): array
    {
        return self::dot($this->toArray(), $delimiter);
    }

    /**
     * If a top-level element with this key is set to boolean true, the config
     * data are assumed valid, and all checks are skipped. The array returned by
     * validate() will always have this key set. This behavior exists to enable
     * fast instantiation of cached known-good data.
     */
    public const string FLAG_VALID = '_TimDevTypedConfig_Valid';

    /**
     * Sets validity flag in $array and returns it, or throws if array contains
     * dotted-string keys.
     *
     * @param array<mixed> $array
     * @return array<string,mixed>
     * @throws ContainsDottedKeys
     */
    public static function validate(array &$array): array
    {
        // only skip validation if flag is set to boolean true.
        if (($array[self::FLAG_VALID] ?? false) !== true) {
            self::assertNoDottedKeys($array);
            self::assertHasOnlyStringKeys($array);
            $array[self::FLAG_VALID] = true;
        }
        /** @var array<string,mixed> $array */
        return $array;
    }

    /**
     * @param array<mixed>          $config
     * @param list<array-key>       $path
     * @param list<list<array-key>> $dotted
     * @return list<string>
     */
    private static function findErrors(array $config, array $path = [], array &$dotted = []): array
    {
        /** @var mixed $v */
        foreach ($config as $k => $v) {
            if (is_string($k) && str_contains($k, '.')) {
                $dotted[] = [...$path, $k];
            }
            if (is_array($v)) {
                self::findErrors($v, [...$path, $k], $dotted);
            }
        }

        return array_map(
            static fn($d) => implode(' => ', $d),
            $dotted
        );
    }

    /**
     * @param array<mixed> $array
     */
    private static function assertNoDottedKeys(array $array): void
    {
        $dotted = self::findErrors($array);
        if (count($dotted) > 0) {
            throw ContainsDottedKeys::from($dotted);
        }
    }

    /**
     * @param array<mixed> $array
     * @psalm-assert array<string,mixed> $array
     */
    protected static function assertHasOnlyStringKeys(array $array): void
    {
        foreach (array_keys($array) as $key) {
            assert(is_string($key), "Expected string keys in array, but found a non-string key");
        }
    }
}
