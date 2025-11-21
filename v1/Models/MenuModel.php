<?php
namespace BO_System\Models;

use PDO;
use PDOException;

class MenuModel extends BaseModel
{
    protected string $table = 'sec_menu_items';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Lấy tất cả các mục menu, sắp xếp theo parent_id và display_order.
     *
     * @return array Mảng các mục menu.
     */
    public function findAllOrdered(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY parent_id ASC, display_order ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching menu items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tạo một mục menu mới.
     *
     * @param array $data Dữ liệu menu.
     * @return bool True nếu thành công.
     */
    public function createMenuItem(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (parent_id, title, module, icon_svg, display_order, required_permission_slug) 
                    VALUES (:parent_id, :title, :module, :icon_svg, :display_order, :required_permission_slug)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
                ':title' => $data['title'],
                ':module' => $data['module'],
                ':icon_svg' => $data['icon_svg'] ?: null,
                ':display_order' => (int)($data['display_order'] ?? 0),
                ':required_permission_slug' => !empty($data['required_permission_slug']) ? $data['required_permission_slug'] : null,
            ]);
        } catch (PDOException $e) {
            error_log("Error creating menu item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật một mục menu.
     *
     * @param int $id ID menu.
     * @param array $data Dữ liệu cập nhật.
     * @return bool True nếu thành công.
     */
    public function updateMenuItem(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                        parent_id = :parent_id, 
                        title = :title, 
                        module = :module, 
                        icon_svg = :icon_svg, 
                        display_order = :display_order, 
                        required_permission_slug = :required_permission_slug
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
                ':title' => $data['title'],
                ':module' => $data['module'],
                ':icon_svg' => $data['icon_svg'] ?: null,
                ':display_order' => (int)($data['display_order'] ?? 0),
                ':required_permission_slug' => !empty($data['required_permission_slug']) ? $data['required_permission_slug'] : null,
            ]);
        } catch (PDOException $e) {
            error_log("Error updating menu item {$id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật cấu trúc và thứ tự menu (kéo thả).
     *
     * @param array $menuStructure Cấu trúc menu phân cấp.
     * @return bool True nếu thành công.
     */
    public function updateMenuStructure(array $menuStructure): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE {$this->table} SET display_order = :display_order, parent_id = :parent_id WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);

            $updateNode = function (array $nodes, ?int $parentId) use (&$stmt, &$updateNode) {
                foreach ($nodes as $order => $node) {
                    $stmt->execute([
                        ':id' => $node['id'],
                        ':display_order' => $order,
                        ':parent_id' => $parentId
                    ]);
                    if (!empty($node['children'])) {
                        $updateNode($node['children'], $node['id']);
                    }
                }
            };
            
            $updateNode($menuStructure, null);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating menu structure: " . $e->getMessage());
            return false;
        }
    }
}