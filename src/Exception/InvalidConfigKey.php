<?php

declare(strict_types=1);

namespace TimDev\TypedConfig\Exception;

use InvalidArgumentException;

/**
 * Thrown when attempt to dereference $key fails.
 */
final class InvalidConfigKey extends InvalidArgumentException
{
}
