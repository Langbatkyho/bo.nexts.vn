<?php
namespace BO_System\Helpers;

/**
 * Lớp Helper chứa các hàm tiện ích tĩnh có thể sử dụng ở mọi nơi trong ứng dụng. 
 */
class Helper
{
    
    /**
     * Định dạng một chuỗi timestamp (hoặc chuỗi ngày tháng) thành định dạng 'Y-m-d\TH:i'
     * để sử dụng cho input type="datetime-local".
     *
     * @param string|null $timestamp Chuỗi thời gian đầu vào.
     * @return string Chuỗi đã định dạng hoặc chuỗi rỗng.
     */
    public static function formatForDatetimeLocal(?string $timestamp): string
    {
        if (empty($timestamp) || strtotime($timestamp) === false) {
            return '';
        }
        return date('Y-m-d\TH:i', strtotime($timestamp));
    }

    /**
     * Trả về một chuỗi HTML để hiển thị thông báo lỗi.
     *
     * @param string $errorMessage Nội dung lỗi.
     * @return string Chuỗi HTML.
     */
    public static function renderError(string $errorMessage): string
    {
        $safeMessage = htmlspecialchars($errorMessage);
        return "<div class='p-4 m-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-900/30 dark:text-red-300'>Lỗi: {$safeMessage}</div>";
    }

    /**
     * Xây dựng URL với các tham số query.
     *
     * @param string $basePath Đường dẫn cơ sở (ví dụ: '/v2/index.php').
     * @param array $params Mảng các tham số query.
     * @return string URL hoàn chỉnh.
     */
    public static function buildUrl(string $basePath, array $params = []): string
    {
        // Nếu basePath bắt đầu bằng /v2/, thay thế bằng APP_VERSION
        if (strpos($basePath, '/v2/') === 0) {
            $basePath = '/' . APP_VERSION . substr($basePath, 3);
        }
        
        if (empty($params)) {
            return $basePath;
        }
        return $basePath . '?' . http_build_query($params);
    }
    
    /**
     * Render toàn bộ HTML cho sidebar từ một cây menu.
     * PHIÊN BẢN CUỐI CÙNG - LOGIC INLINE ĐÃ SỬA LỖI.
     *
     * @param array $menuTree Cây menu từ MenuService.
     * @param string $currentPageModule Module của trang hiện tại để highlight.
     * @return string HTML của sidebar.
     */
    public static function renderSidebar(array $menuTree, string $currentPageModule): string
    {
        $initialSelectedMenu = '';
        $page = strtolower(trim($currentPageModule));

        foreach ($menuTree as $item) {
            if (!empty($item['children'])) {
                $hasActiveChild = false;
                foreach ($item['children'] as $child) {
                    if (strtolower(trim($child['module'] ?? '')) === $page) {
                        $hasActiveChild = true;
                        break;
                    }
                }
                if ($hasActiveChild) {
                    $initialSelectedMenu = $item['title'] ?? '';
                    break;
                }
            }
        }
        
        // Khởi tạo x-data với giá trị ban đầu. Dùng nháy đơn để bao bọc.
        $selected = json_encode($initialSelectedMenu);
        $html = "<nav x-data='{ selected: $selected }'>";
        $html .= '<ul class="mt-5 flex flex-col gap-2.5">';
        foreach ($menuTree as $item) {
            $html .= self::renderMenuItem($item, $currentPageModule);
        }
        $html .= '</ul></nav>';
        return $html;
    }

