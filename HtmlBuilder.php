<?php

namespace biv\core;

use JetBrains\PhpStorm\Pure;
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
     * @param string $tag
     * @param array  $attributes
     * @param null   $content
     *
     * @return string
     */
    public function tag(string $tag, array $attributes = [], $content = null): string
    {

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
            if ($url && $url !== '#' && $url !== self::voidHref() && !self::hasPermissionUrl($url)) {
                $return_empty = true;
            }
            if (!$return_empty && $url_data) {
                $allowed = false;
                foreach ($url_data as $d_url) {
                    if ($d_url === '#' || $d_url === self::voidHref() || self::hasPermissionUrl($d_url)) {
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

        $tag = strtolower($tag);

        $csrf_tag = '';
        if ($tag === 'form' && isset($attributes['method']) && !isset($attributes['action']) && strtolower($attributes['method']) === 'post') {
            $attributes['action'] = self::uri();
        }

        $content_html = self::arrayToString($content);

        $start_tag = "<$tag" . static::renderTagAttributes($attributes, $tag) . '>';

        $html = $start_tag;

        if (in_array($tag, static::$autoClosedTags, true)) {
            $output = $html;
        } else {
            $output = in_array(strtolower($tag), static::$voidElements, true) ? $html : "$html$csrf_tag$content_html</$tag>";
        }

        return $output;
    }

    public static function voidHref(): string
    {
        return 'javascript:void(0)';
    }

    public static function hasPermissionUrl($url = null): bool
    {
        /// check if user has permission show link
        return (bool)$url;
    }

    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * @param        $data
     * @param string $delimiter
     *
     * @return string
     */
    public static function arrayToString($data, string $delimiter = ''): string
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
                        $str .= $delimiter . '';
                    } elseif (is_callable($val)) {
                        $str .= self::arrayToString($val, $delimiter);
                    }
                }
            }
        }

        return $str;
    }

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

        $html = '';

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
                    $html .= " $name";
                }
            } elseif ($name === 'href' || $name === 'src' || $name === 'action') {
                if ($value) {
                    $html .= " $name=\"" . self::link($value) . '"';

                }
            } elseif (is_array($value)) {
                if (str_contains('data-', $name)) {
                    foreach ($value as $data_name => $data_val) {
                        if (is_array($data_val)) {
                            $html .= " $name-$data_name='" . self::htmlEncode($data_val) . "'";
                        } else {
                            $html .= " $name-$data_name=\"" . self::textEncode($data_val) . '"';
                        }
                    }
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . self::textEncode(self::styleFromArray($value)) . '"';
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . self::textEncode(implode(' ', $value)) . '"';
                } else {
                    $html .= " $name='" . self::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" . self::textEncode($value) . '"';
            }
        }

        return $html;
    }

    /**
     * @param null $link
     *
     * @return bool
     */
    public static function isCurrent($link = null): bool
    {
        return self::link($link) === self::link(self::uri());
    }

    /**
     * @param      $url
     * @param bool $relative
     *
     * @return string
     */
    public static function link($url, bool $relative = true): string
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

    public static function toRoute(array $url): string
    {
        $query_arr = $url;
        unset($query_arr[0]);
        $query = http_build_query($query_arr);
        return $url[0] . ($query ? ('?' . $query) : '');
    }

    public static function base(): string
    {
        return str_replace('www.', '', $_SERVER['SERVER_NAME'] ?? '');
    }

    /**
     * @param $link
     *
     * @return bool
     */
    public static function isExternal($link): bool
    {
        $url = $link;
        if (is_array($link)) {
            $url = $link[0] ?? '';
        }
        return str_contains($url, '://');
    }

    public static function textEncode(string $text = null, bool $doubleEncode = true): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8',
            $doubleEncode);
    }

    /**
     * @param $value
     *
     * @return string
     */
    #[Pure] public static function htmlEncode($value): string
    {
        return static::textEncode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * Konvertuje pole do inline stylu
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
