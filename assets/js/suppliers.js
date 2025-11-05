/**
 * Suppliers Module - Frontend JavaScript
 */

let suppliersTable;
let currentSupplierId = null;

$(document).ready(function() {
    initializeSuppliersTable();
    setupEventListeners();
});

/**
 * Initialize DataTable
 */
function initializeSuppliersTable() {
    suppliersTable = $('#suppliersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'suppliers.php?action=list_json',
            type: 'GET',
            data: function(d) {
                d.connection_type = $('#currentConnectionType').val() || 'supplier';
                d.executive = $('#filterExecutive').val();
                d.city = $('#filterCity').val();
                d.state = $('#filterState').val();
            },
            dataSrc: function(json) {
                // Handle nested response structure
                if (json.success && json.data) {
                    // Copy pagination data to root level for DataTables
                    json.recordsTotal = json.data.recordsTotal;
                    json.recordsFiltered = json.data.recordsFiltered;
                    json.draw = json.data.draw;
                    return json.data.data;
                }
                return [];
            },
            error: function(xhr, error, code) {
                console.error('DataTable Ajax Error:', error, code);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'company' },
            { data: 'contact' },
            { data: 'relation', orderable: false, className: 'text-center' },
            { data: 'last_talk' },
            { data: 'next_action' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No connections found",
            paginate: {
                first: "«",
                last: "»",
                next: "›",
                previous: "‹"
            }
        }
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Add hidden input for connection type
    if ($('#currentConnectionType').length === 0) {
        $('body').append('<input type="hidden" id="currentConnectionType" value="supplier">');
    }
    
    // Filter changes
    $('#filterExecutive, #filterCity, #filterState').on('change', function() {
        suppliersTable.ajax.reload();
    });
    
    // Top search
    $('#topSearch').on('keyup', debounce(function() {
        suppliersTable.search($(this).val()).draw();
    }, 300));
    
    // Supplier form submission
    $('#supplierForm').on('submit', function(e) {
        e.preventDefault();
        saveSupplier();
    });
    
    // Contact form submission
    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        saveContact();
    });
    
    // Import form submission
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        importCSV();
    });
    
    // Reset form when modal closes
    $('#supplierModal').on('hidden.bs.modal', function() {
        resetSupplierForm();
    });
    
    $('#contactsModal').on('hidden.bs.modal', function() {
        resetContactForm();
    });
}

/**
 * Add new supplier
 */
function addSupplier() {
    resetSupplierForm();
    $('#supplierModalLabel').text('Enter Supplier');
    // Don't auto-generate code - user can click Auto button if needed
    $('#supplierModal').modal('show');
}

/**
 * Edit supplier
 */
function editSupplier(id) {
    resetSupplierForm();
    $('#supplierModalLabel').text('Edit Supplier');
    
    $.ajax({
        url: 'suppliers.php?action=get_supplier',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const supplier = response.data;
                $('#supplierId').val(supplier.id);
                $('#supplierCode').val(supplier.code);
                $('#supplierName').val(supplier.name);
                $('#supplierType').val(supplier.type);
                $('#supplierStatus').val(supplier.status);
                $('#contactPerson').val(supplier.contact_person);
                $('#supplierEmail').val(supplier.email);
                $('#supplierPhone').val(supplier.phone);
                $('#supplierMobile').val(supplier.mobile);
                $('#supplierWebsite').val(supplier.website);
                $('#supplierAddress').val(supplier.address);
                $('#supplierCity').val(supplier.city);
                $('#supplierState').val(supplier.state);
                $('#supplierPincode').val(supplier.pincode);
                $('#supplierCountry').val(supplier.country);
                $('#supplierGstin').val(supplier.gstin);
                $('#supplierPan').val(supplier.pan);
                $('#paymentTerms').val(supplier.payment_terms);
                $('#creditLimit').val(supplier.credit_limit);
                $('#creditDays').val(supplier.credit_days);
                $('#openingBalance').val(supplier.opening_balance);
                $('#supplierNotes').val(supplier.notes);
                
                $('#supplierModal').modal('show');
            } else {
                showAlert('danger', response.message || 'Failed to load supplier');
            }
        },
        error: function() {
            showAlert('danger', 'Failed to load supplier');
        }
    });
}

/**
 * View supplier details
 */
function viewSupplier(id) {
    editSupplier(id);
    // Make all form fields readonly
    $('#supplierForm :input').prop('readonly', true);
    $('#supplierForm select').prop('disabled', true);
    $('#supplierForm button[type="submit"]').hide();
    $('#supplierModalLabel').text('View Supplier');
}

