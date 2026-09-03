<?php

use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('global-ip');
    RateLimiter::clear('api');
    RateLimiter::clear('webhooks');
    RateLimiter::clear('public-verification');
});

test('public verification route rate limits by IP address', function () {
    $ip = '192.168.1.100';

    for ($i = 0; $i < 20; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get(route('verify.admission', ['identifier' => 'NONEXISTENT_STUDENT']));
        
        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // 21st request from same IP should return 429 Too Many Requests
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->get(route('verify.admission', ['identifier' => 'NONEXISTENT_STUDENT']));

    $response->assertStatus(429);

    // A request from a different IP address should be allowed
    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.101'])
        ->get(route('verify.admission', ['identifier' => 'NONEXISTENT_STUDENT']));

    $this->assertNotEquals(429, $response->getStatusCode());
});

test('webhook routes rate limit by IP address', function () {
    $ip = '10.0.0.50';

    for ($i = 0; $i < 30; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('webhooks.paystack'));

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // 31st request should be throttled (429)
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson(route('webhooks.paystack'));

    $response->assertStatus(429);
});

test('whitelisted IP addresses bypass webhook throttling', function () {
    $whitelistedIp = '10.0.0.99';
    putenv('THROTTLE_WHITELIST_IPS=10.0.0.99');
    $_ENV['THROTTLE_WHITELIST_IPS'] = '10.0.0.99';

    for ($i = 0; $i < 35; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $whitelistedIp])
            ->postJson(route('webhooks.paystack'));

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    putenv('THROTTLE_WHITELIST_IPS=');
    $_ENV['THROTTLE_WHITELIST_IPS'] = '';
});

test('api routes rate limit by IP address', function () {
    $ip = '172.16.0.10';

    for ($i = 0; $i < 60; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/attendance/device');

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // 61st request should be throttled (429)
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/attendance/device');

    $response->assertStatus(429);
});

test('global web routes rate limit by IP address', function () {
    $ip = '192.168.2.50';

    for ($i = 0; $i < 120; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/');

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // 121st request should be throttled (429)
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->get('/');

    $response->assertStatus(429);
});
