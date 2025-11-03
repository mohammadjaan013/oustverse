<?php
/**
 * Supplier Model
 * Handles all database operations for suppliers and supplier contacts
 */

class Supplier {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all suppliers (alias for getSuppliers)
     * @param array $filters
     * @return array
     */
    public function getAll($filters = []) {
        return $this->getSuppliers($filters);
    }
    
    /**
     * Get all suppliers with optional filters
     * @param array $filters - type, status, search, payment_terms, etc.
     * @return array
     */
    public function getSuppliers($filters = []) {
        try {
            $sql = "SELECT s.*, 
                    (SELECT COUNT(*) FROM supplier_contacts WHERE supplier_id = s.id) as contact_count,
                    0 as po_count,
                    0 as total_purchases
                    FROM suppliers s
                    WHERE s.active = 1";
            
            $params = [];
            
            // Filter by connection type (for the tabs)
            if (!empty($filters['connection_type'])) {
                if ($filters['connection_type'] === 'supplier') {
                    // This is default, already filtering suppliers table
                } elseif ($filters['connection_type'] === 'all') {
                    // Show all
                }
                // Note: customer, neighbour, friend would need additional fields in suppliers table
            }
            
            // Filter by type (vendor, manufacturer, distributor)
            if (!empty($filters['type'])) {
                $sql .= " AND s.type = ?";
                $params[] = $filters['type'];
            }
            
            // Filter by executive (created_by)
            if (!empty($filters['executive'])) {
                $sql .= " AND s.created_by = ?";
                $params[] = $filters['executive'];
            }
            
            // Filter by city
            if (!empty($filters['city'])) {
                $sql .= " AND s.city = ?";
                $params[] = $filters['city'];
            }
            
            // Filter by state
            if (!empty($filters['state'])) {
                $sql .= " AND s.state = ?";
                $params[] = $filters['state'];
            }
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND s.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by payment terms
            if (!empty($filters['payment_terms'])) {
                $sql .= " AND s.payment_terms = ?";
                $params[] = $filters['payment_terms'];
            }
            
            // Search by name, code, email, phone, city
            if (!empty($filters['search'])) {
                $sql .= " AND (s.name LIKE ? OR s.code LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.city LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Sorting
            $orderBy = $filters['order_by'] ?? 'name';
            $orderDir = $filters['order_dir'] ?? 'ASC';
            $allowedColumns = ['name', 'code', 'type', 'city', 'status', 'created_at'];
            if (in_array($orderBy, $allowedColumns)) {
                $sql .= " ORDER BY s.$orderBy $orderDir";
            }
            
            // Pagination
            if (isset($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
                
                if (isset($filters['offset'])) {
                    $sql .= " OFFSET ?";
                    $params[] = (int)$filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching suppliers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of suppliers (for pagination)
     * @param array $filters
     * @return int
     */
    public function getSuppliersCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) FROM suppliers WHERE active = 1";
            $params = [];
            
            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (name LIKE ? OR code LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Error counting suppliers: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get a single supplier by ID
     * @param int $id
     * @return array|null
     */
    public function getSupplierById($id) {
        try {
            $sql = "SELECT * FROM suppliers WHERE id = ? AND active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching supplier: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get supplier by code
     * @param string $code
     * @param int $excludeId - Exclude this ID (for update validation)
     * @return array|null
     */
    public function getSupplierByCode($code, $excludeId = null) {
        try {
            $sql = "SELECT * FROM suppliers WHERE code = ? AND active = 1";
            $params = [$code];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching supplier by code: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new supplier
     * @param array $data
     * @return int|false - Returns supplier ID on success, false on failure
     */
    public function createSupplier($data) {
        try {
            $sql = "INSERT INTO suppliers (
                        code, type, name, contact_name, phone, mobile, whatsapp, email,
                        website, industry, segment, gstin, msme_no, pan, status,
                        address, city, state, pincode, country,
                        payment_terms, credit_limit, credit_days, opening_balance, notes, 
                        active, created_by, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        1, ?, NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['code'],
                $data['type'] ?? 'vendor',
                $data['name'],
                $data['contact_person'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['email'] ?? null,
                $data['website'] ?? null,
                $data['industry'] ?? null,
                $data['segment'] ?? null,
                $data['gstin'] ?? null,
                $data['msme_no'] ?? null,
                $data['pan'] ?? null,
                $data['status'] ?? 'active',
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['pincode'] ?? null,
                $data['country'] ?? 'India',
                $data['payment_terms'] ?? 'net30',
                $data['credit_limit'] ?? 0,
                $data['credit_days'] ?? 30,
                $data['opening_balance'] ?? 0,
                $data['notes'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Error creating supplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing supplier
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSupplier($id, $data) {
        try {
            $sql = "UPDATE suppliers SET
                        code = ?, type = ?, name = ?, contact_name = ?, phone = ?, mobile = ?, whatsapp = ?, email = ?,
                        website = ?, industry = ?, segment = ?, gstin = ?, msme_no = ?, pan = ?, status = ?,
                        address = ?, city = ?, state = ?, pincode = ?, country = ?,
                        payment_terms = ?, credit_limit = ?, credit_days = ?, opening_balance = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND active = 1";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['code'],
                $data['type'] ?? 'vendor',
                $data['name'],
                $data['contact_person'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['email'] ?? null,
                $data['website'] ?? null,
                $data['industry'] ?? null,
                $data['segment'] ?? null,
                $data['gstin'] ?? null,
                $data['msme_no'] ?? null,
                $data['pan'] ?? null,
                $data['status'] ?? 'active',
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['pincode'] ?? null,
                $data['country'] ?? 'India',
                $data['payment_terms'] ?? 'net30',
                $data['credit_limit'] ?? 0,
                $data['credit_days'] ?? 30,
                $data['opening_balance'] ?? 0,
                $data['notes'] ?? null,
                $id
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating supplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a supplier (soft delete)
     * @param int $id
     * @return bool
     */
    public function deleteSupplier($id) {
        try {
            // Soft delete
            $sql = "UPDATE suppliers SET active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Error deleting supplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get supplier contacts
     * @param int $supplierId
     * @return array
     */
    public function getContacts($supplierId) {
        try {
            $sql = "SELECT * FROM supplier_contacts WHERE supplier_id = ? ORDER BY is_primary DESC, name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$supplierId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching contacts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add a contact to supplier
     * @param array $data
     * @return int|false
     */
    public function addContact($data) {
        try {
            // If this is primary contact, unset other primary contacts
            if (!empty($data['is_primary'])) {
                $sql = "UPDATE supplier_contacts SET is_primary = 0 WHERE supplier_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['supplier_id']]);
            }
            
            $sql = "INSERT INTO supplier_contacts (
                        supplier_id, name, designation, email, phone, mobile, whatsapp,
                        is_primary, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['supplier_id'],
                $data['name'],
                $data['designation'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['is_primary'] ?? 0,
                $data['notes'] ?? null
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Error adding contact: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a contact
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateContact($id, $data) {
        try {
            // If this is primary contact, unset other primary contacts
            if (!empty($data['is_primary'])) {
                $sql = "UPDATE supplier_contacts SET is_primary = 0 WHERE supplier_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['supplier_id']]);
            }
            
            $sql = "UPDATE supplier_contacts SET
                        name = ?, designation = ?, email = ?, phone = ?, mobile = ?, whatsapp = ?, is_primary = ?, notes = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['designation'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['is_primary'] ?? 0,
                $data['notes'] ?? null,
                $id
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating contact: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a contact
     * @param int $id
     * @return bool
     */
    public function deleteContact($id) {
        try {
            $sql = "DELETE FROM supplier_contacts WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting contact: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate next supplier code
     * @return string
     */
    public function generateCode() {
        try {
            $sql = "SELECT code FROM suppliers WHERE code LIKE 'SUP%' ORDER BY code DESC LIMIT 1";
            $stmt = $this->db->query($sql);
            $lastCode = $stmt->fetchColumn();
            
            if ($lastCode) {
                $num = intval(substr($lastCode, 3)) + 1;
            } else {
                $num = 1;
            }
            
            return 'SUP' . str_pad($num, 5, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating code: " . $e->getMessage());
            return 'SUP00001';
        }
    }
    
    /**
     * Get supplier statistics
     * @param int $supplierId
     * @return array
     */
    public function getStatistics($supplierId) {
        try {
            $stats = [];
            
            // Get contact count
            $sql = "SELECT COUNT(*) as total_contacts FROM supplier_contacts WHERE supplier_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$supplierId]);
            $stats['total_contacts'] = $stmt->fetchColumn();
            
            // Placeholders for future features
            $stats['total_pos'] = 0;
            $stats['approved_pos'] = 0;
            $stats['total_amount'] = 0;
            $stats['last_order_date'] = null;
            $stats['total_items'] = 0;
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return [];
        }
    }
}
