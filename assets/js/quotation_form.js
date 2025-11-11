/**
 * Quotation Form Management
 */

let itemRowCount = 0;

$(document).ready(function() {
    // Initialize
    initializeForm();
    
    // Back button
    $('#btnBack').on('click', function() {
        window.location.href = 'quotations.php';
    });
    
    // Add Item button
    $('#btnAddItem').on('click', function() {
        addItemRow();
    });
    
    // Remove item button (delegated)
    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
        recalculateTotals();
        renumberItems();
    });
    
    // Item calculations (delegated)
    $(document).on('input', '.item-qty, .item-rate, .item-discount, .item-cgst, .item-sgst', function() {
        const row = $(this).closest('tr');
        calculateItemRow(row);
        recalculateTotals();
    });
    
    // Add Term button
    $('#btnAddTerm').on('click', function() {
        addTermRow();
    });
    
    // Remove term button (delegated)
    $(document).on('click', '.btn-remove-term', function() {
        $(this).closest('.term-row').remove();
    });
    
    // Same as billing address checkbox
    $('#sameAsBilling').on('change', function() {
        if ($(this).is(':checked')) {
            $('#shippingAddress').val($('#address').val());
        }
    });
    
    // Copy address when billing address changes
    $('#address').on('input', function() {
        if ($('#sameAsBilling').is(':checked')) {
            $('#shippingAddress').val($(this).val());
        }
    });
    
    // Save quotation
    $('#btnSave, #btnSaveQuotation').on('click', function() {
        saveQuotation(false);
    });
    
    // Save and enter another
    $('#btnSaveAndEnterAnother').on('click', function() {
        saveQuotation(true);
    });
    
    // Placeholder buttons
    $('#btnPrintSettings, #btnSearchCustomer, #btnAddCustomer, #btnAddAddress, #btnAddExtraCharge, #btnAddDiscount').on('click', function() {
        showNotification('Coming Soon', 'This feature will be available soon!', 'info');
    });
    
    // Initialize calculations if editing
    if (window.QUOTATION_ID > 0) {
        $('#itemsTableBody tr').each(function() {
            calculateItemRow($(this));
        });
        recalculateTotals();
    }
});

/**
 * Initialize form
 */
function initializeForm() {
    // If no items exist, add first row
    if ($('#itemsTableBody tr').length === 0) {
        addItemRow();
    }
    
    itemRowCount = $('#itemsTableBody tr').length;
}

/**
 * Add item row
 */
