/**
 * Quotations Management
 */

let quotationsTable;
let currentType = '';
let currentFilters = {};

$(document).ready(function() {
    // Initialize DataTable
    initializeDataTable();
    
    // Load totals
    loadTotals();
    
    // Filter buttons
    $('[data-filter]').on('click', function() {
        $('[data-filter]').removeClass('active');
        $(this).addClass('active');
        
        const filter = $(this).data('filter');
        currentType = filter === 'all' ? '' : filter === 'proforma' ? 'proforma_invoice' : filter;
        quotationsTable.ajax.reload();
        loadTotals();
    });
    
    // Filter dropdowns
    $('#filterMonth, #filterStatus, #filterBranch, #filterExecutive').on('change', function() {
        quotationsTable.ajax.reload();
        loadTotals();
    });
    
    // Create Quotation Button
    $('#btnCreateQuotation').on('click', function() {
        window.location.href = 'quotation_form.php';
    });
    
    // Placeholder buttons
    $('#btnPrintSettings, #btnUpload, #btnGrid').on('click', function() {
        showNotification('Coming Soon', 'This feature will be available soon!', 'info');
    });
});

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    quotationsTable = $('#quotationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'quotations.php?action=list_json',
            type: 'POST',
            data: function(d) {
                d.type = currentType;
                d.month = $('#filterMonth').val();
                d.status = $('#filterStatus').val();
                d.branch = $('#filterBranch').val();
                d.executive = $('#filterExecutive').val();
                d[window.CSRF_TOKEN_NAME] = $('input[name="' + window.CSRF_TOKEN_NAME + '"]').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable Error:', error, thrown);
                showNotification('Error', 'Failed to load quotations data', 'error');
            }
        },
        columns: [
            { data: 'quote_no', orderable: true },
            { data: 'customer', orderable: true },
            { 
                data: 'amount',
                className: 'text-end',
                orderable: true
            },
            { data: 'valid_till', orderable: true },
            { data: 'issued_on', orderable: true },
            { data: 'issued_by', orderable: false },
            { data: 'type', orderable: false },
            { data: 'executive', orderable: false },
            { data: 'response', orderable: false },
            { data: 'actions', orderable: false }
        ],
        order: [[4, 'desc']], // Order by issued_on
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No quotations found',
            zeroRecords: 'No matching quotations found'
        },
        drawCallback: function(settings) {
            // Bind view buttons
            $('.btn-view').off('click').on('click', function() {
                const quoteId = $(this).data('id');
                viewQuotation(quoteId);
            });
            
            // Bind edit buttons
            $('.btn-edit').off('click').on('click', function() {
                const quoteId = $(this).data('id');
                editQuotation(quoteId);
            });
        }
    });
}

/**
 * Load Totals
 */
function loadTotals() {
    $.ajax({
        url: 'quotations.php',
        type: 'GET',
        data: {
            action: 'get_totals',
            type: currentType,
            month: $('#filterMonth').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#totalCount').text(data.count || 0);
                $('#preTaxTotal').text(parseFloat(data.pre_tax || 0).toFixed(2));
                $('#grandTotal').text(parseFloat(data.total || 0).toFixed(2));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading totals:', error);
        }
    });
}

/**
 * View Quotation
 */
function viewQuotation(quoteId) {
    // Placeholder - open in new window or modal
    showNotification('Info', 'View functionality coming soon!', 'info');
}

/**
 * Edit Quotation
 */
function editQuotation(quoteId) {
    window.location.href = 'quotation_form.php?id=' + quoteId;
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
