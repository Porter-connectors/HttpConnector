<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use Amp\Http\Client\Body\StringBody;
use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;

/**
 * @see HttpDataSource
 */
final class HttpDataSourceTest extends TestCase
{
    private HttpDataSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = new HttpDataSource('foo');
    }

    /**
     * Tests that when properties change, the hash changes and when they do not, the hash stays the same.
     */
    public function testComputeHash(): void
    {
        self::assertNotEmpty($hash = $this->source->computeHash());
        self::assertSame($hash, $this->source->computeHash());

        self::assertNotSame($hash, $hash = $this->source->setMethod('Alfa')->computeHash());
        self::assertNotSame($hash, $hash = $this->source->setBody('Bravo')->computeHash());
        self::assertNotSame($hash, $hash = $this->source->addHeader('Charlie', 'Delta')->computeHash());

        self::assertSame($hash, $this->source->computeHash());
    }

    /**
     * Tests that when headers are hashed with the same names in different orders, they return the same hash.
     */
    public function testHashHeaderNames(): void
    {
        $initialHash = $this->source->computeHash();

        $this->source->addHeader($k1 = 'Alfa', $v1 = 'Bravo');
        $this->source->addHeader($k2 = 'Charlie', $v2 = 'Delta');

        self::assertNotSame($initialHash, $populatedHash = $this->source->computeHash());
        self::assertSame($initialHash, $this->source->removeHeaders($k1)->removeHeaders($k2)->computeHash());

        $this->source->addHeader($k2, $v2);
        $this->source->addHeader($k1, $v1);

        self::assertSame($populatedHash, $this->source->computeHash());
    }

    /**
     * Tests that when headers are hashed with the same values in different orders, they return the same hash.
     */
    public function testHashHeaderValues(): void
    {
        $initialHash = $this->source->computeHash();

        $this->source->addHeader($k = 'Alfa', $v1 = 'Bravo');
        $this->source->addHeader($k, $v2 = 'Charlie');

        self::assertNotSame($initialHash, $populatedHash = $this->source->computeHash());
        self::assertSame($initialHash, $this->source->removeHeaders($k)->computeHash());

        $this->source->addHeader($k, $v2);
        $this->source->addHeader($k, $v1);

        self::assertSame($populatedHash, $this->source->computeHash());
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
