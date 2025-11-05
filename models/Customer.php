<?php
/**
 * Customer Model
 * Handles customer data operations
 */

class Customer {
    private $conn;
    private $table = 'customers';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all active customers
     */
    public function getAll($activeOnly = true) {
        $query = "SELECT 
                    id,
                    code,
                    name,
                    contact_name,
                    phone,
                    email,
                    address,
                    city,
                    state
                  FROM {$this->table}
                  WHERE 1=1";
        
        if ($activeOnly) {
            $query .= " AND active = 1";
        }
        
        $query .= " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get customer by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get customer by code
     */
    public function getByCode($code) {
        $query = "SELECT * FROM {$this->table} WHERE code = :code";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new customer
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (code, name, contact_name, phone, email, gstin, address, city, state, pincode, country, credit_limit, notes, active, created_by) 
                  VALUES 
                  (:code, :name, :contact_name, :phone, :email, :gstin, :address, :city, :state, :pincode, :country, :credit_limit, :notes, :active, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':contact_name', $data['contact_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':gstin', $data['gstin']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':pincode', $data['pincode']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':credit_limit', $data['credit_limit']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':active', $data['active']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        return $stmt->execute();
    }

    /**
     * Update customer
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET name = :name,
                      contact_name = :contact_name,
                      phone = :phone,
                      email = :email,
                      gstin = :gstin,
                      address = :address,
                      city = :city,
                      state = :state,
                      pincode = :pincode,
                      country = :country,
                      credit_limit = :credit_limit,
                      notes = :notes,
                      active = :active
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':contact_name', $data['contact_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':gstin', $data['gstin']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':pincode', $data['pincode']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':credit_limit', $data['credit_limit']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':active', $data['active']);
        
        return $stmt->execute();
    }

    /**
     * Delete customer
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Search customers
     */
    public function search($searchTerm) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE (name LIKE :search 
                     OR code LIKE :search 
                     OR phone LIKE :search 
                     OR email LIKE :search)
                  AND active = 1
                  ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
