<?php
namespace BO_System\TikTokShop;

class TikTokShopSigner
{
    public static function generateSign(
        string $path,
        array $queryParams,
        string $appSecret,
        string $bodyContent = '',
        string $contentType = ''
    ): string {
        ksort($queryParams);

        $queryStringForSignInternal = '';
        foreach ($queryParams as $key => $value) {
            $queryStringForSignInternal .= $key . $value;
        }

        $baseSignContent = $path . $queryStringForSignInternal;

        if (!empty($bodyContent) && strtolower($contentType) !== 'multipart/form-data') {
            $baseSignContent .= $bodyContent;
        }

        $signString = $appSecret . $baseSignContent . $appSecret;

        $sign = hash_hmac('sha256', $signString, $appSecret, false);

        return $sign;
    }
}
