/**
 * NIS Asset Management System
 * Weapon Issue Module JavaScript
 */

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeWeaponIssue();
});

function initializeWeaponIssue() {
    // Set default dates
    setDefaultDates();
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Initialize search functionality
    initializeSearch();

    // Auto-populate when URL contains requisition_id parameter
    const urlParams = new URLSearchParams(window.location.search);
    const reqIdFromUrl = urlParams.get('requisition_id');
    if (reqIdFromUrl) {
        const weaponReqSelect = document.getElementById('requisition_id');
        const ammoReqSelect = document.getElementById('ammo_requisition_id');
        
        if (weaponReqSelect) {
            weaponReqSelect.value = reqIdFromUrl;
            loadRequisitionDetails(reqIdFromUrl, 'weapon');
        }
        if (ammoReqSelect) {
            ammoReqSelect.value = reqIdFromUrl;
            loadRequisitionDetails(reqIdFromUrl, 'ammo');
        }
    }
}

function setDefaultDates() {
    const today = new Date().toISOString().split('T')[0];
    
    const issueDateFields = ['issue_date', 'ammo_issue_date'];
    issueDateFields.forEach(id => {
        const field = document.getElementById(id);
        if (field && !field.value) {
            field.value = today;
        }
    });
    
    // Set default return date to 30 days from now
    const returnDate = document.getElementById('expected_return_date');
    if (returnDate && !returnDate.value) {
        const date = new Date();
        date.setDate(date.getDate() + 30);
        returnDate.value = date.toISOString().split('T')[0];
    }
}

function initializeEventListeners() {
    // Purpose other field toggles
    const purposeSelect = document.getElementById('purpose');
    if (purposeSelect) {
        purposeSelect.addEventListener('change', function() {
            toggleOtherField(this, 'purposeOtherGroup');
        });
    }
    
    const ammoPurposeSelect = document.getElementById('ammo_purpose');
    if (ammoPurposeSelect) {
        ammoPurposeSelect.addEventListener('change', function() {
            toggleOtherField(this, 'ammoPurposeOtherGroup');
        });
    }
    
    // Weapon select change
    const weaponSelect = document.getElementById('weapon_id');
    if (weaponSelect) {
        weaponSelect.addEventListener('change', function() {
            updateWeaponDetails(this);
        });
    }
    
    // Ammo select change
    const ammoSelect = document.getElementById('ammo_id');
    if (ammoSelect) {
        ammoSelect.addEventListener('change', function() {
            updateAmmoDetails(this);
            calculateTotalRounds();
        });
    }
    
    // Units issued change
    const unitsInput = document.getElementById('units_issued');
    if (unitsInput) {
        unitsInput.addEventListener('input', calculateTotalRounds);
    }
    
    // Requisition selects
    const reqSelect = document.getElementById('requisition_id');
    if (reqSelect) {
        reqSelect.addEventListener('change', function() {
            loadRequisitionDetails(this.value, 'weapon');
        });
    }
    
    const ammoReqSelect = document.getElementById('ammo_requisition_id');
    if (ammoReqSelect) {
        ammoReqSelect.addEventListener('change', function() {
            loadRequisitionDetails(this.value, 'ammo');
        });
    }
}

function toggleOtherField(select, targetId) {
    const otherGroup = document.getElementById(targetId);
    if (!otherGroup) return;
    
    if (select.value === 'Other') {
        otherGroup.style.display = 'block';
    } else {
        otherGroup.style.display = 'none';
    }
}

function updateWeaponDetails(select) {
    const option = select.options[select.selectedIndex];
    
    const typeDisplay = document.getElementById('weaponTypeDisplay');
    const modelDisplay = document.getElementById('weaponModelDisplay');
    const serialDisplay = document.getElementById('weaponSerialDisplay');
    const calibreDisplay = document.getElementById('weaponCalibreDisplay');
    
    if (typeDisplay) typeDisplay.textContent = option.getAttribute('data-type') || '-';
    if (modelDisplay) modelDisplay.textContent = option.getAttribute('data-model') || '-';
    if (serialDisplay) serialDisplay.textContent = option.getAttribute('data-serial') || '-';
    if (calibreDisplay) calibreDisplay.textContent = option.getAttribute('data-calibre') || '-';
}

function updateAmmoDetails(select) {
    const option = select.options[select.selectedIndex];
    
    const typeDisplay = document.getElementById('ammoTypeDisplay');
    const calibreDisplay = document.getElementById('ammoCalibreDisplay');
    const balanceDisplay = document.getElementById('ammoBalanceDisplay');
    const unitsInput = document.getElementById('units_issued');
    
    const balance = parseInt(option.getAttribute('data-balance')) || 0;
    
    if (typeDisplay) typeDisplay.textContent = option.getAttribute('data-type') || '-';
    if (calibreDisplay) calibreDisplay.textContent = option.getAttribute('data-calibre') || '-';
    if (balanceDisplay) balanceDisplay.textContent = balance;
    
    if (unitsInput) {
        unitsInput.max = balance;
        if (parseInt(unitsInput.value) > balance) {
            unitsInput.value = balance;
        }
    }
}

