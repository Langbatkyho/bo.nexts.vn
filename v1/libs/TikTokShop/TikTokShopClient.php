<?php
namespace BO_System\TikTokShop;

require_once __DIR__ . '/TikTokShopSigner.php';

use BO_System\Helpers\LogHelper;

class TikTokShopClient
{
    private string $appKey;
    private string $appSecret;
    private string $shopInfoApiUrl;

    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?string $accessTokenExpiresAt = null;
    private ?string $refreshTokenExpiresAt = null;

    public function __construct(string $appKey, string $appSecret, string $shopInfoApiUrl)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->shopInfoApiUrl = $shopInfoApiUrl;
    }

    private function log(string $message, string $level = 'info', array $context = []): void
    {
        switch ($level) {
            case 'error':
                LogHelper::error($message, $context);
                break;
            case 'warning':
                LogHelper::warning($message, $context);
                break;
            default:
                LogHelper::info($message, $context);
                break;
        }
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setAccessTokenExpiresAt(string $expiresAt): void
    {
        $this->accessTokenExpiresAt = $expiresAt;
    }

    public function setRefreshTokenExpiresAt(string $expiresAt): void
    {
        $this->refreshTokenExpiresAt = $expiresAt;
    }

    public function exchangeCodeForTokens(string $authorizationCode, string $tokenUrlBase): array
    {
        $params = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'auth_code' => $authorizationCode,
            'grant_type' => 'authorized_code'
        ];

        $tokenUrl = $tokenUrlBase . '?' . http_build_query($params);
        $this->log("TIKTOK Token GET URL: " . $tokenUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->log("Token Exchange Response (HTTP {$httpCode}): " . $response);

        if ($response === false || $httpCode !== 200) {
            $this->log("Failed to exchange code for tokens. HTTP: {$httpCode}. Error: {$curlError}", 'error');
            return ['error' => true, 'message' => "Failed to connect to TIKTOK. " . ($curlError ?: $response)];
        }

        $tokenData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['data'])) {
            $this->log("Invalid JSON or missing data in token response: " . ($response ?: 'empty'), 'error');
            return ['error' => true, 'message' => "Error parsing token response from TIKTOK or missing data."];
        }

        if (isset($tokenData['code']) && $tokenData['code'] !== 0) {
            $this->log("TIKTOK token API error: " . json_encode($tokenData), 'error');
            return ['error' => true, 'message' => "Error from TIKTOK API: " . ($tokenData['message'] ?? 'Unknown')];
        }

        $this->accessToken = $tokenData['data']['access_token'] ?? null;
        $this->refreshToken = $tokenData['data']['refresh_token'] ?? null;
        $this->accessTokenExpiresAt = isset($tokenData['data']['access_token_expire_in']) ? date('Y-m-d H:i:s', $tokenData['data']['access_token_expire_in']) : null;
        $this->refreshTokenExpiresAt = isset($tokenData['data']['refresh_token_expire_in']) ? date('Y-m-d H:i:s', $tokenData['data']['refresh_token_expire_in']) : null;

        return [
            'error' => false,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'access_token_expires_at' => $this->accessTokenExpiresAt,
            'refresh_token_expires_at' => $this->refreshTokenExpiresAt,
        ];
    }

    public function getShopInfo(): array
    {
        if (empty($this->accessToken)) {
            $this->log('Access Token is not set for getShopInfo', 'error');
            return ['error' => true, 'message' => 'Access Token missing'];
        }

        $ch = curl_init();
        $timestamp = time();

        $url_parts = parse_url($this->shopInfoApiUrl);
        $path = $url_parts['path'] ?? '';

        $paramsForSign = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
        ];

        $sign = TikTokShopSigner::generateSign($path, $paramsForSign, $this->appSecret);

        $finalQueryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];

        $fullUrlWithSign = $this->shopInfoApiUrl . '?' . http_build_query($finalQueryParams);
        $this->log('TIKTOK Shop Info URL: ' . $fullUrlWithSign, 'info');

        curl_setopt($ch, CURLOPT_URL, $fullUrlWithSign);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'x-tts-access-token: ' . $this->accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->log("Shop Info Response (HTTP {$httpCode}): " . $response);

        if ($response === false || $httpCode !== 200) {
            $this->log("Failed to get shop info. HTTP: {$httpCode}. Error: {$curlError}", 'error');
            return ['error' => true, 'message' => 'Failed to get shop info: ' . ($curlError ?: $response)];
        }

        $shopData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($shopData['data']['shops'][0])) {
            $this->log('Invalid JSON or missing shop data: ' . ($response ?: 'empty'), 'error');
            return ['error' => true, 'message' => 'Error parsing shop info response or missing data.'];
        }

        if (isset($shopData['code']) && $shopData['code'] !== 0) {
            $this->log('TIKTOK Shop API error: ' . json_encode($shopData), 'error');
            return ['error' => true, 'message' => 'Error from TIKTOK Shop API: ' . ($shopData['message'] ?? 'Unknown')];
        }

        $shop = $shopData['data']['shops'][0];
        return [
            'error' => false,
            'platform_shop_id' => $shop['id'] ?? null,
            'platform_shop_code' => $shop['code'] ?? null,
            'shop_name' => $shop['name'] ?? null
        ];
    }
}
