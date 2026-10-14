<?php

echo "=== all extensions, PHP " . PHP_VERSION . ": " . implode(' ', get_loaded_extensions()) . "\n";

echo "\n=== task 2: ctype filter tokenizer session bcmath date ===\n";
if (function_exists('sys_psram_free')) {
    echo "heap at start: psram " . sys_psram_free() . " B, internal " . sys_heap_free() . " B\n";
}
echo "PHP " . PHP_VERSION . ", extensions: " . implode(' ', array_values(array_intersect(['ctype', 'filter', 'tokenizer', 'session', 'bcmath', 'date'], get_loaded_extensions()))) . "\n";

if (extension_loaded('ctype')) {
    echo "ctype_digit('12345'): " . var_export(ctype_digit('12345'), true) . ", ctype_digit('12a'): " . var_export(ctype_digit('12a'), true) . "\n";
}
if (extension_loaded('filter')) {
    echo "filter email ok: " . var_export(filter_var('user@example.com', FILTER_VALIDATE_EMAIL), true)
        . ", bad: " . var_export(filter_var('not-an-email', FILTER_VALIDATE_EMAIL), true) . "\n";
}
if (extension_loaded('tokenizer')) {
    $tokens = token_get_all('<?php echo $x + 1;');
    echo "token_get_all: " . count($tokens) . " tokens, first " . token_name($tokens[0][0]) . ", " . implode(',', array_map(fn ($t) => is_array($t) ? token_name($t[0]) : $t, $tokens)) . "\n";
}
if (extension_loaded('session')) {
    echo "session handler " . ini_get('session.save_handler') . ", path '" . ini_get('session.save_path') . "'\n";
    $ok = session_start();
    $_SESSION['count'] = ($_SESSION['count'] ?? 0) + 1;
    echo "session_start: " . var_export($ok, true) . ", id " . strlen(session_id()) . " chars, count " . $_SESSION['count'] . "\n";
    $id = session_id();
    session_write_close();
    session_id($id);
    session_start();
    echo "session reopened: count " . ($_SESSION['count'] ?? 'missing') . "\n";
    session_write_close();
    if (ini_get('session.save_handler') === 'files') {
        $file = ini_get('session.save_path') . '/sess_' . $id;
        echo "session file: " . (is_file($file) ? filesize($file) . " B: " . file_get_contents($file) : 'missing') . "\n";
    }
}
if (extension_loaded('bcmath')) {
    $a = '12345678901234567890123456789012345678901234567890';
    $b = '98765432109876543210987654321098765432109876543210';
    echo "bcadd: " . bcadd($a, $b) . "\n";
    echo "bcmul: " . bcmul($a, '3') . ", bcdiv(1,7,30): " . bcdiv('1', '7', 30) . "\n";
}
if (extension_loaded('date')) {
    echo "date('c'): " . date('c') . ", default tz " . date_default_timezone_get() . "\n";
    try {
        $d = new DateTime('2026-07-01 12:00:00', new DateTimeZone('Europe/Rome'));
        echo "Europe/Rome: " . $d->format('c T') . ", UTC " . $d->setTimezone(new DateTimeZone('UTC'))->format('H:i') . "\n";
    } catch (Throwable $e) {
        echo "Europe/Rome: " . get_class($e) . ": " . $e->getMessage() . "\n";
    }
    echo "UTC diff: " . (new DateTime('2026-01-01', new DateTimeZone('UTC')))->diff(new DateTime('2026-10-17', new DateTimeZone('UTC')))->days . " days\n";
}
echo "memory " . memory_get_usage() . "\n";



echo "\n=== task 3: mbstring ===\n";
if (function_exists('sys_psram_free')) {
    echo "heap at start: psram " . sys_psram_free() . " B, internal " . sys_heap_free() . " B\n";
}
echo "PHP " . PHP_VERSION . ", mbstring " . var_export(extension_loaded('mbstring'), true) . "\n";

