<?php

declare(strict_types=1);

namespace TimDev\Test\TypedConfig;

use PHPUnit\Framework\TestCase;
use TimDev\TypedConfig\Config;
use TimDev\TypedConfig\Exception\ContainsDottedKeys;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class ValidationTest extends TestCase
{
    public function testConstructorThrowsOnTopLevelDottedKeys(): void
    {
        $this->expectException(ContainsDottedKeys::class);
        $this->expectExceptionMessage('contains dotted keys');
        $this->expectExceptionMessage('invalid.key');
        new Config(['invalid.key' => "It's got ze dots!"]);
    }

    public function testConstructorThrowsOnNestedDottedKey(): void
    {
        $this->expectException(ContainsDottedKeys::class);
        $this->expectExceptionMessage('contains dotted keys');
        $this->expectExceptionMessage('a => c.d');
        $this->expectExceptionMessage('x => y => z.z');
        new Config([
            'a' => [
                'b' => 0,
                'c.d' => 'Nope'
            ],
            'x' => [
                'y' => [
                    'z.z' => 'Nope'
                ]
            ]
        ]);
    }

    public function testConstructorExceptionProvidesUsefulErrors(): void
    {
        try {
            new Config([
               'a' => [
                   'b' => 0,
                   'c.d' => 'Nope'
               ],
               'invalid.key' => "It's got ze dots!"
            ]);
        } catch (ContainsDottedKeys $e) {
            assertStringContainsString("Configuration array contains dotted keys", $e->getMessage());
            assertCount(2, $e->getErrors());
            assertSame("a => c.d", $e->getErrors()[0]);
            assertSame("invalid.key", $e->getErrors()[1]);
        }
    }
}
