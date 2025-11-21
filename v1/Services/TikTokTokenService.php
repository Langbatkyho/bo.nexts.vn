<?php
namespace BO_System\Services;

use Exception;

class TikTokTokenService
{
    private $appKey;
    private $appSecret;
    private $tokenUrl;
    private $refreshTokenUrl;

    public function __construct()
    {
        // Lấy config từ hằng số ECOMMERCE_PLATFORMS
        if (!defined('ECOMMERCE_PLATFORMS') || !isset(ECOMMERCE_PLATFORMS['TIKTOK'])) {
            throw new Exception("TikTok platform configuration not found.");
        }

        $platformConfig = ECOMMERCE_PLATFORMS['TIKTOK'];
        
        $this->appKey = $platformConfig['APP_KEY'] ?? null;
        $this->appSecret = $platformConfig['APP_SECRET'] ?? null;
        $this->tokenUrl = $platformConfig['TOKEN_URL'] ?? null;
        $this->refreshTokenUrl = $platformConfig['REFRESH_TOKEN_URL'] ?? $this->tokenUrl;

        if (!$this->appKey || !$this->appSecret || !$this->tokenUrl) {
            throw new Exception("TikTok platform configuration is incomplete.");
        }
    }

    /**
     * Làm mới access token và refresh token
     *
     * @param string $refreshToken Refresh token hiện tại
     * @return array Dữ liệu token mới
     * @throws Exception Nếu có lỗi xảy ra
     */
    public function refreshAccessToken($refreshToken)
    {
        if (empty($refreshToken)) {
            throw new Exception("Refresh token is required.");
        }

        // Xây dựng URL GET cho việc làm mới token
        $params = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $tokenRefreshUrl = $this->refreshTokenUrl . '?' . http_build_query($params);

        // Gọi API TikTok
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenRefreshUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("cURL Error: " . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error {$httpCode}: " . $response);
        }

        // Parse JSON response
        $tokenData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }

        if (!isset($tokenData['data'])) {
            throw new Exception("Missing 'data' key in response: " . $response);
        }

        if (isset($tokenData['code']) && $tokenData['code'] !== 0) {
            $errorMessage = $tokenData['message'] ?? 'Unknown error';
            throw new Exception("TikTok API Error (Code {$tokenData['code']}): {$errorMessage}");
        }

        // Trích xuất dữ liệu token
        $newAccessToken = $tokenData['data']['access_token'] ?? null;
        $newRefreshToken = $tokenData['data']['refresh_token'] ?? null;
        $newAccessTokenExpiresIn = $tokenData['data']['access_token_expire_in'] ?? null;
        $newRefreshTokenExpiresIn = $tokenData['data']['refresh_token_expire_in'] ?? null;

        if (!$newAccessToken || !$newAccessTokenExpiresIn) {
            throw new Exception("Missing essential token data in response.");
        }

        // Chuyển đổi timestamp sang datetime
        $newAccessTokenExpiresAt = date('Y-m-d H:i:s', $newAccessTokenExpiresIn);
        $newRefreshTokenExpiresAt = $newRefreshTokenExpiresIn ? date('Y-m-d H:i:s', $newRefreshTokenExpiresIn) : null;

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'access_token_expires_at' => $newAccessTokenExpiresAt,
            'refresh_token_expires_at' => $newRefreshTokenExpiresAt,
            'raw_response' => $tokenData
        ];
    }

    /**
     * Kiểm tra xem shop có cần làm mới token không
     *
     * @param array $shop Dữ liệu shop
     * @param int $thresholdSeconds Ngưỡng thời gian (giây)
     * @return array ['needs_refresh' => bool, 'reason' => string]
     */
    public function checkTokenRefreshNeeded($shop, $thresholdSeconds = 86400)
    {
        $accessTokenExpiresAt = strtotime($shop['access_token_expires_at'] ?? '');
        $refreshTokenExpiresAt = strtotime($shop['refresh_token_expires_at'] ?? '');
        $currentTime = time();

        if ($accessTokenExpiresAt === false) {
            return [
                'needs_refresh' => false,
                'reason' => 'Invalid access token expiration date'
            ];
        }

        $refreshThresholdAccessToken = $accessTokenExpiresAt - $thresholdSeconds;
        $refreshThresholdRefreshToken = $refreshTokenExpiresAt !== false ? $refreshTokenExpiresAt - $thresholdSeconds : 0;

        $needsAccessTokenRefresh = $currentTime >= $refreshThresholdAccessToken;
        $needsRefreshTokenRefresh = $refreshTokenExpiresAt !== false && $currentTime >= $refreshThresholdRefreshToken;

        if ($needsAccessTokenRefresh || $needsRefreshTokenRefresh) {
            $reasons = [];
            if ($needsAccessTokenRefresh) {
                $reasons[] = "Access token expires at " . date('Y-m-d H:i:s', $accessTokenExpiresAt);
            }
            if ($needsRefreshTokenRefresh) {
                $reasons[] = "Refresh token expires at " . date('Y-m-d H:i:s', $refreshTokenExpiresAt);
            }

            return [
                'needs_refresh' => true,
                'reason' => implode(', ', $reasons),
                'access_token_refresh' => $needsAccessTokenRefresh,
                'refresh_token_refresh' => $needsRefreshTokenRefresh
            ];
        }

        return [
            'needs_refresh' => false,
            'reason' => 'Tokens are still valid',
            'access_token_expires_at' => date('Y-m-d H:i:s', $accessTokenExpiresAt),
            'refresh_token_expires_at' => $refreshTokenExpiresAt !== false ? date('Y-m-d H:i:s', $refreshTokenExpiresAt) : 'N/A'
        ];
    }
}