/**
 * Delete supplier
 */
function deleteSupplier(id) {
    if (!confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: 'suppliers.php?action=delete',
        type: 'POST',
        data: {
            id: id,
            [CSRF_TOKEN_NAME]: CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                suppliersTable.ajax.reload();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to delete supplier');
        }
    });
}

/**
 * Save supplier (create or update)
 */
function saveSupplier() {
    const formData = $('#supplierForm').serialize();
    const isEdit = $('#supplierId').val() !== '';
    const action = isEdit ? 'update' : 'create';
    
    $.ajax({
        url: 'suppliers.php?action=' + action,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#supplierModal').modal('hide');
                suppliersTable.ajax.reload();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to save supplier');
        }
    });
}

/**
 * Reset supplier form
 */
function resetSupplierForm() {
    $('#supplierForm')[0].reset();
    $('#supplierId').val('');
    $('#supplierForm :input').prop('readonly', false);
    $('#supplierForm select').prop('disabled', false);
    $('#supplierForm button[type="submit"]').show();
    
    // Reset checkboxes
    $('#isCustomer').prop('checked', false);
    $('#isSupplier').prop('checked', true);
    $('#isNeighbour').prop('checked', false);
    $('#isFriend').prop('checked', false);
    
    // Hide more details section
    $('#moreDetailsSection').hide();
    $('#moreDetailsIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
}

/**
 * Generate supplier code
 */
function generateSupplierCode() {
    $.ajax({
        url: 'suppliers.php?action=generate_code',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#supplierCode').val(response.data.code);
            }
        }
    });
}

/**
 * Manage supplier contacts
 */
function manageContacts(supplierId) {
    currentSupplierId = supplierId;
    $('#contactSupplierId').val(supplierId);
    
    // Get supplier name
    $.ajax({
        url: 'suppliers.php?action=get_supplier',
        type: 'GET',
        data: { id: supplierId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#contactSupplierName').text(response.data.name);
            }
        }
    });
    
    loadContacts(supplierId);
    $('#contactsModal').modal('show');
}

/**
 * Load contacts for supplier
 */
function loadContacts(supplierId) {
    $('#contactsList').html('<p class="text-muted text-center">Loading contacts...</p>');
    
    $.ajax({
        url: 'suppliers.php?action=get_contacts',
        type: 'GET',
        data: { supplier_id: supplierId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayContacts(response.data);
            } else {
                $('#contactsList').html('<p class="text-danger text-center">Failed to load contacts</p>');
            }
        },
        error: function() {
            $('#contactsList').html('<p class="text-danger text-center">Error loading contacts</p>');
        }
    });
}

/**
 * Display contacts list
 */
