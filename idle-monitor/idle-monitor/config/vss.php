<?php

// config/vss.php

return [
    'base_url'  => env('VSS_BASE_URL', 'http://vss.ptdigital.co.id'),
    'username'  => env('VSS_USERNAME'),
    'password'  => env('VSS_PASSWORD'),   // MD5 hash dari password asli

    // Howen API specific configs
    'howen_api_url' => env('HOWEN_API_URL', 'https://vss.ptdigital.co.id/vss/'),
    'howen_username' => env('HOWEN_USERNAME'),
    'howen_password' => env('HOWEN_PASSWORD'),

    // Jumlah record per page saat tarik GPS track
    // Rekomendasi VSS: jangan terlalu besar, 200 aman
    'per_page'  => env('VSS_PER_PAGE', 200),

    // Jeda antar page dalam milidetik (agar tidak membebani server)
    'delay_between_pages_ms' => env('VSS_PAGE_DELAY_MS', 500),
];
