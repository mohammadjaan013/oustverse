$(document).ready(function() {
    let itemCounter = 0;
    
    // Initialize Select2 for supplier dropdown
    $('#supplier_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: 'controllers/SupplierInvoiceController.php?action=getSuppliers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.results
                };
            }
        },
        placeholder: 'Select Supplier',
        minimumInputLength: 0
    });
    
    // Generate invoice number on page load
    generateInvoiceNumber();
    
    // Check if page loaded with inter-state transfer type
    let urlParams = new URLSearchParams(window.location.search);
    let isInterStateTransfer = urlParams.get('type') === 'inter_state_transfer';
    
    // Handle invoice type change
    $('input[name="invoice_type"]').change(function() {
        if ($(this).val() === 'inter_state_transfer') {
            // Show modal for inter-state transfer
            $('#interStateModal').modal('show');
            $('#supplierField').hide();
            $('#sourceBranchField').show();
        } else {
            $('#supplierField').show();
            $('#sourceBranchField').hide();
        }
    });
    
    // Cancel inter-state transfer modal
    $('#cancelInterState').click(function() {
        // If this was opened from the "Enter Inter-State Transfer" button, go back
        if (isInterStateTransfer && !$('#invoice_id').val()) {
            window.location.href = 'purchases.php';
        } else {
            // Switch back to supplier invoice
            $('#typeSupplierInvoice').prop('checked', true);
            $('#supplierField').show();
            $('#sourceBranchField').hide();
        }
    });
    
    // Confirm inter-state transfer
    $('#confirmInterState').click(function() {
        let sourceBranch = $('#modal_source_branch').val();
        if (!sourceBranch) {
            alert('Please select a source branch');
            return;
        }
        $('#source_branch_transfer').val(sourceBranch);
        $('#interStateModal').modal('hide');
    });
    
    // Add source address
    $('#addSourceAddress').click(function() {
        $('#addAddressModal').modal('show');
    });
    
    $('#saveAddress').click(function() {
        let address = $('#modal_address').val();
        let city = $('#modal_city').val();
        let state = $('#modal_state').val();
        let pincode = $('#modal_pincode').val();
        
        if (address) {
            let fullAddress = address;
            if (city) fullAddress += ', ' + city;
            if (state) fullAddress += ', ' + state;
            if (pincode) fullAddress += ' - ' + pincode;
            
            $('#sourceAddressDisplay').text(fullAddress).show();
            $('#addSourceAddress').text('Change Address');
            $('#addAddressModal').modal('hide');
        }
    });
    
    // Show/hide terms textarea
    $('#addTermBtn').click(function() {
        $('#terms_conditions').slideToggle();
    });
    
    // Add item row
    $('#addItemBtn').click(function() {
        addItemRow();
    });
    
    // Generate invoice number
    function generateInvoiceNumber() {
        $.ajax({
            url: 'controllers/SupplierInvoiceController.php?action=generateInvoiceNo',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#invoice_no').val(response.invoice_no);
                }
            }
        });
    }
    
    // Add item row
    function addItemRow() {
        itemCounter++;
        
        let row = `
            <tr data-item-id="${itemCounter}">
                <td class="text-center">${itemCounter}</td>
                <td>
                    <select class="form-select form-select-sm item-select" name="items[${itemCounter}][item_id]" data-row="${itemCounter}">
                        <option value="">Select Item</option>
                    </select>
                    <input type="text" class="form-control form-control-sm mt-1" name="items[${itemCounter}][description]" placeholder="Description">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="items[${itemCounter}][hsn_sac]" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-qty" name="items[${itemCounter}][qty]" value="1" min="0" step="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="items[${itemCounter}][unit]" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-rate" name="items[${itemCounter}][rate]" value="0" min="0" step="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-discount" name="items[${itemCounter}][discount_amount]" value="0" min="0" step="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-taxable" name="items[${itemCounter}][taxable_amount]" value="0" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-cgst" name="items[${itemCounter}][cgst_amount]" value="0" readonly>
                    <input type="hidden" name="items[${itemCounter}][cgst_percent]" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-sgst" name="items[${itemCounter}][sgst_amount]" value="0" readonly>
                    <input type="hidden" name="items[${itemCounter}][sgst_percent]" value="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm item-total" name="items[${itemCounter}][total_amount]" value="0" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#itemsTableBody').append(row);
        
        // Initialize Select2 for the new item select
        initializeItemSelect(itemCounter);
    }
    
    // Initialize item select with Select2
    function initializeItemSelect(rowId) {
        $(`select[name="items[${rowId}][item_id]"]`).select2({
            theme: 'bootstrap-5',
            ajax: {
                url: 'controllers/SupplierInvoiceController.php?action=getProducts',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                }
            },
            placeholder: 'Select Item',
            minimumInputLength: 0
        }).on('select2:select', function(e) {
            let data = e.params.data;
            let row = $(this).data('row');
            
            // Populate item details
            $(`input[name="items[${row}][hsn_sac]"]`).val(data.hsn_code || '');
            $(`input[name="items[${row}][unit]"]`).val(data.unit || '');
            $(`input[name="items[${row}][rate]"]`).val(data.price || 0);
            $(`input[name="items[${row}][cgst_percent]"]`).val((data.tax_rate / 2) || 0);
            $(`input[name="items[${row}][sgst_percent]"]`).val((data.tax_rate / 2) || 0);
            
            calculateRowTotal(row);
        });
    }
    
    // Remove item row
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotal();
        renumberItems();
    });
    
    // Renumber items
    function renumberItems() {
        $('#itemsTableBody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    // Calculate row total on input change
    $(document).on('input', '.item-qty, .item-rate, .item-discount', function() {
        let row = $(this).data('row');
        calculateRowTotal(row);
    });
    
    // Calculate row total
    function calculateRowTotal(row) {
        let qty = parseFloat($(`input[name="items[${row}][qty]"]`).val()) || 0;
        let rate = parseFloat($(`input[name="items[${row}][rate]"]`).val()) || 0;
        let discount = parseFloat($(`input[name="items[${row}][discount_amount]"]`).val()) || 0;
        let cgstPercent = parseFloat($(`input[name="items[${row}][cgst_percent]"]`).val()) || 0;
        let sgstPercent = parseFloat($(`input[name="items[${row}][sgst_percent]"]`).val()) || 0;
        
        // Calculate taxable amount
        let taxable = (qty * rate) - discount;
        
        // Calculate tax amounts
        let cgstAmount = (taxable * cgstPercent) / 100;
        let sgstAmount = (taxable * sgstPercent) / 100;
        
        // Calculate total
        let total = taxable + cgstAmount + sgstAmount;
        
        // Update fields
        $(`input[name="items[${row}][taxable_amount]"]`).val(taxable.toFixed(2));
        $(`input[name="items[${row}][cgst_amount]"]`).val(cgstAmount.toFixed(2));
        $(`input[name="items[${row}][sgst_amount]"]`).val(sgstAmount.toFixed(2));
        $(`input[name="items[${row}][total_amount]"]`).val(total.toFixed(2));
        
        calculateTotal();
    }
    
    // Calculate grand total
    function calculateTotal() {
        let subtotal = 0;
        let taxTotal = 0;
        
        $('.item-total').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        $('#subtotal').text(subtotal.toFixed(2));
        $('#grandTotal').text(subtotal.toFixed(2));
    }
    
    // Load supplier details when selected
    $('#supplier_id').on('select2:select', function(e) {
        let data = e.params.data;
        
        // Populate contact person
        $('#contact_person').val(data.contact_name || '');
        
        // Populate source address if available
        if (data.address) {
            let fullAddress = data.address;
            if (data.city) fullAddress += ', ' + data.city;
            if (data.state) fullAddress += ', ' + data.state;
            if (data.pincode) fullAddress += ' - ' + data.pincode;
            
            $('#sourceAddressDisplay').text(fullAddress).show();
            $('#addSourceAddress').text('Change Address');
            
            // Store in modal fields
            $('#modal_address').val(data.address);
            $('#modal_city').val(data.city);
            $('#modal_state').val(data.state);
            $('#modal_pincode').val(data.pincode);
        }
    });
    
    // Save invoice
    function saveInvoice(saveAndEnterAnother = false) {
        // Validate form
        let invoiceType = $('input[name="invoice_type"]:checked').val();
        
        if (invoiceType === 'supplier_invoice' && !$('#supplier_id').val()) {
            alert('Please select a supplier');
            return;
        }
        
        if (invoiceType === 'inter_state_transfer' && !$('#source_branch_transfer').val()) {
            alert('Please select a source branch');
            return;
        }
        
        if ($('#itemsTableBody tr').length === 0) {
            alert('Please add at least one item');
            return;
        }
        
        // Collect form data
        let formData = {
            id: $('#invoice_id').val(),
            invoice_no: $('#invoice_no').val(),
            invoice_type: invoiceType,
            supplier_id: $('#supplier_id').val(),
            invoice_date: $('#invoice_date').val(),
            due_date: $('#due_date').val(),
            reference: $('#reference').val(),
            source_branch: invoiceType === 'inter_state_transfer' ? $('#source_branch_transfer').val() : $('#branch').val(),
            source_address: $('#sourceAddressDisplay').text(),
            notes: $('#notes').val(),
            terms_conditions: $('#terms_conditions').val(),
            subtotal: parseFloat($('#subtotal').text()),
            total_amount: parseFloat($('#grandTotal').text()),
            status: 'draft',
            items: []
        };
        
        // Collect items
        $('#itemsTableBody tr').each(function() {
            let row = $(this);
            let item = {
                item_id: row.find('.item-select').val(),
                description: row.find('input[name*="[description]"]').val(),
                hsn_sac: row.find('input[name*="[hsn_sac]"]').val(),
                qty: row.find('input[name*="[qty]"]').val(),
                unit: row.find('input[name*="[unit]"]').val(),
                rate: row.find('input[name*="[rate]"]').val(),
                discount_amount: row.find('input[name*="[discount_amount]"]').val(),
                taxable_amount: row.find('input[name*="[taxable_amount]"]').val(),
                cgst_percent: row.find('input[name*="[cgst_percent]"]').val(),
                cgst_amount: row.find('input[name*="[cgst_amount]"]').val(),
                sgst_percent: row.find('input[name*="[sgst_percent]"]').val(),
                sgst_amount: row.find('input[name*="[sgst_amount]"]').val(),
                total_amount: row.find('input[name*="[total_amount]"]').val()
            };
            formData.items.push(item);
        });
        
        // Determine action
        let action = formData.id ? 'update' : 'create';
        
        // Send AJAX request
        $.ajax({
            url: 'controllers/SupplierInvoiceController.php?action=' + action,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    if (saveAndEnterAnother) {
                        window.location.reload();
                    } else {
                        window.location.href = 'purchases.php';
                    }
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Failed to save invoice');
            }
        });
    }
    
    // Save button click
    $('#saveBtn, #saveInvoice').click(function() {
        saveInvoice(false);
    });
    
    // Save & Enter Another button click
    $('#saveAndEnterAnotherBtn').click(function() {
        saveInvoice(true);
    });
    
    // Add first item row on load
    addItemRow();
    
    // Load invoice if editing
    let invoiceId = $('#invoice_id').val();
    if (invoiceId) {
        loadInvoice(invoiceId);
    }
    
    // Load invoice data
    function loadInvoice(id) {
        $.ajax({
            url: 'controllers/SupplierInvoiceController.php?action=getInvoice&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let invoice = response.invoice;
                    
                    // Populate form fields
                    $('#invoice_no').val(invoice.invoice_no);
                    $('input[name="invoice_type"][value="' + invoice.invoice_type + '"]').prop('checked', true).trigger('change');
                    $('#invoice_date').val(invoice.invoice_date);
                    $('#due_date').val(invoice.due_date);
                    $('#reference').val(invoice.reference);
                    $('#notes').val(invoice.notes);
                    $('#terms_conditions').val(invoice.terms_conditions);
                    
                    // Load supplier
                    if (invoice.supplier_id) {
                        let option = new Option(invoice.supplier_name, invoice.supplier_id, true, true);
                        $('#supplier_id').append(option).trigger('change');
                    }
                    
                    // Load items
                    $('#itemsTableBody').empty();
                    itemCounter = 0;
                    invoice.items.forEach(function(item) {
                        addItemRow();
                        let row = itemCounter;
                        
                        // Populate item dropdown
                        // For free-text items (item_id is null/0), show description in dropdown
                        // For inventory items, show item name
                        let displayText = item.item_name || item.description || 'Custom Item';
                        let itemId = item.item_id || '';
                        let itemOption = new Option(displayText, itemId, true, true);
                        $(`select[name="items[${row}][item_id]"]`).append(itemOption).trigger('change');
                        
                        // Populate all fields - use textarea for description if it exists, otherwise input
                        let descField = $(`textarea[name="items[${row}][description]"], input[name="items[${row}][description]"]`);
                        descField.val(item.description);
                        
                        $(`input[name="items[${row}][hsn_sac]"]`).val(item.hsn_sac);
                        $(`input[name="items[${row}][qty]"]`).val(item.qty);
                        $(`input[name="items[${row}][unit]"]`).val(item.unit);
                        $(`input[name="items[${row}][rate]"]`).val(item.rate);
                        $(`input[name="items[${row}][discount_amount]"]`).val(item.discount_amount || 0);
                        
                        // Populate tax percentages
                        $(`input[name="items[${row}][cgst_percent]"]`).val(item.cgst_percent || 0);
                        $(`input[name="items[${row}][sgst_percent]"]`).val(item.sgst_percent || 0);
                        $(`input[name="items[${row}][igst_percent]"]`).val(item.igst_percent || 0);
                        
                        // Recalculate tax amounts and totals based on qty, rate, discount, and tax percentages
                        calculateRowTotal(row);
                    });
                    
                    calculateTotal();
                }
            }
        });
    }
});
