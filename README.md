# HtmlBuilder


PHP array[] to HTML string

Write .php files without html tags.

Simple usage:

```php
<?php
use biv\core\H;

echo H::div(['class' => 'alert alert-primary', 'role' => 'alert'], [
    'A simple primary alert—check it out!'
]);
```
Output:
```html
<div class="alert alert-primary" role="alert">A simple primary alert—check it out!</div>
```



Advanced usage:

```php
<?php
use biv\core\H;

$var1 = ['a', 'b', 'c'];
$var2 = 'Lorem Ipsum';


return
    H::div(['class' => 'alert alert-primary', 'role' => 'alert'], [
        H::div([], [
            static function () use ($var1, $var2) {
                $out = [];

                if ($var1) {
                    foreach ($var1 as $k => $v) {
                        $out[] = H::span(content: [
                            'A simple primary alert—check it out!',
                            H::strong([], $v)
                        ]);
                    }
                    $out[] = H::img([
                        'class'  => ['ico','me-3','me-sm-5'],
                        'src'    => '/image1.jpg',
                        'width'  => 28,
                        'height' => 28,
                        'alt'    => $var2,
                    ]);
                }
                return $out;
            },
            H::strong([], 'text2')
        ])
    ]);
```

Output:
```html
<div class="alert alert-primary" role="alert">
    <div>
        <span>A simple primary alert—check it out!
            <strong>a</strong>
        </span>
        <span>A simple primary alert—check it out!
            <strong>b</strong>
        </span>
        <span>A simple primary alert—check it out!
            <strong>c</strong>
        </span>
        <img class="ico me-3 me-sm-5" src="/image1.jpg" width="28" height="28" alt="Lorem Ipsum" title="Lorem Ipsum" loading="lazy">
        <strong>text2</strong>
    </div>
</div>
```
