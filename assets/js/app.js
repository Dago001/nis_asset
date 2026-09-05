/**
 * NIS Asset Management System
 * Main JavaScript File
 */

// =============================================
// NOTIFICATION SYSTEM
// =============================================
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${getIconForType(type)}"></i>
        <span>${message}</span>
        <span class="notification-close">&times;</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
}

function getIconForType(type) {
    switch(type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        case 'info': return 'fa-info-circle';
        default: return 'fa-bell';
    }
}

// =============================================
// SIDEBAR FUNCTIONALITY
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    // Auto-expand dropdown if any child is active
    document.querySelectorAll('.sub-item.active').forEach(function(activeItem) {
        const parentDropdown = activeItem.closest('.dropdown-items');
        if (parentDropdown) {
            parentDropdown.classList.add('show');
            const toggle = document.querySelector(`.dropdown-toggle[data-target="${parentDropdown.id}"]`);
            if (toggle) toggle.classList.add('open');
        }
    });

    // Toggle dropdowns on click
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const targetId = this.dataset.target;
            const dropdown = document.getElementById(targetId);
            if (dropdown) {
                dropdown.classList.toggle('show');
                this.classList.toggle('open');
            }
        });
    });
    
    // User Profile Dropdown
    const adminProfileBtn = document.getElementById('adminProfileBtn');
    const adminProfileMenu = document.getElementById('adminProfileMenu');
    
    if (adminProfileBtn && adminProfileMenu) {
        adminProfileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            adminProfileBtn.classList.toggle('active');
            adminProfileMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!adminProfileBtn.contains(e.target) && !adminProfileMenu.contains(e.target)) {
                adminProfileBtn.classList.remove('active');
                adminProfileMenu.classList.remove('active');
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                adminProfileBtn.classList.remove('active');
                adminProfileMenu.classList.remove('active');
            }
        });
    }
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            const isActive = sidebar.classList.contains('active');
            
            if (isActive) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-open');
                mobileMenuToggle.innerHTML = '<i class="fas fa-times"></i>';
            }
        }
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (sidebar && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
    }
    
    // Close sidebar when clicking on a link (mobile)
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991 && sidebar && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
    });
    
    // Handle window resize
    function handleResize() {
        if (window.innerWidth > 991) {
            if (sidebar) sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            if (mobileMenuToggle) {
                mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize();
    
    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
});

// =============================================
// FORM VALIDATION HELPERS
// =============================================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\+\-\(\)]{10,15}$/;
    return re.test(phone);
}

function validateNIS(nis) {
    const re = /^[A-Z0-9\-]+$/i;
    return re.test(nis);
}

function validatePassword(password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return re.test(password);
}

// =============================================
// DATE HELPERS
// =============================================
function formatDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateTime(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

function daysBetween(date1, date2) {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    const diffTime = Math.abs(d2 - d1);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

// =============================================
// CURRENCY FORMATTING
// =============================================
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function formatNumber(number) {
    return new Intl.NumberFormat('en-NG').format(number);
}

// =============================================
// FILE UPLOAD PREVIEW
// =============================================
function setupFileUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function() {
        preview.innerHTML = '';
        
        Array.from(this.files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.innerHTML = `
                <i class="fas fa-file"></i>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${(file.size / 1024).toFixed(2)} KB)</span>
            `;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                fileItem.remove();
                // Note: This doesn't remove from FileList, just UI
            };
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            preview.appendChild(fileItem);
        });
    });
}

// =============================================
// AJAX REQUEST HELPER
// =============================================
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    };
    
    // Add CSRF token for POST requests
    if (method.toUpperCase() !== 'GET') {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }
    }
    
    if (data) {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }
    
    try {
        // Resolve app-relative URLs ("/api/...") against the deploy base path.
        const appBase = document.querySelector('meta[name="app-base"]')?.getAttribute('content') || '';
        const resolvedUrl = /^https?:\/\//i.test(url)
            ? url
            : appBase + (url.startsWith('/') ? url : '/' + url);

        const response = await fetch(resolvedUrl, options);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            return await response.text();
        }
    } catch (error) {
        console.error('AJAX Error:', error);
        showNotification('error', 'An error occurred. Please try again.');
        throw error;
    }
}

// =============================================
// SEARCH TABLE HELPER
// =============================================
function setupTableSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// =============================================
// CONFIRM DIALOG
// =============================================
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// =============================================
// PRINT SECTION
// =============================================
function printSection(elementId) {
    const printContents = document.getElementById(elementId).innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// =============================================
// EXPORT TABLE TO CSV
// =============================================
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => {
            rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// =============================================
// CHARTS INITIALIZATION
// Charts management is safely handled in assets/js/charts.js

// =============================================
// FORM VALIDATION
// =============================================
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const required = this.querySelectorAll('[required]');
        let isValid = true;
        
        required.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
                
                // Show error message
                let errorMsg = field.parentNode.querySelector('.error-text');
                if (!errorMsg) {
                    errorMsg = document.createElement('small');
                    errorMsg.className = 'error-text';
                    field.parentNode.appendChild(errorMsg);
                }
                errorMsg.textContent = `${field.getAttribute('name')} is required`;
            } else {
                field.classList.remove('error');
                const errorMsg = field.parentNode.querySelector('.error-text');
                if (errorMsg) errorMsg.remove();
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('error', 'Please fill in all required fields');
        }
    });
});

// =============================================
// PASSWORD STRENGTH INDICATOR
// =============================================
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return Math.min(strength, 4);
}

document.querySelectorAll('input[type="password"][data-strength]').forEach(input => {
    input.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const meter = this.parentNode.querySelector('.strength-meter');
        
        if (meter) {
            meter.className = 'strength-meter strength-' + strength;
        }
    });
});

// =============================================
// DATE RANGE PICKER
// =============================================
function initDateRangePicker(startId, endId) {
    const startInput = document.getElementById(startId);
    const endInput = document.getElementById(endId);
    
    if (startInput && endInput) {
        startInput.addEventListener('change', function() {
            endInput.min = this.value;
        });
        
        endInput.addEventListener('change', function() {
            if (startInput.value && this.value < startInput.value) {
                this.value = startInput.value;
                showNotification('warning', 'End date cannot be before start date');
            }
        });
    }
}