function calculateTotalRounds() {
    const units = document.getElementById('units_issued')?.value;
    const totalField = document.getElementById('total_rounds');
    
    if (units && totalField) {
        const roundsPerUnit = 30; // Standard
        totalField.value = parseInt(units) * roundsPerUnit;
    }
}

function loadRequisitionDetails(requisitionId, type) {
    const select = document.getElementById(type === 'weapon' ? 'requisition_id' : 'ammo_requisition_id');
    if (!select) return;
    
    if (!requisitionId) {
        if (type === 'weapon') {
            const officerField = document.getElementById('officer_name');
            const rankField = document.getElementById('officer_rank');
            const nisField = document.getElementById('officer_nis');
            const unitField = document.getElementById('unit');
            const purposeSelect = document.getElementById('purpose');
            const purposeOther = document.getElementById('purpose_other');
            const purposeOtherGroup = document.getElementById('purposeOtherGroup');
            const approvedByField = document.getElementById('approved_by');

            if (officerField) officerField.value = '';
            if (rankField) rankField.selectedIndex = 0;
            if (nisField) nisField.value = '';
            if (unitField) unitField.value = '';
            if (purposeSelect) purposeSelect.selectedIndex = 0;
            if (purposeOther) purposeOther.value = '';
            if (purposeOtherGroup) purposeOtherGroup.style.display = 'none';
            if (approvedByField) approvedByField.value = '';
            const qtyField = document.getElementById('weapon_quantity') || document.getElementById('quantity');
            if (qtyField) qtyField.value = 1;
        } else {
            const issuedTo = document.getElementById('issued_to');
            const ammoPurpose = document.getElementById('ammo_purpose');
            const ammoPurposeOther = document.getElementById('ammo_purpose_other');
            const ammoPurposeOtherGroup = document.getElementById('ammoPurposeOtherGroup');
            const ammoApprovedBy = document.getElementById('ammo_approved_by');

            if (issuedTo) issuedTo.value = '';
            if (ammoPurpose) ammoPurpose.selectedIndex = 0;
            if (ammoPurposeOther) ammoPurposeOther.value = '';
            if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'none';
            if (ammoApprovedBy) ammoApprovedBy.value = '';
            const unitsField = document.getElementById('units_issued');
            if (unitsField) {
                unitsField.value = 1;
                if (typeof calculateTotalRounds === 'function') {
                    calculateTotalRounds();
                }
            }
        }
        return;
    }
    
    const option = select.options[select.selectedIndex];
    if (!option) return;
    
    const officer = option.getAttribute('data-officer') || '';
    const rank = option.getAttribute('data-rank') || '';
    const nis = option.getAttribute('data-nis') || '';
    const unit = option.getAttribute('data-unit') || '';
    const purpose = option.getAttribute('data-purpose') || '';
    const purposeOther = option.getAttribute('data-purpose-other') || '';
    const approvedBy = option.getAttribute('data-approved-by') || '';
    
    if (type === 'weapon') {
        const officerField = document.getElementById('officer_name');
        const rankField = document.getElementById('officer_rank');
        const nisField = document.getElementById('officer_nis');
        const unitField = document.getElementById('unit');
        const purposeSelect = document.getElementById('purpose');
        const purposeOtherInput = document.getElementById('purpose_other');
        const purposeOtherGroup = document.getElementById('purposeOtherGroup');
        const approvedByField = document.getElementById('approved_by');
        const remWeapons = option.getAttribute('data-remaining-weapons') || option.getAttribute('data-total-weapons') || '';
        const qtyField = document.getElementById('weapon_quantity') || document.getElementById('quantity');
        
        if (officerField) officerField.value = officer;
        if (nisField) nisField.value = nis;
        if (unitField) unitField.value = unit;
        if (approvedByField) approvedByField.value = approvedBy;
        if (qtyField && remWeapons && parseInt(remWeapons) > 0) {
            qtyField.value = parseInt(remWeapons);
        }
        
        // Rank selection matching
        if (rankField && rank) {
            const rLower = rank.trim().toLowerCase();
            let matchedRank = false;

            // 1. Exact match on value or text
            for (let i = 0; i < rankField.options.length; i++) {
                const val = (rankField.options[i].value || '').trim().toLowerCase();
                const txt = (rankField.options[i].text || '').trim().toLowerCase();
                if (val === rLower || txt === rLower) {
                    rankField.selectedIndex = i;
                    matchedRank = true;
                    break;
                }
            }

            // 2. Match abbreviation in parentheses (e.g. "SI", "ASI-1")
            if (!matchedRank) {
                for (let i = 0; i < rankField.options.length; i++) {
                    const optStr = rankField.options[i].value || rankField.options[i].text || '';
                    const parenMatch = optStr.match(/\(([^)]+)\)/);
                    if (parenMatch) {
                        const abbr = parenMatch[1].trim().toLowerCase();
                        if (abbr === rLower || abbr.replace(/[^a-z0-9]/g, '') === rLower.replace(/[^a-z0-9]/g, '')) {
                            rankField.selectedIndex = i;
                            matchedRank = true;
                            break;
                        }
                    }
                }
            }

            // 3. Clean alphanumeric match
            if (!matchedRank) {
                const rClean = rLower.replace(/[^a-z0-9]/g, '');
                for (let i = 0; i < rankField.options.length; i++) {
                    const valClean = (rankField.options[i].value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (valClean && valClean === rClean) {
                        rankField.selectedIndex = i;
                        matchedRank = true;
                        break;
                    }
                }
            }

            if (!matchedRank) {
                rankField.value = rank;
            }
        }
        
        // Purpose & Specify Purpose matching
        if (purposeSelect) {
            let matched = false;
            if (purpose && !purposeOther) {
                for (let i = 0; i < purposeSelect.options.length; i++) {
                    const optVal = purposeSelect.options[i].value;
                    if (optVal && optVal.toLowerCase() === purpose.toLowerCase()) {
                        purposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                    if (optVal && (optVal.toLowerCase().includes(purpose.toLowerCase()) || purpose.toLowerCase().includes(optVal.toLowerCase()))) {
                        purposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            
            if (purposeOther) {
                purposeSelect.value = 'Other';
                if (purposeOtherInput) purposeOtherInput.value = purposeOther;
                if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
            } else if (matched) {
                if (purposeSelect.value === 'Other') {
                    if (purposeOtherInput) purposeOtherInput.value = purpose;
                    if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
                } else {
                    if (purposeOtherInput) purposeOtherInput.value = '';
                    if (purposeOtherGroup) purposeOtherGroup.style.display = 'none';
                }
            } else if (purpose) {
                purposeSelect.value = 'Other';
                if (purposeOtherInput) purposeOtherInput.value = purpose;
                if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
            }
        }
    } else {
        const issuedTo = document.getElementById('issued_to');
        const ammoApprovedBy = document.getElementById('ammo_approved_by');
        const ammoPurposeSelect = document.getElementById('ammo_purpose');
        const ammoPurposeOtherInput = document.getElementById('ammo_purpose_other');
        const ammoPurposeOtherGroup = document.getElementById('ammoPurposeOtherGroup');
        
        if (issuedTo) issuedTo.value = officer;
        if (ammoApprovedBy) ammoApprovedBy.value = approvedBy;
        
        if (ammoPurposeSelect) {
            let matched = false;
            if (purpose && !purposeOther) {
                for (let i = 0; i < ammoPurposeSelect.options.length; i++) {
                    const optVal = ammoPurposeSelect.options[i].value;
                    if (optVal && optVal.toLowerCase() === purpose.toLowerCase()) {
                        ammoPurposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                    if (optVal && (optVal.toLowerCase().includes(purpose.toLowerCase()) || purpose.toLowerCase().includes(optVal.toLowerCase()))) {
                        ammoPurposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            
            if (purposeOther) {
                ammoPurposeSelect.value = 'Other';
                if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purposeOther;
                if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
            } else if (matched) {
                if (ammoPurposeSelect.value === 'Other') {
                    if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purpose;
                    if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
                } else {
                    if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = '';
                    if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'none';
                }
            } else if (purpose) {
                ammoPurposeSelect.value = 'Other';
                if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purpose;
                if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
            }
        }

        const remAmmo = option.getAttribute('data-remaining-ammo') || option.getAttribute('data-total-ammo') || '';
        const unitsField = document.getElementById('units_issued');
        if (unitsField && remAmmo && parseInt(remAmmo) > 0) {
            unitsField.value = parseInt(remAmmo);
            if (typeof calculateTotalRounds === 'function') {
                calculateTotalRounds();
            }
        }
    }
}

function initializeSearch() {
    const searchInputs = [
        { id: 'searchReturns', tables: ['issuedWeaponsTable', 'issuedAmmoTable'] }
    ];
    
    searchInputs.forEach(item => {
        const input = document.getElementById(item.id);
        if (!input) return;
        
        input.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            item.tables.forEach(tableId => {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });
    });
}

// Form validation
function validateWeaponForm() {
    const weaponId = document.getElementById('weapon_id')?.value;
    const officerName = document.getElementById('officer_name')?.value;
    
    if (!weaponId) {
        alert('Please select a weapon');
        return false;
    }
    
    if (!officerName) {
        alert('Please enter officer name');
        return false;
    }
    
    return true;
}

function validateAmmoForm() {
    const ammoId = document.getElementById('ammo_id')?.value;
    const units = parseInt(document.getElementById('units_issued')?.value) || 0;
    const balance = parseInt(document.getElementById('ammo_id')?.options[document.getElementById('ammo_id')?.selectedIndex]?.getAttribute('data-balance') || 0);
    
    if (!ammoId) {
        alert('Please select ammunition');
        return false;
    }
    
    if (units <= 0) {
        alert('Please enter valid units to issue');
        return false;
    }
    
    if (units > balance) {
        alert('Cannot issue more than available balance');
        return false;
    }
    
    return true;
}

// Reset forms
function resetWeaponForm() {
    if (!confirm('Reset weapon issue form?')) return;
    
    const form = document.getElementById('weaponIssueForm');
    if (form) {
        form.reset();
        
        // Reset weapon details display
        document.getElementById('weaponTypeDisplay').textContent = '-';
        document.getElementById('weaponModelDisplay').textContent = '-';
        document.getElementById('weaponSerialDisplay').textContent = '-';
        document.getElementById('weaponCalibreDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('purposeOtherGroup').style.display = 'none';
    }
}

function resetAmmoForm() {
    if (!confirm('Reset ammunition issue form?')) return;
    
    const form = document.getElementById('ammoIssueForm');
    if (form) {
        form.reset();
        
        // Reset ammo details display
        document.getElementById('ammoTypeDisplay').textContent = '-';
        document.getElementById('ammoCalibreDisplay').textContent = '-';
        document.getElementById('ammoBalanceDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('ammoPurposeOtherGroup').style.display = 'none';
        
        // Reset total rounds
        document.getElementById('total_rounds').value = '30';
    }
}

// Tab switching
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const tab = document.getElementById('tab-' + tabName);
    if (tab) tab.classList.add('active');
    
    const btn = event?.target.closest('.tab-btn');
    if (btn) btn.classList.add('active');
}

// Issue type switching
function showIssueType(type) {
    document.querySelectorAll('.issue-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const form = document.getElementById(type + '-issue-form');
    if (form) form.classList.add('active');
    
    const btn = event?.target.closest('.type-btn');
    if (btn) btn.classList.add('active');
}

// Filter functions for history page
function applyFilters() {
    const status = document.getElementById('filterStatus')?.value.toLowerCase();
    const dateFrom = document.getElementById('filterDateFrom')?.value;
    const dateTo = document.getElementById('filterDateTo')?.value;
    const searchTerm = document.getElementById('searchHistory')?.value.toLowerCase();
    
    // Filter weapons table
    const weaponRows = document.querySelectorAll('#weaponHistoryTable tbody tr');
    weaponRows.forEach(row => {
        let show = true;
        
        if (status) {
            const rowStatus = row.dataset.status?.toLowerCase() || '';
            show = show && rowStatus === status;
        }
        
        if (dateFrom) {
            const rowDate = row.dataset.date;
            show = show && rowDate >= dateFrom;
        }
        
        if (dateTo) {
            const rowDate = row.dataset.date;
            show = show && rowDate <= dateTo;
        }
        
        if (searchTerm) {
            const text = row.textContent.toLowerCase();
            show = show && text.includes(searchTerm);
        }
        
        row.style.display = show ? '' : 'none';
    });
    
    // Filter ammo table
    const ammoRows = document.querySelectorAll('#ammoHistoryTable tbody tr');
    ammoRows.forEach(row => {
        let show = true;
        
        if (status) {
            const rowStatus = row.dataset.status?.toLowerCase() || '';
            show = show && rowStatus === status;
        }
        
        if (dateFrom) {
            const rowDate = row.dataset.date;
            show = show && rowDate >= dateFrom;
        }
        
        if (dateTo) {
            const rowDate = row.dataset.date;
            show = show && rowDate <= dateTo;
        }
        
        if (searchTerm) {
            const text = row.textContent.toLowerCase();
            show = show && text.includes(searchTerm);
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function resetFilters() {
    const filterIds = ['filterStatus', 'filterDateFrom', 'filterDateTo', 'searchHistory'];
    filterIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    
    document.querySelectorAll('#weaponHistoryTable tbody tr, #ammoHistoryTable tbody tr').forEach(row => {
        row.style.display = '';
    });
}