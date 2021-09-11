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
    /**
     * If a top-level element with this key is set to boolean true, the config
     * data are assumed valid, and all checks are skipped. The array returned by
     * validate() will always have this key set. This behavior exists to enable
     * fast instantiation of cached known-good data.
     */
    public const FLAG_VALID = '_TimDevTypedConfig_Valid';

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

    /**
     * @param array              $config
     * @param list<string>       $path
     * @param list<list<string>> $dotted
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

    private static function assertNoDottedKeys(array $array): void
    {
        $dotted = self::findErrors($array);
        if (count($dotted) > 0) {
            throw ContainsDottedKeys::from($dotted);
        }
    }

    /**
     * Sets validity flag in $array and returns it, or throws if array contains
     * dotted-string keys.
     *
     * @param array $array
     * @return array
     * @throws ContainsDottedKeys
     */
    public static function validate(array &$array): array
    {
        // only skip validation if flag is set to boolean true.
        if ($array[self::FLAG_VALID] ?? false !== true) {
            self::assertNoDottedKeys($array);
            $array[self::FLAG_VALID] = true;
        }
        return $array;
    }
}
