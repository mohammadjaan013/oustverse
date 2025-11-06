/**
 * Inventory Module JavaScript
 */

(function($) {
    'use strict';

    let currentAction = '';
    let selectedLocationId = null;
    
    $(document).ready(function() {
        
        // Initialize DataTable
        const table = $('#inventoryTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search items..."
            }
        });

        // Filter by type tabs
        $('#inventoryTypeTabs button').on('click', function() {
            const type = $(this).data('type');
            // Here you can filter by type if you implement type field in database
            // table.column(3).search(type).draw();
        });

        // Filter handlers
        $('#filterLocation, #filterCategory, #filterSubCategory, #filterStock, #filterImportance').on('change', function() {
            applyFilters();
        });

        $('#filterTag').on('keyup', debounce(function() {
            table.search($(this).val()).draw();
        }, 300));

        // Add Item button
        $('#btnAddItem').on('click', function() {
            resetItemForm();
            $('#itemModal').modal('show');
        });

        // Edit Item
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            loadItem(id);
        });

        // Delete Item
        $(document).on('click', '.btn-delete', function() {
            if (!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            const id = $(this).data('id');
            $.ajax({
                url: 'inventory.php?action=delete',
                type: 'POST',
                data: {
                    id: id,
                    [CSRF_TOKEN_NAME]: $('input[name="' + CSRF_TOKEN_NAME + '"]').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred');
                }
            });
        });

        // Save Item
        $('#btnSaveItem').on('click', function() {
            const form = $('#itemForm');
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            
            const id = $('#itemId').val();
            const action = id ? 'update' : 'create';
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            $.ajax({
                url: 'inventory.php?action=' + action,
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#itemModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred');
                },
                complete: function() {
                    $('#btnSaveItem').prop('disabled', false).html('<i class="fas fa-check me-1"></i>Save');
                }
            });
        });

        // Stock In button
        $('#btnStockIn').on('click', function() {
            currentAction = 'in';
            $('#stockInModal').modal('show');
        });

        // Stock Out button
        $('#btnStockOut').on('click', function() {
            currentAction = 'out';
            $('#stockOutModal').modal('show');
        });

        // Handle stock in/out type selection
        $('#stockInModal .list-group-item, #stockOutModal .list-group-item').on('click', function() {
            const type = $(this).data('type');
            $('#stockInModal, #stockOutModal').modal('hide');
            showSelectItemsModal(type);
        });

        // Store selection in Select Items modal
        $('#selectStoreDropdown').on('change', function() {
            selectedLocationId = $(this).val();
            if (selectedLocationId) {
                loadItemsForSelection();
            }
        });

        // Search in Select Items modal
        $('#selectItemSearch').on('keyup', debounce(function() {
            const search = $(this).val().toLowerCase();
            $('#selectItemsList .list-group-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(search) > -1);
            });
        }, 300));

        // Select items button
        $('#btnSelectItems').on('click', function() {
            const selectedItems = [];
            $('#selectItemsList input:checked').each(function() {
                selectedItems.push({
                    id: $(this).data('id'),
                    name: $(this).data('name'),
                    qty: $(this).closest('.list-group-item').find('.item-qty').val() || 0
                });
            });
            
            if (selectedItems.length === 0) {
                showAlert('warning', 'Please select at least one item');
                return;
            }
            
            // Process selected items based on current action
            processStockMovement(selectedItems);
        });

        // Export CSV
        $('#btnExportCSV').on('click', function() {
            window.location.href = 'inventory.php?action=export_csv';
        });

        // Import Items
        $('#btnImportItems').on('click', function() {
            showAlert('info', 'Import functionality coming soon!');
        });

        // View Item Details
        $(document).on('click', '.btn-view', function() {
            const id = $(this).data('id');
            loadItemMovements(id);
        });

    });

    /**
     * Reset item form
     */
    function resetItemForm() {
        $('#itemForm')[0].reset();
        $('#itemId').val('');
        $('#itemModal .modal-title').text('Enter Item');
    }

    /**
     * Load item data for editing
     */
    function loadItem(id) {
        $.ajax({
            url: 'inventory.php?action=get_item',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const item = response.data;
                    $('#itemId').val(item.id);
                    $('#itemName').val(item.name);
                    $('#itemSKU').val(item.sku);
                    $('#itemCategory').val(item.category_id).trigger('change');
                    $('#itemUnit').val(item.unit);
                    $('#itemStdCost').val(item.standard_cost);
                    $('#itemRetailPrice').val(item.retail_price);
                    $('#itemMinStock').val(item.reorder_level);
                    $('#itemHSN').val(item.hsn_code);
                    $('#itemGST').val(item.tax_rate);
                    $('#itemDescription').val(item.description);
                    
                    $('#itemModal .modal-title').text('Edit Item');
                    $('#itemModal').modal('show');
                } else {
                    showAlert('danger', 'Failed to load item data');
                }
            },
            error: function() {
                showAlert('danger', 'Failed to load item data');
            }
        });
    }

    /**
     * Show select items modal
     */
    function showSelectItemsModal(type) {
        $('#selectItemsModal').data('type', type);
        $('#selectItemsList').html('<div class="text-center text-muted p-3">Please select store.</div>');
        $('#selectStoreDropdown').val('');
        $('#selectItemsModal').modal('show');
    }

    /**
     * Load items for selection
     */
    function loadItemsForSelection() {
        $.ajax({
            url: 'inventory.php?action=get_items',
            type: 'GET',
            data: { 
                location_id: selectedLocationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(item) {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check flex-grow-1">
                                        <input class="form-check-input" type="checkbox" 
                                               data-id="${item.id}" data-name="${item.name}">
                                        <label class="form-check-label">
                                            <strong>${item.name}</strong><br>
                                            <small class="text-muted">#${item.sku}</small><br>
                                            <small>${item.category_name || 'No Category'}</small>
                                        </label>
                                    </div>
                                    <div class="text-end">
                                        <input type="number" class="form-control form-control-sm item-qty" 
                                               value="0" min="0" step="1" style="width: 100px;">
                                        <small class="text-muted">Stock: ${item.total_qty || 0} ${item.unit}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#selectItemsList').html(html);
                } else {
                    $('#selectItemsList').html('<div class="text-center text-muted p-3">No items found for this location.</div>');
                }
            },
            error: function() {
                $('#selectItemsList').html('<div class="text-center text-danger p-3">Failed to load items.</div>');
            }
        });
    }

    /**
     * Process stock movement
     */
    function processStockMovement(items) {
        const type = $('#selectItemsModal').data('type');
        const assignedTo = $('#assignedToPerson').val();
        const assignmentNotes = $('#assignmentNotes').val();
        
        // Validate store selection
        if (!selectedLocationId) {
            showAlert('danger', 'Please select a store first');
            return;
        }
        
        // Process each item
        let successCount = 0;
        let failCount = 0;
        
        items.forEach(function(item) {
            if (item.qty > 0) {
                const data = {
                    item_id: item.id,
                    qty: item.qty,
                    location_id: selectedLocationId,
                    ref_type: type,
                    assigned_to: assignedTo || '',
                    assignment_notes: assignmentNotes || '',
                    [CSRF_TOKEN_NAME]: $('input[name="' + CSRF_TOKEN_NAME + '"]').val()
                };
                
                const action = currentAction === 'in' ? 'stock_in' : 'stock_out';
                
                $.ajax({
                    url: 'inventory.php?action=' + action,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            successCount++;
                            showAlert('success', `${item.name}: ${response.message}`);
                        } else {
                            failCount++;
                            showAlert('danger', `${item.name}: ${response.message}`);
                        }
                    },
                    error: function() {
                        failCount++;
                        showAlert('danger', `${item.name}: Failed to process`);
                    }
                });
            }
        });
        
        $('#selectItemsModal').modal('hide');
        
        // Show summary message
        setTimeout(() => {
            if (successCount > 0) {
                showAlert('success', `Successfully processed ${successCount} item(s)`);
            }
            if (failCount > 0) {
                showAlert('warning', `Failed to process ${failCount} item(s)`);
            }
            location.reload();
        }, 2000);
    }

    /**
     * Load item movements
     */
    function loadItemMovements(itemId) {
        $.ajax({
            url: 'inventory.php?action=movements',
            type: 'GET',
            data: { item_id: itemId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    let html = '<div class="table-responsive"><table class="table table-sm">';
                    html += '<thead><tr><th>Date</th><th>Type</th><th>From</th><th>To</th><th>Qty</th><th>User</th></tr></thead><tbody>';
                    
                    response.data.forEach(function(mov) {
                        html += `<tr>
                            <td>${mov.created_at}</td>
                            <td><span class="badge bg-${mov.type === 'in' ? 'success' : 'warning'}">${mov.type}</span></td>
                            <td>${mov.location_from_name || '-'}</td>
                            <td>${mov.location_to_name || '-'}</td>
                            <td>${mov.qty}</td>
                            <td>${mov.created_by_name}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    
                    // Show in a modal or alert
                    showAlert('info', html);
                }
            }
        });
    }

    /**
     * Apply filters
     */
    function applyFilters() {
        const filters = {
            location: $('#filterLocation').val(),
            category: $('#filterCategory').val(),
            stock: $('#filterStock').val(),
            importance: $('#filterImportance').val()
        };
        
        // Reload table with filters
        // Implementation depends on whether you're using server-side or client-side processing
        console.log('Filters:', filters);
    }

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

})(jQuery);
