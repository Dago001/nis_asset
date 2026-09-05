/**
 * API Interaction JavaScript
 */

const API = {
    baseUrl: (document.querySelector('meta[name="app-base"]')?.getAttribute('content'))
        || window.location.origin,
    
    async get(endpoint, params = {}) {
        const url = new URL(`${this.baseUrl}/api/${endpoint}`);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        return this.handleResponse(response);
    },
    
    async post(endpoint, data = {}) {
        const formData = new FormData();
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        // Add data
        Object.keys(data).forEach(key => {
            if (data[key] instanceof FileList) {
                Array.from(data[key]).forEach(file => formData.append(`${key}[]`, file));
            } else if (Array.isArray(data[key])) {
                data[key].forEach(item => formData.append(`${key}[]`, item));
            } else {
                formData.append(key, data[key]);
            }
        });
        
        const response = await fetch(`${this.baseUrl}/api/${endpoint}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        return this.handleResponse(response);
    },
    
    async handleResponse(response) {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        }
        
        return await response.text();
    },
    
    // Asset specific endpoints
    async getWeapons(search = '') {
        return this.get('get_weapons.php', { search });
    },
    
    async getAmmunition(search = '') {
        return this.get('get_ammunition.php', { search });
    },
    
    async getRequisitions(filters = {}) {
        return this.get('get_requisitions.php', filters);
    },
    
    async getReturns(filters = {}) {
        return this.get('get_returns_log.php', filters);
    },
    
    async getDashboardStats() {
        return this.get('dashboard_stats.php');
    },
    
    async updateInventory(type, id, field, value) {
        return this.post('update_inventory.php', { type, id, field, value });
    },
    
    async validateSerial(type, serial, excludeId = null) {
        return this.get('validate_serial.php', { type, serial, exclude_id: excludeId });
    },
    
    async getLGAs(stateId) {
        return this.get('get_lgas.php', { state_id: stateId });
    },
    
    async getCommands(zoneId) {
        return this.get('get_commands.php', { zone_id: zoneId });
    }
};

// Initialize API helpers
document.addEventListener('DOMContentLoaded', function() {
    // Search inputs
    document.querySelectorAll('.api-search').forEach(input => {
        let timeout;
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            
            timeout = setTimeout(async () => {
                const type = this.dataset.type;
                const value = this.value;
                const resultsContainer = document.getElementById(this.dataset.results);
                
                if (!resultsContainer) return;
                
                try {
                    let results;
                    if (type === 'weapons') {
                        results = await API.getWeapons(value);
                    } else if (type === 'ammunition') {
                        results = await API.getAmmunition(value);
                    }
                    
                    if (results?.success) {
                        displaySearchResults(results.data, resultsContainer, this.dataset);
                    }
                } catch (error) {
                    console.error('Search failed:', error);
                }
            }, 300);
        });
    });
});

function displaySearchResults(results, container, dataset) {
    container.innerHTML = '';
    
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="no-results">No results found</div>';
        return;
    }
    
    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'search-result-item';
        div.setAttribute('data-id', item.id);
        
        if (dataset.displayField) {
            div.textContent = item[dataset.displayField];
        } else {
            div.textContent = item.name || item.weapon_id || item.ammo_id || 'Unknown';
        }
        
        div.addEventListener('click', function() {
            const targetInput = document.getElementById(dataset.target);
            if (targetInput) {
                targetInput.value = this.textContent;
                targetInput.setAttribute('data-id', this.dataset.id);
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                targetInput.dispatchEvent(event);
            }
            container.innerHTML = '';
        });
        
        container.appendChild(div);
    });
}