if (extension_loaded('mbstring')) {
    $s = 'àèìòù';
    echo "strlen " . strlen($s) . ", mb_strlen " . mb_strlen($s) . ", internal " . mb_internal_encoding() . "\n";
    echo "mb_strtoupper: " . mb_strtoupper($s) . ", mb_substr(1,3): " . mb_substr($s, 1, 3) . "\n";
    $latin1 = mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    echo "UTF-8 -> ISO-8859-1: " . strlen($latin1) . " B " . bin2hex($latin1) . ", back: " . mb_convert_encoding($latin1, 'UTF-8', 'ISO-8859-1') . "\n";
    echo "mb_check_encoding: " . var_export(mb_check_encoding($s, 'UTF-8'), true) . "/" . var_export(mb_check_encoding("\xff", 'UTF-8'), true) . ", detect: " . mb_detect_encoding($s, ['ASCII', 'UTF-8']) . "\n";
    echo "by name/alias: KOI8-R " . bin2hex(mb_convert_encoding('Ж', 'KOI8-R', 'UTF-8')) . ", latin1 " . bin2hex(mb_convert_encoding('é', 'latin1', 'UTF-8')) . ", utf-16le " . bin2hex(mb_convert_encoding('é', 'utf-16le', 'UTF-8')) . ", cp1252 " . bin2hex(mb_convert_encoding('€', 'Windows-1252', 'UTF-8')) . "\n";
    echo "encodings: " . count(mb_list_encodings()) . ", SJIS listed: " . var_export(in_array('SJIS', mb_list_encodings(), true), true) . ", mb_convert_kana: " . var_export(function_exists('mb_convert_kana'), true) . "\n";
    try {
        $jp = 'こんにちは';
        $sjis = mb_convert_encoding($jp, 'SJIS', 'UTF-8');
        echo "SJIS: " . strlen($sjis) . " B " . bin2hex($sjis) . ", back: " . mb_convert_encoding($sjis, 'UTF-8', 'SJIS') . ", kana: " . mb_convert_kana('ｱｲｳ', 'KV') . "\n";
    } catch (Throwable $e) {
        echo "SJIS: " . get_class($e) . ": " . $e->getMessage() . "\n";
    }
    foreach (['ja', 'ko', 'zh-cn', 'zh-tw', 'ru', 'en'] as $lang) {
        try {
            mb_language($lang);
            mb_detect_order('auto');
            $det = mb_detect_encoding('àèìòù', 'auto');
            echo "lang $lang: " . mb_language() . ", order " . implode(',', mb_detect_order()) . ", detect " . var_export($det, true) . ", mime " . mb_encode_mimeheader('Città è') . "\n";
        } catch (Throwable $e) {
            echo "lang $lang: " . get_class($e) . ": " . $e->getMessage() . ", still " . mb_language() . "\n";
        }
    }
    mb_language('neutral');
    echo "mb_ereg: " . var_export(function_exists('mb_ereg_replace'), true) . "\n";
    if (function_exists('mb_ereg_replace')) {
        mb_regex_encoding('UTF-8');
        echo "mb_ereg_replace: " . mb_ereg_replace('[àè]+', '<\0>', 'xàèy èz') . "\n";
        echo "mb_split: " . implode('|', mb_split('[,;]\s*', 'à, è;ì')) . ", mb_ereg: " . var_export(mb_ereg('(\w+)ò', 'abcòd', $m), true) . " " . ($m[1] ?? '') . "\n";
        echo "mb_eregi('ÀÈ', 'xàèy'): " . var_export(mb_eregi('ÀÈ', 'xàèy'), true) . ", mb_eregi_replace('STRASSE', 'x', 'Straße'): " . mb_eregi_replace('STRASSE', 'x', 'Straße') . "\n";
        echo "onig: " . MB_ONIGURUMA_VERSION . "\n";
    }
}
echo "memory " . memory_get_usage() . "\n";



