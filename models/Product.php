<?php
/**
 * Product Model
 * Handles all database operations for products/inventory
 */

class Product {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all active products
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT p.*, c.name as category_name, l.name as location_name
                    FROM items p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN locations l ON p.location_id = l.id
                    WHERE p.active = 1";
            
            $params = [];
            
            // Filter by category
            if (!empty($filters['category_id'])) {
                $sql .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // Filter by location
            if (!empty($filters['location_id'])) {
                $sql .= " AND p.location_id = ?";
                $params[] = $filters['location_id'];
            }
            
            // Search by name or SKU
            if (!empty($filters['search'])) {
                $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY p.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, c.name as category_name, l.name as location_name
                    FROM items p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN locations l ON p.location_id = l.id
                    WHERE p.id = ? AND p.active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching product: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get product by SKU
     */
    public function getBySku($sku) {
        try {
            $sql = "SELECT * FROM items WHERE sku = ? AND active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sku]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching product by SKU: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO items (
                        sku, name, description, category_id, unit, 
                        hsn_code, purchase_price, selling_price, mrp,
                        min_stock_level, max_stock_level, reorder_level,
                        location_id, active, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['sku'] ?? $this->generateSku(),
                $data['name'],
                $data['description'] ?? null,
                $data['category_id'] ?? null,
                $data['unit'] ?? 'PCS',
                $data['hsn_code'] ?? null,
                $data['purchase_price'] ?? 0,
                $data['selling_price'] ?? 0,
                $data['mrp'] ?? 0,
                $data['min_stock_level'] ?? 0,
                $data['max_stock_level'] ?? 0,
                $data['reorder_level'] ?? 0,
                $data['location_id'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE items SET
                        name = ?, description = ?, category_id = ?, unit = ?,
                        hsn_code = ?, purchase_price = ?, selling_price = ?, mrp = ?,
                        min_stock_level = ?, max_stock_level = ?, reorder_level = ?,
                        location_id = ?, updated_at = NOW()
                    WHERE id = ? AND active = 1";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['category_id'] ?? null,
                $data['unit'] ?? 'PCS',
                $data['hsn_code'] ?? null,
                $data['purchase_price'] ?? 0,
                $data['selling_price'] ?? 0,
                $data['mrp'] ?? 0,
                $data['min_stock_level'] ?? 0,
                $data['max_stock_level'] ?? 0,
                $data['reorder_level'] ?? 0,
                $data['location_id'] ?? null,
                $id
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete product (soft delete)
     */
    public function delete($id) {
        try {
            $sql = "UPDATE items SET active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current stock quantity
     */
    public function getCurrentStock($productId, $locationId = null) {
        try {
            $sql = "SELECT COALESCE(SUM(quantity), 0) as stock 
                    FROM stock_movements 
                    WHERE product_id = ?";
            
            $params = [$productId];
            
            if ($locationId) {
                $sql .= " AND location_id = ?";
                $params[] = $locationId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return floatval($result['stock'] ?? 0);
            
        } catch (PDOException $e) {
            error_log("Error getting stock: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate unique SKU
     */
    private function generateSku() {
        try {
            $prefix = 'PRD-';
            $sql = "SELECT sku FROM items WHERE sku LIKE ? ORDER BY sku DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
            $lastProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastProduct) {
                $lastNumber = intval(substr($lastProduct['sku'], strlen($prefix)));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating SKU: " . $e->getMessage());
            return 'PRD-' . time();
        }
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock() {
        try {
            $sql = "SELECT p.*, 
                    COALESCE(SUM(sm.quantity), 0) as current_stock
                    FROM items p
                    LEFT JOIN stock_movements sm ON p.id = sm.item_id
                    WHERE p.active = 1
                    GROUP BY p.id
                    HAVING current_stock <= p.reorder_level AND p.reorder_level > 0
                    ORDER BY current_stock ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching low stock products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        try {
            $stats = [
                'total_products' => 0,
                'total_categories' => 0,
                'low_stock_items' => 0,
                'total_value' => 0
            ];
            
            // Total products
            $sql = "SELECT COUNT(*) as count FROM items WHERE active = 1";
            $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
            $stats['total_products'] = intval($result['count']);
            
            // Total categories
            $sql = "SELECT COUNT(*) as count FROM categories WHERE active = 1";
            $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
            $stats['total_categories'] = intval($result['count']);
            
            // Low stock items
            $stats['low_stock_items'] = count($this->getLowStock());
            
            // Total inventory value
            $sql = "SELECT SUM(sm.quantity * p.purchase_price) as total_value
                    FROM stock_movements sm
                    JOIN items p ON sm.item_id = p.id
                    WHERE p.active = 1";
            $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
            $stats['total_value'] = floatval($result['total_value'] ?? 0);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return $stats;
        }
    }
}
