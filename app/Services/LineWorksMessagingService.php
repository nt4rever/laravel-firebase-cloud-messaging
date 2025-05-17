<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LineWorksMessagingService
{
    /**
     * Get the access token from Line Works.
     */
    public function getAccessToken()
    {
        $privateKey = config(key: 'lineworks.private_key');
        $ttl = 3600;
        $now = now();
        $payload = [
            'iss' => config(key: 'lineworks.client_id'),
            'sub' => config(key: 'lineworks.service_account_id'),
            'iat' => $now->timestamp,
            'exp' => $now->addSeconds($ttl)->timestamp,
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        $response = Http::asForm()
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post('https://auth.worksmobile.com/oauth2/v2.0/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
                'client_id' => config(key: 'lineworks.client_id'),
                'client_secret' => config(key: 'lineworks.client_secret'),
                'scope' => 'bot',
            ]);

        return $response->json();
    }
}