echo "\n=== task 4: pdo pdo_sqlite sqlite3 opcache ===\n";
if (function_exists('sys_psram_free')) {
    echo "heap at start: psram " . sys_psram_free() . " B, internal " . sys_heap_free() . " B\n";
}
echo "PHP " . PHP_VERSION . ", extensions: " . implode(' ', array_values(array_intersect(['pdo', 'pdo_sqlite', 'sqlite3', 'Zend OPcache'], get_loaded_extensions()))) . "\n";
if (class_exists('PDO')) {
    echo "PDO drivers: " . implode(',', PDO::getAvailableDrivers()) . "\n";
}
if (extension_loaded('pdo_sqlite')) {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "sqlite " . $db->query('select sqlite_version()')->fetchColumn() . ", compile options: " . implode(' ', $db->query('pragma compile_options')->fetchAll(PDO::FETCH_COLUMN)) . "\n";
    $db->exec('create table t (id integer primary key, name text)');
    $st = $db->prepare('insert into t (name) values (?)');
    foreach (['a', 'b', 'c'] as $n) { $st->execute([$n]); }
    echo "pdo memory rows: " . $db->query('select count(*) from t')->fetchColumn() . ", loadExtension: " . var_export(method_exists($db, 'loadExtension'), true) . "\n";
}
if (class_exists('SQLite3')) {
    $s = new SQLite3(':memory:');
    echo "SQLite3 " . SQLite3::version()['versionString'] . ", 6*7 = " . $s->querySingle('select 6*7') . ", loadExtension: " . var_export(method_exists($s, 'loadExtension'), true) . "\n";
}
if (function_exists('opcache_get_status')) {
    $st = opcache_get_status(false);
    echo "opcache enabled " . var_export($st['opcache_enabled'] ?? false, true) . ", memory " . json_encode($st['memory_usage'] ?? null) . ", max files " . ini_get('opcache.max_accelerated_files') . ", interned " . ini_get('opcache.interned_strings_buffer') . ", validate " . ini_get('opcache.validate_timestamps') . "\n";
}
echo "memory_limit " . ini_get('memory_limit') . ", memory " . memory_get_usage() . "\n";



echo "\n=== task 5: openssl zlib ===\n";
function check(string $what, bool $ok, string $detail = ''): void
{
    $GLOBALS['checks'] = ($GLOBALS['checks'] ?? 0) + 1;
    if (!$ok) {
        $GLOBALS['fails'] = ($GLOBALS['fails'] ?? 0) + 1;
    }
    echo ($ok ? 'OK   ' : 'FAIL ') . $what . ($detail !== '' ? " ({$detail})" : '') . "\n";
}

function ms(float $t0): string
{
    return round((microtime(true) - $t0) * 1000, 1) . ' ms';
}

if (function_exists('sys_psram_free')) {
    echo "heap at start: psram " . sys_psram_free() . " B, internal " . sys_heap_free() . " B\n";
}
echo "PHP " . PHP_VERSION . ", memory_limit " . ini_get('memory_limit') . ", extensions: " . implode(' ', array_values(array_intersect(['openssl', 'zlib'], get_loaded_extensions()))) . "\n";

