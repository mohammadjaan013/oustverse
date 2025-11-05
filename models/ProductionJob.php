<?php
/**
 * ProductionJob Model
 * Handles all database operations for production jobs
 */

class ProductionJob {
    private $conn;
    private $table = 'production_jobs';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all production jobs with filters
     */
    public function getAll($filters = []) {
        $query = "SELECT 
                    pj.*,
                    i.name as product_name,
                    i.sku as product_code,
                    c.name as customer_name,
                    u.name as created_by_name,
                    DATEDIFF(pj.target_date, CURDATE()) as days_remaining
                  FROM {$this->table} pj
                  LEFT JOIN items i ON pj.product_id = i.id
                  LEFT JOIN customers c ON pj.customer_id = c.id
                  LEFT JOIN users u ON pj.created_by = u.id
                  WHERE 1=1";

        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query .= " AND pj.status = :status";
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query .= " AND (pj.wip_no LIKE :search 
                        OR p.name LIKE :search 
                        OR c.name LIKE :search)";
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query .= " AND pj.target_date < CURDATE() AND pj.status != 'completed'";
        }

        $query .= " ORDER BY pj.created_at DESC";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $stmt->bindParam(':search', $search);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get production job by ID
     */
    public function getById($id) {
        $query = "SELECT 
                    pj.*,
                    i.name as product_name,
                    i.sku as product_code,
                    c.name as customer_name,
                    c.email as customer_email,
                    u.name as created_by_name
                  FROM {$this->table} pj
                  LEFT JOIN items i ON pj.product_id = i.id
                  LEFT JOIN customers c ON pj.customer_id = c.id
                  LEFT JOIN users u ON pj.created_by = u.id
                  WHERE pj.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get production job by WIP number
     */
    public function getByWipNo($wip_no) {
        $query = "SELECT pj.*, i.name as product_name, c.name as customer_name
                  FROM {$this->table} pj
                  LEFT JOIN items i ON pj.product_id = i.id
                  LEFT JOIN customers c ON pj.customer_id = c.id
                  WHERE pj.wip_no = :wip_no";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':wip_no', $wip_no);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new production job
     */
    public function create($data) {
        // Generate WIP number if not provided
        if (empty($data['wip_no'])) {
            $data['wip_no'] = $this->generateWipNo();
        }

        $query = "INSERT INTO {$this->table} 
                    (wip_no, customer_id, product_id, quantity, target_date, 
                     status, special_instructions, created_by)
                  VALUES 
                    (:wip_no, :customer_id, :product_id, :quantity, :target_date,
                     :status, :special_instructions, :created_by)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':wip_no', $data['wip_no']);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':target_date', $data['target_date']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':special_instructions', $data['special_instructions']);
        $stmt->bindParam(':created_by', $data['created_by']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Update production job
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET customer_id = :customer_id,
                      product_id = :product_id,
                      quantity = :quantity,
                      target_date = :target_date,
                      status = :status,
                      special_instructions = :special_instructions
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':target_date', $data['target_date']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':special_instructions', $data['special_instructions']);

        return $stmt->execute();
    }

    /**
     * Update production job status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    /**
     * Delete production job
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_jobs,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as wip_jobs,
                    SUM(CASE WHEN target_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_jobs,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs
                  FROM {$this->table}";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate unique WIP number
     */
    private function generateWipNo() {
        $year = date('Y');
        $prefix = "WIP-{$year}-";

        // Get the last WIP number for current year
        $query = "SELECT wip_no FROM {$this->table} 
                  WHERE wip_no LIKE :prefix 
                  ORDER BY id DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . '%';
        $stmt->bindParam(':prefix', $searchPrefix);
        $stmt->execute();

        $lastWip = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastWip) {
            // Extract number and increment
            $lastNumber = (int)substr($lastWip['wip_no'], -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get production job items
     */
    public function getJobItems($jobId) {
        $query = "SELECT 
                    pji.*,
                    i.name as product_name,
                    i.sku as product_code
                  FROM production_job_items pji
                  LEFT JOIN items i ON pji.product_id = i.id
                  WHERE pji.production_job_id = :job_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':job_id', $jobId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add item to production job
     */
    public function addJobItem($data) {
        $query = "INSERT INTO production_job_items 
                    (production_job_id, product_id, quantity, unit, notes)
                  VALUES 
                    (:production_job_id, :product_id, :quantity, :unit, :notes)";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':production_job_id', $data['production_job_id']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':notes', $data['notes']);

        return $stmt->execute();
    }

    /**
     * Get count by status
     */
    public function getCountByStatus($status) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = :status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
