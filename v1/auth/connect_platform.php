<?php
// Entry to start OAuth connection for platforms (v2)
require_once __DIR__ . '/../initialize.php';

use BO_System\Helpers\LogHelper;

$platform = $_GET['platform'] ?? '';
if (empty($platform)) {
    LogHelper::error('No platform specified for connection', ['file' => __FILE__]);
    http_response_code(400);
    echo 'Missing platform parameter';
    exit();
}

$platform = strtoupper($platform);
$platformConfig = defined('ECOMMERCE_PLATFORMS') && isset(ECOMMERCE_PLATFORMS[$platform]) ? ECOMMERCE_PLATFORMS[$platform] : null;
if (!$platformConfig) {
    LogHelper::error('Unsupported platform: ' . $platform);
    http_response_code(400);
    echo 'Unsupported platform';
    exit();
}

// Generate state to mitigate CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state_' . strtolower($platform)] = $state;
LogHelper::info("Generated OAuth state for {$platform}: {$state}");

$appKey = $platformConfig['APP_KEY'] ?? '';
$serviceId = $platformConfig['SERVICE_ID'] ?? '';
$scopes = $platformConfig['SCOPES'] ?? '';
$callbackUri = $platformConfig['CALLBACK_URI'] ?? '';

$oauthUrl = $platformConfig['OAUTH_AUTHORIZE_URL'] . "?" .
    "app_key=" . urlencode($appKey) .
    "&service_id=" . urlencode($serviceId) .
    "&redirect_uri=" . urlencode($callbackUri) .
    "&state=" . urlencode($state) .
    "&scope=" . urlencode($scopes);

LogHelper::info('Redirecting to OAuth URL for ' . $platform . ': ' . $oauthUrl);
header('Location: ' . $oauthUrl);
exit();
