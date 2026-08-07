<?php

declare(strict_types=1);

return [

    'gemini_models' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'LEMO_GEMINI_MODELS',
            'gemini-flash-lite-latest,gemini-flash-latest,gemini-2.5-flash,gemini-2.0-flash-lite'
        ))
    ))),

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('LEMO_GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout' => (int) env('GROQ_REQUEST_TIMEOUT', 45),
    ],

];
