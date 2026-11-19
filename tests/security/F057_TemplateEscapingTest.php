<?php
/**
 * F057 / F067 (audit 2026-08-14, V1.2.1 / V1.2.3): template output escaping.
 *
 * - {$var}      HTML-escaped with ENT_QUOTES | ENT_SUBSTITUTE (single quotes too, invalid UTF-8
 *               no longer blanks the value)
 * - {$var:js}   escaped for a quoted JavaScript string literal
 * - every other modifier behaves as before
 */

/** @var lcHTMLTemplateViewFilter $filter */
$filter = (new ReflectionClass('lcHTMLTemplateViewFilter'))->newInstanceWithoutConstructor();

$render = function ($template, array $params) use ($filter) {
    $m = new ReflectionMethod('lcHTMLTemplateViewFilter', 'parseTemplate');
    $m->setAccessible(true);

    return $m->invoke($filter, $template, new lcIterateParamHolder(null, $params));
};

// builds a JS unicode escape sequence: backslash, u, four hex digits
$u = function ($hex) {
    return chr(92) . 'u' . $hex;
};

$attack = "x'\"<script>alert(1)</script>&";

// default escaping
lc_assert_same('{$v} escapes quotes, tags and &',
    'x&#039;&quot;&lt;script&gt;alert(1)&lt;/script&gt;&amp;', $render('{$v}', ['v' => $attack]));
lc_assert_same('{$v} single quote cannot break a single-quoted attribute',
    "<a title='O&#039;Brien'>", $render("<a title='{\$v}'>", ['v' => "O'Brien"]));
lc_assert_same('{$v} invalid UTF-8 is substituted, not blanked',
    "ab\u{FFFD}cd", $render('{$v}', ['v' => "ab\xC3cd"]));
lc_assert_same('{$v} UTF-8 text unchanged', 'Ünïcödé 日本', $render('{$v}', ['v' => 'Ünïcödé 日本']));
lc_assert_same('{$v} integer', '42', $render('{$v}', ['v' => 42]));
lc_assert_same('{$v} missing stays a placeholder', '{$missing}', $render('{$missing}', []));
lc_assert_same('{$v} array renders empty', '', $render('{$v}', ['v' => ['a']]));

// :asis is untouched
lc_assert_same('{$v:asis} raw', $attack, $render('{$v:asis}', ['v' => $attack]));

// :js
$js = $render('{$v:js}', ['v' => $attack]);
lc_assert_same('{$v:js} attack payload',
    'x' . $u('0027') . $u('0022') . $u('003C') . 'script' . $u('003E') . 'alert(1)' . $u('003C') . '\\/script'
    . $u('003E') . $u('0026'), $js);
lc_assert_true('{$v:js} contains no quote, <, > or &', strpbrk($js, "'\"<>&") === false);
lc_assert_same('{$v:js} backslash and newline',
    'a\\\\b\nc', $render('{$v:js}', ['v' => "a\\b\nc"]));
lc_assert_same('{$v:js} U+2028 / U+2029 escaped',
    'a' . $u('2028') . 'b' . $u('2029') . 'c', $render('{$v:js}', ['v' => "a\u{2028}b\u{2029}c"]));
lc_assert_same('{$v:js} unicode kept readable', 'Ünïcödé 日本', $render('{$v:js}', ['v' => 'Ünïcödé 日本']));
lc_assert_same('{$v:js} invalid UTF-8 substituted', "ab\u{FFFD}cd", $render('{$v:js}', ['v' => "ab\xC3cd"]));
lc_assert_same('{$v:js} missing value is empty', '', $render('{$v:js}', []));
lc_assert_same('{$v:js} array is empty', '', $render('{$v:js}', ['v' => ['a' => 1]]));
lc_assert_same('{$v:js} number', '3.5', $render('{$v:js}', ['v' => 3.5]));
lc_assert_same("{\$v:js} O'Brien round-trips through JSON",
    "O'Brien", json_decode('"' . $render('{$v:js}', ['v' => "O'Brien"]) . '"'));

// existing modifiers keep their behaviour
lc_assert_same('{$v:sp} escapes (now with ENT_QUOTES)', 'a&lt;b&#039;', $render('{$v:sp}', ['v' => "a<b'"]));
lc_assert_same('{$v:sp} empty', '', $render('{$v:sp}', ['v' => '']));
lc_assert_same('{$v:seopath}', 'a-b_c', $render('{$v:seopath}', ['v' => 'a b/c']));
lc_assert_same('{$v:keylink}', lcStrings::keyLink('Hello World'), $render('{$v:keylink}', ['v' => 'Hello World']));
lc_assert_same('{$v:urlencode} encodes the name (unchanged semantics)', 'v', $render('{$v:urlencode}', ['v' => 'a b']));
lc_assert_same('{{literal}}', 'literal', $render('{{literal}}', []));

// the helpers directly
lc_assert_same('escapeHtml', '&lt;&#039;&quot;&amp;', lcHTMLTemplateViewFilter::escapeHtml("<'\"&"));
lc_assert_same('escapeJsString null', '', lcStrings::escapeJsString(null));
lc_assert_same('escapeJsString bool', '1', lcStrings::escapeJsString(true));
