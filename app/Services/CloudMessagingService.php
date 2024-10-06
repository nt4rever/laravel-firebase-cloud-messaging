<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CloudMessagingService
{
    /**
     * Get Google Oauth2 access token.
     *
     * @throws \RuntimeException
     */
    private function getAccessToken(): string
    {
        $privateKey = config(key: 'firebase.credentials.private_key');
        $ttl = config('firebase.ttl', 3600);
        $now = now();
        $payload = [
            'iss' => config('firebase.credentials.client_email'),
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now->addSeconds($ttl)->timestamp,
            'iat' => $now->timestamp,
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        $response = Http::asJson()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('[Google Oauth2]: Error when exchange access token.');
        }

        return $response->json('access_token');
    }

    /**
     * Get Google Oauth2 access token from cache storage.
     *
     * @throws \RuntimeException
     */
    private function getAccessTokenFromCache(): string
    {
        $cacheKey = config('firebase.cache_key');

        $ttl = config('firebase.ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->getAccessToken();
        });
    }

    public function send(array $message)
    {
        $accessToken = $this->getAccessTokenFromCache();

        $projectId = config('firebase.credentials.project_id');

        $response = Http::asJson()
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
            ])
            ->post(
                "https://fcm.googleapis.com/v1/projects/$projectId/messages:send",
                [
                    'message' => $message,
                ]
            );

        return [
            'outcome' => $response->successful() ? 'SUCCESS' : 'FAIL',
            'response' => $response->json(),
        ];
    }

    public function sendAll(array $messages)
    {
        $accessToken = $this->getAccessTokenFromCache();

        $projectId = config('firebase.credentials.project_id');

        $responses = Http::pool(
            fn (Pool $pool) => collect($messages)->map(
                fn ($message) => $pool->asJson()
                    ->withHeaders([
                        'Authorization' => "Bearer $accessToken",
                    ])
                    ->post(
                        "https://fcm.googleapis.com/v1/projects/$projectId/messages:send",
                        [
                            'message' => $message,
                        ]
                    )
            )
        );

        $results = [];

        foreach ($responses as $response) {
            $results[] = [
                'outcome' => $response->successful() ? 'SUCCESS' : 'FAIL',
                'response' => $response->json(),
            ];
        }

        return $results;
    }
}
