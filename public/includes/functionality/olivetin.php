<?php

declare(strict_types=1);

use OliveTin\Api\OliveTinApiException;
use OliveTin\Api\OliveTinClient;

/**
 * @return array{0: string, 1: string, 2: string} baseUrl, apiKey, apiPrefix
 */
function lanlistResolveOliveTinConfig(): array
{
    $baseUrl = '';
    if (defined('OLIVETIN_BASE_URL') && (string) OLIVETIN_BASE_URL !== '') {
        $baseUrl = (string) OLIVETIN_BASE_URL;
    }
    if ($baseUrl === '') {
        $fromEnv = getenv('OLIVETIN_BASE_URL');
        if ($fromEnv !== false && $fromEnv !== '') {
            $baseUrl = $fromEnv;
        }
    }

    $apiKey = '';
    if (defined('OLIVETIN_API_KEY') && (string) OLIVETIN_API_KEY !== '') {
        $apiKey = (string) OLIVETIN_API_KEY;
    }
    if ($apiKey === '') {
        $fromEnv = getenv('OLIVETIN_API_KEY');
        if ($fromEnv !== false && $fromEnv !== '') {
            $apiKey = $fromEnv;
        }
    }

    $apiPrefix = '/api';
    if (defined('OLIVETIN_API_PREFIX') && (string) OLIVETIN_API_PREFIX !== '') {
        $apiPrefix = (string) OLIVETIN_API_PREFIX;
    }

    return [$baseUrl, $apiKey, $apiPrefix];
}

function lanlistOliveTinConfigured(): bool
{
    [$baseUrl, $apiKey] = lanlistResolveOliveTinConfig();

    return $baseUrl !== '' && $apiKey !== '';
}

function lanlistOrganizerFaviconOliveTinBindingId(): string
{
    if (defined('OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH') && (string) OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH !== '') {
        return (string) OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH;
    }
    $fromEnv = getenv('OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH');
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }

    return '';
}

function lanlistOliveTinClient(): OliveTinClient
{
    static $client;

    if ($client instanceof OliveTinClient) {
        return $client;
    }

    [$baseUrl, $apiKey, $apiPrefix] = lanlistResolveOliveTinConfig();
    if ($baseUrl === '' || $apiKey === '') {
        throw new \InvalidArgumentException('OliveTin is not configured (set OLIVETIN_BASE_URL and OLIVETIN_API_KEY).');
    }

    $client = new OliveTinClient(rtrim($baseUrl, '/'), $apiKey, $apiPrefix);

    return $client;
}

/**
 * Probe OliveTin via Init RPC (TLS, API path, and bearer auth).
 *
 * @return array{
 *     configured: bool,
 *     ok: bool,
 *     baseUrl: string,
 *     error: ?string,
 *     init: ?array<string, mixed>,
 * }
 */
function lanlistOliveTinConnectionTest(): array
{
    [$baseUrl] = lanlistResolveOliveTinConfig();
    $result = [
        'configured' => lanlistOliveTinConfigured(),
        'ok' => false,
        'baseUrl' => rtrim($baseUrl, '/'),
        'error' => null,
        'init' => null,
    ];

    if (!$result['configured']) {
        $result['error'] = 'OliveTin is not configured (set OLIVETIN_BASE_URL and OLIVETIN_API_KEY).';

        return $result;
    }

    try {
        $result['init'] = lanlistOliveTinClient()->init();
        $result['ok'] = true;
    } catch (OliveTinApiException $e) {
        $message = $e->getMessage();
        $status = $e->httpStatus();
        if ($status > 0) {
            $message .= ' (HTTP ' . $status . ')';
        }
        $result['error'] = $message;
    } catch (\InvalidArgumentException $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}
