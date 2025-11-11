/**
 * Order Form JavaScript
 * Handles order creation/editing with item calculations
 */

$(document).ready(function() {
    let itemIndex = $('#itemsTableBody tr').length || 0;
    let termIndex = $('#termsContainer .input-group').length || 0;
    
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
                    <input type="number" class="form-control item-discount" name="items[${itemIndex}][discount_percent]" value="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control item-cgst" name="items[${itemIndex}][cgst_percent]" value="0" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control item-sgst" name="items[${itemIndex}][sgst_percent]" value="0" step="0.01">
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
    $(document).on('input', '.item-quantity, .item-rate, .item-discount, .item-cgst, .item-sgst', function() {
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
        const discountPercent = parseFloat(row.find('.item-discount').val()) || 0;
        const cgstPercent = parseFloat(row.find('.item-cgst').val()) || 0;
        const sgstPercent = parseFloat(row.find('.item-sgst').val()) || 0;
        
        // Calculate base amount
        let amount = quantity * rate;
        
        // Apply discount
        const discountAmount = (amount * discountPercent) / 100;
        amount -= discountAmount;
        
        // Apply CGST
        const cgstAmount = (amount * cgstPercent) / 100;
        amount += cgstAmount;
        
        // Apply SGST
        const sgstAmount = (amount * sgstPercent) / 100;
        amount += sgstAmount;
        
        // Update item amount
        row.find('.item-amount').val(amount.toFixed(2));
    }
    
    /**
     * Calculate all totals
     */
    function calculateTotals() {
        let subtotal = 0;
        let totalDiscount = 0;
        let totalTax = 0;
        
        $('#itemsTableBody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const rate = parseFloat($(this).find('.item-rate').val()) || 0;
            const discountPercent = parseFloat($(this).find('.item-discount').val()) || 0;
            const cgstPercent = parseFloat($(this).find('.item-cgst').val()) || 0;
            const sgstPercent = parseFloat($(this).find('.item-sgst').val()) || 0;
            
            // Calculate base amount
            const baseAmount = quantity * rate;
            subtotal += baseAmount;
            
            // Calculate discount
            const discountAmount = (baseAmount * discountPercent) / 100;
            totalDiscount += discountAmount;
            
            // Calculate tax on discounted amount
            const amountAfterDiscount = baseAmount - discountAmount;
            const cgstAmount = (amountAfterDiscount * cgstPercent) / 100;
            const sgstAmount = (amountAfterDiscount * sgstPercent) / 100;
            totalTax += (cgstAmount + sgstAmount);
        });
        
        const extraCharges = parseFloat($('#extraCharges').val()) || 0;
        const totalAmount = subtotal - totalDiscount + totalTax + extraCharges;
        
        // Update display
        $('#displaySubtotal').text('₹' + subtotal.toFixed(2));
        $('#displayDiscount').text('₹' + totalDiscount.toFixed(2));
        $('#displayTax').text('₹' + totalTax.toFixed(2));
        $('#displayTotal').text('₹' + totalAmount.toFixed(2));
        
        // Update hidden inputs
        $('#subtotal').val(subtotal.toFixed(2));
        $('#discountAmount').val(totalDiscount.toFixed(2));
        $('#taxAmount').val(totalTax.toFixed(2));
        $('#totalAmount').val(totalAmount.toFixed(2));
    }
    
    /**
     * Extra charges change
     */
    $('#extraCharges').on('input', function() {
        calculateTotals();
    });
    
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
     * Save order
     */
    $('#saveOrderBtn').on('click', function() {
        $('#printAfterSaving').val('0');
        submitOrderForm();
    });
    
    /**
     * Save and print order
     */
    $('#saveAndPrintBtn').on('click', function() {
        $('#printAfterSaving').val('1');
        submitOrderForm();
    });
    
    /**
     * Submit order form
     */
    function submitOrderForm() {
        // Validate form
        if (!$('#orderForm')[0].checkValidity()) {
            $('#orderForm')[0].reportValidity();
            return;
        }
        
        // Check if at least one item exists
        if ($('#itemsTableBody tr').length === 0) {
            showToast('Error', 'Please add at least one item', 'error');
            return;
        }
        
        const formData = $('#orderForm').serialize();
        const orderId = $('input[name="order_id"]').val();
        const action = orderId ? 'updateOrder' : 'createOrder';
        
        $.ajax({
            url: 'controllers/OrderController.php',
            method: 'POST',
            data: formData + '&action=' + action,
            dataType: 'json',
            beforeSend: function() {
                $('#saveOrderBtn, #saveAndPrintBtn').prop('disabled', true);
                $('#saveOrderBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showToast('Success', response.message, 'success');
                    
                    // Check if print after saving
                    if ($('#printAfterSaving').val() === '1') {
                        setTimeout(function() {
                            window.location.href = 'order_form.php?id=' + response.data.id + '&mode=view';
                            setTimeout(function() {
                                window.print();
                            }, 500);
                        }, 1000);
                    } else {
                        setTimeout(function() {
                            window.location.href = 'orders.php';
                        }, 1500);
                    }
                } else {
                    showToast('Error', response.message, 'error');
                    $('#saveOrderBtn, #saveAndPrintBtn').prop('disabled', false);
                    $('#saveOrderBtn').html('<i class="fas fa-save"></i> Save Order');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showToast('Error', 'An error occurred while saving the order', 'error');
                $('#saveOrderBtn, #saveAndPrintBtn').prop('disabled', false);
                $('#saveOrderBtn').html('<i class="fas fa-save"></i> Save Order');
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
