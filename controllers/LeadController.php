<?php
/**
 * Lead Controller
 * Handles all CRM lead requests
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Lead.php';

class LeadController {
    private $model;
    
    public function __construct() {
        $this->model = new Lead();
    }
    
    /**
     * Get leads for DataTables
     */
    public function getLeadsJson() {
        requireLogin();
        
        // Accept both GET and POST
        $request = array_merge($_GET, $_POST);
        
        // Handle sorting
        $sort = $request['sort'] ?? 'newest';
        $orderBy = 'created_at';
        $orderDir = ($sort === 'oldest') ? 'ASC' : 'DESC';
        
        $filters = [
            'stage' => $request['stage'] ?? '',
            'status' => $request['status'] ?? '',
            'assigned_to' => $request['assigned_to'] ?? '',
            'source' => $request['source'] ?? '',
            'search' => $request['search']['value'] ?? ($request['search'] ?? ''),
            'starred_only' => !empty($request['starred_only']) ? true : false,
            'order_by' => $orderBy,
            'order_dir' => $orderDir
        ];
        
        $leads = $this->model->getAll($filters);
        
        $data = [];
        foreach ($leads as $lead) {
            $data[] = [
                'id' => $lead['id'],
                'business' => '<strong>' . htmlspecialchars($lead['business_name']) . '</strong>',
                'contact' => htmlspecialchars($lead['contact_name'] ?? '-') . 
                            ($lead['mobile'] ? '<br><small class="text-muted">' . htmlspecialchars($lead['mobile']) . '</small>' : ''),
                'source' => htmlspecialchars($lead['source'] ?? '-'),
                'stage' => $this->getStageBadge($lead['stage']),
                'since' => date('d-M', strtotime($lead['created_at'])),
                'assigned_to' => htmlspecialchars($lead['assigned_to_name'] ?? '-'),
                'last_talk' => $lead['last_activity_date'] ? date('d-M', strtotime($lead['last_activity_date'])) : '-',
                'next' => $lead['next_followup_date'] ? date('d-M', strtotime($lead['next_followup_date'])) : '-',
                'requirements' => htmlspecialchars($lead['requirements'] ?? '-'),
                'notes' => htmlspecialchars($lead['notes'] ?? '-'),
                'star' => $lead['is_starred'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star"></i>',
                'actions' => $this->getActionButtons($lead['id'])
            ];
        }
        
        // Return simple JSON for client-side DataTables
        header('Content-Type: application/json');
        echo json_encode([
            'data' => $data,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data)
        ]);
        exit;
    }
    
    /**
     * Create lead
     */
    public function create() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        if (empty($_POST['business_name'])) {
            jsonResponse(false, 'Business name is required');
        }
        
        $data = [
            'business_name' => sanitize($_POST['business_name']),
            'title' => sanitize($_POST['title'] ?? 'Mr'),
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'contact_name' => sanitize($_POST['contact_name'] ?? ''),
            'designation' => sanitize($_POST['designation'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'whatsapp' => sanitize($_POST['whatsapp'] ?? ''),
            'website' => sanitize($_POST['website'] ?? ''),
            'source' => sanitize($_POST['source'] ?? ''),
            'stage' => sanitize($_POST['stage'] ?? 'raw'),
            'status' => sanitize($_POST['status'] ?? 'active'),
            'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
            'requirements' => sanitize($_POST['requirements'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'address_line1' => sanitize($_POST['address_line1'] ?? ''),
            'address_line2' => sanitize($_POST['address_line2'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'state' => sanitize($_POST['state'] ?? ''),
            'pincode' => sanitize($_POST['pincode'] ?? ''),
            'country' => sanitize($_POST['country'] ?? 'India'),
            'gstin' => sanitize($_POST['gstin'] ?? ''),
            'code' => sanitize($_POST['code'] ?? ''),
            'category' => sanitize($_POST['category'] ?? ''),
            'product' => sanitize($_POST['product'] ?? ''),
            'potential' => !empty($_POST['potential']) ? floatval($_POST['potential']) : 0.00,
            'since_date' => !empty($_POST['since_date']) ? $_POST['since_date'] : null,
            'tags' => sanitize($_POST['tags'] ?? ''),
            'priority' => sanitize($_POST['priority'] ?? 'medium')
        ];
        
        $leadId = $this->model->create($data);
        
        if ($leadId) {
            logAudit('create', 'leads', $leadId, null, $data);
            jsonResponse(true, 'Lead created successfully', ['id' => $leadId]);
        } else {
            jsonResponse(false, 'Failed to create lead');
        }
    }
    
    /**
     * Update lead
     */
    public function update() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid lead ID');
        }
        
        $oldData = $this->model->getById($id);
        if (!$oldData) {
            jsonResponse(false, 'Lead not found');
        }
        
        $data = [
            'business_name' => sanitize($_POST['business_name']),
            'title' => sanitize($_POST['title'] ?? 'Mr'),
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'contact_name' => sanitize($_POST['contact_name'] ?? ''),
            'designation' => sanitize($_POST['designation'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'whatsapp' => sanitize($_POST['whatsapp'] ?? ''),
            'website' => sanitize($_POST['website'] ?? ''),
            'source' => sanitize($_POST['source'] ?? ''),
            'stage' => sanitize($_POST['stage'] ?? 'raw'),
            'status' => sanitize($_POST['status'] ?? 'active'),
            'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
            'requirements' => sanitize($_POST['requirements'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'address_line1' => sanitize($_POST['address_line1'] ?? ''),
            'address_line2' => sanitize($_POST['address_line2'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'state' => sanitize($_POST['state'] ?? ''),
            'pincode' => sanitize($_POST['pincode'] ?? ''),
            'country' => sanitize($_POST['country'] ?? 'India'),
            'gstin' => sanitize($_POST['gstin'] ?? ''),
            'code' => sanitize($_POST['code'] ?? ''),
            'category' => sanitize($_POST['category'] ?? ''),
            'product' => sanitize($_POST['product'] ?? ''),
            'potential' => !empty($_POST['potential']) ? floatval($_POST['potential']) : 0.00,
            'since_date' => !empty($_POST['since_date']) ? $_POST['since_date'] : null,
            'tags' => sanitize($_POST['tags'] ?? ''),
            'priority' => sanitize($_POST['priority'] ?? 'medium'),
            'last_activity_date' => $_POST['last_activity_date'] ?? null,
            'next_followup_date' => $_POST['next_followup_date'] ?? null
        ];
        
        if ($this->model->update($id, $data)) {
            logAudit('update', 'leads', $id, $oldData, $data);
            jsonResponse(true, 'Lead updated successfully');
        } else {
            jsonResponse(false, 'Failed to update lead');
        }
    }
    
    /**
     * Delete lead
     */
    public function delete() {
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            jsonResponse(false, 'Invalid security token');
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid lead ID');
        }
        
        if ($this->model->delete($id)) {
            logAudit('delete', 'leads', $id, null, null);
            jsonResponse(true, 'Lead deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete lead');
        }
    }
    
    /**
     * Toggle star
     */
    public function toggleStar() {
        requireLogin();
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid lead ID');
        }
        
        if ($this->model->toggleStar($id)) {
            jsonResponse(true, 'Star toggled successfully');
        } else {
            jsonResponse(false, 'Failed to toggle star');
        }
    }
    
    /**
     * Get lead details
     */
    public function getLead() {
        requireLogin();
        
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid lead ID');
            return;
        }
        
        $lead = $this->model->getById($id);
        if ($lead) {
            jsonResponse(true, 'Lead retrieved successfully', $lead);
        } else {
            jsonResponse(false, 'Lead not found');
        }
    }
    
    /**
     * Get stage badge HTML
     */
    private function getStageBadge($stage) {
        $badges = [
            'raw' => '<span class="badge bg-secondary">Raw</span>',
            'new' => '<span class="badge bg-info">New</span>',
            'discussion' => '<span class="badge bg-primary">Discussion</span>',
            'demo' => '<span class="badge bg-warning">Demo</span>',
            'proposal' => '<span class="badge bg-success">Proposal</span>',
            'decided' => '<span class="badge bg-success">Decided</span>',
            'inactive' => '<span class="badge bg-dark">Inactive</span>'
        ];
        
        return $badges[$stage] ?? '<span class="badge bg-secondary">' . ucfirst($stage) . '</span>';
    }
    
    /**
     * Get action buttons
     */
    private function getActionButtons($id) {
        return '
            <button class="btn btn-sm btn-warning btn-edit" data-id="' . $id . '" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-success btn-whatsapp" data-id="' . $id . '" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </button>
        ';
    }
}
