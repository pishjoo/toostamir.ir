<?php
/**
 * توس تعمیر - تنظیمات برنامه
 * مقادیر قابل‌تنظیم از طریق نصب‌wizard و پنل مدیریت (جدول settings)
 */
return [
    'name'        => 'توس تعمیر',
    'domain'      => 'https://toostamir.ir',
    'timezone'    => 'Asia/Tehran',
    'debug'       => false,               // در محیط تولید خاموش بماند
    'session'     => [
        'name'     => 'TTSESS',
        'lifetime' => 7200,
    ],
    'uploads'     => [
        'dir'          => __DIR__ . '/../public/uploads',
        'url'          => '/uploads',
        'max_size_kb'  => 3072,
        'allowed_ext'  => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    ],
    'paths' => [
        'root'    => dirname(__DIR__),
        'app'     => dirname(__DIR__) . '/app',
        'views'   => dirname(__DIR__) . '/app/Views',
        'storage' => dirname(__DIR__) . '/storage',
        'logs'    => dirname(__DIR__) . '/storage/logs',
        'cache'   => dirname(__DIR__) . '/storage/cache',
    ],
];
