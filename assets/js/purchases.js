$(document).ready(function() {
    // Set today's date as default for payment date
    $('#payment_date').val(new Date().toISOString().split('T')[0]);
    
    // Initialize DataTable
    let table = $('#invoicesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'controllers/SupplierInvoiceController.php?action=getInvoices',
            type: 'GET',
            data: function(d) {
                d.status = $('#status').val();
                d.invoice_type = $('#invoice_type').val();
                d.period = $('#period').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            { data: 'supplier' },
            { data: 'contact' },
            { data: 'invoice_no' },
            { data: 'invoice_date' },
            { data: 'taxable', className: 'text-end' },
            { data: 'amount', className: 'text-end' },
            { data: 'credit_month' },
            { data: 'status' },
            { data: 'payment_status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']], // Sort by invoice date descending
        pageLength: 25,
        language: {
            emptyTable: "No supplier invoices found"
        }
    });
    
    // Period filter change
    $('#period').change(function() {
        let period = $(this).val();
        
        if (period === 'custom') {
            $('#dateFromGroup, #dateToGroup').show();
        } else {
            $('#dateFromGroup, #dateToGroup').hide();
            
            // Calculate date range based on period
            let today = new Date();
            let dateFrom = null;
            let dateTo = today.toISOString().split('T')[0];
            
            switch(period) {
                case 'today':
                    dateFrom = dateTo;
                    break;
                case 'yesterday':
                    let yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    dateFrom = dateTo = yesterday.toISOString().split('T')[0];
                    break;
                case 'this_week':
                    let weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    dateFrom = weekStart.toISOString().split('T')[0];
                    break;
                case 'this_month':
                    dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    break;
                case 'last_month':
                    let lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    let lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    dateFrom = lastMonth.toISOString().split('T')[0];
                    dateTo = lastMonthEnd.toISOString().split('T')[0];
                    break;
                case 'this_quarter':
                    let quarter = Math.floor(today.getMonth() / 3);
                    dateFrom = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
                    break;
                case 'this_year':
                    dateFrom = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                    break;
            }
            
            $('#date_from').val(dateFrom || '');
            $('#date_to').val(dateTo);
            
            table.ajax.reload();
        }
    });
    
    // Filter changes
    $('#status, #invoice_type, #date_from, #date_to').change(function() {
        table.ajax.reload();
    });
    
    // Reset filters
    $('#resetFilter').click(function() {
        $('#period').val('this_month');
        $('#status').val('');
        $('#invoice_type').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#dateFromGroup, #dateToGroup').hide();
        table.ajax.reload();
    });
    
    // Approve invoice
    $(document).on('click', '.approve-invoice', function() {
        let id = $(this).data('id');
        
        if (confirm('Are you sure you want to approve this invoice?')) {
            $.ajax({
                url: 'controllers/SupplierInvoiceController.php?action=approve&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        table.ajax.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Failed to approve invoice');
                }
            });
        }
    });
    
    // Delete invoice
    $(document).on('click', '.delete-invoice', function() {
        let id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this invoice?')) {
            $.ajax({
                url: 'controllers/SupplierInvoiceController.php?action=delete&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        table.ajax.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Failed to delete invoice');
                }
            });
        }
    });
    
    // Add payment
    let currentInvoiceId = null;
    
    $(document).on('click', '.add-payment', function() {
        currentInvoiceId = $(this).data('id');
        $('#payment_invoice_id').val(currentInvoiceId);
        $('#paymentForm')[0].reset();
        $('#payment_date').val(new Date().toISOString().split('T')[0]);
        $('#paymentModal').modal('show');
    });
    
    $('#savePayment').click(function() {
        if (!$('#paymentForm')[0].checkValidity()) {
            $('#paymentForm')[0].reportValidity();
            return;
        }
        
        let formData = {
            invoice_id: $('#payment_invoice_id').val(),
            payment_date: $('#payment_date').val(),
            amount: $('#payment_amount').val(),
            payment_mode: $('#payment_mode').val(),
            reference_no: $('#reference_no').val(),
            notes: $('#payment_notes').val()
        };
        
        $.ajax({
            url: 'controllers/SupplierInvoiceController.php?action=addPayment',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#paymentModal').modal('hide');
                    table.ajax.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Failed to add payment');
            }
        });
    });
    
    // Trigger default filter (this month)
    $('#period').trigger('change');
});
