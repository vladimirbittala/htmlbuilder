<?php /** @noinspection PhpUnused */
namespace biv\core;
use Stringable;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
/**
 * Static HTML helper.
 *
 * Environment (domain, IE detection) is no longer read from a global constant;
 * call {@see H::init()} with an explicit `$_SERVER`-like array for deterministic
 * behaviour. The domain is lazily initialised from `$_SERVER` when needed.
 *
 * @method static string div(array|null $attributes = null, mixed $content = null)
 * @method static string a(array|null $attributes = null, mixed $content = null)
 * @method static string span(array|null $attributes = null, mixed $content = null)
 * @method static string img(array|null $attributes = null)
 */
class H
{
    public const string ACCESS_TAG_BY_LOGGED = 'data-access-by-logged';
    public const string ACCESS_TAG_BY_ROLE_AND = 'data-access-by-role-and';
    public const string ACCESS_TAG_BY_ROLE_OR = 'data-access-by-role-or';
    public const string ACCESS_ONLY_LOGGED = 'only-logged';
    public const string ACCESS_ONLY_GUEST = 'only-guest';
    public const string ACCESS_TAG_URL = 'data-access_url';
    public const string VOID_HREF = '#';
    /** Tags available as `H::<tag>()` shortcuts (besides link/start/body helpers). */
    public const array TAG_METHODS = [
        'div', 'link', 'h1', 'h2', 'h3', 'h4', 'h5', 'input', 'label', 'option', 'select', 'ul', 'ol', 'li', 'a',
        'strong', 'span', 'img', 'table', 'thead', 'tbody', 'th', 'tr', 'td', 'br', 'hr', 'main', 'script', 'meta',
        'code', 'button', 'form', 'pre', 'textarea', 'svg', 'rect', 'header', 'p', 'style', 'footer', 'head',
        'title', 'figure', 'article', 'small', 'i', 'nav', 'body',
    ];
    public static bool $is_ie = false;
    public static string $language = 'cs';
    public static string $domain;
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
    public function __construct(?array $server = null)
    {
        self::init($server);
    }
    /**
     * Initialises domain and IE detection from the given server array (defaults to `$_SERVER`).
     */
    public static function init(?array $server = null): void
    {
        $server ??= $_SERVER;
        $name = $server['SERVER_NAME'] ?? 'localhost';
        self::$domain = str_replace('www.', '', is_string($name) && $name !== '' ? $name : 'localhost');
        $ua = $server['HTTP_USER_AGENT'] ?? '';
        self::$is_ie = self::detectIe(is_string($ua) ? $ua : '');
    }
    public static function detectIe(string $userAgent): bool
    {
        $ua = htmlentities($userAgent, ENT_QUOTES, 'UTF-8');
        return (bool)preg_match('~MSIE|Internet Explorer~i', $ua)
            || (str_contains($ua, 'Trident/7.0') && str_contains($ua, 'rv:11.0'));
    }
    /**
     * Shortcut for tags listed in {@see H::TAG_METHODS}, e.g. `H::div($attributes, $content)`.
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        $tag = strtolower($name);
        if (!in_array($tag, self::TAG_METHODS, true)) {
            throw new \BadMethodCallException('Call to undefined method ' . static::class . "::{$name}()");
        }
        $attributes = $arguments['attributes'] ?? $arguments[0] ?? null;
        $content = $arguments['content'] ?? $arguments[1] ?? null;
        return self::tag($tag, is_array($attributes) ? $attributes : null, $content);
    }
    final public static function tag(string $tag, array|null $attributes = null, mixed $content = null): string
    {
        $attributes ??= [];
        $tag = strtolower($tag);
        if (self::$is_ie && in_array($tag, self::$html5Tags, true)) { //IE fix
            $tag = 'div';
        }
        $html = "<{$tag}" . self::renderTagAttributes($attributes, $tag) . '>';
        if (in_array($tag, self::$autoClosedTags, true) || in_array($tag, self::$voidElements, true)) {
            return $html;
        }
        return $html . (is_string($content) ? $content : self::arrayToString($content)) . "</{$tag}>";
    }
    public static function arrayToString(mixed $data, string $delimiter = ''): string
    {
        if ($data === '' || $data === null) {
            return '';
        }
        if (is_string($data) || is_numeric($data)) {
            return (string)$data;
        }
        if ($data instanceof Stringable) {
            return (string)$data;
        }
        if (is_callable($data)) {
            return self::arrayToString($data(), $delimiter);
        }
        if (!is_array($data)) {
            return '';
        }
        $str = '';
        foreach ($data as $val) {
            if (is_array($val)) {
                $str .= $delimiter . self::arrayToString($val, $delimiter);
            } elseif (is_string($val) || is_numeric($val) || $val instanceof Stringable) {
                $str .= $delimiter . $val;
            } elseif ($val === null) {
                $str .= $delimiter;
            } elseif (is_callable($val)) {
                $str .= self::arrayToString($val, $delimiter);
            }
        }
        return $str;
    }
    /**
     * Converts a scalar-ish value to string; arrays and unsupported objects become ''.
     */
    public static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string)$value;
        }
        return '';
    }
    public static function encode(mixed $content, bool $doubleEncode = true): string
    {
        return htmlspecialchars(
            self::stringify($content),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $doubleEncode
        );
    }
    /**
     * Strings are HTML-escaped, any other value is JSON encoded (safe inside single or double quoted attributes).
     */
    public static function htmlEncode(mixed $value = null): string
    {
        if (is_string($value)) {
            return self::encode($value);
        }
        return self::jsonEncode($value);
    }
    public static function jsonEncode(mixed $value): string
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
        return $json === false ? '' : $json;
    }
    public static function styleFromArray(array $style): string
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "{$name}: " . self::stringify($value) . '; ';
        }
        return rtrim($result);
    }
    private static function isValidAttributeName(mixed $name): bool
    {
        return is_string($name) && preg_match('~^[A-Za-z_:][-A-Za-z0-9_:.]*$~', $name) === 1;
    }
    private static function renderUrlAttribute(string $name, mixed $value): string
    {
        $url = self::linkUrl($value);
        if ($url === '') {
            return '';
        }
        return " {$name}=\"" . self::encode($url, false) . '"';
    }
    private static function renderDataAttributes(string $name, array $values): string
    {
        $html = '';
        foreach ($values as $dataName => $dataVal) {
            $attr = "{$name}-{$dataName}";
            if (!self::isValidAttributeName($attr)) {
                continue;
            }
            if (is_array($dataVal)) {
                $html .= " {$attr}='" . self::jsonEncode($dataVal) . "'";
            } else {
                $html .= " {$attr}=\"" . self::encode($dataVal) . '"';
            }
        }
        return $html;
    }
    private static function renderArrayAttribute(string $name, array $value): string
    {
        if ($value === []) {
            return '';
        }
        if (str_starts_with($name, 'data-')) {
            return self::renderDataAttributes($name, $value);
        }
        if ($name === 'style') {
            return " {$name}=\"" . self::encode(self::styleFromArray($value)) . '"';
        }
        if ($name === 'class') {
            $classes = array_filter(
                array_map(static fn(mixed $v): string => trim(self::stringify($v)), $value),
                static fn(string $v): bool => $v !== ''
            );
            return $classes === [] ? '' : " {$name}=\"" . self::encode(implode(' ', $classes)) . '"';
        }
        return " {$name}='" . self::jsonEncode($value) . "'";
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
            if (!self::isValidAttributeName($name)) {
                continue;
            }
            if ($name === 'id' && ($value === null || $value === false || $value === '' || $value === [])) {
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $html .= " {$name}";
                }
            } elseif ($name === 'href' || $name === 'action' || $name === 'src') {
                if ($value) {
                    $html .= self::renderUrlAttribute($name, $value);
                }
            } elseif (is_array($value)) {
                $html .= self::renderArrayAttribute($name, $value);
            } elseif ($value !== null) {
                $html .= " {$name}=\"" . self::encode($value) . '"';
            }
        }
        return $html;
    }
    /**
     * Renders a URL from a string or a route array (`['/path', 'query' => 'value']`).
     * Dangerous schemes (javascript:, vbscript:) yield an empty string.
     */
    public static function linkUrl(mixed $url, bool $relative = true): string
    {
        if (is_array($url)) {
            $action = self::stringify($url[0] ?? '');
        } else {
            $action = self::stringify($url);
            $url = [$action];
        }
        if ($action !== '' && $action[0] === '#') {
            return $action;
        }
        $route = self::toRoute($url);
        if (preg_match('~^[\x00-\x20]*(?:javascript|vbscript):~i', preg_replace('~[\x00-\x20]+~', '', $route) ?? '')) {
            return '';
        }
        return $relative ? $route : self::base() . $route;
    }
    public static function base(): string
    {
        if (!isset(self::$domain)) {
            self::init();
        }
        return 'https://www.' . self::$domain;
    }
    public static function toRoute(array $url): string
    {
        $path = self::stringify($url[0] ?? '');
        unset($url[0]);
        $query = http_build_query($url);
        return $path . ($query !== '' ? '?' . $query : '');
    }
    final public static function wrap(array $values = []): string
    {
        return self::arrayToString($values);
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
}
