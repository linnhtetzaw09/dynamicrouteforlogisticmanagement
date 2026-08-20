/**
 * Main JavaScript File
 * Contains common functionality and AJAX requests for the Logistics Web Application
 */

// Global configuration
const APP_CONFIG = {
    baseUrl: window.location.origin + '/LHZ/',
    ajaxTimeout: 5000,
    debounceDelay: 300
};

// Document ready
$(document).ready(function() {
    initializeEventListeners();
    initializeTooltips();
});

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Search functionality with debounce
    $(document).on('keyup', '.search-input', debounce(function() {
        performSearch($(this));
    }, APP_CONFIG.debounceDelay));

    // Delete confirmation
    $(document).on('click', '.btn-delete', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Form submission with AJAX
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        submitFormAjax($(this));
    });

    // Modal form submission
    $(document).on('submit', '.modal-form', function(e) {
        e.preventDefault();
        submitModalFormAjax($(this));
    });

    // Status filter
    $(document).on('change', '.status-filter', function() {
        filterByStatus($(this).val());
    });

    // Date range filter
    $(document).on('change', '.date-filter', function() {
        filterByDateRange();
    });
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Debounce function to limit function calls
 * @param {Function} func - Function to debounce
 * @param {Number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Perform search with AJAX
 * @param {jQuery} element - Search input element
 */
function performSearch(element) {
    const searchTerm = element.val();
    const searchType = element.data('search-type') || 'general';
    const container = element.closest('.search-container').find('.search-results');

    if (searchTerm.length < 2) {
        container.html('');
        return;
    }

    $.ajax({
        url: APP_CONFIG.baseUrl + 'admin/jobs.php',
        type: 'POST',
        dataType: 'json',
        data: {
            q: searchTerm,
            type: searchType
        },
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                displaySearchResults(response.data, container);
            } else {
                showAlert('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Search error:', error);
            showAlert('Error', 'Failed to perform search. Please try again.', 'danger');
        }
    });
}

/**
 * Display search results
 * @param {Array} results - Search results
 * @param {jQuery} container - Container element
 */
function displaySearchResults(results, container) {
    if (results.length === 0) {
        container.html('<div class="alert alert-info">No results found.</div>');
        return;
    }

    let html = '<div class="list-group">';
    results.forEach(function(item) {
        html += '<a href="' + item.url + '" class="list-group-item list-group-item-action">';
        html += '<h6 class="mb-1">' + escapeHtml(item.title) + '</h6>';
        html += '<p class="mb-1 small text-muted">' + escapeHtml(item.description) + '</p>';
        html += '</a>';
    });
    html += '</div>';
    container.html(html);
}

/**
 * Submit form via AJAX
 * @param {jQuery} form - Form element
 */
function submitFormAjax(form) {
    const submitBtn = form.find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    // Show loading state
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method') || 'POST',
        dataType: 'json',
        data: form.serialize(),
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                showAlert('Success', response.message, 'success');
                if (response.redirect) {
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    form.reset();
                    if (response.callback) {
                        window[response.callback]();
                    }
                }
            } else {
                showAlert('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Form submission error:', error);
            showAlert('Error', 'Failed to submit form. Please try again.', 'danger');
        },
        complete: function() {
            // Restore button state
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
}

/**
 * Submit modal form via AJAX
 * @param {jQuery} form - Form element
 */
function submitModalFormAjax(form) {
    const modal = form.closest('.modal');
    const submitBtn = form.find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    // Show loading state
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method') || 'POST',
        dataType: 'json',
        data: form.serialize(),
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                showAlert('Success', response.message, 'success');
                modal.modal('hide');
                if (response.callback) {
                    window[response.callback]();
                }
            } else {
                showAlert('Error', response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Modal form submission error:', error);
            showAlert('Error', 'Failed to save. Please try again.', 'danger');
        },
        complete: function() {
            // Restore button state
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
}

/**
 * Filter results by status
 * @param {String} status - Status to filter by
 */
function filterByStatus(status) {
    $.ajax({
        url: APP_CONFIG.baseUrl + 'api/filter.php',
        type: 'POST',
        dataType: 'json',
        data: {
            filter: 'status',
            value: status
        },
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                updateTableData(response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Filter error:', error);
        }
    });
}

/**
 * Filter results by date range
 */
function filterByDateRange() {
    const startDate = $('.date-start').val();
    const endDate = $('.date-end').val();

    if (!startDate || !endDate) {
        return;
    }

    $.ajax({
        url: APP_CONFIG.baseUrl + 'api/filter.php',
        type: 'POST',
        dataType: 'json',
        data: {
            filter: 'daterange',
            startDate: startDate,
            endDate: endDate
        },
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                updateTableData(response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Date filter error:', error);
        }
    });
}

/**
 * Update table data
 * @param {Array} data - New data to display
 */
function updateTableData(data) {
    const table = $('table tbody');
    table.empty();

    if (data.length === 0) {
        table.html('<tr><td colspan="100%" class="text-center text-muted">No data available</td></tr>');
        return;
    }

    data.forEach(function(row) {
        const tr = $('<tr>');
        Object.values(row).forEach(function(cell) {
            tr.append($('<td>').html(cell));
        });
        table.append(tr);
    });
}

/**
 * Show alert message
 * @param {String} title - Alert title
 * @param {String} message - Alert message
 * @param {String} type - Alert type (success, danger, warning, info)
 */
function showAlert(title, message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" aria-live="assertive">
            <strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    const alertContainer = $('.alert-container');
    if (alertContainer.length) {
        alertContainer.prepend(alertHtml);
    } else {
        $('main').prepend(alertHtml);
    }

    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').not(':has(.btn-close:focus)').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Escape HTML special characters
 * @param {String} text - Text to escape
 * @returns {String} Escaped text
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Format date to readable format
 * @param {String} dateString - Date string
 * @returns {String} Formatted date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-GB', options);
}

/**
 * Format currency
 * @param {Number} amount - Amount to format
 * @returns {String} Formatted currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
    }).format(amount);
}

/**
 * Load data via AJAX
 * @param {String} url - URL to load from
 * @param {String} container - Container selector
 */
function loadDataAjax(url, container) {
    $(container).html('<div class="spinner"></div>');

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        timeout: APP_CONFIG.ajaxTimeout,
        success: function(response) {
            if (response.success) {
                $(container).html(response.html);
            } else {
                $(container).html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Load data error:', error);
            $(container).html('<div class="alert alert-danger">Failed to load data. Please try again.</div>');
        }
    });
}

/**
 * Export table to CSV
 * @param {String} tableId - Table ID
 * @param {String} filename - Export filename
 */
function exportTableToCSV(tableId, filename) {
    const csv = [];
    const rows = document.querySelectorAll(tableId + ' tr');

    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(function(col) {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });

    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 * @param {String} csv - CSV content
 * @param {String} filename - Filename
 */
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
