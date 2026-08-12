<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Adsterra static banner inventory
    |--------------------------------------------------------------------------
    |
    | Keys are dimension labels used by adsterra_banner('160x300').
    | Invoke URL pattern: https://www.highperformanceformat.com/{key}/invoke.js
    |
    */
    'banners' => [
        '160x300' => [
            'key' => 'a7b09f72480831b06af8ab5aded701c7',
            'format' => 'iframe',
            'width' => 160,
            'height' => 300,
        ],
        '160x600' => [
            'key' => 'c8445809c86f7c60e9fb8ecdcdbf0142',
            'format' => 'iframe',
            'width' => 160,
            'height' => 600,
        ],
        '728x90' => [
            'key' => '963ab40eb348a56928c1c7524fca8db2',
            'format' => 'iframe',
            'width' => 728,
            'height' => 90,
        ],
        '320x50' => [
            'key' => 'd332d1def39068e89ad8bcf47332dfde',
            'format' => 'iframe',
            'width' => 320,
            'height' => 50,
        ],
        '468x60' => [
            'key' => '1f37ae2a2c9eaa11dea96b8f7fc46e3e',
            'format' => 'iframe',
            'width' => 468,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive feed breakpoints
    |--------------------------------------------------------------------------
    |
    | Used between list cards on Course / Funding indexes.
    |
    */
    'feed' => [
        'mobile' => '320x50',
        'tablet' => '468x60',
        'desktop' => '728x90',
        'breakpoints' => [
            'tablet' => 640,
            'desktop' => 1024,
        ],
    ],
];
