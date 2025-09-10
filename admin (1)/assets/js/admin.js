// Admin Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            
            // Simpan state sidebar di localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // Restore sidebar state
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isSidebarCollapsed) {
        document.querySelector('.sidebar')?.classList.add('collapsed');
        document.querySelector('.main-content')?.classList.add('collapsed');
    }

    // Chart Initialization
    initializeCharts();

    // DataTable Initialization
    initializeDataTables();

    // Form Validation
    initializeFormValidation();

    // Notification System
    initializeNotifications();

    // Auto Refresh Data
    initializeAutoRefresh();

    // Export functionality
    initializeExportButtons();
});

// Chart Functions
function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000000, 19000000, 15000000, 25000000, 22000000, 30000000],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart');
    if (ordersCtx) {
        const ordersChart = new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Orders',
                    data: [120, 190, 150, 250, 220, 300],
                    backgroundColor: '#28a745',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// DataTable Functions
function initializeDataTables() {
    const tables = document.querySelectorAll('table[data-datatable]');
    
    tables.forEach(table => {
        const options = {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        };

        if (table.classList.contains('exportable')) {
            options.dom = '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                          '<"row"<"col-sm-12"tr>>' +
                          '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>';
        }

        $(table).DataTable(options);
    });
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Harap periksa form yang diisi', 'error');
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            markAsInvalid(input);
            isValid = false;
        } else {
            markAsValid(input);
        }
    });

    return isValid;
}

function markAsInvalid(element) {
    element.classList.add('is-invalid');
    element.classList.remove('is-valid');
}

function markAsValid(element) {
    element.classList.remove('is-invalid');
    element.classList.add('is-valid');
}

// Notification System
function initializeNotifications() {
    // Check for unread notifications
    checkUnreadNotifications();
    
    // Auto refresh notifications every 30 seconds
    setInterval(checkUnreadNotifications, 30000);
}

function checkUnreadNotifications() {
    fetch('../includes/notifications.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

function updateNotificationBadge(count) {
    let badge = document.querySelector('.notification-badge');
    if (!badge) {
        const notificationLink = document.querySelector('a[href="admin_notifications.php"]');
        if (notificationLink) {
            badge = document.createElement('span');
            badge.className = 'notification-badge badge bg-danger';
            notificationLink.appendChild(badge);
        }
    }
    
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function showNotification(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alert, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Auto Refresh
function initializeAutoRefresh() {
    const refreshableSections = document.querySelectorAll('[data-refresh]');
    
    refreshableSections.forEach(section => {
        const interval = section.getAttribute('data-refresh-interval') || 30000;
        setInterval(() => refreshSection(section), interval);
    });
}

function refreshSection(section) {
    const url = section.getAttribute('data-refresh-url');
    if (!url) return;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            section.innerHTML = html;
            initializeDataTables(); // Reinitialize datatables if any
        })
        .catch(error => console.error('Error refreshing section:', error));
}

// Export functionality
function initializeExportButtons() {
    const exportButtons = document.querySelectorAll('[data-export]');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const exportType = this.getAttribute('data-export');
            const filters = getCurrentFilters();
            
            const url = new URL('export_data.php', window.location.origin);
            url.searchParams.set('export', exportType);
            
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    url.searchParams.set(key, filters[key]);
                }
            });
            
            window.location.href = url.toString();
        });
    });
}

function getCurrentFilters() {
    const filters = {};
    const form = document.querySelector('form[data-filter-form]');
    
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name && input.value) {
                filters[input.name] = input.value;
            }
        });
    }
    
    return filters;
}

// Modal handling
function openModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function closeModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) {
        modal.hide();
    }
}

// Utility functions
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
}

function formatDateTime(date) {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// API functions
async function apiCall(endpoint, data = {}, method = 'POST') {
    try {
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: method !== 'GET' ? JSON.stringify(data) : undefined
        });
        
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        showNotification('Terjadi kesalahan saat menghubungi server', 'error');
        throw error;
    }
}

// Event delegation for dynamic content
document.addEventListener('click', function(e) {
    // Handle approve/reject actions
    if (e.target.closest('[data-action]')) {
        const element = e.target.closest('[data-action]');
        const action = element.getAttribute('data-action');
        const id = element.getAttribute('data-id');
        
        handleAction(action, id, element);
    }
    
    // Handle quick edit buttons
    if (e.target.closest('.quick-edit-btn')) {
        const element = e.target.closest('.quick-edit-btn');
        const id = element.getAttribute('data-id');
        const type = element.getAttribute('data-type');
        
        openQuickEditModal(type, id);
    }
});

async function handleAction(action, id, element) {
    const loadingClass = 'btn-loading';
    const originalText = element.innerHTML;
    
    // Show loading state
    element.innerHTML = '<span class="loading-spinner"></span>';
    element.classList.add(loadingClass);
    element.disabled = true;
    
    try {
        const response = await apiCall('../includes/ajax_handler.php', {
            action: action,
            id: id
        });
        
        if (response.success) {
            showNotification(response.message, 'success');
            
            // Refresh the section or update specific element
            if (element.closest('[data-refresh-on-action]')) {
                refreshSection(element.closest('[data-refresh-on-action]'));
            }
        } else {
            showNotification(response.message, 'error');
        }
    } catch (error) {
        showNotification('Terjadi kesalahan saat memproses permintaan', 'error');
    } finally {
        // Restore original state
        element.innerHTML = originalText;
        element.classList.remove(loadingClass);
        element.disabled = false;
    }
}

function openQuickEditModal(type, id) {
    // Load modal content via AJAX
    fetch(`../includes/ajax_handler.php?action=get_${type}_data&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalContent = document.getElementById('quickEditModalContent');
                modalContent.innerHTML = data.html;
                openModal('quickEditModal');
            } else {
                showNotification('Gagal memuat data', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading modal content:', error);
            showNotification('Terjadi kesalahan saat memuat data', 'error');
        });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + K for search focus
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape key to close modals
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
});

// Responsive table handling
function initializeResponsiveTables() {
    const tables = document.querySelectorAll('.table-responsive');
    
    tables.forEach(table => {
        if (table.offsetWidth < table.scrollWidth) {
            table.classList.add('scrollable');
        }
    });
}

// Initialize everything when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdminJS);
} else {
    initializeAdminJS();
}

function initializeAdminJS() {
    initializeCharts();
    initializeDataTables();
    initializeFormValidation();
    initializeNotifications();
    initializeAutoRefresh();
    initializeExportButtons();
    initializeResponsiveTables();
}