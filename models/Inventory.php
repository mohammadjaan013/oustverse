<?php
/**
 * Inventory Model
 * Handles all database operations for inventory items and stock movements
 */

class Inventory {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Get all items with filters and pagination
     */
    public function getItems($filters = []) {
        $sql = "SELECT i.*, c.name as category_name, 
                COALESCE(SUM(sv.qty_on_hand), 0) as total_qty,
                COALESCE(SUM(sv.total_value), 0) as total_value
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN stock_valuations sv ON i.id = sv.item_id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['type'])) {
            // Type filter will be used for Products/Materials/Spares/Assemblies
            // For now, we can use a field or handle via category
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND i.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (i.name LIKE :search OR i.sku LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['location_id'])) {
            $sql .= " AND sv.location_id = :location_id";
            $params['location_id'] = $filters['location_id'];
        }
        
        if (isset($filters['is_active'])) {
            $sql .= " AND i.is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }
        
        $sql .= " GROUP BY i.id ORDER BY i.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single item by ID
     */
    public function getItemById($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, c.name as category_name 
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get item by SKU
     */
    public function getItemBySKU($sku) {
        $stmt = $this->db->prepare("SELECT * FROM items WHERE sku = ?");
        $stmt->execute([$sku]);
        return $stmt->fetch();
    }

    /**
     * Create new item
     */
    public function createItem($data) {
        $sql = "INSERT INTO items (sku, name, description, category_id, unit, standard_cost, 
                retail_price, reorder_level, hsn_code, tax_rate, is_active, created_by) 
                VALUES (:sku, :name, :description, :category_id, :unit, :standard_cost, 
                :retail_price, :reorder_level, :hsn_code, :tax_rate, :is_active, :created_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unit' => $data['unit'] ?? 'PCS',
            'standard_cost' => $data['standard_cost'] ?? 0,
            'retail_price' => $data['retail_price'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'hsn_code' => $data['hsn_code'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $_SESSION['user_id']
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Update item
     */
    public function updateItem($id, $data) {
        $sql = "UPDATE items SET 
                sku = :sku, name = :name, description = :description, 
                category_id = :category_id, unit = :unit, standard_cost = :standard_cost,
                retail_price = :retail_price, reorder_level = :reorder_level, 
                hsn_code = :hsn_code, tax_rate = :tax_rate, is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unit' => $data['unit'] ?? 'PCS',
            'standard_cost' => $data['standard_cost'] ?? 0,
            'retail_price' => $data['retail_price'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'hsn_code' => $data['hsn_code'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Delete item (soft delete by setting is_active = 0)
     */
    public function deleteItem($id) {
        $stmt = $this->db->prepare("UPDATE items SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get stock for item at specific location
     */
    public function getStockByLocation($itemId, $locationId) {
        $stmt = $this->db->prepare("
            SELECT * FROM stock_valuations 
            WHERE item_id = ? AND location_id = ?
        ");
        $stmt->execute([$itemId, $locationId]);
        return $stmt->fetch();
    }

    /**
     * Add stock movement (IN/OUT/TRANSFER)
     */
    public function addStockMovement($data) {
        $this->db->beginTransaction();
        
        try {
            // Insert stock movement
            $sql = "INSERT INTO stock_movements 
                    (item_id, location_from, location_to, qty, rate, type, ref_type, ref_id, notes, created_by) 
                    VALUES (:item_id, :location_from, :location_to, :qty, :rate, :type, :ref_type, :ref_id, :notes, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'item_id' => $data['item_id'],
                'location_from' => $data['location_from'] ?? null,
                'location_to' => $data['location_to'] ?? null,
                'qty' => $data['qty'],
                'rate' => $data['rate'] ?? 0,
                'type' => $data['type'], // 'in', 'out', 'transfer', 'adjustment'
                'ref_type' => $data['ref_type'] ?? null,
                'ref_id' => $data['ref_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $_SESSION['user_id']
            ]);
            
            $movementId = $this->db->lastInsertId();
            
            // Update stock valuations
            $this->updateStockValuation($data);
            
            $this->db->commit();
            return $movementId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update stock valuation after movement
     */
    private function updateStockValuation($data) {
        $itemId = $data['item_id'];
        $qty = $data['qty'];
        $rate = $data['rate'] ?? 0;
        $type = $data['type'];
        
        if ($type === 'in') {
            // Stock IN
            $locationId = $data['location_to'];
            $this->adjustStock($itemId, $locationId, $qty, $rate);
            
        } elseif ($type === 'out') {
            // Stock OUT
            $locationId = $data['location_from'];
            $this->adjustStock($itemId, $locationId, -$qty, $rate);
            
        } elseif ($type === 'transfer') {
            // Stock TRANSFER
            $this->adjustStock($itemId, $data['location_from'], -$qty, $rate);
            $this->adjustStock($itemId, $data['location_to'], $qty, $rate);
        }
    }

    /**
     * Adjust stock quantity and value
     */
    private function adjustStock($itemId, $locationId, $qtyChange, $rate) {
        // Check if stock valuation exists
        $stock = $this->getStockByLocation($itemId, $locationId);
        
        if ($stock) {
            // Update existing
            $newQty = $stock['qty_on_hand'] + $qtyChange;
            $newValue = $stock['total_value'] + ($qtyChange * $rate);
            
            $stmt = $this->db->prepare("
                UPDATE stock_valuations 
                SET qty_on_hand = :qty, total_value = :value, last_updated = NOW()
                WHERE item_id = :item_id AND location_id = :location_id
            ");
            $stmt->execute([
                'qty' => $newQty,
                'value' => $newValue,
                'item_id' => $itemId,
                'location_id' => $locationId
            ]);
        } else {
            // Create new
            $stmt = $this->db->prepare("
                INSERT INTO stock_valuations (item_id, location_id, qty_on_hand, total_value)
                VALUES (:item_id, :location_id, :qty, :value)
            ");
            $stmt->execute([
                'item_id' => $itemId,
                'location_id' => $locationId,
                'qty' => $qtyChange,
                'value' => $qtyChange * $rate
            ]);
        }
    }

    /**
     * Get stock movements for item
     */
    public function getStockMovements($itemId = null, $limit = 100) {
        $sql = "SELECT sm.*, i.name as item_name, i.sku,
                lf.name as location_from_name, lt.name as location_to_name,
                u.name as created_by_name
                FROM stock_movements sm
                LEFT JOIN items i ON sm.item_id = i.id
                LEFT JOIN locations lf ON sm.location_from = lf.id
                LEFT JOIN locations lt ON sm.location_to = lt.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($itemId) {
            $sql .= " AND sm.item_id = :item_id";
            $params['item_id'] = $itemId;
        }
        
        $sql .= " ORDER BY sm.created_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        
        if ($itemId) {
            $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     */
    public function getCategories() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get all locations
     */
    public function getLocations() {
        $stmt = $this->db->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get stock valuation report
     */
    public function getStockValuationReport($locationId = null) {
        $sql = "SELECT i.id, i.sku, i.name, i.unit, i.standard_cost,
                sv.qty_on_hand, sv.total_value, l.name as location_name,
                c.name as category_name
                FROM items i
                LEFT JOIN stock_valuations sv ON i.id = sv.item_id
                LEFT JOIN locations l ON sv.location_id = l.id
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.is_active = 1 AND sv.qty_on_hand > 0";
        
        $params = [];
        
        if ($locationId) {
            $sql .= " AND sv.location_id = :location_id";
            $params['location_id'] = $locationId;
        }
        
        $sql .= " ORDER BY i.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems() {
        $sql = "SELECT i.*, c.name as category_name,
                COALESCE(SUM(sv.qty_on_hand), 0) as total_qty
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN stock_valuations sv ON i.id = sv.item_id
                WHERE i.is_active = 1
                GROUP BY i.id
                HAVING total_qty <= i.reorder_level
                ORDER BY total_qty ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