if (extension_loaded('openssl')) {
    echo "openssl version " . phpversion('openssl') . "\n";
    echo "cipher methods: " . implode(' ', openssl_get_cipher_methods()) . "\n";
    echo "md methods: " . implode(' ', openssl_get_md_methods()) . "\n";
    echo "functions: " . implode(' ', get_extension_funcs('openssl')) . "\n";

    check('sha256 abc', openssl_digest('abc', 'sha256') === 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad');
    check('sha512 abc', openssl_digest('abc', 'SHA512') === 'ddaf35a193617abacc417349ae20413112e6fa4e89a97ea20a9eeee64b55d39a2192992a274fc1a836ba3c23a3feebbd454d4423643ce80e2a9ac94fa54ca49f');
    check('sha1 raw', openssl_digest('abc', 'sha1', true) === sha1('abc', true));
    check('md5', openssl_digest('abc', 'md5') === md5('abc'));
    $big = str_repeat('0123456789abcdef', 6400);
    $t0 = microtime(true);
    $h = openssl_digest($big, 'sha256');
    check('sha256 100 KB', $h === hash('sha256', $big), ms($t0));
    if (in_array('sha3-256', openssl_get_md_methods(), true)) {
        check('sha3-256', openssl_digest('abc', 'sha3-256') === hash('sha3-256', 'abc'));
    }
    check('unknown digest', @openssl_digest('abc', 'whirlpool') === false, (string) (error_get_last()['message'] ?? ''));

    $r = openssl_random_pseudo_bytes(32, $strong);
    check('random 32', strlen($r) === 32 && $strong === true && $r !== openssl_random_pseudo_bytes(32), bin2hex(substr($r, 0, 8)));
    try {
        openssl_random_pseudo_bytes(0);
        check('random 0 throws', false);
    } catch (ValueError $e) {
        check('random 0 throws', true, $e->getMessage());
    }

    $k16 = '0123456789abcdef';
    $k32 = '0123456789abcdef0123456789abcdef';
    $iv = 'fedcba9876543210';
    $n = '123456789012';
    $p = 'The quick brown fox jumps over the lazy dog';
    $ref = [
        'aes-128-cbc' => [$k16, '7boD/hk+NZRr9SFSNNP4R1nnvzYKS4nlQfVBxuhPu5wk1lIAS/cx2CLXUwvThERX'],
        'aes-192-cbc' => [substr($k32, 0, 24), 'ZivZcumF2o1L9TGnQSePGJOgvlDUCKjOmKBpC6uxA27zyg1nVFi9uVbzdw/H/2iZ'],
        'aes-256-cbc' => [$k32, 'ICjeiLV/+CNjfEOv0or928Kh+Gj+Y0+k5ma8BdnYb18VTwPgF4S2kl2Ip7xglEwf'],
        'aes-128-ctr' => [$k16, 'XyOzUYcFFmrTGKGkpWsEHSQKlBnF0uD/ldg+wNf7V1Xs39IigUsGXis+9A=='],
        'aes-192-ctr' => [substr($k32, 0, 24), 'ri8qRrhuhM8uTyqTC4JO9NspMJYGztldqenbqaMRGYzARs+Md5fH9ELvbw=='],
        'aes-256-ctr' => [$k32, 'ijAef72FJ9cZBIToVffZPLWjG11LoZ0u01U8ResIjewiKgkYogRdylF2vA=='],
    ];
    foreach ($ref as $cipher => [$key, $expected]) {
        $ct = openssl_encrypt($p, $cipher, $key, 0, $iv);
        check("{$cipher} vector", $ct === $expected, (string) $ct);
        check("{$cipher} decrypt", openssl_decrypt($ct, $cipher, $key, 0, $iv) === $p);
    }
    $gcm = [
        'aes-128-gcm' => [$k16, 'kjkn1Zob7shasfkOYFB2VQKlq/r/7m9BB6htHeHzIDlBSDCaGuhzYkUKXQ==', '5ae71ff76dec59ea3154f246e1de3a5a'],
        'aes-192-gcm' => [substr($k32, 0, 24), '/NE/Dt94pv7neASKquGNH5ho5aMTgy/DNWXWmlqkG3qz6CFbYxpkYG1EMg==', '4199dc3400862a98e33bb40e244df3cc'],
        'aes-256-gcm' => [$k32, '3TKs09cNWT04v2OXaZhts/CFkVJSiXXgNH1rg08fhwVl1A38x57AoAR60g==', '673f469d5a802ed9215fb1ad2e8ba980'],
    ];
    foreach ($gcm as $cipher => [$key, $expected, $expectedTag]) {
        $ct = openssl_encrypt($p, $cipher, $key, 0, $n, $tag, 'aad');
        check("{$cipher} vector", $ct === $expected && bin2hex((string) $tag) === $expectedTag, "{$ct} " . bin2hex((string) $tag));
        check("{$cipher} decrypt", openssl_decrypt($ct, $cipher, $key, 0, $n, $tag, 'aad') === $p);
        $bad = $tag;
        $bad[0] = chr(ord($bad[0]) ^ 1);
        check("{$cipher} bad tag", openssl_decrypt($ct, $cipher, $key, 0, $n, $bad, 'aad') === false);
        check("{$cipher} bad aad", openssl_decrypt($ct, $cipher, $key, 0, $n, $tag, 'aaX') === false);
    }
    $ct = openssl_encrypt($p, 'aes-256-gcm', $k32, 0, $n, $tag8, '', 8);
    check('gcm tag 8', bin2hex((string) $tag8) === '899a1ddbca5c4159' && openssl_decrypt($ct, 'aes-256-gcm', $k32, 0, $n, $tag8) === $p);
    check('gcm without tag', @openssl_encrypt($p, 'aes-128-gcm', $k16, 0, $n) === false, (string) (error_get_last()['message'] ?? ''));
    check('zero padding', openssl_encrypt(str_repeat('A', 32), 'aes-128-cbc', $k16, OPENSSL_ZERO_PADDING, $iv) === 'zwZOba0ytwj1iCB+1HKb7m9WZZwFZTNcsdxWYbIlzfM=');
    check('zero padding unaligned', @openssl_encrypt('abc', 'aes-128-cbc', $k16, OPENSSL_ZERO_PADDING | OPENSSL_RAW_DATA, $iv) === false);
    check('short key zero-padded', openssl_encrypt($p, 'aes-256-cbc', 'short', 0, $iv) === '5a3Puf9+cdc9MGKZav/HJ3pkh2vIkh/RVlzSQi+YDtIzgu0CoPk1irpNdz1PGCQ1');
    check('dont zero pad key', @openssl_encrypt($p, 'aes-256-cbc', 'short', OPENSSL_DONT_ZERO_PAD_KEY, $iv) === false);
    check('short iv warns', @openssl_encrypt($p, 'aes-128-cbc', $k16, 0, 'abc') !== false && str_contains((string) (error_get_last()['message'] ?? ''), 'padding with'));
    check('wrong key', openssl_decrypt(openssl_encrypt($p, 'aes-128-cbc', $k16, 0, $iv), 'aes-128-cbc', 'x' . substr($k16, 1), 0, $iv) !== $p);
    check('unknown cipher', @openssl_encrypt($p, 'des-ede3-cbc', $k16) === false, (string) (error_get_last()['message'] ?? ''));
    check('iv/key length', openssl_cipher_iv_length('AES-256-GCM') === 12 && openssl_cipher_iv_length('aes-128-cbc') === 16 && openssl_cipher_key_length('aes-192-cbc') === 24);
    $big = random_bytes(100 * 1024);
    foreach (['aes-256-cbc', 'aes-256-gcm'] as $cipher) {
        $ivx = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $t0 = microtime(true);
        $ct = openssl_encrypt($big, $cipher, $k32, OPENSSL_RAW_DATA, $ivx, $tagx);
        $te = ms($t0);
        $t0 = microtime(true);
        $pt = openssl_decrypt($ct, $cipher, $k32, OPENSSL_RAW_DATA, $ivx, $tagx);
        check("{$cipher} 100 KB round trip", $pt === $big, "enc {$te}, dec " . ms($t0));
    }
}

if (extension_loaded('zlib')) {
    echo "zlib version " . ZLIB_VERSION . "\n";
    check('gzcompress vector', bin2hex(gzcompress('hello hello hello hello')) === '789ccb48cdc9c957c8402701680308b1');
    $text = str_repeat("php-baremetal runs PHP on microcontrollers. ", 2400);
    $rnd = random_bytes(100 * 1024);
    foreach (['text' => $text, 'random' => $rnd] as $kind => $data) {
        $t0 = microtime(true);
        $c = gzcompress($data);
        $tc = ms($t0);
        $t0 = microtime(true);
        $ok = gzuncompress($c) === $data;
        check("gzcompress {$kind} " . strlen($data) . " B -> " . strlen($c) . ' B', $ok, "c {$tc}, u " . ms($t0));
        $e = gzencode($data, 9);
        check("gzencode {$kind} -> " . strlen($e) . ' B', gzdecode($e) === $data && substr($e, 0, 2) === "\x1f\x8b");
        check("gzdeflate {$kind}", gzinflate(gzdeflate($data)) === $data);
        check("zlib_encode {$kind}", zlib_decode(zlib_encode($data, ZLIB_ENCODING_DEFLATE)) === $data);
    }
    $ctx = deflate_init(ZLIB_ENCODING_GZIP);
    $s = deflate_add($ctx, substr($text, 0, 5000), ZLIB_NO_FLUSH) . deflate_add($ctx, substr($text, 5000), ZLIB_FINISH);
    $ictx = inflate_init(ZLIB_ENCODING_GZIP);
    check('incremental deflate/inflate', inflate_add($ictx, $s) === $text);
    check('gzuncompress garbage', @gzuncompress('not zlib') === false, (string) (error_get_last()['message'] ?? ''));
    check('compress.zlib wrapper', in_array('compress.zlib', stream_get_wrappers(), true));
    $gz = __DIR__ . '/lines.txt.gz';
    if (is_file($gz)) {
        $lines = gzfile($gz);
        $plain = file_get_contents('compress.zlib://' . $gz);
        $h = gzopen($gz, 'rb');
        $first = gzgets($h);
        gzclose($h);
        check('gz file ' . filesize($gz) . ' B', count($lines) === 2000 && strlen($plain) === 2000 * 25 && $first === "line 0000 of the gz file\n", count($lines) . ' lines, ' . strlen($plain) . ' B');
    }
    $churn = function (string $file): bool {
        for ($i = 0; $i < 300; $i++) {
            $h = gzopen($file, 'rb');
            if ($h === false || gzread($h, 9) !== substr(gzdecode(file_get_contents($file)), 0, 9)) {
                return false;
            }
            gzclose($h);
            if (file_get_contents('compress.zlib://' . $file, false, null, 0, 4) === false) {
                return false;
            }
        }
        $h = fopen($file, 'rb');
        $ok = $h !== false && fread($h, 2) === "\x1f\x8b";
        fclose($h);
        return $ok;
    };
    if (is_file($gz)) {
        check('300 gzopen/gzclose + compress.zlib reads (descriptors closed once)', $churn($gz));
    }
    if (is_dir('/sdcard')) {
        @mkdir('/sdcard/data');
        $f = '/sdcard/data/t3f5.gz';
        $h = gzopen($f, 'wb9');
        $w = gzwrite($h, $text);
        gzclose($h);
        check('gzwrite to card ' . filesize($f) . ' B', $w === strlen($text) && implode('', gzfile($f)) === $text && gzdecode(file_get_contents($f)) === $text);
        $n = file_put_contents('compress.zlib:///sdcard/data/t3f5b.gz', $text);
        check('compress.zlib write', $n === strlen($text) && gzdecode(file_get_contents('/sdcard/data/t3f5b.gz')) === $text, filesize('/sdcard/data/t3f5b.gz') . ' B');
        check('300 gzopen/gzclose on the card', $churn($f));
    }
}
echo "memory " . memory_get_usage() . ", peak " . memory_get_peak_usage() . "\n";
if (function_exists('sys_psram_free')) {
    echo "heap at end: psram " . sys_psram_free() . " B, internal " . sys_heap_free() . " B\n";
}


echo "\n=== regression: PCRE lookaheads and preg_* (GCC strict aliasing, -Os/-O2) ===\n";
echo "PCRE " . PCRE_VERSION . "\n";
$rxProbes = [
 'la255' => ['/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})/', 'a@b.it'],
 'la65' => ['/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)/', 'a@b.it'],
 'local' => ['/^(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)@/', 'a@b.it'],
 'dom126' => ['/^a@(?:(?:[a-z0-9]+(?:-+[a-z0-9]+)*\.){1,126}){1,}[a-z]+$/iD', 'a@b.it'],
 'dom12' => ['/^a@(?:(?:[a-z0-9]+\.){1,12}){1,}[a-z]+$/iD', 'a@b.it'],
 'dom126s' => ['/^a@(?:[a-z0-9]+\.){1,126}[a-z]+$/', 'a@b.it'],
 'nest' => ['/^a@(?:(?:b\.){1,3}){1,}it$/', 'a@b.it'],
 'la64' => ['/^a@(?!.*[^.]{64,})b/', 'a@b.it'],
 'i' => ['/^A@B\.IT$/i', 'a@b.it'],
 'D' => ['/^a@b\.it$/D', 'a@b.it'],
];
foreach ($rxProbes as $k => [$rx, $s]) {
    check("preg_match {$k}", @preg_match($rx, $s) === 1, 'error ' . preg_last_error());
}
check('lookahead /^(?!x)a/ matches a', preg_match('/^(?!x)a/', 'a') === 1);
check('lookahead /^(?!x)a/ rejects x', preg_match('/^(?!x)a/', 'x') === 0);
check('lookahead /^a@(?!.*[^.]{64,})b/', preg_match('/^a@(?!.*[^.]{64,})b/', 'a@b.it') === 1);
check('lookahead long label rejected', preg_match('/^a@(?!.*[^.]{64,})b/', 'a@b' . str_repeat('c', 70) . '.it') === 0);
check('positive lookahead', preg_match('/a(?=b)/', 'ab') === 1 && preg_match('/a(?=b)/', 'ac') === 0);
check('lookbehind', preg_match('/(?<=a)b/', 'ab') === 1 && preg_match('/(?<!a)b/', 'ab') === 0 && preg_match('/(?<!a)b/', 'cb') === 1);
check('named groups', preg_match('/(?<y>\d{4})-(?<m>\d{2})/', 'on 2026-10-18', $mm) === 1 && $mm['y'] === '2026' && $mm['m'] === '10');
check('preg_match_all', preg_match_all('/\d+/', 'a1b22c333', $all) === 3 && $all[0] === ['1', '22', '333']);
check('preg_replace', preg_replace('/(\w+) (\w+)/', '$2 $1', 'hello world') === 'world hello');
check('preg_replace_callback', preg_replace_callback('/\d+/', fn ($m) => $m[0] * 2, 'a1 b22 c333') === 'a2 b44 c666');
check('preg_split', preg_split('/[\s,]+/', "a, b  c,d", -1, PREG_SPLIT_NO_EMPTY) === ['a', 'b', 'c', 'd']);
check('preg_quote', preg_quote('a.b*c?') === 'a\.b\*c\?');
check('preg_grep', preg_grep('/^a/', ['ab', 'ba', 'ac']) === [0 => 'ab', 2 => 'ac']);
check('/u unicode', preg_match('/^\p{L}+$/u', 'àèìòù') === 1 && preg_match('/^.{5}$/u', 'àèìòù') === 1 && preg_match('/^\p{L}+$/u', 'ab1') === 0);
check('/u invalid utf-8', @preg_match('/./u', "\xff") === false && preg_last_error() === PREG_BAD_UTF8_ERROR);
check('/i /m /s', preg_match('/^b$/im', "a\nB") === 1 && preg_match('/a.b/s', "a\nb") === 1);
check('FILTER_VALIDATE_EMAIL valid', filter_var('a@b.it', FILTER_VALIDATE_EMAIL) === 'a@b.it');
check('FILTER_VALIDATE_EMAIL valid 2', filter_var('user.name+tag@example.com', FILTER_VALIDATE_EMAIL) === 'user.name+tag@example.com');
check('FILTER_VALIDATE_EMAIL invalid', filter_var('not-an-email', FILTER_VALIDATE_EMAIL) === false && filter_var('a@@b.it', FILTER_VALIDATE_EMAIL) === false && filter_var('a@b', FILTER_VALIDATE_EMAIL) === false);
check('FILTER_VALIDATE_EMAIL long label', filter_var('a@' . str_repeat('c', 64) . '.it', FILTER_VALIDATE_EMAIL) === false);
check('FILTER_VALIDATE_URL', filter_var('https://example.com/x?y=1', FILTER_VALIDATE_URL) !== false && filter_var('nourl', FILTER_VALIDATE_URL) === false);
check('FILTER_VALIDATE_REGEXP lookahead', filter_var('abc', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(?!x)\w+$/']]) === 'abc');
if (function_exists('mb_ereg')) {
    check('mb_ereg', mb_ereg('^a(b+)c$', 'abbbc', $mr) === true && $mr[1] === 'bbb');
}

echo "=== regression totals: " . ($GLOBALS['checks'] ?? 0) . " checks, " . ($GLOBALS['fails'] ?? 0) . " failed\n";

echo "=== end of checks: memory_limit " . ini_get('memory_limit') . ", memory " . memory_get_usage() . ", peak " . memory_get_peak_usage() . "\n";

function setup(): void
{
    echo "setup\n";
}

function loop(int $tick): void
{
    if ($tick % 50000 === 0) {
        echo "tick {$tick}, memory " . memory_get_usage() . "\n";
    }
}
