<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SurrealDbClient
{
    public function query(string $surql, array $params = []): array
    {
        $response = $this->http()
            ->withToken($this->token())
            ->withHeaders([
                'Surreal-NS' => config('surrealdb.namespace'),
                'Surreal-DB' => config('surrealdb.database'),
            ])
            ->withQueryParameters($params)
            ->withBody($surql, 'text/plain')
            ->post('/sql');

        if ($response->failed()) {
            throw new RuntimeException('SurrealDB query failed: '.$response->body());
        }

        $results = $response->json();
        
        // SurrealDB /sql returns an array of results for each statement.
        // We check each result for potential errors.
        foreach ($results as $result) {
            if (isset($result['status']) && $result['status'] === 'ERR') {
                throw new RuntimeException('SurrealDB query error: ' . ($result['information'] ?? $result['result'] ?? 'Unknown error'));
            }
        }

        return $results;
    }

    private function token(): string
    {
        return Cache::remember('surrealdb.token', now()->addMinutes(50), function (): string {
            $payload = [
                'user' => config('surrealdb.username'),
                'pass' => config('surrealdb.password'),
            ];

            // Only include ns and db if the user is not root, 
            // as system users must authenticate at the system level.
            if (config('surrealdb.username') !== 'root') {
                if ($ns = config('surrealdb.namespace')) {
                    $payload['ns'] = $ns;
                }
                if ($db = config('surrealdb.database')) {
                    $payload['db'] = $db;
                }
            }

            $response = $this->http()->post('/signin', $payload);

            if ($response->failed()) {
                throw new RuntimeException('SurrealDB signin failed: '.$response->body());
            }

            $token = $response->json('token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('SurrealDB signin did not return a token.');
            }

            return $token;
        });
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('surrealdb.endpoint'))
            ->acceptJson()
            ->timeout(config('surrealdb.timeout'));
    }
}
