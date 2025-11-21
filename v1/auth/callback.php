<?php
// OAuth callback handler for supported platforms (v2)
require_once __DIR__ . '/../initialize.php';

use BO_System\Helpers\LogHelper;
use BO_System\TikTokShop\TikTokShopClient;
use BO_System\Models\ShopModel;
use BO_System\Core\Config;

// Log
LogHelper::info('OAuth callback received', ['query' => $_GET]);

$receivedState = $_GET['state'] ?? null;
if (!$receivedState) {
    LogHelper::error('Missing state in callback');
    die('Authorization failed: Missing state parameter.');
}

$platform = '';
$expectedState = null;
foreach (ECOMMERCE_PLATFORMS as $pName => $pConfig) {
    if (isset($_SESSION['oauth_state_' . strtolower($pName)]) && $_SESSION['oauth_state_' . strtolower($pName)] === $receivedState) {
        $platform = $pName;
        $expectedState = $_SESSION['oauth_state_' . strtolower($pName)];
        unset($_SESSION['oauth_state_' . strtolower($pName)]);
        break;
    }
}

if (empty($platform)) {
    LogHelper::error('Could not determine platform from state: ' . $receivedState);
    die('Authorization failed: Invalid or expired state parameter.');
}

$currentPlatformConfig = ECOMMERCE_PLATFORMS[$platform] ?? null;
if (!$currentPlatformConfig) {
    LogHelper::error("Platform config missing for {$platform}");
    die('Authorization failed: Platform configuration missing.');
}

if ($receivedState !== $expectedState) {
    LogHelper::error("State mismatch for {$platform}");
    die('Authorization failed: Invalid state parameter.');
}

if (!isset($_GET['code'])) {
    $error = $_GET['error'] ?? 'unknown_error';
    $errorDescription = $_GET['error_description'] ?? 'No authorization code received.';
    LogHelper::error("Authorization failed for {$platform}. Error: {$error}, Desc: {$errorDescription}");
    die('Authorization failed: ' . htmlspecialchars($errorDescription));
}

$authorizationCode = $_GET['code'];
LogHelper::info('Received authorization code for ' . $platform, ['code' => $authorizationCode]);

$appKey = $currentPlatformConfig['APP_KEY'];
$appSecret = $currentPlatformConfig['APP_SECRET'];
$tokenUrlBase = $currentPlatformConfig['TOKEN_URL'];
$shopInfoApiUrl = $currentPlatformConfig['GET_SHOP_INFO_API'];

$shopName = null;
$platformShopId = null;
$platformShopCode = null;
$accessToken = null;
$refreshToken = null;
$accessTokenExpiresAt = null;
$refreshTokenExpiresAt = null;

switch ($platform) {
    case 'TIKTOK':
        // Use TikTokShopClient library
        require_once __DIR__ . '/../libs/TikTokShop/TikTokShopClient.php';
        $tiktokClient = new TikTokShopClient($appKey, $appSecret, $shopInfoApiUrl);

        $tokenResult = $tiktokClient->exchangeCodeForTokens($authorizationCode, $tokenUrlBase);
        if ($tokenResult['error']) {
            LogHelper::error('Token exchange failed: ' . $tokenResult['message']);
            die($tokenResult['message']);
        }

        $accessToken = $tokenResult['access_token'];
        $refreshToken = $tokenResult['refresh_token'];
        $accessTokenExpiresAt = $tokenResult['access_token_expires_at'];
        $refreshTokenExpiresAt = $tokenResult['refresh_token_expires_at'];

        $tiktokClient->setAccessToken($accessToken);
        $tiktokClient->setRefreshToken($refreshToken);

        $shopInfo = $tiktokClient->getShopInfo();
        if ($shopInfo['error']) {
            LogHelper::error('Failed to fetch shop info: ' . $shopInfo['message']);
            die($shopInfo['message']);
        }

        $platformShopId = $shopInfo['platform_shop_id'];
        $platformShopCode = $shopInfo['platform_shop_code'];
        $shopName = $shopInfo['shop_name'];
        break;
    default:
        LogHelper::error('Unsupported platform in callback: ' . $platform);
        die('Unsupported platform');
}

if (!$platformShopId || !$platformShopCode || !$accessToken || !$accessTokenExpiresAt) {
    LogHelper::error('Missing essential token/shop data', ['platform' => $platform, 'shop_id' => $platformShopId]);
    die('Could not retrieve full shop credentials');
}

try {
    $pdo = Config::connectPDO();
    $shopModel = new ShopModel($pdo);

    $shopModel->upsertByPlatformShopId([
        'platform' => $platform,
        'platform_shop_id' => $platformShopId,
        'platform_shop_code' => $platformShopCode,
        'shop_name' => $shopName,
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'access_token_expires_at' => $accessTokenExpiresAt,
        'refresh_token_expires_at' => $refreshTokenExpiresAt,
    ]);

    LogHelper::info('Saved tokens and shop info for ' . $platform, ['shop_id' => $platformShopId]);

    header('Location: /' . APP_VERSION . '/index.php?module=Shops&action=index&status=success&platform=' . urlencode($platform) . '&shop_name=' . urlencode($shopName));
    exit();

} catch (\Exception $e) {
    LogHelper::error('Database error while saving shop: ' . $e->getMessage());
    die('Error saving shop credentials.');
}
