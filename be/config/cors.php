<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS – Cho phép Frontend (Vercel) gọi API sang Backend (Render)
    |--------------------------------------------------------------------------
    | VITE_API_BASE_URL trên Vercel sẽ trỏ về APP_FRONTEND_URL ở đây.
    | Thay '*' bằng domain Vercel cụ thể sau khi có link deploy, ví dụ:
    |   'https://smart-classroom-fe.vercel.app'
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
