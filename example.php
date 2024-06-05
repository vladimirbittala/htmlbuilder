<?php

/** @var string $content */

use biv\core\H;

$a = 1;
$b = 1;

return H::div(['class' => 'notice-alert' . ' auto_closed'], [
    H::div(['class' => 'notice-inner'], '[static-html id="666"]'),
    H::div(['class' => 'notice-shutter'], [
        H::a(['href' => ['/option/toggle-alert'], 'class' => 'open_close_notice open_notice', 'rel' => 'nofollow'], [
            H::span(['class' => 'notice_info_txt'], [
                H::img([
                    'src'    => '/img/ico/message.svg',
                    'class'  => 'ico',
                    'width'  => 20,
                    'height' => 20,
                    'alt'    => 'Lorem Ipsum'
                ]),
            ])
        ]),
        H::a(['class' => 'open_close_notice', 'rel' => 'nofollow', 'href' => ['/option/toggle-alert'], 'aria-label' => 'ttttt'], [
            H::div(['class' => 'close_notice'], [
                'x'
            ]),
            H::span(['class' => 'text-horizontal'], [
                static function () use ($a, $b) {
                    return $a + $b;
                }
            ]),
        ])
    ])
]);