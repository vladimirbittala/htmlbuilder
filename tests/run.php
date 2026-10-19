<?php
// Dependency-free tests: php tests/run.php
require __DIR__ . '/../H.php';

use biv\core\H;

$failed = 0;
function check(string $name, mixed $expected, mixed $actual): void
{
    global $failed;
    if ($expected !== $actual) {
        $failed++;
        echo "FAIL {$name}\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
    }
}

H::init(['SERVER_NAME' => 'www.example.com', 'HTTP_USER_AGENT' => 'Mozilla/5.0']);
check('domain', 'example.com', H::$domain);
check('base', 'https://www.example.com', H::base());
check('div', '<div class="a b">x</div>', H::div(['class' => ['a', ' ', 'b']], 'x'));
check('data array', '<div data-x-a="1" data-x-b=\'{"k":"v"}\'></div>', H::div(['data-x' => ['a' => 1, 'b' => ['k' => 'v']]]));
check('json attr', '<div foo=\'{"a":1}\'></div>', H::div(['foo' => ['a' => 1]]));
check('style', '<p style="color: red; top: 0;">t</p>', H::p(['style' => ['color' => 'red', 'top' => 0]], 't'));
check('bool', '<input disabled>', H::input(['disabled' => true, 'readonly' => false]));
check('input value escaped once', '<input value="a&amp;b &quot;q&quot;">', H::input(['value' => 'a&b "q"']));
check('void', '<br>', H::br());
check('img', '<img src="/a.png" alt="x" title="x" loading="lazy">', H::img(['src' => '/a.png', 'alt' => 'x']));
check('href route', '<a href="/x?a=1&amp;b=2">l</a>', H::a(['href' => ['/x', 'a' => 1, 'b' => 2]], 'l'));
check('href void', '<a href="#" rel="nofollow noopener">l</a>', H::a(['href' => '#'], 'l'));
check('javascript url', '<a>l</a>', H::a(['href' => 'java script:alert(1)'], 'l'));
check('absolute', 'https://www.example.com/p', H::linkUrl('/p', false));
check('empty route', '', H::linkUrl([]));
check('empty id', '<div></div>', H::div(['id' => '', 'x' => null]));
check('invalid attr name', '<div></div>', H::div(['"><script>' => 'x']));
check('callable content', '<span>3</span>', H::span(null, [static fn() => 1 + 2]));
check('arrayToString', ',1,2,', H::arrayToString([1, 2, null], ','));
check('htmlEncode array', '{"a":"\u0022"}', H::htmlEncode(['a' => '"']));
check('wrap', 'ab', H::wrap(['a', 'b']));
check('start', '<!doctype html><html lang="cs"></html>', H::start());
check('tag case', '<div></div>', H::tag('DIV'));
H::init(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Trident/7.0; rv:11.0)']);
check('ie', true, H::$is_ie);
check('ie html5', '<div>x</div>', H::nav(null, 'x'));
check('localhost', 'localhost', H::$domain);
H::init([]);
try { H::foo(); check('unknown', true, false); } catch (BadMethodCallException) {}
echo $failed ? "{$failed} failed\n" : "All tests passed\n";
exit($failed ? 1 : 0);
