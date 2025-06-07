<?php

return [
    'api_url' => env('ALQURAN_CLOUD_API_URL', 'http://api.alquran.cloud/v1'),
    'default_translation_edition' => env('DEFAULT_TRANSLATION_EDITION', 'en.sahih'), // Contoh default
    'cache_duration' => env('QURAN_API_CACHE_DURATION', 60 * 24 * 7), // Cache selama 7 hari (dalam menit)
]; 