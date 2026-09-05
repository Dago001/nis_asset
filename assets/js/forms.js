/**
 * Form Handling JavaScript
 */

// =============================================
// DYNAMIC DROPDOWNS
// =============================================
function initDynamicDropdowns() {
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    const stateSelect = document.getElementById('state_id');
    const lgaSelect = document.getElementById('lga_id');

    // Helper to fetch JSON with fallbacks
    function fetchJsonData(urlPrimary, urlFallback) {
        const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
        return fetch(urlPrimary, { headers })
            .then(res => {
                if (!res.ok) throw new Error('Primary API error ' + res.status);
                return res.json();
            })
            .catch(() => {
                return fetch(urlFallback, { headers }).then(res => res.json());
            });
    }

    // Helper to toggle manual command input
    function handleCommandOtherToggle() {
        if (!commandSelect) return;
        let wrapper = document.getElementById('commandOtherWrapper');
        let input = document.getElementById('command_other');

        if (!wrapper && commandSelect.parentElement) {
            wrapper = document.createElement('div');
            wrapper.id = 'commandOtherWrapper';
            wrapper.className = 'form-group';
            wrapper.style.marginTop = '10px';
            wrapper.style.display = 'none';
            wrapper.innerHTML = `
                <label class="required font-medium" style="margin-top: 6px; display: block; font-size: 0.85rem; color: #475569;">Specify Command / Formation Name</label>
                <input type="text" name="command_other" id="command_other" class="form-control" placeholder="Enter custom command or formation name">
            `;
            commandSelect.parentElement.appendChild(wrapper);
            input = document.getElementById('command_other');
        }

        if (commandSelect.value === 'Other') {
            if (wrapper) wrapper.style.display = 'block';
            if (input) input.required = true;
        } else {
            if (wrapper) wrapper.style.display = 'none';
            if (input) {
                input.required = false;
                input.value = '';
            }
        }
    }

    // 1. Zone -> Command
    if (zoneSelect && commandSelect) {
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            if (!zoneId) {
                commandSelect.innerHTML = '<option value="">Select Zone First</option>';
                handleCommandOtherToggle();
                return;
            }

            const appBase = document.querySelector('meta[name="app-base"]')?.getAttribute('content') || '';
            const urlPrimary = (appBase ? appBase : '') + `/api/get_commands?zone_id=${zoneId}`;
            const urlFallback = (appBase ? appBase : '') + `/api/get_commands.php?zone_id=${zoneId}`;

            fetchJsonData(urlPrimary, urlFallback)
                .then(commands => {
                    commandSelect.innerHTML = '<option value="">Select Command / Directorate</option>';
                    if (Array.isArray(commands) && commands.length > 0) {
                        commands.forEach(cmd => {
                            const option = document.createElement('option');
                            option.value = cmd.id;
                            option.textContent = cmd.command_name;
                            if (cmd.state_id) option.dataset.stateId = cmd.state_id;
                            if (cmd.lga_id) option.dataset.lgaId = cmd.lga_id;
                            commandSelect.appendChild(option);
                        });
                    }
                    // Always append "Others" option
                    const otherOpt = document.createElement('option');
                    otherOpt.value = 'Other';
                    otherOpt.textContent = 'Others (Specify below)';
                    commandSelect.appendChild(otherOpt);
                    handleCommandOtherToggle();
                })
                .catch(err => {
                    console.error('Error fetching commands:', err);
                    commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                    const otherOpt = document.createElement('option');
                    otherOpt.value = 'Other';
                    otherOpt.textContent = 'Others (Specify below)';
                    commandSelect.appendChild(otherOpt);
                    handleCommandOtherToggle();
                });
        });
    }

    // 2. Command -> Auto preselect State & Toggle "Others" input
    if (commandSelect) {
        commandSelect.addEventListener('change', function() {
            handleCommandOtherToggle();
            if (stateSelect) {
                const selectedOpt = this.options[this.selectedIndex];
                if (selectedOpt && selectedOpt.dataset.stateId && (!stateSelect.value || stateSelect.value === '0')) {
                    stateSelect.value = selectedOpt.dataset.stateId;
                    stateSelect.dispatchEvent(new Event('change'));
                }
            }
        });
    }

    // 3. State -> LGA (Populates all states including FCT)
    if (stateSelect && lgaSelect) {
        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select State First</option>';
                return;
            }

            const appBase = document.querySelector('meta[name="app-base"]')?.getAttribute('content') || '';
            const urlPrimary = (appBase ? appBase : '') + `/api/get_lgas?state_id=${stateId}`;
            const urlFallback = (appBase ? appBase : '') + `/api/get_lgas.php?state_id=${stateId}`;

            fetchJsonData(urlPrimary, urlFallback)
                .then(lgas => {
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    if (Array.isArray(lgas) && lgas.length > 0) {
                        lgas.forEach(lga => {
                            const option = document.createElement('option');
                            option.value = lga.id;
                            option.textContent = lga.lga_name;
                            lgaSelect.appendChild(option);
                        });
                    } else {
                        lgaSelect.innerHTML = '<option value="">No LGAs found</option>';
                    }
                })
                .catch(err => {
                    console.error('Error fetching LGAs:', err);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });
    }
}

