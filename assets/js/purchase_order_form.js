/**
 * Purchase Order Form - Frontend JavaScript
 */

let itemCounter = 1;

/**
 * Generate PO Number
 */
function generatePoNumber() {
    $.ajax({
        url: 'purchase_orders.php?action=generate_po_number',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#poNo').val(response.data.po_number);
            }
        }
    });
}

/**
 * Load supplier details when supplier is selected
 */
function loadSupplierDetails() {
    const supplierId = $('#supplierId').val();
    if (!supplierId) return;
    
    $.ajax({
        url: 'suppliers.php?action=get_supplier',
        method: 'GET',
        data: { id: supplierId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const supplier = response.data;
                $('#contactPerson').val(supplier.contact_name || '');
                
                // Build address
                let address = '';
                if (supplier.address) address += supplier.address + '\n';
                if (supplier.city) address += supplier.city + ', ';
                if (supplier.state) address += supplier.state + ' ';
                if (supplier.pincode) address += supplier.pincode;
                
                $('#sourceAddress').val(address.trim());
            }
        }
    });
}

/**
 * Load product details when product is selected
 */
function loadProductDetails(selectElement, rowIndex) {
    const selectedOption = $(selectElement).find(':selected');
    const row = $(selectElement).closest('tr');
    
    // Set HSN/SAC
    row.find('input[name="items[' + rowIndex + '][hsn]"]').val(selectedOption.data('hsn') || '');
    
    // Set Unit
    row.find('input[name="items[' + rowIndex + '][unit]"]').val(selectedOption.data('unit') || 'PCS');
    
    // Set Unit Price
    const price = parseFloat(selectedOption.data('price')) || 0;
    row.find('input[name="items[' + rowIndex + '][unit_price]"]').val(price.toFixed(2));
    
    // Calculate row
    calculateRow(rowIndex);
}

/**
 * Calculate row totals
 */
function calculateRow(rowIndex) {
    const row = $('tr[data-row="' + (rowIndex + 1) + '"]');
    
    const quantity = parseFloat(row.find('input[name="items[' + rowIndex + '][quantity]"]').val()) || 0;
    const unitPrice = parseFloat(row.find('input[name="items[' + rowIndex + '][unit_price]"]').val()) || 0;
    const discountAmount = parseFloat(row.find('input[name="items[' + rowIndex + '][discount_amount]"]').val()) || 0;
    const cgst = parseFloat(row.find('input[name="items[' + rowIndex + '][cgst]"]').val()) || 0;
    const sgst = parseFloat(row.find('input[name="items[' + rowIndex + '][sgst]"]').val()) || 0;
    
    // Calculate taxable amount
    const taxable = (quantity * unitPrice) - discountAmount;
    row.find('input[name="items[' + rowIndex + '][taxable]"]').val(taxable.toFixed(2));
    
    // Calculate total
    const total = taxable + cgst + sgst;
    row.find('input[name="items[' + rowIndex + '][total_amount]"]').val(total.toFixed(2));
    
    // Recalculate grand total
    calculateTotal();
}

/**
 * Calculate grand total
 */
function calculateTotal() {
    let total = 0;
    
    $('input[name$="[total_amount]"]').each(function() {
        total += parseFloat($(this).val()) || 0;
    });
    
    $('#summaryTotal').text('₹ ' + total.toFixed(2));
    $('#summaryGrandTotal').text('₹ ' + total.toFixed(2));
}

/**
 * Add new item row
 */
