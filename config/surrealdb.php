<?php

declare(strict_types=1);

return [
    'endpoint' => env('SURREALDB_ENDPOINT', 'http://127.0.0.1:8000'),
    'namespace' => env('SURREALDB_NAMESPACE', 'insan_taqwa'),
    'database' => env('SURREALDB_GRAPH_DATABASE', 'school_graph_v1'),
    'username' => env('SURREALDB_APP_USER', 'school_app'),
    'password' => env('SURREALDB_APP_PASS'),
    'role' => env('SURREALDB_APP_ROLE', 'app'),
    'timeout' => (int) env('SURREALDB_TIMEOUT', 15),
];
