<?php

namespace biv\core;

use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;

/**
 */
class HtmlBuilder
{
    public const ACCESS_TAG_URL = 'data-access_url';
    public static array $autoClosedTags = [
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
    public static array $voidElements = [
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

    /**
     * Base method
     *
     * @param string $tag
     * @param array  $attributes
     * @param null   $content
     *
     * @return string
     */
    public function tag(string $tag, array $attributes = [], $content = null): string
    {
        $tag = strtolower($tag);

        $return_empty = false;

        $url_data = ($attributes[self::ACCESS_TAG_URL] ?? '');
        $url_href = ($attributes['href'] ?? '');
        $url_action = ($attributes['action'] ?? '');
        $url_src = ($attributes['src'] ?? '');

        if ($url_data || $url_href || $url_action || $url_src) {
            $url = '';

            if ($url_href) {
                $url = $url_href;
            }
            if ($url_action) {
                $url = $url_action;
            }
            if ($url_src) {
                $url = $url_src;
            }
            if ($url && $url !== self::voidHref() && !self::hasPermissionUrl($url)) {
                $return_empty = true;
            }
            if (!$return_empty && $url_data) {
                $allowed = false;
                foreach ($url_data as $d_url) {
                    if ($d_url === self::voidHref() || self::hasPermissionUrl($d_url)) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    $return_empty = true;
                }
            }
        }

        if ($return_empty) {
            return '';
        }

        $csrf_tag = '';

        if ($tag === 'form' && isset($attributes['method']) && !isset($attributes['action']) && strtolower($attributes['method']) === 'post') {
            $attributes['action'] = self::uri();
        }

        $content_html = self::toString($content);
        $start_tag = "<$tag" . static::renderTagAttributes($attributes, $tag) . '>';
        $html = $start_tag;

        if (in_array($tag, static::$autoClosedTags, true)) {
            $output = $html;
        } else {
            $output = in_array(strtolower($tag), static::$voidElements, true) ? $html : "$html$csrf_tag$content_html</$tag>";
        }

        return $output;
    }

    /**
     * Link that does not reload the page
     *
     * @return string
     */
    public static function voidHref(): string
    {
        return '#'; // javascript:void(0) ???
    }

    /**
     * Hide HTML block, if access not allowed
     *
     * @param null $url
     *
     * @return bool
     */
    public static function hasPermissionUrl($url = null): bool
    {
        /// check if user has permission show link
        /// write your code HERE
        return (bool)$url;
    }

    /**
     * Get full URL
     *
     * @return string
     */
    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * Convert array, function, number to string
     *
     * @param        $data
     * @param string $delimiter
     *
     * @return string
     */
    public static function toString($data, string $delimiter = ''): string
    {

        $str = '';
        if ($data !== '' && $data !== null) {
            if (is_string($data) || is_numeric($data)) {
                $str .= $data;
            } elseif (is_callable($data)) {
                $str .= self::toString($data(), $delimiter);
            } elseif (is_array($data)) {
                foreach ($data as $val) {
                    if (is_array($val)) {
                        $str .= $delimiter . self::toString($val, $delimiter);
                    } elseif (is_string($val)) {
                        $str .= $delimiter . $val;
                    } elseif (is_numeric($val)) {
                        $str .= $delimiter . $val;
                    } elseif ($val === null) {
                        $str .= $delimiter . '';
                    } elseif (is_callable($val)) {
                        $str .= self::toString($val, $delimiter);
                    }
                }
            }
        }

        return $str;
    }

    /**
     * Build XML attributes from array
     *
     * @param array  $attributes
     * @param string $tag
     *
     * @return string
     */
    private static function renderTagAttributes(array $attributes, string $tag): string
    {

        if ($attributes === []) {
            return '';
        }

        if (isset($attributes['href'])) {
            $link_url = $attributes['href'];
            if ($tag !== 'meta' && self::isCurrent($link_url)) {
                isset($attributes['class']) ? $attributes['class'] .= ' active current-post' : $attributes['class'] = 'active current-post';
            }
            if (self::isExternal($link_url)) {
                isset($attributes['class']) ? $attributes['class'] .= ' external-link' : $attributes['class'] = 'external-link';
            }
        }
        if ($tag === 'img' && !isset($attributes['alt'])) {
            $attributes['alt'] = 'image';
        }

        $out = '';

        foreach ($attributes as $name => $value) {
            if (is_string($value) && $tag === 'input' && $name === 'value') {
                $value = self::textEncode($value);
            }

            //check duplicate ID ?, kill?
            if (($name === 'id') && !$value) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $out .= " $name";
                }
            } elseif ($name === 'href' || $name === 'src' || $name === 'action') {
                if ($value) {
                    $out .= " $name=\"" . self::link($value) . '"';
                }
            } elseif (is_array($value)) {
                if (str_contains('data-', $name)) {
                    foreach ($value as $data_name => $data_val) {
                        if (is_array($data_val)) {
                            $out .= " $name-$data_name='" . self::htmlEncode($data_val) . "'";
                        } else {
                            $out .= " $name-$data_name=\"" . self::textEncode($data_val) . '"';
                        }
                    }
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $out .= " $name=\"" . self::textEncode(self::styleFromArray($value)) . '"';
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $out .= " $name=\"" . self::textEncode(implode(' ', $value)) . '"';
                } else {
                    $out .= " $name='" . self::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $out .= " $name=\"" . self::textEncode($value) . '"';
            }
        }

        return $out;
    }

    /**
     * Compare anchor href with browser address
     *
     * @param null $link
     *
     * @return bool
     */
    public static function isCurrent(mixed $link = null): bool
    {
        return self::link($link) === self::link(self::uri());
    }

    /**
     * Build link
     *
     * @param mixed $url
     * @param bool  $relative
     *
     * @return string
     */
    public static function link(mixed $url, bool $relative = true): string
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

    /**
     * Build relative link from array
     *
     * @param array $url
     *
     * @return string
     */
    public static function toRoute(array $url): string
    {
        $query_arr = $url;
        unset($query_arr[0]);
        $query = http_build_query($query_arr);
        return $url[0] . ($query ? ('?' . $query) : '');
    }

    /*
     * Return base domain
     */
    public static function base(): string
    {
        return str_replace('www.', '', $_SERVER['SERVER_NAME'] ?? '');
    }

    /**
     * Safe string output
     *
     * @param string|array $link
     *
     * @return bool
     */
    public static function isExternal(string|array $link): bool
    {
        $url = $link;

        if (is_array($link)) {
            $url = $link[0] ?? '';
        }

        return str_contains($url, '://');
    }

    /**
     * Safe string
     *
     * @param string|null $text
     * @param bool        $doubleEncode
     *
     * @return string
     */
    public static function textEncode(string $text = null, bool $doubleEncode = true): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Safe HTML string output
     *
     * @param $value
     *
     * @return string
     */
    public static function htmlEncode($value): string
    {
        return static::textEncode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * Convert array to inline styles
     *
     * @param array $style
     *
     * @return string
     */
    public static function styleFromArray(array $style): string
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "$name: $value; ";
        }

        return $result === '' ? '' : rtrim($result);
    }

}
