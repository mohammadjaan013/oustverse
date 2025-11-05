/**
 * Production Jobs JavaScript
 * Handles all interactions for the production jobs module
 */

$(document).ready(function() {
    // Initialize DataTables
    let pendingTable = null;
    let historyTable = null;
    
    // Initialize only the pending table (active tab)
    pendingTable = initializeDataTable('#pendingJobsTable', 'pending');

    // Initialize Select2 for dropdowns
    initializeSelect2();

    // Load initial data
    loadStatistics();
    
    // Initialize history table when its tab is clicked
    $('a[href="#history"]').one('shown.bs.tab', function() {
        if (historyTable === null) {
            historyTable = initializeDataTable('#historyJobsTable', 'completed');
        }
    });

    // Set default target date (7 days from now)
    const targetDate = new Date();
    targetDate.setDate(targetDate.getDate() + 7);
    $('#targetDate').val(targetDate.toISOString().split('T')[0]);

    /**
     * Initialize DataTable
     */
    function initializeDataTable(selector, status) {
        return $(selector).DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'controllers/ProductionJobController.php?action=getJobs',
                data: function(d) {
                    d.status = status;
                    d.search = $('#searchInput').val();
                },
                dataSrc: 'data'
            },
            columns: [
                { 
                    data: 'wip_no',
                    render: function(data, type, row) {
                        if (row.is_overdue) {
                            return '<span class="text-danger fw-bold">' + data + '</span>';
                        }
                        return data;
                    }
                },
                { 
                    data: 'product_name',
                    render: function(data, type, row) {
                        return data + '<br><small class="text-muted">' + row.product_code + '</small>';
                    }
                },
                { data: 'customer_name' },
                { data: 'quantity' },
                { 
                    data: 'target_date',
                    render: function(data, type, row) {
                        if (row.is_overdue) {
                            return '<span class="text-danger">' + data + '</span>';
                        }
                        return data;
                    }
                },
                { 
                    data: 'days_remaining',
                    render: function(data, type, row) {
                        if (selector === '#pendingJobsTable') {
                            if (data < 0) {
                                return '<span class="badge bg-danger">' + Math.abs(data) + ' days overdue</span>';
                            } else if (data <= 3) {
                                return '<span class="badge bg-warning">' + data + ' days</span>';
                            } else {
                                return '<span class="badge bg-success">' + data + ' days</span>';
                            }
                        }
                        return '-';
                    }
                },
                { data: 'status' },
                ...(selector === '#historyJobsTable' ? [{ data: 'created_at' }] : []),
                { 
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                emptyTable: status === 'pending' ? 'No pending production jobs' : 'No production job history'
            }
        });
    }

    /**
     * Initialize Select2
     */
    function initializeSelect2() {
        // Product dropdown
        $('#productId').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Product',
            dropdownParent: $('#productionJobModal'),
            ajax: {
                url: 'controllers/ProductionJobController.php?action=getProducts',
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        // Customer dropdown
        $('#customerId').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Customer',
            allowClear: true,
            dropdownParent: $('#productionJobModal'),
            ajax: {
                url: 'controllers/ProductionJobController.php?action=getCustomers',
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
    }

    /**
     * Load statistics
     */
    function loadStatistics() {
        $.ajax({
            url: 'controllers/ProductionJobController.php?action=getStatistics',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#wipCount').text(response.data.wip_jobs || 0);
                    $('#overdueCount').text(response.data.overdue_jobs || 0);
                    $('#totalCount').text(response.data.total_jobs || 0);
                    $('#completedCount').text(response.data.completed_jobs || 0);
                }
            }
        });
    }

    /**
     * Search functionality
     */
    $('#searchInput').on('keyup', function() {
        pendingTable.ajax.reload();
        historyTable.ajax.reload();
    });

    /**
     * Create Job button click
     */
    $('#createJobBtn, #createJobCardBtn').click(function() {
        $('#modalTitle').text('Create Production Job');
        $('#productionJobForm')[0].reset();
        $('#jobId').val('');
        $('#productId').val(null).trigger('change');
        $('#customerId').val(null).trigger('change');
        
        // Set default target date
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 7);
        $('#targetDate').val(targetDate.toISOString().split('T')[0]);
        
        $('#status').val('pending');
        $('#productionJobModal').modal('show');
    });

    /**
     * Quick Entry button
     */
    $('#quickEntryBtn').click(function() {
        $('#createJobBtn').click();
    });

    /**
     * Add Product button
     */
    $('#addProductBtn').click(function() {
        // Redirect to inventory page to add product
        window.location.href = 'inventory.php';
    });

    /**
     * Save Job
     */
    $('#saveJobBtn').click(function() {
        const jobId = $('#jobId').val();
        const formData = $('#productionJobForm').serialize();
        const action = jobId ? 'update' : 'create';
        
        $.ajax({
            url: 'controllers/ProductionJobController.php?action=' + action,
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    $('#productionJobModal').modal('hide');
                    pendingTable.ajax.reload();
                    historyTable.ajax.reload();
                    loadStatistics();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save production job'
                });
            }
        });
    });

    /**
     * Edit Job
     */
    $(document).on('click', '.edit-job', function() {
        const jobId = $(this).data('id');
        
        $.ajax({
            url: 'controllers/ProductionJobController.php?action=getJob&id=' + jobId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const job = response.data;
                    
                    $('#modalTitle').text('Edit Production Job');
                    $('#jobId').val(job.id);
                    $('#wipNo').val(job.wip_no);
                    $('#targetDate').val(job.target_date);
                    $('#quantity').val(job.quantity);
                    $('#specialInstructions').val(job.special_instructions);
                    $('#status').val(job.status);
                    
                    // Set product
                    const productOption = new Option(
                        job.product_name + ' (' + job.product_code + ')',
                        job.product_id,
                        true,
                        true
                    );
                    $('#productId').append(productOption).trigger('change');
                    
                    // Set customer if exists
                    if (job.customer_id) {
                        const customerOption = new Option(
                            job.customer_name,
                            job.customer_id,
                            true,
                            true
                        );
                        $('#customerId').append(customerOption).trigger('change');
                    }
                    
                    $('#productionJobModal').modal('show');
                }
            }
        });
    });

    /**
     * View Job Details
     */
    $(document).on('click', '.view-job', function() {
        const jobId = $(this).data('id');
        
        $.ajax({
            url: 'controllers/ProductionJobController.php?action=getJob&id=' + jobId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const job = response.data;
                    
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>WIP No:</strong> ${job.wip_no}</p>
                                <p><strong>Product:</strong> ${job.product_name} (${job.product_code})</p>
                                <p><strong>Customer:</strong> ${job.customer_name || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Quantity:</strong> ${job.quantity}</p>
                                <p><strong>Target Date:</strong> ${job.target_date}</p>
                                <p><strong>Status:</strong> <span class="badge bg-info">${job.status}</span></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>Special Instructions:</strong></p>
                                <p>${job.special_instructions || 'No special instructions'}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <p><small class="text-muted">Created by: ${job.created_by_name} on ${job.created_at}</small></p>
                            </div>
                        </div>
                    `;
                    
                    $('#jobDetailsContent').html(html);
                    $('#viewJobModal').modal('show');
                }
            }
        });
    });

    /**
     * Start Production
     */
    $(document).on('click', '.start-job', function() {
        const jobId = $(this).data('id');
        
        Swal.fire({
            title: 'Start Production?',
            text: 'This will change the job status to In Progress',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Start',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                updateJobStatus(jobId, 'in_progress');
            }
        });
    });

    /**
     * Complete Job
     */
    $(document).on('click', '.complete-job', function() {
        const jobId = $(this).data('id');
        
        Swal.fire({
            title: 'Mark as Complete?',
            text: 'This will mark the production job as completed',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Complete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                updateJobStatus(jobId, 'completed');
            }
        });
    });

    /**
     * Delete Job
     */
    $(document).on('click', '.delete-job', function() {
        const jobId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Job?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'controllers/ProductionJobController.php?action=delete',
                    method: 'POST',
                    data: { id: jobId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            pendingTable.ajax.reload();
                            historyTable.ajax.reload();
                            loadStatistics();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    }
                });
            }
        });
    });

    /**
     * Update Job Status
     */
    function updateJobStatus(jobId, status) {
        $.ajax({
            url: 'controllers/ProductionJobController.php?action=updateStatus',
            method: 'POST',
            data: {
                id: jobId,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    pendingTable.ajax.reload();
                    historyTable.ajax.reload();
                    loadStatistics();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            }
        });
    }

    /**
     * Tab change event
     */
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).data('bs-target');
        
        if (target === '#pending') {
            pendingTable.ajax.reload();
        } else if (target === '#history') {
            historyTable.ajax.reload();
        }
    });
});
