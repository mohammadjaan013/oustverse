/**
 * Invoice Form JavaScript
 * Handles invoice creation/editing with item calculations
 */

$(document).ready(function() {
    let itemIndex = $('#itemsTableBody tr').length || 0;
    let termIndex = $('#termsContainer .input-group').length || 0;
    let extraChargesAmount = 0;
    let discountAmount = 0;
    
    /**
     * Add new item row
     */
    $('#addItemBtn').on('click', function() {
        const row = `
            <tr class="item-row" data-index="${itemIndex}">
                <td>${itemIndex + 1}</td>
                <td>
                    <textarea class="form-control item-description" name="items[${itemIndex}][description]" rows="1" required></textarea>
                </td>
                <td>
                    <input type="text" class="form-control" name="items[${itemIndex}][hsn_sac]">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemIndex}][quantity]" value="1" step="0.01" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="items[${itemIndex}][unit]" value="nos">
                </td>
                <td>
                    <input type="number" class="form-control item-rate" name="items[${itemIndex}][rate]" value="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" class="form-control item-discount-amt" name="items[${itemIndex}][discount_amount]" value="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control item-taxable" name="items[${itemIndex}][taxable_amount]" value="0" readonly>
                </td>
                <td>
                    <input type="number" class="form-control item-cgst" name="items[${itemIndex}][cgst_amount]" value="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control item-sgst" name="items[${itemIndex}][sgst_amount]" value="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control item-amount" name="items[${itemIndex}][amount]" value="0" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsTableBody').append(row);
        itemIndex++;
        updateRowNumbers();
        calculateTotals();
    });
    
    /**
     * Remove item row
     */
    $(document).on('click', '.remove-item-btn', function() {
        $(this).closest('tr').remove();
        updateRowNumbers();
        calculateTotals();
    });
    
    /**
     * Update row numbers
     */
    function updateRowNumbers() {
        $('#itemsTableBody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    /**
     * Calculate item amount on input change
     */
    $(document).on('input', '.item-quantity, .item-rate, .item-discount-amt, .item-cgst, .item-sgst', function() {
        const row = $(this).closest('tr');
        calculateItemAmount(row);
        calculateTotals();
    });
    
    /**
     * Calculate single item amount
     */
    function calculateItemAmount(row) {
        const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
        const rate = parseFloat(row.find('.item-rate').val()) || 0;
        const discountAmt = parseFloat(row.find('.item-discount-amt').val()) || 0;
        const cgstAmt = parseFloat(row.find('.item-cgst').val()) || 0;
        const sgstAmt = parseFloat(row.find('.item-sgst').val()) || 0;
        
        // Calculate base amount
        const baseAmount = quantity * rate;
        
        // Taxable amount = Base - Discount
        const taxableAmount = baseAmount - discountAmt;
        
        // Total amount = Taxable + CGST + SGST
        const totalAmount = taxableAmount + cgstAmt + sgstAmt;
        
        // Update fields
        row.find('.item-taxable').val(taxableAmount.toFixed(2));
        row.find('.item-amount').val(totalAmount.toFixed(2));
    }
    
    /**
     * Calculate all totals
     */
    function calculateTotals() {
        let subtotal = 0;
        let totalTaxable = 0;
        let totalTax = 0;
        let totalDiscount = 0;
        
        $('#itemsTableBody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const rate = parseFloat($(this).find('.item-rate').val()) || 0;
            const discountAmt = parseFloat($(this).find('.item-discount-amt').val()) || 0;
            const cgstAmt = parseFloat($(this).find('.item-cgst').val()) || 0;
            const sgstAmt = parseFloat($(this).find('.item-sgst').val()) || 0;
            const amount = parseFloat($(this).find('.item-amount').val()) || 0;
            
            subtotal += amount;
            totalTaxable += (quantity * rate - discountAmt);
            totalTax += (cgstAmt + sgstAmt);
            totalDiscount += discountAmt;
        });
        
        const grandTotal = subtotal + extraChargesAmount - discountAmount;
        
        // Update display
        $('#displayTotal').text('₹ ' + subtotal.toFixed(2));
        $('#displayGrandTotal').text('₹ ' + grandTotal.toFixed(2));
        
        // Update hidden inputs
        $('#subtotal').val(subtotal.toFixed(2));
        $('#totalAmount').val(grandTotal.toFixed(2));
        $('#taxableAmount').val(totalTaxable.toFixed(2));
        $('#taxAmount').val(totalTax.toFixed(2));
        $('#discountAmount').val(totalDiscount.toFixed(2));
        $('#extraCharges').val(extraChargesAmount.toFixed(2));
    }
    
    /**
     * Same as billing checkbox
     */
    $('#sameAsBilling').on('change', function() {
        if ($(this).is(':checked')) {
            const billingAddress = $('textarea[name="billing_address"]').val();
            $('textarea[name="shipping_address"]').val(billingAddress);
        }
    });
    
    /**
     * Add term
     */
    $('#addTermBtn').on('click', function() {
        termIndex++;
        const termHtml = `
            <div class="input-group mb-2">
                <span class="input-group-text">${termIndex}.</span>
                <input type="text" class="form-control" name="terms[]" placeholder="Enter term or condition">
                <button type="button" class="btn btn-danger remove-term-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        $('#termsContainer').append(termHtml);
        updateTermNumbers();
    });
    
    /**
     * Remove term
     */
    $(document).on('click', '.remove-term-btn', function() {
        $(this).closest('.input-group').remove();
        updateTermNumbers();
    });
    
    /**
     * Update term numbers
     */
    function updateTermNumbers() {
        $('#termsContainer .input-group').each(function(index) {
            $(this).find('.input-group-text').text((index + 1) + '.');
        });
        termIndex = $('#termsContainer .input-group').length;
    }
    
    /**
     * Add extra charge
     */
    $('#addExtraChargeBtn').on('click', function() {
        const amount = prompt('Enter extra charge amount:');
        if (amount && !isNaN(amount)) {
            extraChargesAmount = parseFloat(amount);
            calculateTotals();
        }
    });
    
    /**
     * Add discount
     */
    $('#addDiscountBtn').on('click', function() {
        const amount = prompt('Enter discount amount:');
        if (amount && !isNaN(amount)) {
            discountAmount = parseFloat(amount);
            calculateTotals();
        }
    });
    
    /**
     * Save invoice
     */
    $('#saveBtn, #saveInvoiceBtn').on('click', function() {
        submitInvoiceForm(false);
    });
    
    /**
     * Save and enter another
     */
    $('#saveAndEnterAnotherBtn').on('click', function() {
        submitInvoiceForm(true);
    });
    
    /**
     * Submit invoice form
     */
    function submitInvoiceForm(enterAnother) {
        // Validate form
        if (!$('#invoiceForm')[0].checkValidity()) {
            $('#invoiceForm')[0].reportValidity();
            return;
        }
        
        // Check if at least one item exists
        if ($('#itemsTableBody tr').length === 0) {
            showToast('Error', 'Please add at least one item', 'error');
            return;
        }
        
        const formData = $('#invoiceForm').serialize();
        const invoiceId = $('input[name="invoice_id"]').val();
        const action = invoiceId ? 'updateInvoice' : 'createInvoice';
        
        $.ajax({
            url: 'controllers/InvoiceController.php',
            method: 'POST',
            data: formData + '&action=' + action,
            dataType: 'json',
            beforeSend: function() {
                $('#saveBtn, #saveInvoiceBtn, #saveAndEnterAnotherBtn').prop('disabled', true);
                $('#saveBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showToast('Success', response.message, 'success');
                    
                    if (enterAnother) {
                        setTimeout(function() {
                            window.location.href = 'invoice_form.php';
                        }, 1000);
                    } else {
                        setTimeout(function() {
                            window.location.href = 'invoices.php';
                        }, 1500);
                    }
                } else {
                    showToast('Error', response.message, 'error');
                    $('#saveBtn, #saveInvoiceBtn, #saveAndEnterAnotherBtn').prop('disabled', false);
                    $('#saveBtn').html('<i class="fas fa-check"></i> Save');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showToast('Error', 'An error occurred while saving the invoice', 'error');
                $('#saveBtn, #saveInvoiceBtn, #saveAndEnterAnotherBtn').prop('disabled', false);
                $('#saveBtn').html('<i class="fas fa-check"></i> Save');
            }
        });
    }
    
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
    
    // Initialize calculations on page load
    $('#itemsTableBody tr').each(function() {
        calculateItemAmount($(this));
    });
    calculateTotals();
});
