<?php
/**
 * Delivery Model
 */

class Delivery {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate next delivery number
     */
    public function generateDeliveryNo() {
        try {
            $stmt = $this->db->query("SELECT MAX(CAST(delivery_no AS UNSIGNED)) as max_no FROM deliveries");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNo = ($result['max_no'] ?? 1000) + 1;
            return (string)$nextNo;
        } catch (PDOException $e) {
            error_log("Error generating delivery number: " . $e->getMessage());
            return date('Ymd') . '001';
        }
    }
    
    /**
     * Create delivery
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO deliveries (
                        delivery_no, order_id, customer_id, customer_name,
                        delivery_date, due_date, sales_executive_id,
                        responsible_executive_id, billing_address, shipping_address,
                        same_as_billing, delivery_details, recovery_amount,
                        add_recovery, notes, invoice_file, update_by_email,
                        update_by_whatsapp, status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['delivery_no'],
                $data['order_id'] ?? null,
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['delivery_date'],
                $data['due_date'] ?? null,
                $data['sales_executive_id'] ?? null,
                $data['responsible_executive_id'] ?? null,
                $data['billing_address'] ?? null,
                $data['shipping_address'] ?? null,
                $data['same_as_billing'] ?? 1,
                $data['delivery_details'] ?? null,
                $data['recovery_amount'] ?? 0,
                $data['add_recovery'] ?? 0,
                $data['notes'] ?? null,
                $data['invoice_file'] ?? null,
                $data['update_by_email'] ?? 0,
                $data['update_by_whatsapp'] ?? 0,
                $data['status'] ?? 'pending',
                $_SESSION['user_id']
            ]);
            
            $deliveryId = $this->db->lastInsertId();
            
            // Insert items
            if (!empty($data['items'])) {
                $this->saveItems($deliveryId, $data['items']);
            }
            
            $this->db->commit();
            return $deliveryId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating delivery: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save delivery items
     */
    private function saveItems($deliveryId, $items) {
        $sql = "INSERT INTO delivery_items (
                    delivery_id, item_description, quantity, unit, rate, amount, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $item) {
            $stmt->execute([
                $deliveryId,
                $item['item_description'],
                $item['quantity'] ?? 1,
                $item['unit'] ?? 'no.s',
                $item['rate'] ?? 0,
                $item['amount'] ?? 0,
                $item['notes'] ?? null
            ]);
        }
    }
    
    /**
     * Get delivery by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM deliveries WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching delivery: " . $e->getMessage());
            return null;
        }
    }
}