function addItem() {
    const rowNumber = itemCounter++;
    const newRow = `
        <tr class="item-row" data-row="${rowNumber}">
            <td>${rowNumber}</td>
            <td>
                <select class="form-select form-select-sm product-select" name="items[${rowNumber - 1}][product_id]" onchange="loadProductDetails(this, ${rowNumber - 1})">
                    <option value="">Select Product</option>
                    <?php foreach ($GLOBALS['products'] as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                                data-hsn="<?php echo htmlspecialchars($product['hsn_code'] ?? ''); ?>"
                                data-unit="<?php echo htmlspecialchars($product['unit'] ?? 'PCS'); ?>"
                                data-price="<?php echo $product['purchase_price'] ?? 0; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="form-control form-control-sm mt-1" name="items[${rowNumber - 1}][description]" placeholder="Description">
            </td>
            <td><input type="text" class="form-control form-control-sm" name="items[${rowNumber - 1}][hsn]" readonly></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][quantity]" value="1" step="0.01" onchange="calculateRow(${rowNumber - 1})"></td>
            <td><input type="text" class="form-control form-control-sm" name="items[${rowNumber - 1}][unit]" value="PCS" readonly></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][unit_price]" value="0" step="0.01" onchange="calculateRow(${rowNumber - 1})"></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][discount_amount]" value="0" step="0.01" onchange="calculateRow(${rowNumber - 1})"></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][taxable]" value="0" step="0.01" readonly></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][cgst]" value="0" step="0.01" onchange="calculateRow(${rowNumber - 1})"></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][sgst]" value="0" step="0.01" onchange="calculateRow(${rowNumber - 1})"></td>
            <td><input type="number" class="form-control form-control-sm" name="items[${rowNumber - 1}][total_amount]" value="0" step="0.01" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    
    $('#itemsBody').append(newRow);
    
    // Re-populate product dropdown for new row
    loadProductsForRow(rowNumber - 1);
}

/**
 * Load products for a specific row
 */
function loadProductsForRow(rowIndex) {
    $.ajax({
        url: 'purchase_orders.php?action=get_products',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('select[name="items[' + rowIndex + '][product_id]"]');
                select.empty();
                select.append('<option value="">Select Product</option>');
                
                response.data.forEach(function(product) {
                    select.append(`
                        <option value="${product.id}" 
                                data-hsn="${product.hsn_code || ''}"
                                data-unit="${product.unit || 'PCS'}"
                                data-price="${product.purchase_price || 0}">
                            ${product.name}
                        </option>
                    `);
                });
            }
        }
    });
}

/**
 * Remove item row
 */
function removeItem(button) {
    if ($('#itemsBody tr').length <= 1) {
        alert('At least one item is required');
        return;
    }
    
    $(button).closest('tr').remove();
    
    // Renumber rows
    let rowNum = 1;
    $('#itemsBody tr').each(function() {
        $(this).attr('data-row', rowNum);
        $(this).find('td:first').text(rowNum);
        rowNum++;
    });
    
    calculateTotal();
}

/**
 * Save purchase order
 */
function savePurchaseOrder(saveAndNew = false) {
    // Validate required fields
    if (!$('#supplierId').val()) {
        alert('Please select a supplier');
        $('#supplierId').focus();
        return;
    }
    
    if (!$('#poDate').val()) {
        alert('Please enter PO date');
        $('#poDate').focus();
        return;
    }
    
    // Check if at least one item exists
    if ($('#itemsBody tr').length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    // Prepare form data
    const formData = $('#poForm').serialize();
    const action = $('#poId').val() ? 'update' : 'create';
    
    // Calculate totals
    let taxableAmount = 0;
    let taxAmount = 0;
    let totalAmount = 0;
    
    $('input[name$="[taxable]"]').each(function() {
        taxableAmount += parseFloat($(this).val()) || 0;
    });
    
    $('input[name$="[cgst]"], input[name$="[sgst]"]').each(function() {
        taxAmount += parseFloat($(this).val()) || 0;
    });
    
    $('input[name$="[total_amount]"]').each(function() {
        totalAmount += parseFloat($(this).val()) || 0;
    });
    
    // Add totals to form data
    const finalData = formData + 
                     '&taxable_amount=' + taxableAmount.toFixed(2) +
                     '&tax_amount=' + taxAmount.toFixed(2) +
                     '&total_amount=' + totalAmount.toFixed(2) +
                     '&status=draft';
    
    // Show loading
    $('button[onclick="savePurchaseOrder()"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: 'purchase_orders.php?action=' + action,
        method: 'POST',
        data: finalData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Purchase order saved successfully!');
                
                if (saveAndNew) {
                    window.location.reload();
                } else {
                    window.location.href = 'purchase_orders.php';
                }
            } else {
                alert('Error: ' + response.message);
                $('button[onclick="savePurchaseOrder()"]').prop('disabled', false).html('<i class="fas fa-check"></i> Save');
            }
        },
        error: function(xhr, status, error) {
            alert('Error saving purchase order: ' + error);
            $('button[onclick="savePurchaseOrder()"]').prop('disabled', false).html('<i class="fas fa-check"></i> Save');
        }
    });
}

/**
 * Copy from existing PO
 */
function copyFromPO() {
    const poId = $('#copyFrom').val();
    if (!poId) return;
    
    $.ajax({
        url: 'purchase_orders.php?action=get_po',
        method: 'GET',
        data: { id: poId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const po = response.data;
                
                // Fill basic details
                $('#supplierId').val(po.supplier_id).trigger('change');
                $('#contactPerson').val(po.contact_name || '');
                $('#sourceAddress').val(po.address || '');
                $('#termsConditions').val(po.notes || '');
                
                // Clear existing items
                $('#itemsBody').empty();
                itemCounter = 1;
                
                // Add items
                if (po.items && po.items.length > 0) {
                    po.items.forEach(function(item, index) {
                        addItem();
                        const row = $('tr[data-row="' + (index + 1) + '"]');
                        
                        row.find('select[name*="[product_id]"]').val(item.product_id);
                        row.find('input[name*="[description]"]').val(item.description || '');
                        row.find('input[name*="[hsn]"]').val(item.sku || '');
                        row.find('input[name*="[quantity]"]').val(item.quantity);
                        row.find('input[name*="[unit]"]').val(item.unit || 'PCS');
                        row.find('input[name*="[unit_price]"]').val(item.unit_price);
                        row.find('input[name*="[discount_amount]"]').val(item.discount_amount || 0);
                        
                        calculateRow(index);
                    });
                }
                
                alert('PO copied successfully. Please update PO number and date.');
            }
        }
    });
}

/**
 * Placeholder functions for buttons
 */
function searchSupplier() {
    alert('Supplier search coming soon!');
}

function addNewSupplier() {
    window.location.href = 'suppliers.php';
}

function addAddress() {
    alert('Address management coming soon!');
}

function addTermCondition() {
    const currentTerms = $('#termsConditions').val();
    const newTerm = prompt('Enter new term/condition:');
    if (newTerm) {
        $('#termsConditions').val(currentTerms + (currentTerms ? '\n' : '') + '• ' + newTerm);
    }
}

function addExtraCharge() {
    const amount = parseFloat(prompt('Enter extra charge amount:'));
    if (!isNaN(amount) && amount > 0) {
        const currentTotal = parseFloat($('#summaryGrandTotal').text().replace('₹ ', '').replace(',', ''));
        $('#summaryGrandTotal').text('₹ ' + (currentTotal + amount).toFixed(2));
    }
}

function addDiscount() {
    const amount = parseFloat(prompt('Enter discount amount:'));
    if (!isNaN(amount) && amount > 0) {
        const currentTotal = parseFloat($('#summaryGrandTotal').text().replace('₹ ', '').replace(',', ''));
        $('#summaryGrandTotal').text('₹ ' + Math.max(0, currentTotal - amount).toFixed(2));
    }
}

// Initialize on page load
$(document).ready(function() {
    // Count existing rows
    itemCounter = $('#itemsBody tr').length + 1;
});
