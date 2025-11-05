<?php
/**
 * Supplier Controller
 * Handles all supplier-related business logic and API endpoints
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Supplier.php';

class SupplierController {
    private $model;
    
    public function __construct() {
        $this->model = new Supplier();
    }
    
    /**
     * Load main supplier page with initial data
     */
    public function index() {
        requireLogin();
        
        return [
            'suppliers' => $this->model->getSuppliers(['limit' => 25]),
            'total_count' => $this->model->getSuppliersCount(),
            'types' => ['vendor', 'manufacturer', 'distributor', 'service_provider'],
            'statuses' => ['active', 'inactive', 'blocked'],
            'payment_terms' => ['net15', 'net30', 'net45', 'net60', 'cod', 'advance']
        ];
    }
    
    /**
     * Get suppliers in JSON format (for DataTables)
     */
    public function getSuppliersJson() {
        requireLogin();
        
        // DataTables parameters
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 25);
        $searchValue = $_GET['search']['value'] ?? '';
        $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
        $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
        
        // Column mapping for sorting
        $columns = ['code', 'name', 'type', 'city', 'phone', 'status', 'created_at'];
        $orderBy = $columns[$orderColumn] ?? 'name';
        
        // Build filters
        $filters = [
            'search' => $searchValue,
            'order_by' => $orderBy,
            'order_dir' => strtoupper($orderDir),
            'limit' => $length,
            'offset' => $start
        ];
        
        // Additional filters from custom inputs
        if (!empty($_GET['connection_type'])) {
            $filters['connection_type'] = $_GET['connection_type'];
        }
        if (!empty($_GET['executive'])) {
            $filters['executive'] = $_GET['executive'];
        }
        if (!empty($_GET['city'])) {
            $filters['city'] = $_GET['city'];
        }
        if (!empty($_GET['state'])) {
            $filters['state'] = $_GET['state'];
        }
        
        // Get data
        $suppliers = $this->model->getSuppliers($filters);
        $totalRecords = $this->model->getSuppliersCount();
        $filteredRecords = $this->model->getSuppliersCount($filters);
        
        // Format data for display (new Biziverse format)
        $data = [];
        foreach ($suppliers as $supplier) {
            $company = '<strong>' . htmlspecialchars($supplier['name']) . '</strong>';
            if (!empty($supplier['code'])) {
                $company .= '<br><small class="text-muted">' . htmlspecialchars($supplier['code']) . '</small>';
            }
            
            $contact = htmlspecialchars($supplier['contact_name'] ?? '-');
            $contactNumber = $supplier['mobile'] ?? $supplier['phone'] ?? '';
            if (!empty($contactNumber)) {
                $contact .= '<br><small class="text-muted">' . htmlspecialchars($contactNumber) . '</small>';
            }
            
            // Relation indicator (green dot for active suppliers)
            $relation = '<i class="fas fa-circle text-success"></i>';
            
            // Last Talk and Next Action (placeholders for now)
            $lastTalk = '-';
            $nextAction = '-';
            
            $data[] = [
                'company' => $company,
                'contact' => $contact,
                'relation' => $relation,
                'last_talk' => $lastTalk,
                'next_action' => $nextAction,
                'actions' => $this->getActionButtons($supplier['id'], $supplier)
            ];
        }
        
        jsonResponse(true, '', [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    /**
     * Create a new supplier
     */
    public function create() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        // Validate required fields
        $errors = [];
        if (empty($_POST['name'])) {
            $errors[] = 'Supplier name is required';
        }
        if (empty($_POST['code'])) {
            $errors[] = 'Supplier code is required';
        }
        
        // Check if code already exists
        if (!empty($_POST['code'])) {
            $existing = $this->model->getSupplierByCode($_POST['code']);
            if ($existing) {
                $errors[] = 'Supplier code already exists';
            }
        }
        
        // Validate email format
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($errors)) {
            jsonResponse(false, implode(', ', $errors));
            return;
        }
        
        // Auto-generate code if not provided
        $code = !empty($_POST['code']) ? sanitize($_POST['code']) : $this->model->generateCode();
        
        // Build contact person name from prefix and names
        $contactPerson = sanitize($_POST['contact_person'] ?? '');
        if (!empty($_POST['name_prefix']) && !empty($contactPerson)) {
            $contactPerson = sanitize($_POST['name_prefix']) . ' ' . $contactPerson;
            if (!empty($_POST['last_name'])) {
                $contactPerson .= ' ' . sanitize($_POST['last_name']);
            }
        }
        
        // Prepare data
        $data = [
            'code' => $code,
            'name' => sanitize($_POST['name']),
            'type' => sanitize($_POST['type'] ?? 'vendor'),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'website' => sanitize($_POST['website'] ?? ''),
            'gstin' => sanitize($_POST['gstin'] ?? ''),
            'pan' => sanitize($_POST['pan'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'state' => sanitize($_POST['state'] ?? ''),
            'pincode' => sanitize($_POST['pincode'] ?? ''),
            'country' => sanitize($_POST['country'] ?? 'India'),
            'contact_person' => $contactPerson,
            'payment_terms' => sanitize($_POST['payment_terms'] ?? 'net30'),
            'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
            'credit_days' => intval($_POST['credit_days'] ?? 30),
            'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
            'status' => sanitize($_POST['status'] ?? 'active'),
            'notes' => sanitize($_POST['notes'] ?? '')
        ];
        
        $supplierId = $this->model->createSupplier($data);
        
        if ($supplierId) {
            logAudit('suppliers', $supplierId, 'create', 'Created supplier: ' . $data['name']);
            jsonResponse(true, 'Supplier created successfully', ['id' => $supplierId]);
        } else {
            jsonResponse(false, 'Failed to create supplier');
        }
    }
    
    /**
     * Update an existing supplier
     */
    public function update() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid supplier ID');
            return;
        }
        
        // Validate required fields
        $errors = [];
        if (empty($_POST['name'])) {
            $errors[] = 'Supplier name is required';
        }
        if (empty($_POST['code'])) {
            $errors[] = 'Supplier code is required';
        }
        
        // Check if code already exists (excluding current supplier)
        if (!empty($_POST['code'])) {
            $existing = $this->model->getSupplierByCode($_POST['code'], $id);
            if ($existing) {
                $errors[] = 'Supplier code already exists';
            }
        }
        
        // Validate email format
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($errors)) {
            jsonResponse(false, implode(', ', $errors));
            return;
        }
        
        // Get old data for audit
        $oldData = $this->model->getSupplierById($id);
        
        // Prepare data
        $data = [
            'code' => sanitize($_POST['code']),
            'name' => sanitize($_POST['name']),
            'type' => sanitize($_POST['type'] ?? 'vendor'),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'website' => sanitize($_POST['website'] ?? ''),
            'gstin' => sanitize($_POST['gstin'] ?? ''),
            'pan' => sanitize($_POST['pan'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'state' => sanitize($_POST['state'] ?? ''),
            'pincode' => sanitize($_POST['pincode'] ?? ''),
            'country' => sanitize($_POST['country'] ?? 'India'),
            'contact_person' => sanitize($_POST['contact_person'] ?? ''),
            'payment_terms' => sanitize($_POST['payment_terms'] ?? 'net30'),
            'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
            'credit_days' => intval($_POST['credit_days'] ?? 30),
            'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
            'status' => sanitize($_POST['status'] ?? 'active'),
            'notes' => sanitize($_POST['notes'] ?? '')
        ];
        
        $result = $this->model->updateSupplier($id, $data);
        
        if ($result) {
            logAudit('suppliers', $id, 'update', 'Updated supplier: ' . $data['name'], json_encode($oldData), json_encode($data));
            jsonResponse(true, 'Supplier updated successfully');
        } else {
            jsonResponse(false, 'Failed to update supplier');
        }
    }
    
    /**
     * Delete a supplier
     */
    public function delete() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid supplier ID');
            return;
        }
        
        $supplier = $this->model->getSupplierById($id);
        if (!$supplier) {
            jsonResponse(false, 'Supplier not found');
            return;
        }
        
        $result = $this->model->deleteSupplier($id);
        
        if ($result) {
            logAudit('suppliers', $id, 'delete', 'Deleted supplier: ' . $supplier['name']);
            jsonResponse(true, 'Supplier deleted successfully');
        } else {
            jsonResponse(false, 'Cannot delete supplier with existing purchase orders');
        }
    }
    
    /**
     * Get a single supplier
     */
    public function getSupplier() {
        requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid supplier ID');
            return;
        }
        
        $supplier = $this->model->getSupplierById($id);
        if ($supplier) {
            jsonResponse(true, 'Supplier retrieved', $supplier);
        } else {
            jsonResponse(false, 'Supplier not found');
        }
    }
    
    /**
     * Get supplier contacts
     */
    public function getContacts() {
        requireLogin();
        
        $supplierId = intval($_GET['supplier_id'] ?? 0);
        if (!$supplierId) {
            jsonResponse(false, 'Invalid supplier ID');
            return;
        }
        
        $contacts = $this->model->getContacts($supplierId);
        jsonResponse(true, 'Contacts retrieved', $contacts);
    }
    
    /**
     * Add a contact to supplier
     */
    public function addContact() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $supplierId = intval($_POST['supplier_id'] ?? 0);
        if (!$supplierId) {
            jsonResponse(false, 'Invalid supplier ID');
            return;
        }
        
        if (empty($_POST['name'])) {
            jsonResponse(false, 'Contact name is required');
            return;
        }
        
        $data = [
            'supplier_id' => $supplierId,
            'name' => sanitize($_POST['name']),
            'designation' => sanitize($_POST['designation'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'whatsapp' => sanitize($_POST['whatsapp'] ?? ''),
            'is_primary' => intval($_POST['is_primary'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? '')
        ];
        
        $contactId = $this->model->addContact($data);
        
        if ($contactId) {
            logAudit('supplier_contacts', $contactId, 'create', 'Added contact for supplier ID: ' . $supplierId);
            jsonResponse(true, 'Contact added successfully', ['id' => $contactId]);
        } else {
            jsonResponse(false, 'Failed to add contact');
        }
    }
    
    /**
     * Update a contact
     */
    public function updateContact() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $supplierId = intval($_POST['supplier_id'] ?? 0);
        
        if (!$id || !$supplierId) {
            jsonResponse(false, 'Invalid contact or supplier ID');
            return;
        }
        
        if (empty($_POST['name'])) {
            jsonResponse(false, 'Contact name is required');
            return;
        }
        
        $data = [
            'supplier_id' => $supplierId,
            'name' => sanitize($_POST['name']),
            'designation' => sanitize($_POST['designation'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'mobile' => sanitize($_POST['mobile'] ?? ''),
            'whatsapp' => sanitize($_POST['whatsapp'] ?? ''),
            'is_primary' => intval($_POST['is_primary'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? '')
        ];
        
        $result = $this->model->updateContact($id, $data);
        
        if ($result) {
            logAudit('supplier_contacts', $id, 'update', 'Updated contact for supplier ID: ' . $supplierId);
            jsonResponse(true, 'Contact updated successfully');
        } else {
            jsonResponse(false, 'Failed to update contact');
        }
    }
    
    /**
     * Delete a contact
     */
    public function deleteContact() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(false, 'Invalid contact ID');
            return;
        }
        
        $result = $this->model->deleteContact($id);
        
        if ($result) {
            logAudit('supplier_contacts', $id, 'delete', 'Deleted contact');
            jsonResponse(true, 'Contact deleted successfully');
        } else {
            jsonResponse(false, 'Failed to delete contact');
        }
    }
    
    /**
     * Generate next supplier code
     */
    public function generateCode() {
        requireLogin();
        $code = $this->model->generateCode();
        jsonResponse(true, 'Code generated', ['code' => $code]);
    }
    
    /**
     * Export suppliers to CSV
     */
    public function exportCSV() {
        requireLogin();
        
        $filters = [];
        if (!empty($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $suppliers = $this->model->getSuppliers($filters);
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="suppliers_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Code', 'Name', 'Type', 'Contact Person', 'Email', 'Phone', 'Mobile',
            'Address', 'City', 'State', 'Pincode', 'GSTIN', 'PAN',
            'Payment Terms', 'Credit Limit', 'Credit Days', 'Status'
        ]);
        
        // CSV data
        foreach ($suppliers as $supplier) {
            fputcsv($output, [
                $supplier['code'],
                $supplier['name'],
                $supplier['type'],
                $supplier['contact_person'] ?? '',
                $supplier['email'] ?? '',
                $supplier['phone'] ?? '',
                $supplier['mobile'] ?? '',
                $supplier['address'] ?? '',
                $supplier['city'] ?? '',
                $supplier['state'] ?? '',
                $supplier['pincode'] ?? '',
                $supplier['gstin'] ?? '',
                $supplier['pan'] ?? '',
                $supplier['payment_terms'],
                $supplier['credit_limit'],
                $supplier['credit_days'],
                $supplier['status']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Import suppliers from CSV
     */
    public function importCSV() {
        requireLogin();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Invalid security token');
            return;
        }
        
        if (!isset($_FILES['csv_file'])) {
            jsonResponse(false, 'No file uploaded');
            return;
        }
        
        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'File upload error');
            return;
        }
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            jsonResponse(false, 'Cannot read file');
            return;
        }
        
        $header = fgetcsv($handle); // Skip header row
        $imported = 0;
        $errors = [];
        $row = 1;
        
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Validate minimum required fields
            if (empty($data[0]) || empty($data[1])) {
                $errors[] = "Row $row: Code and Name are required";
                continue;
            }
            
            // Check if code already exists
            $existing = $this->model->getSupplierByCode($data[0]);
            if ($existing) {
                $errors[] = "Row $row: Supplier code {$data[0]} already exists";
                continue;
            }
            
            $supplierData = [
                'code' => $data[0],
                'name' => $data[1],
                'type' => $data[2] ?? 'vendor',
                'contact_person' => $data[3] ?? '',
                'email' => $data[4] ?? '',
                'phone' => $data[5] ?? '',
                'mobile' => $data[6] ?? '',
                'address' => $data[7] ?? '',
                'city' => $data[8] ?? '',
                'state' => $data[9] ?? '',
                'pincode' => $data[10] ?? '',
                'gstin' => $data[11] ?? '',
                'pan' => $data[12] ?? '',
                'payment_terms' => $data[13] ?? 'net30',
                'credit_limit' => floatval($data[14] ?? 0),
                'credit_days' => intval($data[15] ?? 30),
                'status' => $data[16] ?? 'active'
            ];
            
            $supplierId = $this->model->createSupplier($supplierData);
            if ($supplierId) {
                $imported++;
                logAudit('suppliers', $supplierId, 'import', 'Imported supplier via CSV');
            } else {
                $errors[] = "Row $row: Failed to import supplier";
            }
        }
        
        fclose($handle);
        
        $message = "Successfully imported $imported suppliers.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
        }
        
        jsonResponse(true, $message, ['imported' => $imported, 'errors' => $errors]);
    }
    
    /**
     * Get action buttons for supplier row (Biziverse style)
     */
    private function getActionButtons($id, $supplier) {
        $buttons = '';
        
        // WhatsApp button (prefer whatsapp number, then mobile, then phone)
        $whatsappNumber = $supplier['whatsapp'] ?? $supplier['mobile'] ?? $supplier['phone'] ?? '';
        if (!empty($whatsappNumber)) {
            $whatsapp = preg_replace('/[^0-9]/', '', $whatsappNumber);
            if (strlen($whatsapp) === 10) {
                $whatsapp = '91' . $whatsapp; // Add country code
            }
            $buttons .= '<a href="https://wa.me/' . $whatsapp . '" target="_blank" class="btn btn-success btn-sm me-1" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>';
        }
        
        // Email button (if email available)
        if (!empty($supplier['email'])) {
            $buttons .= '<a href="mailto:' . htmlspecialchars($supplier['email']) . '" class="btn btn-warning btn-sm me-1" title="Email">
                <i class="fas fa-envelope"></i>
            </a>';
        }
        
        // Edit button
        $buttons .= '<button class="btn btn-warning btn-sm" onclick="editSupplier(' . $id . ')" title="Edit">
            <i class="fas fa-edit"></i>
        </button>';
        
        return $buttons;
    }
}