    /**
     * Hàm đệ quy để render một mục menu và các con của nó.
     * PHIÊN BẢN CUỐI CÙNG - LOGIC INLINE ĐÃ SỬA LỖI.
     *
     * @param array $item Dữ liệu của mục menu.
     * @param string $currentPageModule Module của trang hiện tại.
     * @return string HTML của một mục menu (<li>).
     */
    private static function renderMenuItem(array $item, string $currentPageModule): string
    {
        $hasChildren = !empty($item['children']);
        $url = $hasChildren ? '#' : self::buildUrl('/' . APP_VERSION . '/index.php', ['module' => $item['module']]);
        $title = htmlspecialchars($item['title'] ?? '');
        $alpineTitleCheck = json_encode($item['title']); // Chuỗi JSON an toàn, ví dụ: "Quản lý"
        $iconHtml = $item['icon_svg'] ?? '';
        $page = strtolower(trim($currentPageModule));
        $itemModule = strtolower(trim($item['module'] ?? ''));

        $isActive = false;
        $hasActiveChild = false;
        if ($hasChildren) {
            foreach ($item['children'] as $child) {
                if (strtolower(trim($child['module'] ?? '')) === $page) {
                    $hasActiveChild = true;
                    break;
                }
            }
            $isActive = ($itemModule === $page) || $hasActiveChild;
        } else {
            $isActive = ($itemModule === $page);
        }

        $baseLinkClasses = 'group relative flex items-center gap-2.5 rounded-md px-4 py-2 font-sm text-gray-700 duration-300 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800';
        $activeLinkClasses = 'menu-item-active';
        $finalLinkClasses = $baseLinkClasses . ($isActive ? ' ' . $activeLinkClasses : '');

        if (!empty($iconHtml)) {
            $iconClassToAdd = $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive';
            $iconHtml = preg_replace('/ fill="[^"]*"/', '', $iconHtml);
            $iconHtml = preg_replace('/<svg(\s|>)/', '<svg class="' . $iconClassToAdd . '" fill="currentColor"$1', $iconHtml, 1);
        } else {
            $iconClassToAdd = $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive';
            $iconHtml = '<svg class="' . $iconClassToAdd . '" width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a6 6 0 100 12 6 6 0 000-12zM2 10a8 8 0 1116 0 8 8 0 01-16 0z"/></svg>';
        }

        $liClass = ($hasActiveChild ? 'menu-parent-active ' : '') . 'relative';

        $liAttributes = 'class="' . $liClass . '"';
        if ($hasChildren) {
            // x-data cho hover vẫn nằm ở đây, nhưng nó sẽ kế thừa `selected` từ <nav> cha
            $liAttributes .= ' x-data="{ hovering: false }" @mouseenter="hovering = true" @mouseleave="hovering = false"';
        }
        $html = '<li ' . $liAttributes . '>';

        if ($hasChildren) {
            // === SỬA LỖI ESCAPING TRIỆT ĐỂ ===
            // 1. Tạo chuỗi logic JavaScript
            $alpineClickLogic = 'if (!sidebarCollapsed) { selected = (selected === ' . $alpineTitleCheck . ' ? \'\' : ' . $alpineTitleCheck . ') }';
            // 2. Dùng htmlspecialchars để escape chuỗi này trước khi đưa vào thuộc tính HTML
            $safeAlpineClickLogic = htmlspecialchars($alpineClickLogic, ENT_QUOTES, 'UTF-8');
            
            $html .= '<a href="#" class="' . $finalLinkClasses . '" @click.prevent="' . $safeAlpineClickLogic . '">';
            $html .= $iconHtml;
            $html .= '<span class="menu-item-text" :class="sidebarCollapsed && \'lg:hidden\'">' . $title . '</span>';
            $html .= '<svg class="absolute right-4 top-1/2 -translate-y-1/2" :class="{\'!\' : !sidebarCollapsed && selected === ' . $alpineTitleCheck . ' && \'rotate-180\', \'hidden\': sidebarCollapsed}" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            $html .= '</a>';
            
            $alpineSubmenuClasses = '{ "absolute left-full top-0 z-20 ml-4 w-[250px] rounded-md border bg-white p-3 shadow-lg dark:border-gray-800 dark:bg-black": sidebarCollapsed }';
            $alpineShowLogic = '(!sidebarCollapsed && selected === ' . $alpineTitleCheck . ') || (sidebarCollapsed && hovering)';
            
            $html .= '<div class="overflow-hidden" :class=\'' . $alpineSubmenuClasses . '\' x-show="' . htmlspecialchars($alpineShowLogic, ENT_QUOTES) . '" x-transition>';
            $html .= '<ul class="flex flex-col " :class="{\'mt-4 mb-5.5 pl-6\': !sidebarCollapsed}">';
            
            foreach ($item['children'] as $child) {
                $childUrl = self::buildUrl('/' . APP_VERSION . '/index.php', ['module' => $child['module']]);
                $childTitle = htmlspecialchars($child['title'] ?? '');
                $isChildActive = (strtolower(trim($child['module'] ?? '')) === $page);
                
                $childLinkClasses = 'group relative flex items-center gap-2.5 rounded-md px-4 py-2 font-sm text-gray-600 duration-300 ease-in-out hover:text-brand-700';
                $activeChildLinkClasses = 'text-brand-700 dark:text-white';
                $finalChildLinkClasses = $childLinkClasses . ($isChildActive ? ' ' . $activeChildLinkClasses : '');

                $html .= '<li><a href="' . $childUrl . '" class="' . $finalChildLinkClasses . '">' . $childTitle . '</a></li>';
            }
            $html .= '</ul></div>';

        } else {
            $html .= '<a href="' . $url . '" class="' . $finalLinkClasses . '">';
            $html .= $iconHtml;
            $html .= '<span class="menu-item-text" :class="sidebarCollapsed && \'lg:hidden\'">' . $title . '</span>';
            $html .= '</a>';
        }

        $html .= '</li>';
        return $html;
    }

    /**
     * Chuyển hướng người dùng đến một URL khác.
     *
     * @param string $url URL đích.
     */
    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    /**
     * Lấy URL hiện tại của trang web.
     *
     * @return string URL hiện tại.
     */
    public static function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        return $protocol . $host . $requestUri;
    }
    
    /**
     * Làm sạch dữ liệu đầu vào để ngăn chặn XSS.
     *
     * @param string $data Dữ liệu thô.
     * @return string Dữ liệu đã được làm sạch.
     */
    public static function sanitize(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}