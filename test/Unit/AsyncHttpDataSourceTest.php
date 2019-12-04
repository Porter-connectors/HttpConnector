<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use Amp\Artax\StringBody;
use Amp\PHPUnit\AsyncTestCase;
use ScriptFUSION\Porter\Net\Http\AsyncHttpDataSource;

/**
 * @see AsyncHttpDataSource
 */
final class AsyncHttpDataSourceTest extends AsyncTestCase
{
    /** @var AsyncHttpDataSource */
    private $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = new AsyncHttpDataSource('foo');
    }

    /**
     * Tests that when properties change, the hash changes and when they do not, the hash stays the same.
     */
    public function testComputeHash(): \Generator
    {
        self::assertNotEmpty($hash = yield $this->source->computeHash());
        self::assertSame($hash, yield $this->source->computeHash());

        self::assertNotSame($hash, $hash = yield $this->source->setMethod('Alfa')->computeHash());
        self::assertNotSame($hash, $hash = yield $this->source->setBody(new StringBody('Bravo'))->computeHash());
        self::assertNotSame($hash, $hash = yield $this->source->addHeader('Charlie', 'Delta')->computeHash());

        self::assertSame($hash, yield $this->source->computeHash());
    }

    /**
     * Tests that when headers are hashed with the same names in different orders, they return the same hash.
     */
    public function testHashHeaderNames(): \Generator
    {
        $initialHash = yield $this->source->computeHash();

        $this->source->addHeader($k1 = 'Alfa', $v1 = 'Bravo');
        $this->source->addHeader($k2 = 'Charlie', $v2 = 'Delta');

        self::assertNotSame($initialHash, $populatedHash = yield $this->source->computeHash());
        self::assertSame($initialHash, yield $this->source->removeHeaders($k1)->removeHeaders($k2)->computeHash());

        $this->source->addHeader($k2, $v2);
        $this->source->addHeader($k1, $v1);

        self::assertSame($populatedHash, yield $this->source->computeHash());
    }

    /**
     * Tests that when headers are hashed with the same values in different orders, they return the same hash.
     */
    public function testHashHeaderValues(): \Generator
    {
        $initialHash = yield $this->source->computeHash();

        $this->source->addHeader($k = 'Alfa', $v1 = 'Bravo');
        $this->source->addHeader($k, $v2 = 'Charlie');

        self::assertNotSame($initialHash, $populatedHash = yield $this->source->computeHash());
        self::assertSame($initialHash, yield $this->source->removeHeaders($k)->computeHash());

        $this->source->addHeader($k, $v2);
        $this->source->addHeader($k, $v1);

        self::assertSame($populatedHash, yield $this->source->computeHash());
    }

    /**
     * Tests that when a header is set, its first value is always found regardless of how many extra values it has.
     */
    public function testFindHeader(): void
    {
        self::assertNull($this->source->findHeader($key = 'Alfa'));

        $this->source->addHeader($key, $value = 'Bravo');
        self::assertSame($value, $this->source->findHeader($key));

        $this->source->addHeader($key, 'Charlie');
        self::assertSame($value, $this->source->findHeader($key));

        $this->source->removeHeaders($key);
        self::assertNull($this->source->findHeader($key));
    }

    /**
     * Tests that when headers are set, all values can be retrieved with findHeaders().
     */
    public function testFindHeaders(): void
    {
        self::assertEmpty($this->source->findHeaders($key = 'Alfa'));

        $this->source->addHeader($key, $v1 = 'Bravo');

        self::assertContains($v1, $this->source->findHeaders($key));

        $this->source->addHeader($key, $v2 = 'Charlie');

        self::assertContains($v1, $this->source->findHeaders($key));
        self::assertContains($v2, $this->source->findHeaders($key));

        $this->source->removeHeaders($key);

        self::assertEmpty($this->source->findHeaders($key));
    }
}
