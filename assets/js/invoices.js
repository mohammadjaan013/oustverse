/**
 * Invoices JavaScript
 * Handles invoices list and filtering
 */

$(document).ready(function() {
    let invoicesTable;
    
    /**
     * Initialize DataTable
     */
    function initDataTable() {
        if (invoicesTable) {
            invoicesTable.destroy();
        }
        
        invoicesTable = $('#invoicesTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'controllers/InvoiceController.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'getInvoicesJson';
                    d.type = $('#filterType').val();
                    d.status = $('#filterStatus').val();
                    d.month = $('#filterMonth').val();
                    d.executive = $('#filterExecutive').val();
                }
            },
            columns: [
                { data: 'customer' },
                { data: 'invoice_no' },
                { data: 'invoice_date' },
                { data: 'taxable', className: 'text-end' },
                { data: 'amount', className: 'text-end' },
                { data: 'status' },
                { data: 'pending', className: 'text-end' },
                { data: 'actions', orderable: false }
            ],
            order: [[2, 'desc']], // Sort by invoice date
            pageLength: 25,
            language: {
                emptyTable: "No invoices found"
            }
        });
    }
    
    /**
     * Load summary totals
     */
    function loadSummaryTotals() {
        $.ajax({
            url: 'controllers/InvoiceController.php',
            method: 'POST',
            data: {
                action: 'getSummaryTotals',
                type: $('#filterType').val(),
                status: $('#filterStatus').val(),
                month: $('#filterMonth').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#summaryCount').text(response.data.count || 0);
                    $('#summaryPreTax').text('₹ ' + parseFloat(response.data.pre_tax_total || 0).toFixed(2));
                    $('#summaryTotal').text('₹ ' + parseFloat(response.data.total || 0).toFixed(2));
                    $('#summaryPending').text('₹ ' + parseFloat(response.data.pending || 0).toFixed(2));
                }
            }
        });
    }
    
    /**
     * Filter changes
     */
    $('#filterMonth, #filterType, #filterStatus, #filterExecutive').on('change', function() {
        invoicesTable.ajax.reload();
        loadSummaryTotals();
    });
    
    /**
     * Refresh button
     */
    $('#refreshBtn').on('click', function() {
        invoicesTable.ajax.reload();
        loadSummaryTotals();
        showToast('Success', 'Data refreshed', 'success');
    });
    
    /**
     * Edit invoice
     */
    $(document).on('click', '.btn-edit', function() {
        const invoiceId = $(this).data('id');
        window.location.href = `invoice_form.php?id=${invoiceId}`;
    });
    
    /**
     * Print invoice
     */
    $(document).on('click', '.btn-print', function() {
        const invoiceId = $(this).data('id');
        window.open(`invoice_form.php?id=${invoiceId}&mode=view`, '_blank');
    });
    
    /**
     * Star/Unstar invoice
     */
    $(document).on('click', '.btn-star', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        if ($icon.hasClass('far')) {
            $icon.removeClass('far').addClass('fas').css('color', '#ffc107');
            showToast('Success', 'Invoice starred', 'success');
        } else {
            $icon.removeClass('fas').addClass('far').css('color', '');
            showToast('Success', 'Invoice unstarred', 'success');
        }
    });
    
    /**
     * Search box
     */
    let searchTimeout;
    $('#searchBox').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            invoicesTable.search($('#searchBox').val()).draw();
        }, 500);
    });
    
    /**
     * Select date checkbox
     */
    $('#selectDate').on('change', function() {
        if ($(this).is(':checked')) {
            // Show date range picker (can be implemented with date picker library)
            alert('Date range picker would open here');
        }
    });
    
    /**
     * Show toast notification
     */
    function showToast(title, message, type) {
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
    
    // Initialize
    initDataTable();
    loadSummaryTotals();
});
