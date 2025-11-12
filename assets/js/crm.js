/**
 * CRM - Leads & Prospects Management
 */

let leadsTable;
let currentStage = '';
let currentSort = 'newest';
let currentFilters = {};

$(document).ready(function() {
    // Initialize DataTable
    initializeDataTable();

    // Stage Tab Click
    $('#stageTabs a').on('click', function(e) {
        e.preventDefault();
        $('#stageTabs a').removeClass('active');
        $(this).addClass('active');
        currentStage = $(this).data('stage');
        leadsTable.ajax.reload();
    });

    // Sort Buttons
    $('#btnNewest, #btnOldest').on('click', function() {
        $('#sortTabs button').removeClass('active');
        $(this).addClass('active');
        currentSort = $(this).attr('id') === 'btnNewest' ? 'newest' : 'oldest';
        console.log('Sort changed to:', currentSort);
        // Remove star filter when sorting
        delete currentFilters.starred_only;
        leadsTable.ajax.reload();
    });

    // Star Leads Filter
    $('#btnStarLeads').on('click', function() {
        $('#sortTabs button').removeClass('active');
        $(this).addClass('active');
        currentFilters.starred_only = true;
        console.log('Star filter enabled:', currentFilters);
        leadsTable.ajax.reload();
    });

    // Global Search
    let searchTimeout;
    $('#globalSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchValue = this.value;
        searchTimeout = setTimeout(function() {
            leadsTable.search(searchValue).draw();
        }, 300);
    });

    // Add Lead Button
    $('#btnAddLead, #btnQuickAddLead').on('click', function() {
        resetLeadForm();
        $('#leadModal .modal-title').text('Add Lead');
        $('#leadModal').modal('show');
    });

    // Save Lead
    $('#btnSaveLead').on('click', function() {
        saveLead();
    });

    // Lead Form Enter Key
    $('#leadForm').on('submit', function(e) {
        e.preventDefault();
        saveLead();
    });

    // Import buttons (placeholder functionality)
    $('#btnImport, #btnIntegrate, #btnImportExcel').on('click', function() {
        showNotification('Coming Soon', 'This feature will be available soon!', 'info');
    });

    // Kanban view (placeholder)
    $('#btnKanban').on('click', function(e) {
        e.preventDefault();
        showNotification('Coming Soon', 'Kanban view will be available soon!', 'info');
        // Don't change active state for placeholders
        return false;
    });

    // Appointments view (placeholder)
    $('#btnAppointments').on('click', function(e) {
        e.preventDefault();
        showNotification('Coming Soon', 'Appointments view will be available soon!', 'info');
        // Don't change active state for placeholders
        return false;
    });

    // Filters (placeholder)
    $('#btnFilters').on('click', function() {
        showNotification('Coming Soon', 'Advanced filters will be available soon!', 'info');
    });
});

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    leadsTable = $('#leadsTable').DataTable({
        processing: true,
        serverSide: false, // Changed to client-side processing for better debugging
        ajax: {
            url: 'crm.php?action=list_json',
            type: 'GET',
            data: function(d) {
                d.stage = currentStage;
                d.sort = currentSort;
                d.starred_only = currentFilters.starred_only || '';
                console.log('AJAX Request Data:', {
                    stage: d.stage,
                    sort: d.sort,
                    starred_only: d.starred_only
                });
                return d;
            },
            dataSrc: function(json) {
                console.log('Received data:', json.data.length, 'records');
                if (json.data.length > 0) {
                    console.log('First record ID:', json.data[0].id);
                    console.log('Last record ID:', json.data[json.data.length - 1].id);
                }
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable Error:', xhr.responseText);
                console.error('Error:', error);
                console.error('Thrown:', thrown);
                showNotification('Error', 'Failed to load leads data. Check console for details.', 'error');
            }
        },
        columns: [
            { 
                data: 'star',
                orderable: false,
                render: function(data, type, row) {
                    return `<span class="star-toggle" data-id="${row.id}" style="cursor:pointer;">${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    // WhatsApp icon will be in actions, keeping this column for consistency
                    return '';
                }
            },
            { 
                data: 'business',
                orderable: false
            },
            { 
                data: 'contact',
                orderable: false
            },
            { data: 'source', orderable: false },
            { data: 'stage', orderable: false },
            { data: 'since', orderable: false },
            { data: 'assigned_to', orderable: false },
            { data: 'last_talk', orderable: false },
            { data: 'next', orderable: false },
            { data: 'requirements', orderable: false },
            { data: 'notes', orderable: false },
            { data: 'actions', orderable: false }
        ],
        order: [], // Disable default ordering - use server-side order instead
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        searching: false, // Disable DataTables search since we handle it separately
        ordering: false, // Disable column sorting since we use custom sort buttons
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No leads found',
            zeroRecords: 'No matching leads found'
        },
        drawCallback: function(settings) {
            // Update count badge
            const info = this.api().page.info();
            const totalRecords = info.recordsTotal || 0;
            $('#leadCount').text(totalRecords);

            // Bind star toggle
            $('.star-toggle').off('click').on('click', function() {
                const leadId = $(this).data('id');
                toggleStar(leadId);
            });

            // Bind edit buttons
            $('.btn-edit').off('click').on('click', function() {
                const leadId = $(this).data('id');
                editLead(leadId);
            });

            // Bind WhatsApp buttons
            $('.btn-whatsapp').off('click').on('click', function() {
                const leadId = $(this).data('id');
                // WhatsApp functionality placeholder
                showNotification('Info', 'WhatsApp integration coming soon!', 'info');
            });
        }
    });
}

/**
 * Reset Lead Form
 */
function resetLeadForm() {
    $('#leadForm')[0].reset();
    $('#leadId').val('');
    $('#title').val('Mr');
    $('#stage').val('raw');
    $('#country').val('India');
    $('#sinceDate').val(new Date().toISOString().split('T')[0]);
    $('#potential').val('0');
}

/**
 * Save Lead (Create or Update)
 */
function saveLead() {
    const formData = new FormData($('#leadForm')[0]);
    const leadId = $('#leadId').val();
    const action = leadId ? 'update' : 'create';
    const url = `crm.php?action=${action}`;

    // Show loading
    $('#btnSaveLead').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('Success', response.message, 'success');
                $('#leadModal').modal('hide');
                leadsTable.ajax.reload();
            } else {
                showNotification('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Save Error:', error);
            showNotification('Error', 'Failed to save lead. Please try again.', 'error');
        },
        complete: function() {
            $('#btnSaveLead').prop('disabled', false).html('<i class="fas fa-check"></i> Save Lead');
        }
    });
}

/**
 * Edit Lead
 */
function editLead(leadId) {
    $.ajax({
        url: 'crm.php?action=get_lead&id=' + leadId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const lead = response.data;
                
                // Populate form - Core Data
                $('#leadId').val(lead.id);
                $('#businessName').val(lead.business_name);
                $('#title').val(lead.title || 'Mr');
                $('#firstName').val(lead.first_name);
                $('#contactName').val(lead.contact_name);
                $('#designation').val(lead.designation);
                $('#mobile').val(lead.mobile);
                $('#email').val(lead.email);
                $('#website').val(lead.website);
                
                // Address fields
                $('#addressLine1').val(lead.address_line1);
                $('#addressLine2').val(lead.address_line2);
                $('#country').val(lead.country || 'India');
                $('#city').val(lead.city);
                $('#state').val(lead.state);
                $('#gstin').val(lead.gstin);
                $('#pincode').val(lead.pincode);
                $('#code').val(lead.code);
                
                // Business Opportunity
                $('#source').val(lead.source);
                $('#sinceDate').val(lead.since_date);
                $('#category').val(lead.category);
                $('#product').val(lead.product);
                $('#potential').val(lead.potential || '0');
                $('#assignedTo').val(lead.assigned_to);
                $('#stage').val(lead.stage);
                $('#tags').val(lead.tags);
                $('#requirements').val(lead.requirements);
                $('#notes').val(lead.notes);
                
                // Update modal title and show
                $('#leadModal .modal-title').text('Edit Lead');
                $('#leadModal').modal('show');
            } else {
                showNotification('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Edit Error:', error);
            showNotification('Error', 'Failed to load lead data', 'error');
        }
    });
}

/**
 * Delete Lead
 */
function deleteLead(leadId) {
    if (!confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', leadId);
    formData.append(window.CSRF_TOKEN_NAME, $('input[name="' + window.CSRF_TOKEN_NAME + '"]').val());

    $.ajax({
        url: 'crm.php?action=delete',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('Success', response.message, 'success');
                leadsTable.ajax.reload();
            } else {
                showNotification('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete Error:', error);
            showNotification('Error', 'Failed to delete lead', 'error');
        }
    });
}

/**
 * Toggle Star
 */
function toggleStar(leadId) {
    const formData = new FormData();
    formData.append('id', leadId);
    formData.append(window.CSRF_TOKEN_NAME, $('input[name="' + window.CSRF_TOKEN_NAME + '"]').val());

    $.ajax({
        url: 'crm.php?action=toggle_star',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                leadsTable.ajax.reload(null, false); // Reload without resetting pagination
            } else {
                showNotification('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Toggle Star Error:', error);
            showNotification('Error', 'Failed to toggle star', 'error');
        }
    });
}

/**
 * Show Notification
 */
function showNotification(title, message, type) {
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const toastHtml = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Create toast container if it doesn't exist
    if ($('#toastContainer').length === 0) {
        $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    const $toast = $(toastHtml);
    $('#toastContainer').append($toast);
    
    const toast = new bootstrap.Toast($toast[0], { delay: 3000 });
    toast.show();
    
    $toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}
