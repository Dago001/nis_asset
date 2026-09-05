/**
 * Table Management JavaScript
 */

// =============================================
// DATA TABLE INITIALIZATION
// =============================================
function initDataTables() {
    initSortableTables();
    initFilterableTables();
    initExportButtons();
}

function initSortableTables() {
    document.querySelectorAll('.asset-table thead th[data-sort]').forEach(th => {
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const index = this.cellIndex;
            const type = this.dataset.sort;
            const ascending = !this.classList.contains('sort-asc');
            
            // Remove sort classes from all headers
            table.querySelectorAll('thead th').forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Add sort class
            this.classList.add(ascending ? 'sort-asc' : 'sort-desc');
            
            sortTable(table, index, type, ascending);
        });
    });
}

function sortTable(table, column, type, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.children[column].textContent.trim();
        const bVal = b.children[column].textContent.trim();
        
        let comparison = 0;
        
        switch(type) {
            case 'number':
                comparison = parseFloat(aVal) - parseFloat(bVal);
                break;
            case 'date':
                comparison = new Date(aVal) - new Date(bVal);
                break;
            default:
                comparison = aVal.localeCompare(bVal);
        }
        
        return ascending ? comparison : -comparison;
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

function initFilterableTables() {
    document.querySelectorAll('.table-filter').forEach(filter => {
        filter.addEventListener('input', function() {
            const tableId = this.dataset.table;
            const column = this.dataset.column;
            const value = this.value.toLowerCase();
            
            filterTable(tableId, column, value);
        });
    });
}

function filterTable(tableId, column, value) {
    if (!tableId) return;
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const colIndex = column ? parseInt(column) : null;
    
    rows.forEach(row => {
        let show = true;
        
        if (colIndex !== null) {
            const cellText = row.children[colIndex].textContent.toLowerCase();
            show = cellText.includes(value);
        } else {
            // Search all columns
            show = Array.from(row.children).some(cell => 
                cell.textContent.toLowerCase().includes(value)
            );
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function initExportButtons() {
    document.querySelectorAll('.export-table').forEach(btn => {
        btn.addEventListener('click', function() {
            const tableId = this.dataset.table;
            const filename = this.dataset.filename || 'export.csv';
            exportTableToCSV(tableId, filename);
        });
    });
}

// =============================================
// PAGINATION
// =============================================
function initPagination(tableId, rowsPerPage = 10) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    if (pageCount <= 1) return;
    
    // Create pagination container
    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    pagination.innerHTML = `
        <button class="btn btn-sm btn-outline" data-page="prev" disabled>
            <i class="fas fa-chevron-left"></i> Previous
        </button>
        <span class="page-info">Page 1 of ${pageCount}</span>
        <button class="btn btn-sm btn-outline" data-page="next">
            Next <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    table.parentNode.appendChild(pagination);
    
    let currentPage = 1;
    
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        
        pagination.querySelector('.page-info').textContent = `Page ${page} of ${pageCount}`;
        pagination.querySelector('[data-page="prev"]').disabled = page === 1;
        pagination.querySelector('[data-page="next"]').disabled = page === pageCount;
        
        currentPage = page;
    }
    
    pagination.querySelector('[data-page="prev"]').addEventListener('click', () => {
        if (currentPage > 1) showPage(currentPage - 1);
    });
    
    pagination.querySelector('[data-page="next"]').addEventListener('click', () => {
        if (currentPage < pageCount) showPage(currentPage + 1);
    });
    
    showPage(1);
}

// =============================================
// ROW EXPAND/COLLAPSE
// =============================================
function initExpandableRows() {
    document.querySelectorAll('.expandable-row').forEach(row => {
        row.addEventListener('click', function() {
            const details = this.nextElementSibling;
            if (details && details.classList.contains('row-details')) {
                details.classList.toggle('show');
                this.classList.toggle('expanded');
            }
        });
    });
}

// =============================================
// SELECT ALL CHECKBOXES
// =============================================
function initSelectAll() {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;
    
    selectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.select-item');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
    
    document.querySelectorAll('.select-item').forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.select-item:checked').length;
            const total = document.querySelectorAll('.select-item').length;
            selectAll.checked = allChecked === total;
            selectAll.indeterminate = allChecked > 0 && allChecked < total;
        });
    });
}

// =============================================
// TABLE COLUMN TOGGLE
// =============================================
function initColumnToggle() {
    const toggleBtn = document.getElementById('toggle-columns');
    if (!toggleBtn) return;
    
    toggleBtn.addEventListener('click', function() {
        const menu = document.getElementById('column-menu');
        if (menu) {
            menu.classList.toggle('show');
        }
    });
    
    document.querySelectorAll('.column-toggle').forEach(cb => {
        cb.addEventListener('change', function() {
            const column = this.dataset.column;
            const table = document.getElementById(this.dataset.table);
            
            if (table) {
                table.querySelectorAll(`td:nth-child(${column}), th:nth-child(${column})`).forEach(cell => {
                    cell.style.display = this.checked ? '' : 'none';
                });
            }
        });
    });
}