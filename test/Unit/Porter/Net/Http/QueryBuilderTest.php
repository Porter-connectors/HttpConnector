<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use ScriptFUSION\Porter\Net\Http\QueryBuilder;

final class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that the specified query is successfully merged into the specified URL.
     *
     * @param string $inUrl
     * @param array $query
     * @param string $outUrl
     *
     * @dataProvider provideQueries
     */
    public function testQuery($inUrl, array $query, $outUrl)
    {
        self::assertSame($outUrl, QueryBuilder::mergeQuery($inUrl, $query));
    }

    public function provideQueries()
    {
        return [
            'No query merged into no query' => [
                'http://example.com',
                [],
                'http://example.com',
            ],
            'Query merged into no query' => [
                'http://example.com',
                ['foo' => 'bar'],
                'http://example.com?foo=bar',
            ],
            'Multiple queries merged into no query' => [
                'http://example.com',
                ['foo' => 'bar', 'baz' => 'qux'],
                'http://example.com?foo=bar&baz=qux',
            ],

            'No query merged into existing query' => [
                'http://example.com?foo=bar',
                [],
                'http://example.com?foo=bar',
            ],
            'Query inserted before existing query' => [
                'http://example.com?foo=bar',
                ['baz' => 'qux'],
                'http://example.com?baz=qux&foo=bar',
            ],

            // Normal fragments.
            'No query merged into fragment' => [
                'http://example.com#foobar',
                [],
                'http://example.com#foobar',
            ],
            'Query inserted before fragment' => [
                'http://example.com#foobar',
                ['baz' => 'qux'],
                'http://example.com?baz=qux#foobar',
            ],
            'Query merged before fragment' => [
                'http://example.com?foo=bar#baz',
                ['quux' => 'quuz'],
                'http://example.com?quux=quuz&foo=bar#baz',
            ],

            // Query fragments.
            'No query merged into query fragment' => [
                'http://example.com#foo?bar',
                [],
                'http://example.com#foo?bar',
            ],
            'Query inserted before query fragment' => [
                'http://example.com#foo?bar',
                ['baz' => 'qux'],
                'http://example.com?baz=qux#foo?bar',
            ],
            'Query merged before query fragment' => [
                'http://example.com?foo=bar#baz?qux',
                ['quux' => 'quuz'],
                'http://example.com?quux=quuz&foo=bar#baz?qux',
            ],

            // Tests that format specifiers in URL do not break sprintf().
            'Format specifier (sprintf)' => [
                'http://example.com',
                ['foo' => '%s'],
                'http://example.com?foo=%25s',
            ],
        ];
    }
}