function addItemRow() {
    itemRowCount++;
    const rowHtml = `
        <tr data-row="${itemRowCount}">
            <td>${itemRowCount}</td>
            <td><input type="file" class="form-control form-control-sm" name="items[${itemRowCount - 1}][image]"></td>
            <td><textarea class="form-control form-control-sm" name="items[${itemRowCount - 1}][item_description]" rows="2" placeholder="Item description"></textarea></td>
            <td><input type="text" class="form-control form-control-sm" name="items[${itemRowCount - 1}][hsn_sac]"></td>
            <td><input type="number" class="form-control form-control-sm item-qty" name="items[${itemRowCount - 1}][quantity]" value="1" step="0.01"></td>
            <td>
                <select class="form-select form-select-sm" name="items[${itemRowCount - 1}][unit]">
                    <option value="Nos">Nos</option>
                    <option value="Kg">Kg</option>
                    <option value="Ltr">Ltr</option>
                    <option value="Mtr">Mtr</option>
                    <option value="Sqft">Sqft</option>
                    <option value="Box">Box</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm item-rate" name="items[${itemRowCount - 1}][rate]" value="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm item-discount" name="items[${itemRowCount - 1}][discount_percent]" value="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm item-taxable" name="items[${itemRowCount - 1}][taxable_amount]" value="0" readonly></td>
            <td><input type="number" class="form-control form-control-sm item-cgst" name="items[${itemRowCount - 1}][cgst_percent]" value="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm item-sgst" name="items[${itemRowCount - 1}][sgst_percent]" value="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-sm item-amount" name="items[${itemRowCount - 1}][amount]" value="0" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="items[${itemRowCount - 1}][lead_time]"></td>
            <td><button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    
    $('#itemsTableBody').append(rowHtml);
}

/**
 * Renumber items after deletion
 */
function renumberItems() {
    $('#itemsTableBody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
        $(this).attr('data-row', index + 1);
        
        // Update field names
        $(this).find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            if (name) {
                const newName = name.replace(/items\[\d+\]/, `items[${index}]`);
                $(this).attr('name', newName);
            }
        });
    });
    
    itemRowCount = $('#itemsTableBody tr').length;
}

/**
 * Calculate item row totals
 */
function calculateItemRow(row) {
    const qty = parseFloat(row.find('.item-qty').val()) || 0;
    const rate = parseFloat(row.find('.item-rate').val()) || 0;
    const discountPercent = parseFloat(row.find('.item-discount').val()) || 0;
    const cgstPercent = parseFloat(row.find('.item-cgst').val()) || 0;
    const sgstPercent = parseFloat(row.find('.item-sgst').val()) || 0;
    
    // Calculate base amount
    const baseAmount = qty * rate;
    
    // Calculate discount amount
    const discountAmount = (baseAmount * discountPercent) / 100;
    
    // Taxable amount (after discount)
    const taxableAmount = baseAmount - discountAmount;
    
    // Calculate tax amounts
    const cgstAmount = (taxableAmount * cgstPercent) / 100;
    const sgstAmount = (taxableAmount * sgstPercent) / 100;
    
    // Final amount
    const finalAmount = taxableAmount + cgstAmount + sgstAmount;
    
    // Update fields
    row.find('.item-taxable').val(taxableAmount.toFixed(2));
    row.find('.item-amount').val(finalAmount.toFixed(2));
    
    // Store hidden values for submission
    row.find('input[name*="[discount_amount]"]').val(discountAmount.toFixed(2));
    row.find('input[name*="[cgst_amount]"]').val(cgstAmount.toFixed(2));
    row.find('input[name*="[sgst_amount]"]').val(sgstAmount.toFixed(2));
}

/**
 * Recalculate grand totals
 */
function recalculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    let totalDiscount = 0;
    
    $('#itemsTableBody tr').each(function() {
        const amount = parseFloat($(this).find('.item-amount').val()) || 0;
        const taxableAmount = parseFloat($(this).find('.item-taxable').val()) || 0;
        const cgstPercent = parseFloat($(this).find('.item-cgst').val()) || 0;
        const sgstPercent = parseFloat($(this).find('.item-sgst').val()) || 0;
        const discountPercent = parseFloat($(this).find('.item-discount').val()) || 0;
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const rate = parseFloat($(this).find('.item-rate').val()) || 0;
        
        const baseAmount = qty * rate;
        const itemDiscount = (baseAmount * discountPercent) / 100;
        const itemTax = (taxableAmount * (cgstPercent + sgstPercent)) / 100;
        
        subtotal += taxableAmount;
        totalTax += itemTax;
        totalDiscount += itemDiscount;
    });
    
    const extraCharges = 0; // Placeholder for extra charges feature
    const grandTotal = subtotal + totalTax + extraCharges;
    
    // Update display
    $('#displayTotal').text(subtotal.toFixed(2));
    $('#displayGrandTotal').text(grandTotal.toFixed(2));
    
    // Update hidden fields
    $('#hiddenSubtotal').val(subtotal.toFixed(2));
    $('#hiddenTaxAmount').val(totalTax.toFixed(2));
    $('#hiddenDiscountAmount').val(totalDiscount.toFixed(2));
    $('#hiddenExtraCharges').val(extraCharges.toFixed(2));
    $('#hiddenTotalAmount').val(grandTotal.toFixed(2));
}

/**
 * Add term/condition row
 */
function addTermRow() {
    const termHtml = `
        <div class="input-group mb-2 term-row">
            <textarea class="form-control" name="terms[]" rows="2" placeholder="Enter term or condition"></textarea>
            <button type="button" class="btn btn-danger btn-remove-term"><i class="fas fa-times"></i></button>
        </div>
    `;
    
    $('#termsContainer').append(termHtml);
}

/**
 * Save quotation
 */
function saveQuotation(enterAnother = false) {
    // Validation
    if (!$('#customerName').val()) {
        showNotification('Error', 'Please enter customer name', 'error');
        return;
    }
    
    if ($('#itemsTableBody tr').length === 0) {
        showNotification('Error', 'Please add at least one item', 'error');
        return;
    }
    
    const formData = new FormData($('#quotationForm')[0]);
    const quotationId = window.QUOTATION_ID;
    const action = quotationId > 0 ? 'update' : 'create';
    const url = `quotation_form.php?action=${action}`;
    
    // Show loading
    $('#btnSave, #btnSaveQuotation, #btnSaveAndEnterAnother').prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
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
                
                if (enterAnother) {
                    // Reset form for new entry
                    setTimeout(function() {
                        window.location.href = 'quotation_form.php';
                    }, 1000);
                } else {
                    // Redirect to list
                    setTimeout(function() {
                        window.location.href = 'quotations.php';
                    }, 1000);
                }
            } else {
                showNotification('Error', response.message, 'error');
                resetSaveButtons();
            }
        },
        error: function(xhr, status, error) {
            console.error('Save Error:', error);
            showNotification('Error', 'Failed to save quotation. Please try again.', 'error');
            resetSaveButtons();
        }
    });
}

/**
 * Reset save buttons
 */
function resetSaveButtons() {
    $('#btnSave, #btnSaveQuotation').prop('disabled', false).html('<i class="fas fa-check"></i> Save');
    $('#btnSaveAndEnterAnother').prop('disabled', false).html('<i class="fas fa-plus"></i> Save & Enter Another');
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
