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
