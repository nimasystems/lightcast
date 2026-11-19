<?php
/**
 * F002 (audit 2026-08-14, V4.1.1): text-based responses declare their charset.
 */

$cases = [
    // [content type, expected header value]
    ['text/html', 'text/html; charset=utf-8'],
    ['text/plain', 'text/plain; charset=utf-8'],
    ['text/xml', 'text/xml; charset=utf-8'],
    ['text/csv', 'text/csv; charset=utf-8'],
    ['application/xml', 'application/xml; charset=utf-8'],
    ['application/javascript', 'application/javascript; charset=utf-8'],
    ['application/rss+xml', 'application/rss+xml; charset=utf-8'],
    ['application/xhtml+xml', 'application/xhtml+xml; charset=utf-8'],
    ['image/svg+xml', 'image/svg+xml; charset=utf-8'],
    ['APPLICATION/XML', 'APPLICATION/XML; charset=utf-8'],
    // audit V4.1.1 verification expects a charset on JSON too (harmless per RFC 8259)
    ['application/json', 'application/json; charset=utf-8'],
    ['application/x-www-form-urlencoded', 'application/x-www-form-urlencoded'],
    ['application/pdf', 'application/pdf'],
    ['application/binary', 'application/binary'],
    ['image/png', 'image/png'],
    // an explicit charset is kept and never duplicated
    ['text/html; charset=iso-8859-1', 'text/html; charset=iso-8859-1'],
    ['text/html;charset=UTF-8', 'text/html;charset=UTF-8'],
    // other parameters survive and the charset is appended after them
    ['application/xml; version=1', 'application/xml; version=1; charset=utf-8'],
];

foreach ($cases as $case) {
    lc_assert_same($case[0], $case[1], lcWebResponse::buildContentTypeHeaderValue($case[0], 'utf-8'));
}

lc_assert_same('empty charset leaves the type alone', 'text/html', lcWebResponse::buildContentTypeHeaderValue('text/html', ''));
