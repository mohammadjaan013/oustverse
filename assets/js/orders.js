/**
 * Orders JavaScript
 * Handles orders list, commitment filtering, and modals
 */

$(document).ready(function() {
    let ordersTable;
    let currentCommitment = 'overdue';
    let quickItemIndex = 1;
    let deliveryItemIndex = 1;
    
    /**
     * Initialize DataTable
     */
    function initDataTable() {
        if (ordersTable) {
            ordersTable.destroy();
        }
        
        ordersTable = $('#ordersTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'controllers/OrderController.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'getOrdersJson';
                    d.commitment = currentCommitment;
                    d.status = $('#statusFilter').val();
                    d.order_type = $('#orderTypeFilter').val();
                }
            },
            columns: [
                { data: 'customer' },
                { data: 'contact' },
                { data: 'order_no' },
                { data: 'cust_po' },
                { data: 'item' },
                { data: 'due_date' },
                { data: 'qty', className: 'text-end' },
                { data: 'pndg', className: 'text-end' },
                { data: 'done', className: 'text-end' },
                { data: 'unit' },
                { data: 'total', className: 'text-end' },
                { 
                    data: 'status',
                    render: function(data) {
                        const badges = {
                            'pending': 'warning',
                            'confirmed': 'info',
                            'processing': 'primary',
                            'completed': 'success',
                            'cancelled': 'danger'
                        };
                        return `<span class="badge bg-${badges[data.toLowerCase()] || 'secondary'}">${data}</span>`;
                    }
                },
                { data: 'actions', orderable: false }
            ],
            order: [[5, 'asc']], // Sort by due date
            pageLength: 25,
            language: {
                emptyTable: "No orders found"
            }
        });
    }
    
    /**
     * Load commitment counts
     */
    function loadCommitmentCounts() {
        $.ajax({
            url: 'controllers/OrderController.php',
            method: 'POST',
            data: { action: 'getCommitmentCounts' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#badge-overdue').text(response.data.overdue || 0);
                    $('#badge-today').text(response.data.today || 0);
                    $('#badge-tomorrow').text(response.data.tomorrow || 0);
                    $('#badge-all').text(response.data.total || 0);
                }
            }
        });
    }
    
    /**
     * Commitment tab click
     */
    $('#commitmentTabs button').on('click', function() {
        currentCommitment = $(this).data('commitment');
        ordersTable.ajax.reload();
    });
    
    /**
     * Filter changes
     */
    $('#statusFilter, #orderTypeFilter').on('change', function() {
        ordersTable.ajax.reload();
    });
    
    /**
     * Order type tabs in modal
     */
    $('[data-order-type]').on('click', function(e) {
        e.preventDefault();
        const orderType = $(this).data('order-type');
        $(this).closest('.nav').find('.nav-link').removeClass('active');
        $(this).addClass('active');
        $('input[name="order_type"]').val(orderType);
    });
    
    /**
     * Same as billing checkbox
     */
    $('#sameAsBilling').on('change', function() {
        if ($(this).is(':checked')) {
            const billingAddress = $('textarea[name="billing_address"]').val();
            $('#quickOrderForm textarea[name="shipping_address"]').val(billingAddress);
        }
    });
    
    $('#deliverySameAsBilling').on('change', function() {
        if ($(this).is(':checked')) {
            const billingAddress = $('#deliveryForm textarea[name="billing_address"]').val();
            $('#deliveryForm textarea[name="shipping_address"]').val(billingAddress);
        }
    });
    
    /**
     * Add quick item
     */
    $('#addQuickItemBtn').on('click', function() {
        const itemRow = `
            <div class="row mb-2 quick-item-row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="items[${quickItemIndex}][description]" placeholder="Item Description" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="items[${quickItemIndex}][quantity]" placeholder="Qty" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[${quickItemIndex}][unit]" placeholder="Unit" value="nos">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="items[${quickItemIndex}][rate]" placeholder="Rate" step="0.01">
                </div>
                <div class="col-md-1">
                    <textarea class="form-control" name="items[${quickItemIndex}][notes]" placeholder="Notes" rows="1"></textarea>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        $('#quickItemsContainer').append(itemRow);
        quickItemIndex++;
    });
    
    /**
     * Remove quick item
     */
    $(document).on('click', '.quick-item-row .remove-item', function() {
        $(this).closest('.quick-item-row').remove();
    });
    
    /**
     * Add delivery item
     */
    $('#addDeliveryItemBtn').on('click', function() {
        const itemRow = `
            <div class="row mb-2 delivery-item-row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="items[${deliveryItemIndex}][description]" placeholder="Item Description" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="items[${deliveryItemIndex}][quantity]" placeholder="Qty" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="items[${deliveryItemIndex}][unit]" placeholder="Unit" value="nos">
                </div>
                <div class="col-md-3">
                    <textarea class="form-control" name="items[${deliveryItemIndex}][notes]" placeholder="Notes" rows="1"></textarea>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-delivery-item"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        $('#deliveryItemsContainer').append(itemRow);
        deliveryItemIndex++;
    });
    
    /**
     * Remove delivery item
     */
    $(document).on('click', '.delivery-item-row .remove-delivery-item', function() {
        $(this).closest('.delivery-item-row').remove();
    });
    
    /**
     * Submit quick order form
     */
    $('#quickOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize() + '&action=createQuickOrder';
        
        $.ajax({
            url: 'controllers/OrderController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#enterOrderModal').modal('hide');
                    $('#quickOrderForm')[0].reset();
                    ordersTable.ajax.reload();
                    loadCommitmentCounts();
                    showToast('Success', response.message, 'success');
                } else {
                    showToast('Error', response.message, 'error');
                }
            },
            error: function() {
                showToast('Error', 'An error occurred while creating the order', 'error');
            }
        });
    });
    
    /**
     * Submit delivery form
     */
    $('#deliveryForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize() + '&action=createDelivery';
        
        $.ajax({
            url: 'controllers/OrderController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#enterDeliveryModal').modal('hide');
                    $('#deliveryForm')[0].reset();
                    ordersTable.ajax.reload();
                    loadCommitmentCounts();
                    showToast('Success', response.message, 'success');
                } else {
                    showToast('Error', response.message, 'error');
                }
            },
            error: function() {
                showToast('Error', 'An error occurred while creating the delivery', 'error');
            }
        });
    });
    
    /**
     * View order
     */
    $(document).on('click', '.btn-view', function() {
        const orderId = $(this).data('id');
        // Redirect to order form in view mode
        window.location.href = `order_form.php?id=${orderId}&mode=view`;
    });
    
    /**
     * Edit order
     */
    $(document).on('click', '.btn-edit', function() {
        const orderId = $(this).data('id');
        // Redirect to order form in edit mode
        window.location.href = `order_form.php?id=${orderId}`;
    });
    
    /**
     * Show toast notification
     */
    function showToast(title, message, type) {
        // Use Bootstrap toast or alert
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                <strong>${title}:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('body').append(alert);
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }
    
    /**
     * Reset modals on close
     */
    $('#enterOrderModal, #enterDeliveryModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        // Keep only first item row
        $('.quick-item-row:gt(0)').remove();
        $('.delivery-item-row:gt(0)').remove();
        quickItemIndex = 1;
        deliveryItemIndex = 1;
    });
    
    // Initialize
    initDataTable();
    loadCommitmentCounts();
});
