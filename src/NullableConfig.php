<?php

declare(strict_types=1);

namespace TimDev\TypedConfig;

use LogicException;
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
    protected array $config;

    protected function __construct(array $config)
    {
        $this->config = $config;
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

    /** @return mixed */
    public function mixed(string $key)
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
                throw new KeyNotFound($key, $path, (string) $current[$k]);
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

    public function hash(string $key): ?array
    {
        $val = $this->mixed($key);
        if (!isset($val)) {
            return $val;
        }
        assert(is_array($val), "Expected a hash at '$key', but it isn't an array");
        assert(!array_is_list($val) || count($val) === 0, "Expected a hash at '$key', but found a list");
        return $val;
    }
}
