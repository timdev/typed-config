<?php

declare(strict_types=1);

namespace TimDev\TypedConfig;

use TimDev\TypedConfig\Exception\ContainsDottedKeys;

use function assert;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

final class Config extends NullableConfig
{
    /** @readonly */
    public NullableConfig $nullable;

    public function __construct(array $config)
    {
        parent::__construct(self::validate($config));
        $this->nullable = new NullableConfig($config);
    }

    public function string(string $key): string
    {
        $val = $this->mixed($key);
        assert(is_string($val));
        return $val;
    }

    public function bool(string $key): bool
    {
        $val = $this->mixed($key);
        assert(is_bool($val));
        return $val;
    }

    public function int(string $key): int
    {
        $val = $this->mixed($key);
        assert(is_int($val));
        return $val;
    }

    public function float(string $key): float
    {
        $val = $this->mixed($key);
        assert(is_float($val));
        return $val;
    }

    /** @return list<mixed> */
    public function list(string $key): array
    {
        $val = $this->mixed($key);
        assert(is_array($val), "Expected a list at '$key', but it isn't an array");
        assert(array_is_list($val), "Expected a list at '$key', but found a hash");
        /** @var list<mixed> $val */
        return $val;
    }

    /**
     * @return array<string|int,mixed>
     */
    public function hash(string $key): array
    {
        $val = $this->mixed($key);
        assert(is_array($val), "Expected a hash at '$key', but it isn't an array");
        assert(!array_is_list($val) || count($val) === 0, "Expected a hash at '$key', but found a list");
        return $val;
    }
}
