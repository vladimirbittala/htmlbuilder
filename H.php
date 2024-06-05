<?php /** @noinspection PhpUnused */
namespace biv\core;
use JetBrains\PhpStorm\Pure;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
define('BIV_SERVER', $_SERVER);
/**
 */
class H
{
    public const string ACCESS_TAG_BY_LOGGED = 'data-access-by-logged';
    public const string ACCESS_TAG_BY_ROLE_AND = 'data-access-by-role-and';
    public const string ACCESS_TAG_BY_ROLE_OR = 'data-access-by-role-or';
    public const string ACCESS_ONLY_LOGGED = 'only-logged';
    public const string ACCESS_ONLY_GUEST = 'only-guest';
    public const string ACCESS_TAG_URL = 'data-access_url';
    public static bool $is_ie = false;
    public static string $language = 'cs';
    public static string $domain;
    public const string VOID_HREF = '#';
    public function __construct()
    {
        self::$domain = str_replace('www.', '', BIV_SERVER['SERVER_NAME'] ?? 'localhost');
        $ua = BIV_SERVER['HTTP_USER_AGENT'] ?? '';
        $ua = $ua ?: '';
        $ua = htmlentities($ua, ENT_QUOTES, 'UTF-8');
        self::$is_ie = preg_match('~MSIE|Internet Explorer~i', $ua) || (str_contains($ua, 'Trident/7.0') && str_contains($ua, 'rv:11.0'));
    }
    public static array $html5Tags
        = [
            'main',
            'footer',
            'section',
            'small',
            'nav',
            'header',
            'figure',
            'article',
        ];
    public static array $autoClosedTags
        = [
            'img',
            'br',
            'hr',
            'input',
            'area',
            'link',
            'meta',
            'param',
            'base',
            'col',
            'command',
            'keygen',
            'source'
        ];
    public static array $voidElements
        = [
            'area',
            'base',
            'br',
            'col',
            'command',
            'embed',
            'hr',
            'img',
            'input',
            'keygen',
            'link',
            'meta',
            'param',
            'source',
            'track',
            'wbr',
        ];
    final public static function div(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('div', $attributes, $content);
    }
    final public static function tag(string $tag, array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        if (self::$is_ie && in_array($tag, self::$html5Tags, true)) { //IE fix
            $tag = 'div';
        }
        $tag = strtolower($tag);
        $content_html = is_string($content) ? $content : self::arrayToString($content);
        $html = "<{$tag}" . self::renderTagAttributes($attributes, $tag) . '>';
        if (in_array($tag, static::$autoClosedTags, true)) {
            $output = $html;
        } else {
            $output = in_array(strtolower($tag), static::$voidElements, true) ?
                $html : "{$html}{$content_html}</{$tag}>";
        }
        return $output;
    }
    public static function arrayToString(mixed $data, string $delimiter = ''): string
    {
        $str = '';
        if ($data !== '' && $data !== null) {
            if (is_string($data) || is_numeric($data)) {
                $str .= $data;
            } elseif (is_callable($data)) {
                $str .= self::arrayToString($data(), $delimiter);
            } elseif (is_array($data)) {
                foreach ($data as $val) {
                    if (is_array($val)) {
                        $str .= $delimiter . self::arrayToString($val, $delimiter);
                    } elseif (is_string($val)) {
                        $str .= $delimiter . $val;
                    } elseif (is_numeric($val)) {
                        $str .= $delimiter . $val;
                    } elseif ($val === null) {
                        $str .= $delimiter;
                    } elseif (is_callable($val)) {
                        $str .= self::arrayToString($val, $delimiter);
                    }
                }
            }
        }
        return $str;
    }
    public static function encode(string $content, bool $doubleEncode = true): string
    {
        return htmlspecialchars(
            $content,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $doubleEncode
        );
    }
    public static function htmlEncode(array|null $value = null): string
    {
        return static::encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );
    }
    #[Pure] public static function styleFromArray(array $style): string
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "{$name}: {$value}; ";
        }
        if ($result === '') {
            return '';
        }
        return rtrim($result);
    }
    private static function renderTagAttributes(array $attributes, string $tag): string
    {
        if ($attributes === []) {
            return '';
        }
        if (isset($attributes['href']) && $attributes['href'] === self::VOID_HREF) {
            $attributes['rel'] = 'nofollow noopener';
        }
        if ($tag === 'img') {
            if (isset($attributes['alt']) && !isset($attributes['title'])) {
                $attributes['title'] = $attributes['alt'];
            }
            if (!isset($attributes['loading'])) {
                $attributes['loading'] = 'lazy';
            }
        }
        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_string($value) && $tag === 'input' && $name === 'value') {
                //$value = Translator::removeInlineTrans($value);
                $value = self::encode($value);
            }
            if ($name === 'id' && !$value) {
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $html .= " {$name}";
                }
            } elseif ($name === 'href' || $name === 'action') {
                if ($value) {
                    $html .= " {$name}=\"" . self::linkUrl($value) . '"';
                }
            } elseif ($name === 'src') {
                if ($value) {
                    $link = self::linkUrl($value);
                    $html .= " {$name}=\"" . $link . '"';
                }
            } elseif (is_array($value)) {
                if (str_contains('data-', $name)) {
                    foreach ($value as $data_name => $data_val) {
                        if (is_array($data_val)) {
                            $html .= " {$name}-{$data_name}='" . self::htmlEncode($data_val) . "'";
                        } else {
                            $html .= " {$name}-{$data_name}=\"" . self::encode($data_val) . '"';
                        }
                    }
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " {$name}=\"" . self::encode(self::styleFromArray($value)) . '"';
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " {$name}=\"" . self::encode(
                            implode(' ',
                                array_filter(
                                    array_map('trim', $value), static function (mixed $value) {
                                    return $value !== '';
                                }
                                )
                            )) . '"';
                } else {
                    $html .= " {$name}='" . self::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " {$name}=\"" . self::encode($value) . '"';
            }
        }
        return $html;
    }
    public static function linkUrl(mixed $url, bool $relative = true): string
    {
        $action = '';
        if (is_string($url)) {
            $action = $url;
            $url = [$url];
        } elseif (is_array($url)) {
            $action = $url[0];
        }
        if ($action && strncmp($action, '#', 1) === 0) {
            return $action;
        }
        if ($relative !== false) {
            $route = self::toRoute($url);
        } else {
            $route = self::base() . self::toRoute($url);
        }
        return $route;
    }
    public static function base(): string
    {
        return 'https://www.' . self::$domain;
    }
    public static function toRoute(array $url): string
    {
        $query_arr = $url;
        unset($query_arr[0]);
        $query = http_build_query($query_arr);
        return $url[0] . ($query ? ('?' . $query) : '');
    }
    final public static function link(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('link', $attributes, $content);
    }
    final public static function wrap(array $values = []): string
    {
        return self::arrayToString($values);
    }
    final public static function h1(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('h1', $attributes, $content);
    }
    final public static function input(array $attributes = []): string
    {
        return self::tag('input', $attributes);
    }
    final public static function label(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('label', $attributes, $content);
    }
    final public static function option(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('option', $attributes, $content);
    }
    final public static function select(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('select', $attributes, $content);
    }
    final public static function ul(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('ul', $attributes, $content);
    }
    final public static function li(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('li', $attributes, $content);
    }
    final public static function a(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('a', $attributes, $content);
    }
    final public static function strong(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('strong', $attributes, $content);
    }
    final public static function span(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('span', $attributes, $content);
    }
    final public static function img(array $attributes = []): string
    {
        return self::tag('img', $attributes);
    }
    final public static function table(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('table', $attributes, $content);
    }
    final public static function th(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('th', $attributes, $content);
    }
    final public static function tr(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('tr', $attributes, $content);
    }
    final public static function td(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('td', $attributes, $content);
    }
    final public static function br(array $attributes = []): string
    {
        return self::tag('br', $attributes);
    }
    final public static function hr(array $attributes = []): string
    {
        return self::tag('hr', $attributes);
    }
    final public static function main(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('main', $attributes, $content);
    }
    final public static function script(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('script', $attributes, $content);
    }
    final public static function meta(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('meta', $attributes, $content);
    }
    final public static function code(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('code', $attributes, $content);
    }
    final public static function h2(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('h2', $attributes, $content);
    }
    final public static function button(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('button', $attributes, $content);
    }
    final public static function form(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('form', $attributes, $content);
    }
    final public static function pre(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('pre', $attributes, $content);
    }
    final public static function h3(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('h3', $attributes, $content);
    }
    final public static function textarea(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('textarea', $attributes, $content);
    }
    final public static function h4(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('h4', $attributes, $content);
    }
    final public static function svg(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('svg', $attributes, $content);
    }
    final public static function rect(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('rect', $attributes, $content);
    }
    final public static function header(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('header', $attributes, $content);
    }
    final public static function p(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('p', $attributes, $content);
    }
    final public static function style(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('style', $attributes, $content);
    }
    final public static function h5(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('h5', $attributes, $content);
    }
    final public static function footer(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('footer', $attributes, $content);
    }
    final public static function head(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('head', $attributes, $content);
    }
    final public static function title(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('title', $attributes, $content);
    }
    final public static function ol(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('ol', $attributes, $content);
    }
    final public static function figure(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('figure', $attributes, $content);
    }
    final public static function article(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('article', $attributes, $content);
    }
    final public static function thead(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('thead', $attributes, $content);
    }
    final public static function tbody(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('tbody', $attributes, $content);
    }
    final public static function small(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('small', $attributes, $content);
    }
    final public static function i(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('i', $attributes, $content);
    }
    final public static function nav(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('nav', $attributes, $content);
    }
    final public static function start(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        $attributes['lang'] = self::$language;
        return self::docType() . self::tag('html', $attributes, $content);
    }
    public static function docType(): string
    {
        return '<!doctype html>';
    }
    final public static function body(array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        return self::tag('body', $attributes, $content);
    }
}