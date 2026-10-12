<?php
// ext/mbstring: multibyte-safe string functions (mb_*). Needs a firmware built with
// the mbstring extension (`[extensions.mbstring] enabled = true`). The mb_ereg* /
// mb_split regex family needs oniguruma -- add `onig = true` to the config and the
// regex section at the bottom runs too (it is skipped otherwise). `no_cjk = true`
// drops the CJK codecs for a ~755 KB smaller mbstring.

$s = 'Città di Località — こんにちは';   // accents, an em dash, some Japanese

echo "internal encoding: " . mb_internal_encoding() . "\n";
echo "string:            $s\n";
echo "strlen (bytes):    " . strlen($s) . "\n";
echo "mb_strlen (chars): " . mb_strlen($s) . "\n";
echo "mb_strtoupper:     " . mb_strtoupper('città') . "\n";
echo "mb_strtolower:     " . mb_strtolower('CITTÀ') . "\n";
echo "mb_substr(0,8):    " . mb_substr($s, 0, 8) . "\n";
echo "mb_strpos('こ'):    " . mb_strpos($s, 'こ') . "\n";

echo "\ndetect + convert:\n";
echo "  detect: " . mb_detect_encoding($s, ['ASCII', 'UTF-8']) . "\n";
$latin = mb_convert_encoding('Città', 'ISO-8859-1', 'UTF-8');
echo "  utf8 'Città' -> latin1 = " . strlen($latin) . " bytes"
   . " (round-trip: " . mb_convert_encoding($latin, 'UTF-8', 'ISO-8859-1') . ")\n";

echo "\nmb_str_split('café'): " . implode('|', mb_str_split('café')) . "\n";

// exactly the kind of thing Str::title() does under the hood
echo "mb_convert_case(TITLE): " . mb_convert_case('éLÉphant bleu', MB_CASE_TITLE) . "\n";

// The multibyte regex family (mb_ereg* / mb_split) -- only when mbstring is built WITH
// oniguruma (onig = true). Unlike a PCRE polyfill this is the real Oniguruma engine, so
// Unicode-aware patterns (\p{L}, character properties) work on multibyte text.
if (function_exists('mb_ereg')) {
    mb_regex_encoding('UTF-8');
    $r = 'Città 42 — naïve 7 — café 100';
    echo "\n--- mb_ereg* (oniguruma) ---\ninput: $r\n";
    echo "mb_split on ' — ': " . implode(' | ', mb_split(' — ', $r)) . "\n";
    echo "mb_ereg_replace (mask numbers): " . mb_ereg_replace('[0-9]+', '#', $r) . "\n";
    if (mb_ereg('([\p{L}]+)\s+([0-9]+)', $r, $m)) {
        printf("mb_ereg \\p{L}+ digits: word=\"%s\" number=%s\n", $m[1], $m[2]);
    }
} else {
    echo "\n(mb_ereg*/mb_split skipped: build with [extensions.mbstring] onig = true)\n";
}