// =============================================
// FILE UPLOAD AREA
// =============================================
function initFileUpload() {
    const uploadAreas = document.querySelectorAll('.file-upload-area');
    
    uploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        const fileList = area.nextElementSibling;
        
        if (!input || !fileList) return;
        
        // Click to upload
        area.addEventListener('click', () => input.click());
        
        // File selection
        input.addEventListener('change', function() {
            handleFiles(this.files, fileList);
        });
        
        // Drag and drop
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFiles(e.dataTransfer.files, fileList);
            }
        });
    });
}

function handleFiles(files, container) {
    container.innerHTML = '';
    
    Array.from(files).forEach(file => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        
        const fileSize = (file.size / 1024).toFixed(2);
        const icon = getFileIcon(file.type);
        
        fileItem.innerHTML = `
            <div class="file-info">
                <i class="fas ${icon}"></i>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${fileSize} KB)</span>
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(fileItem);
    });
}

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'fa-file-pdf';
    if (mimeType.includes('image')) return 'fa-file-image';
    if (mimeType.includes('word')) return 'fa-file-word';
    if (mimeType.includes('excel')) return 'fa-file-excel';
    return 'fa-file';
}

// =============================================
// CALCULATE AMMUNITION BALANCE
// =============================================
function calculateAmmunitionBalance(row) {
    const received = row.querySelector('.quantity-received');
    const issued = row.querySelector('.quantity-issued');
    const balance = row.querySelector('.balance');
    
    if (received && issued && balance) {
        const receivedVal = parseInt(received.value) || 0;
        const issuedVal = parseInt(issued.value) || 0;
        const balanceVal = Math.max(0, receivedVal - issuedVal);
        
        balance.value = balanceVal;
    }
}

// =============================================
// CALCULATE AUDIT VARIANCE
// =============================================
function calculateAuditVariance(row) {
    const system = row.querySelector('.system-units');
    const physical = row.querySelector('.physical-units');
    const variance = row.querySelector('.variance-display');
    const varianceValue = row.querySelector('.variance-value');
    
    if (system && physical && variance && varianceValue) {
        const systemVal = parseInt(system.value) || 0;
        const physicalVal = parseInt(physical.value) || 0;
        const varianceVal = physicalVal - systemVal;
        
        variance.value = varianceVal;
        varianceValue.value = varianceVal;
        
        // Color code
        if (varianceVal < 0) {
            variance.style.color = '#e74c3c';
            variance.style.fontWeight = 'bold';
        } else if (varianceVal > 0) {
            variance.style.color = '#207027';
            variance.style.fontWeight = 'bold';
        } else {
            variance.style.color = '#134617';
            variance.style.fontWeight = 'normal';
        }
    }
}

// =============================================
// DYNAMIC ROW ADDITION
// =============================================
function addTableRow(tableId, templateRow) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const newRow = templateRow.cloneNode(true);
    const rowCount = tbody.children.length + 1;
    
    // Update S/N
    newRow.querySelector('.sn').textContent = rowCount;
    
    // Clear inputs
    newRow.querySelectorAll('input:not([type="hidden"]), select').forEach(field => {
        field.value = '';
    });
    
    tbody.appendChild(newRow);
    updateRemoveButtons(tableId);
}

function removeTableRow(button, tableId) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    
    if (tbody.children.length > 1) {
        row.remove();
        updateRowNumbers(tbody);
        updateRemoveButtons(tableId);
    } else {
        showNotification('warning', 'At least one row is required');
    }
}

function updateRowNumbers(tbody) {
    Array.from(tbody.children).forEach((row, index) => {
        const snCell = row.querySelector('.sn');
        if (snCell) snCell.textContent = index + 1;
    });
}

function updateRemoveButtons(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const removeButtons = table.querySelectorAll('.remove-row');
    
    if (rows.length <= 1) {
        removeButtons.forEach(btn => btn.disabled = true);
    } else {
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

// =============================================
// FORM RESET
// =============================================
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        form.reset();
        
        // Clear file previews
        const fileLists = form.querySelectorAll('.file-list');
        fileLists.forEach(list => list.innerHTML = '');
        
        // Reset dynamic selects
        const lgaSelect = document.getElementById('lga_id');
        if (lgaSelect) lgaSelect.innerHTML = '<option value="">Select State First</option>';
        
        const commandSelect = document.getElementById('command_id');
        if (commandSelect) commandSelect.innerHTML = '<option value="">Select Zone First</option>';
        
        showNotification('info', 'Form has been reset');
    }
}