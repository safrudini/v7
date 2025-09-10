// Reseller Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar toggle
    initSidebar();
    
    // Initialize product selection
    initProductSelection();
    
    // Initialize order form
    initOrderForm();
    
    // Initialize topup form
    initTopupForm();
    
    // Initialize balance auto-update
    initBalanceAutoUpdate();
    
    // Initialize webhook tester
    initWebhookTester();
    
    // Initialize responsive design
    initResponsiveDesign();
});

// Sidebar functionality
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar && mainContent) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });
    }
    
    // Restore sidebar state
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.querySelector('.sidebar')?.classList.add('collapsed');
        document.querySelector('.main-content')?.classList.add('collapsed');
    }
}

// Product selection functionality
function initProductSelection() {
    const productSelect = document.getElementById('produk');
    if (productSelect) {
        productSelect.addEventListener('change', function() {
            updateProductDetails(this.value);
            updatePriceDisplay(this.value);
        });
        
        // Load initial product details if a product is selected
        if (productSelect.value) {
            updateProductDetails(productSelect.value);
            updatePriceDisplay(productSelect.value);
        }
    }
}

function updateProductDetails(productKey) {
    const detailsDiv = document.getElementById('productDetails');
    if (!detailsDiv || !window.productDetails) return;
    
    const product = window.productDetails[productKey];
    if (product) {
        detailsDiv.innerHTML = `
            <strong>Harga:</strong> ${product.harga} <br>
            <strong>Details Kuota:</strong><br> ${product.kuota} <br>
            <strong>Catatan:</strong> ${product.noted}
        `;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
}

function updatePriceDisplay(productKey) {
    const priceDisplay = document.getElementById('priceDisplay');
    if (!priceDisplay || !window.productDetails) return;
    
    const product = window.productDetails[productKey];
    if (product) {
        // Extract numeric price from string (e.g., "Rp 50.000" -> 50000)
        const price = extractPrice(product.harga);
        priceDisplay.textContent = `Harga: ${product.harga}`;
        priceDisplay.style.display = 'block';
        
        // Check if balance is sufficient
        checkBalanceSufficiency(price);
    } else {
        priceDisplay.style.display = 'none';
    }
}

function extractPrice(priceString) {
    const numericString = priceString.replace(/[^\d]/g, '');
    return parseInt(numericString) || 0;
}

function checkBalanceSufficiency(price) {
    const balance = parseFloat(window.resellerBalance || 0);
    const balanceWarning = document.getElementById('balanceWarning');
    
    if (balanceWarning) {
        if (balance < price) {
            balanceWarning.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Saldo tidak mencukupi. Anda perlu top up saldo terlebih dahulu.
                </div>
            `;
            balanceWarning.style.display = 'block';
        } else {
            balanceWarning.style.display = 'none';
        }
    }
}

// Order form functionality
function initOrderForm() {
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const productSelect = document.getElementById('produk');
            const phoneInput = document.getElementById('nomor');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            if (!validatePhoneNumber(phoneInput.value)) {
                showAlert('Format nomor tidak valid. Harus 10-14 digit angka.', 'error');
                return;
            }
            
            if (!productSelect.value) {
                showAlert('Silakan pilih produk terlebih dahulu.', 'error');
                return;
            }
            
            // Show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Memproses...';
            submitBtn.disabled = true;
            
            try {
                const response = await placeOrder({
                    product_code: productSelect.value,
                    destination: phoneInput.value
                });
                
                if (response.success) {
                    showAlert('Order berhasil diproses!', 'success');
                    resetForm(orderForm);
                    updateBalanceDisplay(response.new_balance);
                    
                    // Redirect to history page if needed
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                } else {
                    showAlert(response.message || 'Order gagal diproses.', 'error');
                }
            } catch (error) {
                console.error('Order error:', error);
                showAlert('Terjadi kesalahan saat memproses order.', 'error');
            } finally {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
}

async function placeOrder(orderData) {
    const response = await fetch('../api/process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    });
    
    return await response.json();
}

function validatePhoneNumber(phone) {
    return /^\d{10,14}$/.test(phone);
}

// Topup form functionality
function initTopupForm() {
    const topupForm = document.getElementById('topupForm');
    if (topupForm) {
        topupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const amountInput = document.getElementById('amount');
            const methodSelect = document.getElementById('payment_method');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            const amount = parseFloat(amountInput.value);
            if (amount < 10000) {
                showAlert('Minimum top up adalah Rp 10.000.', 'error');
                return;
            }
            
            // Show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Memproses...';
            submitBtn.disabled = true;
            
            try {
                const response = await requestTopup({
                    amount: amount,
                    payment_method: methodSelect.value
                });
                
                if (response.success) {
                    showAlert('Permintaan top up berhasil dikirim. Menunggu konfirmasi admin.', 'success');
                    resetForm(topupForm);
                    
                    // Update transactions history
                    loadTransactionHistory();
                } else {
                    showAlert(response.message || 'Top up gagal diproses.', 'error');
                }
            } catch (error) {
                console.error('Topup error:', error);
                showAlert('Terjadi kesalahan saat memproses top up.', 'error');
            } finally {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
}

async function requestTopup(topupData) {
    const response = await fetch('../api/request_topup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(topupData)
    });
    
    return await response.json();
}

// Balance auto-update
function initBalanceAutoUpdate() {
    // Update balance every 60 seconds
    setInterval(updateBalanceDisplay, 60000);
    
    // Initial update
    updateBalanceDisplay();
}

async function updateBalanceDisplay() {
    try {
        const response = await fetch('../api/get_balance.php');
        const data = await response.json();
        
        if (data.success) {
            const balanceElements = document.querySelectorAll('.balance-display');
            balanceElements.forEach(element => {
                element.textContent = formatRupiah(data.balance);
            });
            
            window.resellerBalance = data.balance;
        }
    } catch (error) {
        console.error('Balance update error:', error);
    }
}

// Webhook tester
function initWebhookTester() {
    const testWebhookBtn = document.getElementById('testWebhookBtn');
    if (testWebhookBtn) {
        testWebhookBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('../api/test_webhook.php');
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Webhook test berhasil dikirim!', 'success');
                } else {
                    showAlert('Webhook test gagal: ' + data.message, 'error');
                }
            } catch (error) {
                showAlert('Terjadi kesalahan saat testing webhook.', 'error');
            }
        });
    }
}

// Responsive design
function initResponsiveDesign() {
    // Handle window resize
    window.addEventListener('resize', debounce(function() {
        adjustLayoutForScreenSize();
    }, 250));
    
    // Initial adjustment
    adjustLayoutForScreenSize();
}

function adjustLayoutForScreenSize() {
    const width = window.innerWidth;
    
    if (width < 768) {
        // Mobile adjustments
        document.body.classList.add('mobile-view');
        
        // Collapse sidebar on mobile by default
        if (!localStorage.getItem('sidebarCollapsed')) {
            document.querySelector('.sidebar')?.classList.add('collapsed');
            document.querySelector('.main-content')?.classList.add('collapsed');
        }
    } else {
        document.body.classList.remove('mobile-view');
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-dismissible');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Find the best place to insert the alert
    const container = document.querySelector('.main-content') || document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alert, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function resetForm(form) {
    form.reset();
    const detailsDiv = document.getElementById('productDetails');
    if (detailsDiv) {
        detailsDiv.style.display = 'none';
    }
}

function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Load transaction history
async function loadTransactionHistory() {
    try {
        const response = await fetch('../api/get_transactions.php');
        const data = await response.json();
        
        if (data.success) {
            updateTransactionsTable(data.transactions);
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
    }
}

function updateTransactionsTable(transactions) {
    const tbody = document.querySelector('#transactionsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = transactions.map(transaction => `
        <tr>
            <td>${formatDate(transaction.created_at)}</td>
            <td>${transaction.type}</td>
            <td>${formatRupiah(transaction.amount)}</td>
            <td>${transaction.description}</td>
            <td><span class="badge bg-${getStatusClass(transaction.status)}">${transaction.status}</span></td>
        </tr>
    `).join('');
}

function getStatusClass(status) {
    switch (status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
}

function formatDate(dateString) {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateString));
}

// Export functionality
function exportData(type) {
    const url = new URL('../api/export_data.php', window.location.origin);
    url.searchParams.set('type', type);
    url.searchParams.set('format', 'csv');
    
    window.location.href = url.toString();
}

// Initialize everything
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initResellerJS);
} else {
    initResellerJS();
}

function initResellerJS() {
    initSidebar();
    initProductSelection();
    initOrderForm();
    initTopupForm();
    initBalanceAutoUpdate();
    initWebhookTester();
    initResponsiveDesign();
}