<?php
/**
 * File cấu hình chính của ứng dụng.
 * Chứa các hằng số cấu hình database và đường dẫn.
 */

// Đặt là 'development' khi đang code, 'production' khi chạy thật.
define('APP_ENV', 'development');

// --- CẤU HÌNH PHIÊN BẢN ---
define('APP_VERSION', 'v1');

// --- CẤU HÌNH TÊN MIỀN & BRANDNAME ---
define('BRAND_NAME', 'Nexts');
define('SYSTEM_NAME', 'Hệ thống quản trị nội bộ - Nexts Business Operations');
define('SUB_DOMAIN', 'bo.nexts.vn');
define('DOMAIN', 'nexts.vn');
define('EMAIL_ADMIN', 'hi@nexts');

// --- CẤU HÌNH LOGO & FAVICON ---
define('LOGO_WHITE_URL', '/' . APP_VERSION . '/uploads/2025/11/coolmom_logo_white_transparent.png');
define('LOGO_BLACK_URL', '/' . APP_VERSION . '/uploads/2025/11/coolmom_logo_black_transparent.png');
define('FAVICON_URL', '/' . APP_VERSION . '/uploads/2025/11/favicon.png');

// --- CẤU HÌNH DATABASE ---
define('DB_HOST_MYSQL', 'localhost');
define('DB_NAME_MYSQL', 'u165837370_bo');
define('DB_USER_MYSQL', 'u165837370_usr');
define('DB_PASS_MYSQL', 'jjYZUd@L9');
define('DB_CHARSET_MYSQL', 'utf8mb4');

// Mảng cấu hình database (được sử dụng bởi Core/Config)
$GLOBALS['app_db_config'] = [
    'DB_HOST'    => DB_HOST_MYSQL,
    'DB_NAME'    => DB_NAME_MYSQL,
    'DB_USER'    => DB_USER_MYSQL,
    'DB_PASS'    => DB_PASS_MYSQL,
    'DB_CHARSET' => DB_CHARSET_MYSQL,
];

// --- CẤU HÌNH HARAVAN ---
define('HARAVAN_STORE_DOMAIN', 'coolmom.myharavan.com');
define('HARAVAN_ACCESS_TOKEN', '0920963FD523573789874BE27270B8A8DC5D040AE49DE838988A07624178A499');

// --- CẤU HÌNH GEMINI ---
define('GEMINI_API_KEYS_BY_EMAIL', [
    'ninhvh.hkd@gmail.com' => 'AIzaSyCwd2GoOc8e_VY1u7EdyZN_t7piB2A4ejI',
    'chieubaocong@gmail.com' => 'AIzaSyBHTuTHYJPg-qOFL_EcjFjW9y0hrzWEYDc',
    'hi.medelavietnam@gmail.com' => 'AIzaSyAJOCRY3kRlZuUOhelU0hRISwOCWnfoj2M',
    'hi.fatzbabyvietnam@gmail.com' => 'AIzaSyDHnsb2C_WzVffNPgGDJr69EFX4H5TBrE',
    'haininhtb@gmail.com' => 'AIzaSyCyDf3aoFFuPUI52r5v7TU6K0pPMDvQi9w',
]);

// --- CẤU HÌNH EMAIL (SMTP) ---
define('SMTP_HOST', 'smtp.zoho.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'hi@coolmom.vn');
define('SMTP_PASSWORD', 'cBc911588!@#');
define('SMTP_FROM_EMAIL', 'hi@coolmom.vn');
define('SMTP_FROM_NAME', 'Coolmom - Mẹ tròn, con vuông');

// --- CẤU HÌNH API KEYS CHO CÁC HỆ THỐNG CÓ THỂ GỌI ---
define('EMAIL_SERVICE_API_KEYS', [
    'AIzaSyCyDf3aoFFuPUI52r5v7TU6K0pPMDvQi9w' => 'affiliate.coolmom.vn',
    'AIzaSyDHnsb2C_WzVffNPgGDJr69EFX4H5TBrE' => 'bo.coolmom.vn',
]);

// --- CẤU HÌNH API KEY ĐỂ GỌI CỤC BỘ ---
define('EMAIL_SERVICE_API_KEY', 'AIzaSyDHnsb2C_WzVffNPgGDJr69EFX4H5TBrE');

// --- COOLMOM AFFILIATE API URL
define('AFFILIATE_API_BASE_URL', 'https://affiliate.coolmom.vn/api/');

// --- CẤU HÌNH CORS - Danh sách các domain được phép gọi API ---
define('ALLOWED_CORS_ORIGINS', [
    'https://coolmom.vn',
    'https://www.coolmom.vn',
]);

// --- CẤU HÌNH NỀN TẢNG THƯƠNG MẠI ĐIỆN TỬ (E-COMMERCE PLATFORMS) ---
define('ECOMMERCE_PLATFORMS', [
    'TIKTOK' => [
        'APP_KEY' => '6i1afcbfpk0d5',
        'APP_SECRET' => 'ef2cf98987d49eb2b748c04dfe01b7eecc114999',
        'SERVICE_ID' => '7569953661200647957',
        'OAUTH_AUTHORIZE_URL' => 'https://auth.tiktok-shops.com/oauth/authorize',
        'TOKEN_URL' => 'https://auth.tiktok-shops.com/api/v2/token/get',
        'SCOPES' => 'order.read,product.read,webhook.manage,fulfillment.read,logistic.read',
        'CALLBACK_URI' => 'https://bo.coolmom.vn/v2/auth/callback.php',
        'GET_SHOP_INFO_API' => 'https://open-api.tiktokglobalshop.com/authorization/202309/shops',
        'REFRESH_TOKEN_URL' => 'https://auth.tiktok-shops.com/api/v2/token/refresh',
    ]
]);