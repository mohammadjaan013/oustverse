/**
 * Biziverse ERP - Custom JavaScript
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        
        // Initialize DataTables
        if ($.fn.DataTable) {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records..."
                }
            });
        }

        // Initialize Select2
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }

        // Sidebar toggle
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar, #content').toggleClass('active');
        });

        // Form validation
        $('.needs-validation').on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });

        // Confirm delete
        $('.delete-confirm').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert:not(.alert-permanent)').fadeOut('slow');
        }, 5000);

        // Tooltip initialization
        if ($.fn.tooltip) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }

        // Popover initialization
        if ($.fn.popover) {
            $('[data-bs-toggle="popover"]').popover();
        }

        // Number formatting
        $('.format-currency').each(function() {
            var value = parseFloat($(this).text());
            if (!isNaN(value)) {
                $(this).text('₹ ' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
            }
        });

        // Date picker (if using a date picker library)
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true
            });
        }

    });

    // AJAX Form Submit Helper
    window.submitAjaxForm = function(formId, successCallback) {
        $(formId).on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.html();
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        if (typeof successCallback === 'function') {
                            successCallback(response);
                        }
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('danger', 'An error occurred. Please try again.');
                    console.error(error);
                },
                complete: function() {
                    // Re-enable button
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    };

    // Show Alert Helper
    window.showAlert = function(type, message, container) {
        container = container || '.container-fluid';
        
        var alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $(container).prepend(alertHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $(container).find('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    };

    // Format Number Helper
    window.formatNumber = function(num, decimals) {
        decimals = decimals || 2;
        return parseFloat(num).toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };

    // Format Currency Helper
    window.formatCurrency = function(amount) {
        return '₹ ' + formatNumber(amount, 2);
    };

    // Export Table to CSV
    window.exportTableToCSV = function(tableId, filename) {
        filename = filename || 'export.csv';
        
        var csv = [];
        var rows = document.querySelectorAll(tableId + " tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (var j = 0; j < cols.length; j++) {
                var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            
            csv.push(row.join(","));
        }
        
        // Download CSV
        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };

    // Print Page Helper
    window.printPage = function() {
        window.print();
    };

    // Debounce Helper for Search
    window.debounce = function(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    };

})(jQuery);
