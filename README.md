# HtmlBuilder


PHP array[] to HTML string

Write .php files without html tags.

Simple using:

```php
<?php
/** @var HtmlBuilder $html */
$html->tag('div', ['class' => 'alert alert-primary', 'role' => 'alert'], 'A simple primary alert—check it out!');
```

```php
<?php
/** @var HtmlBuilder $html */

$variable_1 = true;
echo 
$html->tag('div', ['class' => 'alert alert-primary', 'role' => 'alert'], [
    $html->tag('span', [], [
        static function () use ($html, $variable_1) {
            $out = '';
            if ($variable_1) {
                $out .= $html->tag('span', [], [
                    'A simple primary alert—check it out!',
                ]);
            }
            return $out;
        },
        $html->tag('strong', [], 'text2')
    ])
]);
```

Output:
```html
<div class="alert alert-primary" role="alert"><span><span>A simple primary alert—check it out!</span><strong>text2</strong></span></div>
```
