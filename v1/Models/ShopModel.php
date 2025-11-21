<?php
namespace BO_System\Models;

use PDO;

class ShopModel extends BaseModel
{
    protected string $table = 'shop';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function upsertByPlatformShopId(array $data): bool
    {
        $sql = "INSERT INTO shop (
            platform, platform_shop_id, platform_shop_code, shop_name,
            access_token, refresh_token, access_token_expires_at, refresh_token_expires_at,
            created_at, updated_at
        ) VALUES (
            :platform, :platform_shop_id, :platform_shop_code, :shop_name,
            :access_token, :refresh_token, :access_token_expires_at, :refresh_token_expires_at,
            NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            platform_shop_code = VALUES(platform_shop_code),
            shop_name = VALUES(shop_name),
            access_token = VALUES(access_token),
            refresh_token = VALUES(refresh_token),
            access_token_expires_at = VALUES(access_token_expires_at),
            refresh_token_expires_at = VALUES(refresh_token_expires_at),
            updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':platform' => $data['platform'] ?? null,
            ':platform_shop_id' => $data['platform_shop_id'] ?? null,
            ':platform_shop_code' => $data['platform_shop_code'] ?? null,
            ':shop_name' => $data['shop_name'] ?? null,
            ':access_token' => $data['access_token'] ?? null,
            ':refresh_token' => $data['refresh_token'] ?? null,
            ':access_token_expires_at' => $data['access_token_expires_at'] ?? null,
            ':refresh_token_expires_at' => $data['refresh_token_expires_at'] ?? null,
        ]);
    }

    /**
     * Cập nhật thông tin shop theo ID.
     *
     * @param int $id ID của shop cần cập nhật.
     * @param array $data Dữ liệu cần cập nhật.
     * @return bool True nếu thành công, False nếu thất bại.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        // Chỉ cập nhật các trường được truyền vào
        if (isset($data['shop_name'])) {
            $fields[] = 'shop_name = :shop_name';
            $params[':shop_name'] = $data['shop_name'];
        }
        if (isset($data['platform'])) {
            $fields[] = 'platform = :platform';
            $params[':platform'] = $data['platform'];
        }
        if (isset($data['platform_shop_id'])) {
            $fields[] = 'platform_shop_id = :platform_shop_id';
            $params[':platform_shop_id'] = $data['platform_shop_id'];
        }
        if (isset($data['platform_shop_code'])) {
            $fields[] = 'platform_shop_code = :platform_shop_code';
            $params[':platform_shop_code'] = $data['platform_shop_code'];
        }
        if (isset($data['access_token'])) {
            $fields[] = 'access_token = :access_token';
            $params[':access_token'] = $data['access_token'];
        }
        if (isset($data['refresh_token'])) {
            $fields[] = 'refresh_token = :refresh_token';
            $params[':refresh_token'] = $data['refresh_token'];
        }
        if (isset($data['access_token_expires_at'])) {
            $fields[] = 'access_token_expires_at = :access_token_expires_at';
            $params[':access_token_expires_at'] = $data['access_token_expires_at'];
        }
        if (isset($data['refresh_token_expires_at'])) {
            $fields[] = 'refresh_token_expires_at = :refresh_token_expires_at';
            $params[':refresh_token_expires_at'] = $data['refresh_token_expires_at'];
        }

        // Luôn cập nhật updated_at
        $fields[] = 'updated_at = NOW()';

        if (empty($fields)) {
            return false; // Không có gì để cập nhật
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Lấy tất cả shop theo platform
     *
     * @param string $platform Tên platform (TIKTOK, SHOPEE, v.v.)
     * @return array Danh sách shop
     */
    public function getShopsByPlatform($platform)
    {
        $sql = "
            SELECT 
                id, 
                platform, 
                platform_shop_id, 
                shop_name, 
                access_token, 
                refresh_token, 
                access_token_expires_at, 
                refresh_token_expires_at,
                created_at,
                updated_at
            FROM shop 
            WHERE platform = :platform
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':platform', $platform, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật token của shop
     *
     * @param int $shopId ID của shop
     * @param string $accessToken Access token mới
     * @param string $refreshToken Refresh token mới
     * @param string $accessTokenExpiresAt Thời gian hết hạn access token
     * @param string|null $refreshTokenExpiresAt Thời gian hết hạn refresh token
     * @return bool True nếu cập nhật thành công
     */
    public function updateShopTokens($shopId, $accessToken, $refreshToken, $accessTokenExpiresAt, $refreshTokenExpiresAt = null)
    {
        $sql = "
            UPDATE shop SET
                access_token = :access_token,
                refresh_token = :refresh_token,
                access_token_expires_at = :access_token_expires_at,
                refresh_token_expires_at = :refresh_token_expires_at,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':access_token', $accessToken, PDO::PARAM_STR);
        $stmt->bindValue(':refresh_token', $refreshToken, PDO::PARAM_STR);
        $stmt->bindValue(':access_token_expires_at', $accessTokenExpiresAt, PDO::PARAM_STR);
        $stmt->bindValue(':refresh_token_expires_at', $refreshTokenExpiresAt, PDO::PARAM_STR);
        $stmt->bindValue(':id', $shopId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Lấy danh sách shop cần làm mới token (sắp hết hạn trong X giây)
     *
     * @param string $platform Platform cần lọc
     * @param int $thresholdSeconds Số giây trước khi hết hạn (mặc định 24h = 86400s)
     * @return array Danh sách shop cần refresh token
     */
    public function getShopsNeedingTokenRefresh($platform = 'TIKTOK', $thresholdSeconds = 86400)
    {
        $sql = "
            SELECT 
                id, 
                platform, 
                platform_shop_id, 
                shop_name, 
                access_token, 
                refresh_token, 
                access_token_expires_at, 
                refresh_token_expires_at
            FROM shop 
            WHERE platform = :platform
                AND (
                    access_token_expires_at <= DATE_ADD(NOW(), INTERVAL :threshold_seconds SECOND)
                    OR refresh_token_expires_at <= DATE_ADD(NOW(), INTERVAL :threshold_seconds SECOND)
                )
                AND refresh_token IS NOT NULL
                AND refresh_token != ''
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':platform', $platform, PDO::PARAM_STR);
        $stmt->bindValue(':threshold_seconds', $thresholdSeconds, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
