<?php
/**
 * Lead Model
 * Handles all database operations for CRM leads
 */

class Lead {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all leads with filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT l.*, 
                    u.name as assigned_to_name,
                    c.name as created_by_name,
                    DATEDIFF(CURDATE(), l.created_at) as days_old
                    FROM leads l
                    LEFT JOIN users u ON l.assigned_to = u.id
                    LEFT JOIN users c ON l.created_by = c.id
                    WHERE 1=1";
            
            $params = [];
            
            // Filter by stage
            if (!empty($filters['stage'])) {
                $sql .= " AND l.stage = ?";
                $params[] = $filters['stage'];
            }
            
            // Filter by status
            if (!empty($filters['status'])) {
                $sql .= " AND l.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filter by assigned user
            if (!empty($filters['assigned_to'])) {
                $sql .= " AND l.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            // Filter by source
            if (!empty($filters['source'])) {
                $sql .= " AND l.source = ?";
                $params[] = $filters['source'];
            }
            
            // Filter starred only
            if (!empty($filters['starred']) || !empty($filters['starred_only'])) {
                $sql .= " AND l.is_starred = 1";
            }
            
            // Search
            if (!empty($filters['search'])) {
                $sql .= " AND (l.business_name LIKE ? OR l.contact_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.requirements LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Sorting
            $orderBy = $filters['order_by'] ?? 'created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY l.$orderBy $orderDir";
            
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
            error_log("Error fetching leads: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get lead by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, 
                       u.name as assigned_to_name,
                       c.name as created_by_name
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                LEFT JOIN users c ON l.created_by = c.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching lead: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new lead
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO leads (
                        business_name, title, first_name, contact_name, designation,
                        email, phone, mobile, whatsapp, website,
                        source, stage, status, assigned_to, requirements, notes,
                        address_line1, address_line2, address, city, state, pincode, country, gstin,
                        code, category, product, potential, since_date, tags, priority,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['business_name'],
                $data['title'] ?? 'Mr',
                $data['first_name'] ?? null,
                $data['contact_name'] ?? null,
                $data['designation'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['website'] ?? null,
                $data['source'] ?? null,
                $data['stage'] ?? 'raw',
                $data['status'] ?? 'active',
                $data['assigned_to'] ?? null,
                $data['requirements'] ?? null,
                $data['notes'] ?? null,
                $data['address_line1'] ?? null,
                $data['address_line2'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['pincode'] ?? null,
                $data['country'] ?? 'India',
                $data['gstin'] ?? null,
                $data['code'] ?? null,
                $data['category'] ?? null,
                $data['product'] ?? null,
                $data['potential'] ?? 0.00,
                $data['since_date'] ?? null,
                $data['tags'] ?? null,
                $data['priority'] ?? 'medium',
                $_SESSION['user_id']
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update lead
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE leads SET
                        business_name = ?, title = ?, first_name = ?, contact_name = ?, designation = ?,
                        email = ?, phone = ?, mobile = ?, whatsapp = ?, website = ?,
                        source = ?, stage = ?, status = ?, assigned_to = ?, requirements = ?, notes = ?,
                        address_line1 = ?, address_line2 = ?, address = ?, city = ?, state = ?, pincode = ?, 
                        country = ?, gstin = ?, code = ?, category = ?, product = ?, potential = ?, 
                        since_date = ?, tags = ?, priority = ?,
                        last_activity_date = ?, next_followup_date = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['business_name'],
                $data['title'] ?? 'Mr',
                $data['first_name'] ?? null,
                $data['contact_name'] ?? null,
                $data['designation'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['mobile'] ?? null,
                $data['whatsapp'] ?? null,
                $data['website'] ?? null,
                $data['source'] ?? null,
                $data['stage'] ?? 'raw',
                $data['status'] ?? 'active',
                $data['assigned_to'] ?? null,
                $data['requirements'] ?? null,
                $data['notes'] ?? null,
                $data['address_line1'] ?? null,
                $data['address_line2'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['pincode'] ?? null,
                $data['country'] ?? 'India',
                $data['gstin'] ?? null,
                $data['code'] ?? null,
                $data['category'] ?? null,
                $data['product'] ?? null,
                $data['potential'] ?? 0.00,
                $data['since_date'] ?? null,
                $data['tags'] ?? null,
                $data['priority'] ?? 'medium',
                $data['last_activity_date'] ?? null,
                $data['next_followup_date'] ?? null,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete lead
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle star status
     */
    public function toggleStar($id) {
        try {
            $stmt = $this->db->prepare("UPDATE leads SET is_starred = NOT is_starred WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error toggling star: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get lead count by stage
     */
    public function getCountByStage() {
        try {
            $stmt = $this->db->query("
                SELECT stage, COUNT(*) as count
                FROM leads
                GROUP BY stage
            ");
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Error getting lead counts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add activity/interaction
     */
    public function addActivity($leadId, $data) {
        try {
            $sql = "INSERT INTO lead_activities (lead_id, activity_type, subject, description, activity_date, duration, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $leadId,
                $data['activity_type'],
                $data['subject'] ?? null,
                $data['description'] ?? null,
                $data['activity_date'] ?? date('Y-m-d H:i:s'),
                $data['duration'] ?? null,
                $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error adding activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get lead activities
     */
    public function getActivities($leadId) {
        try {
            $stmt = $this->db->prepare("
                SELECT la.*, u.name as created_by_name
                FROM lead_activities la
                LEFT JOIN users u ON la.created_by = u.id
                WHERE la.lead_id = ?
                ORDER BY la.activity_date DESC
            ");
            $stmt->execute([$leadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching activities: " . $e->getMessage());
            return [];
        }
    }
}