function displayContacts(contacts) {
    if (contacts.length === 0) {
        $('#contactsList').html('<p class="text-muted text-center">No contacts found</p>');
        return;
    }
    
    let html = '<div class="list-group">';
    contacts.forEach(function(contact) {
        const primaryBadge = contact.is_primary == 1 ? '<span class="badge bg-primary ms-2">Primary</span>' : '';
        const whatsappBtn = contact.whatsapp ? `<a href="https://wa.me/${contact.whatsapp}" target="_blank" class="btn btn-success btn-sm" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>` : '';
        const emailBtn = contact.email ? `<a href="mailto:${contact.email}" class="btn btn-info btn-sm" title="Email"><i class="fas fa-envelope"></i></a>` : '';
        
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            ${htmlEscape(contact.name)}
                            ${primaryBadge}
                        </h6>
                        ${contact.designation ? `<p class="mb-1 text-muted small">${htmlEscape(contact.designation)}</p>` : ''}
                        <div class="small">
                            ${contact.email ? `<i class="fas fa-envelope"></i> ${htmlEscape(contact.email)}<br>` : ''}
                            ${contact.phone ? `<i class="fas fa-phone"></i> ${htmlEscape(contact.phone)}<br>` : ''}
                            ${contact.mobile ? `<i class="fas fa-mobile"></i> ${htmlEscape(contact.mobile)}<br>` : ''}
                            ${contact.whatsapp ? `<i class="fab fa-whatsapp"></i> ${htmlEscape(contact.whatsapp)}` : ''}
                        </div>
                    </div>
                    <div class="btn-group-vertical btn-group-sm ms-3">
                        ${whatsappBtn}
                        ${emailBtn}
                        <button class="btn btn-primary btn-sm" onclick="editContact(${contact.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteContact(${contact.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    $('#contactsList').html(html);
}

/**
 * Show add contact form
 */
function showAddContactForm() {
    resetContactForm();
    $('#contactFormSupplierId').val(currentSupplierId);
    $('#contactFormDiv').slideDown();
}

/**
 * Hide contact form
 */
function hideContactForm() {
    $('#contactFormDiv').slideUp();
    resetContactForm();
}

/**
 * Edit contact
 */
function editContact(contactId) {
    // Get current contacts
    $.ajax({
        url: 'suppliers.php?action=get_contacts',
        type: 'GET',
        data: { supplier_id: currentSupplierId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const contact = response.data.find(c => c.id == contactId);
                if (contact) {
                    $('#contactId').val(contact.id);
                    $('#contactFormSupplierId').val(currentSupplierId);
                    $('#contactName').val(contact.name);
                    $('#contactDesignation').val(contact.designation);
                    $('#contactEmail').val(contact.email);
                    $('#contactPhone').val(contact.phone);
                    $('#contactMobile').val(contact.mobile);
                    $('#contactWhatsapp').val(contact.whatsapp);
                    $('#isPrimary').prop('checked', contact.is_primary == 1);
                    $('#contactNotes').val(contact.notes);
                    $('#contactFormDiv').slideDown();
                }
            }
        }
    });
}

/**
 * Delete contact
 */
function deleteContact(contactId) {
    if (!confirm('Are you sure you want to delete this contact?')) {
        return;
    }
    
    $.ajax({
        url: 'suppliers.php?action=delete_contact',
        type: 'POST',
        data: {
            id: contactId,
            [CSRF_TOKEN_NAME]: CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                loadContacts(currentSupplierId);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to delete contact');
        }
    });
}

/**
 * Save contact (create or update)
 */
function saveContact() {
    const formData = $('#contactForm').serialize();
    const isEdit = $('#contactId').val() !== '';
    const action = isEdit ? 'update_contact' : 'add_contact';
    
    $.ajax({
        url: 'suppliers.php?action=' + action,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                hideContactForm();
                loadContacts(currentSupplierId);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to save contact');
        }
    });
}

/**
 * Reset contact form
 */
function resetContactForm() {
    $('#contactForm')[0].reset();
    $('#contactId').val('');
}

/**
 * Export suppliers to CSV
 */
function exportSuppliers() {
    const type = $('#filterType').val();
    const status = $('#filterStatus').val();
    const search = suppliersTable.search();
    
    let url = 'suppliers.php?action=export_csv';
    if (type) url += '&type=' + type;
    if (status) url += '&status=' + status;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

/**
 * Import suppliers from CSV
 */
function importCSV() {
    const formData = new FormData($('#importForm')[0]);
    formData.append('action', 'import_csv');
    
    $.ajax({
        url: 'suppliers.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#importModal').modal('hide');
                $('#importForm')[0].reset();
                suppliersTable.ajax.reload();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Failed to import CSV');
        }
    });
}

/**
 * Reset all filters
 */
function resetFilters() {
    $('#filterExecutive').val('');
    $('#filterCity').val('');
    $('#filterState').val('');
    $('#topSearch').val('');
    suppliersTable.search('').draw();
    suppliersTable.ajax.reload();
}

/**
 * Filter by connection type (tabs)
 */
function filterByConnectionType(type) {
    // Update hidden field
    $('#currentConnectionType').val(type);
    
    // Update active tab
    $('.nav-tabs .nav-link').removeClass('active');
    $('#tab-' + type).addClass('active');
    
    // Reload table
    suppliersTable.ajax.reload();
}

/**
 * Toggle more details section
 */
function toggleMoreDetails() {
    const section = $('#moreDetailsSection');
    const icon = $('#moreDetailsIcon');
    
    if (section.is(':visible')) {
        section.slideUp();
        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        section.slideDown();
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

/**
 * Fill using GSTIN (placeholder)
 */
function fillUsingGSTIN() {
    const gstin = prompt('Enter GSTIN:');
    if (gstin) {
        // This would call an API to fetch business details
        alert('GSTIN lookup feature coming soon!');
    }
}

/**
 * Debounce helper
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * HTML escape helper
 */
function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.content-wrapper .alert').remove();
    
    // Add new alert at the top
    $('.content-wrapper .container-fluid').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Scroll to top
    $('html, body').animate({ scrollTop: 0 }, 'fast');
}

/**
 * Open Addresses & GST management modal
 */
function manageAddressesGST() {
    // TODO: Implement separate modal for managing multiple addresses and GST details
    showAlert('info', 'Multiple addresses & GST management feature coming soon!');
}
