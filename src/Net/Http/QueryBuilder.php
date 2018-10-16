<?php
namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\StaticClass;

/**
 * Builds the query component of a URL.
 */
final class QueryBuilder
{
    use StaticClass;

    /**
     * Merges the specified query into the specified URL, which may or may not already contain a query component.
     *
     * @param string $url URL.
     * @param array $query Query.
     *
     * @return string URL with merged query component.
     */
    public static function mergeQuery($url, array $query)
    {
        // No query.
        if (!$query) {
            return $url;
        }

        $queryString = http_build_query($query);
        $qPos = strpos($url, '?');
        $hashPos = strpos($url, '#');
        $pos = strlen($url);
        $hasQuery = $qPos !== false && ($qPos < $hashPos || $hashPos === false);

        if ($qPos || $hashPos) {
            $pos = $hasQuery ? $qPos + 1 : $hashPos;
        }

        $prefix = substr($url, 0, $pos);
        $suffix = substr($url, $pos);

        return sprintf("$prefix%s$queryString%s$suffix", $hasQuery ? '' : '?', $hasQuery ? '&' : '');
    }
}
