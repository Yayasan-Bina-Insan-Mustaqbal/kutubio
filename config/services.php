<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'buku_indonesia' => [
        'base_url' => env('BUKU_INDONESIA_BASE_URL', 'https://khrlanamm.github.io/buku-indonesia/api/v1'),
        'timeout' => (int) env('BUKU_INDONESIA_TIMEOUT', 4),
        'connect_timeout' => (int) env('BUKU_INDONESIA_CONNECT_TIMEOUT', 2),
    ],
    'open_library' => [
        'base_url' => env('OPEN_LIBRARY_BASE_URL', 'https://openlibrary.org'),
        'timeout' => (int) env('OPEN_LIBRARY_TIMEOUT', 8),
        'connect_timeout' => (int) env('OPEN_LIBRARY_CONNECT_TIMEOUT', 3),
        'retries' => (int) env('OPEN_LIBRARY_RETRIES', 2),
    ],
    'google_books' => [
        'key' => env('GOOGLE_BOOKS_API_KEY'),
        'url' => 'https://www.googleapis.com/books/v1/volumes',
        'timeout' => 5,
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'vision_model' => env('OLLAMA_VISION_MODEL', 'glm-ocr'),
        'llm_model' => env('OLLAMA_LLM_MODEL', 'llama3.1:latest'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 180),
        'max_image_dimension' => (int) env('OLLAMA_MAX_IMAGE_DIMENSION', 1600),
    ],

];
