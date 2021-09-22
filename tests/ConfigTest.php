<?php

declare(strict_types=1);

namespace TimDev\Test\TypedConfig;

use AssertionError;
use PHPUnit\Framework\TestCase;
use TimDev\TypedConfig\Config;
use TimDev\TypedConfig\Exception\InvalidConfigKey;
use TimDev\TypedConfig\Exception\KeyNotFound;

use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class ConfigTest extends TestCase
{
    /** @var array<mixed> */
    private array $testConfig = [
        'flag' => true,
        'text' => 'a string',
        'timeout' => 600,
        'gravity_acceleration' => 9.8,
        'key_for_list' => ['a', 'list', 'of', 'strings'],
        'key_for_hash' => ['foo' => 'bar', 'baz' => 'qux'],
        'ports' => [
            'http' => 80,
            'https' => 443,
            'ssh' => 22,
            'dns' => 53
        ],
        'a' => ['somewhat' => ['deeply' => ['nested' => ['string' => 'ACTUALLY, A ROPE']]]],
        'expect' => ['to' => 5],
        'always_null' => null,
        'spaces are' => ['actually fine' => 'see?'],
    ];

    public function testCanReturnExpectedTypes(): void
    {
        $config = new Config($this->testConfig);
        assertTrue($config->bool('flag'));
        assertSame('a string', $config->string('text'));
        assertSame(600, $config->int('timeout'));
        assertSame(9.8, $config->float('gravity_acceleration'));
        assertIsArray($config->list('key_for_list'));
        assertIsArray($config->hash('key_for_hash'));
    }

    public function testCanGetAnythingAsMixed(): void
    {
        $config = new Config($this->testConfig);
        assertTrue($config->mixed('flag'));
        assertIsArray($config->mixed('key_for_list'));
        assertIsArray($config->mixed('key_for_hash'));
        assertSame(9.8, $config->mixed('gravity_acceleration'));
    }

    public function testThrowsOnTypeMismatch(): void
    {
        $this->expectException(AssertionError::class);
        (new Config($this->testConfig))->string('flag');
    }

    public function testThrowsOnListHashMismatch(): void
    {
        $this->expectException(AssertionError::class);
        (new Config($this->testConfig))->list('key_for_hash');
    }

    public function testThrowsOnHashListMismatch(): void
    {
        $this->expectException(AssertionError::class);
        (new Config($this->testConfig))->hash('key_for_list');
    }

    public function testUnderstandsBasicDotNotation(): void
    {
        $config = new Config($this->testConfig);
        $http = $config->int('ports.http');
        assertSame(80, $http);
        $rope = $config->string('a.somewhat.deeply.nested.string');
        assertSame('ACTUALLY, A ROPE', $rope);
    }

    public function testProvidesHelpfulExceptionMessagesForNonexistantKeys(): void
    {
        $this->expectException(KeyNotFound::class);
        $this->expectExceptionMessage(
            "Couldn't find key 'a.somewhat.nested.string' in the array. No value found at: 'a.somewhat.nested'"
        );
        // missing "deeply":
        (new Config($this->testConfig))->string('a.somewhat.nested.string');
    }

    public function testProvidesHelpfulExceptionMessageForUnexpectedNonArrayValue(): void
    {
        // what happens when we encounter something that isn't an array?
        $this->expectException(KeyNotFound::class);
        $this->expectExceptionMessage("Encountered value '5' at: 'expect.to'");
        (new Config($this->testConfig))->string('expect.to.find.more.depth.than.exists');
    }

    public function testThrowsOnEmptyStringConfigKey(): void
    {
        $this->expectException(InvalidConfigKey::class);
        (new Config($this->testConfig))->mixed('');
    }

    public function testThrowsOnConsecutiveDotsInKey(): void
    {
        $this->expectException(InvalidConfigKey::class);
        (new Config($this->testConfig))->mixed('this..is.not.allowed');
    }

    public function testCanReturnAnInstanceOfItself(): void
    {
        $subConfig = (new Config($this->testConfig))->config('a.somewhat');
        assertSame('ACTUALLY, A ROPE', $subConfig->string('deeply.nested.string'));
    }

    public function testSpacesAreAllowedInKeys(): void
    {
        $result = (new Config($this->testConfig))->string('spaces are.actually fine');
        assertSame("see?", $result);
    }

    public function testNullableCanReturnNullValues(): void
    {
        $config = new Config($this->testConfig);
        assertNull($config->nullable->mixed('always_null'));
        assertNull($config->nullable->string('always_null'));
        assertNull($config->nullable->hash('always_null'));
        assertNull($config->nullable->list('always_null'));
        assertNull($config->nullable->bool('always_null'));
        assertNull($config->nullable->int('always_null'));
        assertNull($config->nullable->float('always_null'));
    }

    public function testNullableCanReturnExpectedTypes(): void
    {
        $config = new Config($this->testConfig);
        $maybe = $config->nullable;
        assertTrue($maybe->bool('flag'));
        assertSame('a string', $maybe->string('text'));
        assertSame(600, $maybe->int('timeout'));
        assertSame(9.8, $maybe->float('gravity_acceleration'));
        assertIsArray($maybe->list('key_for_list'));
        assertIsArray($maybe->hash('key_for_hash'));
        assertSame('ACTUALLY, A ROPE', $maybe->string('a.somewhat.deeply.nested.string'));
    }

    public function testNullableThrowsOnNonexistentKeyToo(): void
    {
        $this->expectException(KeyNotFound::class);
        $config = new Config($this->testConfig);
        $config->nullable->string('maybe.we.should.rename.maybe.to.nullable');
    }

    public function testConsidersEmptyArrayToBeBothListAndHash(): void
    {
        $config = new Config(['empty-hash' => []]);
        assertSame([], $config->hash('empty-hash'));
        assertSame([], $config->nullable->hash('empty-hash'));
        assertSame([], $config->list('empty-hash'));
        assertSame([], $config->nullable->list('empty-hash'));
    }

    public function testNullableValuesFromDerivedConfig(): void
    {
        $config = new Config(['derive_this' => $this->testConfig]);
        $derived = $config->config('derive_this');
        assertTrue($derived->nullable->bool('flag'));
        assertSame('a string', $derived->nullable->string('text'));
        assertSame(600, $derived->nullable->int('timeout'));
        assertSame(9.8, $derived->nullable->float('gravity_acceleration'));
        assertIsArray($derived->nullable->list('key_for_list'));
        assertIsArray($derived->nullable->hash('key_for_hash'));
    }

    public function testNullableThrowsOnUndefined(): void
    {
        $this->expectException(KeyNotFound::class);
        $this->expectExceptionMessage(
            "Couldn't find key 'a.somewhat.deeply.nested.does.not.exist' in the array. No value found at: 'a.somewhat.deeply.nested.does'"
        );
        $config = new Config($this->testConfig);
        $maybe = $config->nullable;
        $maybe->mixed('a.somewhat.deeply.nested.does.not.exist');
    }

    public function testCanGetConfigArrayBack(): void
    {
        $array = ['minimum_puppies' => 5];
        $config = new Config($array);
        assertSame($array, $config->toArray());
    }

    public function testCanFlattenConfig(): void
    {
        $config = new Config($this->testConfig);
        $flat = $config->toFlatArray();
        assertSame('qux', $flat['key_for_hash.baz']);
        assertSame('strings', $flat['key_for_list.3']);
        assertSame('ACTUALLY, A ROPE', $flat['a.somewhat.deeply.nested.string']);
    }

    public function testCanFlattenConfigWithDelimiter(): void
    {
        $config = new Config($this->testConfig);
        $flat = $config->toFlatArray('|-|');
        assertSame('qux', $flat['key_for_hash|-|baz']);
        assertSame('strings', $flat['key_for_list|-|3']);
        assertSame('ACTUALLY, A ROPE', $flat['a|-|somewhat|-|deeply|-|nested|-|string']);
    }
}
