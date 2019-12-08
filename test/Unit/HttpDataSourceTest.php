<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;

/**
 * @see HttpDataSource
 */
final class HttpDataSourceTest extends TestCase
{
    /** @var HttpDataSource */
    private $source;

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
        self::assertNotSame($hash, $hash = $this->source->addHeader('Charlie: Delta')->computeHash());

        self::assertSame($hash, $this->source->computeHash());
    }

    /**
     * Tests that when headers are hashed with the same names in different orders, they return the same hash.
     */
    public function testHashHeaderNames(): void
    {
        $initialHash = $this->source->computeHash();

        $this->source->addHeader($h1 = 'Alfa: Bravo');
        $this->source->addHeader($h2 = 'Charlie: Delta');

        self::assertNotSame($initialHash, $populatedHash = $this->source->computeHash());
        self::assertSame($initialHash, $this->source->removeHeaders('Alfa')->removeHeaders('Charlie')->computeHash());

        $this->source->addHeader($h2);
        $this->source->addHeader($h1);

        self::assertSame($populatedHash, $this->source->computeHash());
    }

    public function testMethod(): void
    {
        self::assertSame('foo', $this->source->setMethod('foo')->getMethod());
    }

    public function testBody(): void
    {
        self::assertSame($content = "foo\nbar", $this->source->setBody($content)->getBody());
    }

    public function testFindHeader(): void
    {
        $options = $this->source->addHeader('Foo: bar')->addHeader($baz = 'Baz: qux');

        self::assertNull($options->findHeader('baz'));
        self::assertSame($baz, $options->findHeader('Baz'));
    }

    public function testFindHeaders(): void
    {
        $options = $this->source
            ->addHeader($foo1 = 'Foo: bar')
            ->addHeader($foo2 = 'Foo: baz')
            ->addHeader('Qux: Quux')
        ;

        self::assertSame([$foo1, $foo2], $options->findHeaders('Foo'));
    }
}
