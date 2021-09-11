<?php

declare(strict_types=1);

namespace TimDev\TypedConfig\Exception;

use RuntimeException;

/**
 * Thrown when attempt to dereference $key fails.
 */
class KeyNotFound extends RuntimeException
{
    /**
     * @param string      $key        The full key we were looking for.
     * @param string      $path       The sub-path of $key where the error occurred.
     * @param string|null $foundValue The string-ified value found at $path, nor null if the key wasn't set.
     *                                Pass this value when you were expecting
     */
    public function __construct(string $key, string $path, ?string $foundValue = null)
    {
        parent::__construct(
            $foundValue === null
                ? "Couldn't find key '$key' in the array. No value found at: '$path'"
                : "Couldn't find key '$key'. Encountered value '$foundValue' at: '$path'"
        );
    }
}
