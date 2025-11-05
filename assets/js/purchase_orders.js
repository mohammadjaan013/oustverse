/**
 * Purchase Orders Module - Frontend JavaScript
 */

let purchaseOrdersTable;

$(document).ready(function() {
    initializePurchaseOrdersTable();
    setupEventListeners();
});

/**
 * Initialize DataTable
 */
function initializePurchaseOrdersTable() {
    purchaseOrdersTable = $('#purchaseOrdersTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'purchase_orders.php?action=list_json',
            type: 'GET',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.month = $('#filterMonth').val();
                d.created_by = $('#filterExecutive').val();
            },
            dataSrc: function(json) {
                // Extract the data array from the nested response
                if (json.success && json.data && json.data.data) {
                    return json.data.data;
                }
                return [];
            }
        },
        columns: [
            { data: 'supplier' },
            { data: 'contact' },
            { data: 'order_no' },
            { data: 'order_date' },
            { data: 'taxable', className: 'text-end' },
            { data: 'amount', className: 'text-end' },
            { data: 'status', className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[3, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No purchase orders found",
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
    // Filter changes
    $('#filterStatus, #filterMonth, #filterExecutive, #filterBranch').on('change', function() {
        purchaseOrdersTable.ajax.reload();
    });
}

/**
 * Edit purchase order
 */
function editPurchaseOrder(id) {
    window.location.href = 'purchase_order_form.php?id=' + id;
}

/**
 * View purchase order
 */
function viewPurchaseOrder(id) {
    window.location.href = 'purchase_order_view.php?id=' + id;
}

/**
 * Delete purchase order
 */
function deletePurchaseOrder(id) {
    if (!confirm('Are you sure you want to delete this purchase order?')) {
        return;
    }
    
    $.ajax({
        url: 'purchase_orders.php?action=delete',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                purchaseOrdersTable.ajax.reload();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Error deleting purchase order');
        }
    });
}

/**
 * Approve purchase order
 */
function approvePurchaseOrder(id) {
    if (!confirm('Are you sure you want to approve this purchase order?')) {
        return;
    }
    
    $.ajax({
        url: 'purchase_orders.php?action=approve',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                purchaseOrdersTable.ajax.reload();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Error approving purchase order');
        }
    });
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
