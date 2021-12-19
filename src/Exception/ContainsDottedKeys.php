<?php

declare(strict_types=1);

namespace TimDev\TypedConfig\Exception;

use InvalidArgumentException;
use Throwable;

final class ContainsDottedKeys extends InvalidArgumentException
{
    private function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    /**
     * @var list<string>
     */
    private array $errors = [];

    /**
     * @param list<string> $errors
     * @return static
     */
    public static function from(array $errors): self
    {
        $e = new self("Configuration array contains dotted keys:\n\n" . implode("\n", $errors));
        $e->errors = $errors;
        return $e;
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
