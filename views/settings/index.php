<?php
/**
 * System Settings — Professional UI
 * Uses the RESTful API at /api/v1/settings for all operations.
 *
 * IMPORTANT: $groups is the single source of truth for group order (it
 * deliberately puts 'general' first, then the rest alphabetically — see
 * SettingsModel::getGroups()). Both the tab buttons AND the panels below
 * iterate $groups (looking settings up per group via $settings[$key]),
 * so the "active" tab and the visible panel can never drift apart again.
 * A previous version iterated $settings (alphabetical, from the raw SQL
 * order) for panel visibility and $groups for the tabs — two different
 * orderings meant the highlighted tab and the shown panel didn't match.
 */
$title  = 'System Settings';
$active = 'settings';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/nis_ams');
}

if (!isset($settings)) {
    header('Location: ' . BASE_URL . '/settings');
    exit;
}

$extra_css = [BASE_URL . '/assets/css/settings.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// CSRF token — use the Security class for consistency
// The session is already started by Session::init() in config.php.
$csrfToken = Security::csrfToken();

// Flash messages
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Single source of truth for "which group renders first / starts active".
$firstGroupKey = array_key_first($groups) ?? null;

/* ------------------------------------------------------------------ *
 * Helper functions
 * ------------------------------------------------------------------ */
function getGroupIcon(string $group): string {
    return [
        'general'        => 'cog',
        'security'       => 'shield-halved',
        'email'          => 'envelope',
        'notification'   => 'bell',
        'payment'        => 'credit-card',
        'api'            => 'plug',
        'backup'         => 'database',
        'maintenance'    => 'screwdriver-wrench',
        'customization'  => 'palette',
        'integration'    => 'link',
        'system'         => 'server',
    ][$group] ?? 'cog';
}

function getGroupLabel(string $group): string {
    return ucwords(str_replace('_', ' ', $group));
}
?>

<!-- ============================================================
     TOAST CONTAINER
     ============================================================ -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;"></div>

<div class="container-fluid settings-page">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-cogs" style="color:var(--success-color);"></i>
                System Settings
            </h1>
            <p>Manage all system-wide configuration parameters</p>
        </div>
        <div class="header-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
            <button onclick="exportSettings()" class="btn-hdr btn-hdr-outline">
                <i class="fas fa-download"></i> Export
            </button>
            <button onclick="openImportModal()" class="btn-hdr btn-hdr-outline">
                <i class="fas fa-upload"></i> Import
            </button>
            <button onclick="openAddModal()" class="btn-hdr btn-hdr-green">
                <i class="fas fa-plus-circle"></i> Add Setting
            </button>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
    <div class="alert-flash alert-flash-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flashSuccess); ?>
    </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="alert-flash alert-flash-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($flashError); ?>
    </div>
    <?php endif; ?>

    <!-- Settings Layout: tabs left, panel right -->
    <div class="settings-layout">

        <!-- LEFT SIDEBAR TABS -->
        <nav class="settings-tabs">
            <?php foreach ($groups as $groupKey => $groupName): ?>
            <button
                type="button"
                class="settings-tab-btn <?php echo $groupKey === $firstGroupKey ? 'active' : ''; ?>"
                onclick="switchGroup('<?php echo $groupKey; ?>', this)"
                data-group="<?php echo $groupKey; ?>"
            >
                <i class="fas fa-<?php echo getGroupIcon($groupKey); ?>"></i>
                <span><?php echo htmlspecialchars($groupName); ?></span>
                <span class="tab-count"><?php echo count($settings[$groupKey] ?? []); ?></span>
            </button>
            <?php endforeach; ?>
        </nav>

        <!-- RIGHT PANEL -->
        <div class="settings-panel">
            <?php foreach ($groups as $groupKey => $groupName):
                $groupSettings = $settings[$groupKey] ?? [];
            ?>
            <div
                id="group-<?php echo $groupKey; ?>"
                class="settings-group-panel"
                style="display:<?php echo $groupKey === $firstGroupKey ? 'block' : 'none'; ?>;"
                data-group="<?php echo $groupKey; ?>"
            >
                <!-- Group Header -->
                <div class="group-header">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="group-icon-wrap">
                            <i class="fas fa-<?php echo getGroupIcon($groupKey); ?>"></i>
                        </div>
                        <div>
                            <h2 class="group-title">
                                <?php echo htmlspecialchars($groupName); ?> Settings
                            </h2>
                            <p class="group-subtitle"><?php echo count($groupSettings); ?> configuration item<?php echo count($groupSettings) === 1 ? '' : 's'; ?></p>
                        </div>
                    </div>
                    <?php if (!empty($groupSettings)): ?>
                    <button
                        type="button"
                        class="btn-hdr btn-hdr-green"
                        onclick="saveGroupSettings('<?php echo $groupKey; ?>')"
                        id="save-btn-<?php echo $groupKey; ?>"
                        style="font-size:0.85rem;padding:8px 18px;"
                    >
                        <i class="fas fa-save"></i> Save <?php echo htmlspecialchars($groupName); ?>
                    </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($groupSettings)): ?>
                <div class="empty-group-state">
                    <i class="fas fa-inbox"></i>
                    <p>No settings in this group yet.</p>
                </div>
                <?php else: ?>
                <!-- Settings Table -->
                <div class="settings-table-wrap">
                    <table class="settings-table">
                        <thead>
                            <tr>
                                <th style="width:28%">Setting Key</th>
                                <th style="width:32%">Value</th>
                                <th style="width:12%">Type</th>
                                <th style="width:18%">Description</th>
                                <th style="width:10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-<?php echo $groupKey; ?>">
                            <?php foreach ($groupSettings as $setting): ?>
                            <tr id="row-<?php echo htmlspecialchars($setting['setting_key']); ?>" data-group="<?php echo $groupKey; ?>">
                                <td>
                                    <code class="setting-key"><?php echo htmlspecialchars($setting['setting_key']); ?></code>
                                    <?php if ($setting['is_encrypted']): ?>
                                        <span class="badge badge-warning" title="Encrypted at rest"><i class="fas fa-lock"></i> Encrypted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="value-cell">
                                    <?php echo renderSettingInput($setting); ?>
                                </td>
                                <td>
                                    <span class="badge badge-blue"><?php echo htmlspecialchars($setting['data_type']); ?></span>
                                </td>
                                <td class="desc-cell">
                                    <?php echo htmlspecialchars($setting['description'] ?? ''); ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <?php if ($setting['setting_key'] !== 'app_url'): ?>
                                        <button
                                            type="button"
                                            class="icon-btn"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($setting)); ?>)"
                                            title="Edit metadata"
                                        ><i class="fas fa-pen-to-square"></i></button>
                                        <?php endif; ?>
                                        <?php
                                        $protected = ['app_name','app_version','app_url','encryption_key','company_name'];
                                        if (!in_array($setting['setting_key'], $protected, true)):
                                        ?>
                                        <button
                                            type="button"
                                            class="icon-btn icon-btn-danger"
                                            onclick="deleteSetting('<?php echo htmlspecialchars($setting['setting_key']); ?>')"
                                            title="Delete"
                                        ><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ($groupKey === 'email'): ?>
                <!-- SMTP Test Section -->
                <div class="smtp-test-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <i class="fas fa-paper-plane" style="color:var(--success-color);font-size:1.2rem;"></i>
                        <h3 style="margin:0;font-size:1rem;">Test SMTP Configuration</h3>
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:12px;">Send a test email to verify your SMTP settings are working correctly. This saves the form above first.</p>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <input
                            type="email"
                            id="smtpTestEmail"
                            placeholder="recipient@example.com"
                            class="smtp-test-input"
                        >
                        <button type="button" onclick="testSmtp()" class="btn-hdr btn-hdr-green" style="white-space:nowrap;">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                    <div id="smtpTestResult" style="margin-top:10px;display:none;"></div>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<!-- ================================================================
     MODAL: ADD NEW SETTING
     ================================================================ -->
<div class="modal-overlay" id="addModal" style="display:none;">
    <div class="modal-box" style="max-width:620px;">
        <div class="modal-hdr">
            <h3><i class="fas fa-plus-circle"></i> Add New Setting</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('addModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Key <span class="req">*</span></label>
                    <input type="text" id="add_key" placeholder="e.g., feature_flag" pattern="[a-zA-Z0-9_\.]+" required>
                    <small>Letters, numbers, dots, underscores only</small>
                </div>
                <div class="form-group">
                    <label>Data Type <span class="req">*</span></label>
                    <select id="add_type" onchange="onAddTypeChange()">
                        <option value="string">String</option>
                        <option value="text">Text (long)</option>
                        <option value="integer">Integer</option>
                        <option value="float">Float</option>
                        <option value="boolean">Boolean (Yes/No)</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Group <span class="req">*</span></label>
                    <select id="add_group" onchange="onAddGroupChange()">
                        <?php foreach ($groups as $gk => $gn): ?>
                        <option value="<?php echo $gk; ?>"><?php echo htmlspecialchars($gn); ?></option>
                        <?php endforeach; ?>
                        <option value="__custom__">+ Custom Group…</option>
                    </select>
                </div>
                <div class="form-group" id="add_custom_group_wrap" style="display:none;">
                    <label>Custom Group Name</label>
                    <input type="text" id="add_custom_group" placeholder="e.g., payment_gateway">
                </div>
            </div>
            <div class="form-group">
                <label>Value <span class="req">*</span></label>
                <div id="add_value_wrap">
                    <input type="text" id="add_value" placeholder="Setting value">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="add_description" rows="2" placeholder="What does this setting control?"></textarea>
            </div>
            <div class="form-check-row">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="add_encrypted">
                    <span>Encrypt this value (for sensitive data like API keys)</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-hdr btn-hdr-outline" onclick="closeModal('addModal')">Cancel</button>
            <button type="button" class="btn-hdr btn-hdr-green" onclick="submitAddSetting(this)">
                <i class="fas fa-plus"></i> Add Setting
            </button>
        </div>
    </div>
</div>


<!-- ================================================================
     MODAL: EDIT SETTING METADATA
     ================================================================ -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="modal-box" style="max-width:620px;">
        <div class="modal-hdr">
            <h3><i class="fas fa-pen-to-square"></i> Edit Setting</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_key">
            <div class="form-group">
                <label>Key</label>
                <input type="text" id="edit_key_display" readonly class="readonly-field">
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Data Type</label>
                    <select id="edit_type" onchange="onEditTypeChange()">
                        <option value="string">String</option>
                        <option value="text">Text (long)</option>
                        <option value="integer">Integer</option>
                        <option value="float">Float</option>
                        <option value="boolean">Boolean (Yes/No)</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Group</label>
                    <input type="text" id="edit_group">
                </div>
            </div>
            <div class="form-group">
                <label>Value</label>
                <div id="edit_value_wrap">
                    <input type="text" id="edit_value" placeholder="Setting value">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="edit_description" rows="2"></textarea>
            </div>
            <div class="form-check-row">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="edit_encrypted">
                    <span>Encrypt this value</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-hdr btn-hdr-outline" onclick="closeModal('editModal')">Cancel</button>
            <button type="button" class="btn-hdr btn-hdr-green" onclick="submitEditSetting(this)">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>
</div>


<!-- ================================================================
     MODAL: IMPORT
     ================================================================ -->
<div class="modal-overlay" id="importModal" style="display:none;">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-hdr">
            <h3><i class="fas fa-upload"></i> Import Settings</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('importModal')">&times;</button>
        </div>
        <form id="importForm" method="POST" action="<?php echo BASE_URL; ?>/settings/import" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
            <div class="modal-body">
                <p style="color:var(--text-secondary);margin-bottom:15px;">Upload a JSON file previously exported from this system.</p>
                <div class="form-group">
                    <label>JSON File <span class="req">*</span></label>
                    <input type="file" name="import_file" accept=".json,application/json" required>
                </div>
                <div class="warning-box">
                    <i class="fas fa-triangle-exclamation"></i>
                    <strong>Warning:</strong> Importing will overwrite settings with matching keys.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-hdr btn-hdr-outline" onclick="closeModal('importModal')">Cancel</button>
                <button type="submit" class="btn-hdr btn-hdr-green">
                    <i class="fas fa-upload"></i> Import
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ================================================================
     STYLES
     All font-family / font-size values below are unchanged from the
     previous version — only colors were switched from hardcoded hex to
     the app's shared CSS variables (var(--surface), var(--text-primary),
     var(--border-color), ...) so this page follows light/dark theme
     automatically instead of needing page-specific dark-mode overrides.
     ================================================================ -->
<!-- Styles are loaded via $extra_css in <head> (assets/css/settings.css) to eliminate FOUC -->


<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->
<script>
const BASE_URL   = '<?php echo BASE_URL; ?>';
const CSRF_TOKEN = '<?php echo $csrfToken; ?>';

/* ------------------------------------------------------------------ */
/*  API helpers                                                         */
/* ------------------------------------------------------------------ */
async function apiRequest(method, url, body = null) {
    const opts = {
        method,
        headers: {
            'Content-Type':    'application/json',
            'Accept':          'application/json',
            'X-CSRF-Token':    CSRF_TOKEN,
            'X-Requested-With':'XMLHttpRequest',
        },
    };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(BASE_URL + url, opts);
    const data = await res.json().catch(() => ({ success: false, message: 'Server returned non-JSON response.' }));
    data._status = res.status;
    return data;
}

/* ------------------------------------------------------------------ */
/*  Toasts                                                              */
/* ------------------------------------------------------------------ */
function toast(msg, type = 'success') {
    const icons = { success: 'fa-check-circle', error: 'fa-circle-xmark', info: 'fa-circle-info' };
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span></span>`;
    el.querySelector('span').textContent = msg; // textContent, not innerHTML — msg can contain user/DB text
    el.onclick = () => el.remove();
    document.getElementById('toastContainer').appendChild(el);
    setTimeout(() => {
        el.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => el.remove(), 300);
    }, 4000);
}

/* ------------------------------------------------------------------ */
/*  Tab switching                                                       */
/* ------------------------------------------------------------------ */
function switchGroup(groupKey, btn) {
    document.querySelectorAll('.settings-group-panel').forEach(p => p.style.display = 'none');
    const panel = document.getElementById('group-' + groupKey);
    if (panel) panel.style.display = 'block';

    document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// Defensive self-check: whichever tab actually has .active on load should be
// the one whose panel is visible. If a future edit ever lets these drift
// again, this corrects it instead of silently showing the wrong panel.
document.addEventListener('DOMContentLoaded', function () {
    const activeTab = document.querySelector('.settings-tab-btn.active') || document.querySelector('.settings-tab-btn');
    if (activeTab) {
        switchGroup(activeTab.dataset.group, activeTab);
    }
});

/* ------------------------------------------------------------------ */
/*  Collect group values from the table inputs                         */
/* ------------------------------------------------------------------ */
function collectGroupSettings(groupKey) {
    const panel    = document.getElementById('group-' + groupKey);
    const rows     = panel.querySelectorAll('tbody tr[data-group]');
    const settings = {};

    rows.forEach(row => {
        const key   = row.id.replace('row-', '');
        const input = row.querySelector('input[type="text"], input[type="number"], input[type="email"], input[type="url"], input[type="password"], input[type="checkbox"].bool-toggle, textarea, select');
        if (!input) return;

        if (input.type === 'checkbox') {
            settings[key] = input.checked ? 1 : 0;
        } else {
            settings[key] = input.value;
        }
    });

    return settings;
}

/* ------------------------------------------------------------------ */
/*  Save a group (batch)                                               */
/* ------------------------------------------------------------------ */
async function saveGroupSettings(groupKey, opts = {}) {
    const { silent = false, reload = true } = opts;
    const btn = document.getElementById('save-btn-' + groupKey);
    if (btn) { btn.classList.add('btn-loading'); btn.disabled = true; }

    const settings = collectGroupSettings(groupKey);
    let res;

    try {
        const payload = { group: groupKey, settings };
        res = await apiRequest('POST', '/settings/save', payload);
        if (res.success) {
            if (!silent) {
                toast(res.message || 'Settings saved! Reloading...', 'success');
                if (reload) setTimeout(() => location.reload(), 800);
            }
        } else if (!silent) {
            toast(res.message || 'Save failed.', 'error');
        }
    } catch (err) {
        res = { success: false, message: 'Network error. Please try again.' };
        if (!silent) toast(res.message, 'error');
        console.error(err);
    } finally {
        if (btn) { btn.classList.remove('btn-loading'); btn.disabled = false; }
    }

    return res;
}

/* ------------------------------------------------------------------ */
/*  Export                                                              */
/* ------------------------------------------------------------------ */
function exportSettings() {
    window.location.href = BASE_URL + '/settings/export';
}

/* ------------------------------------------------------------------ */
/*  Import modal                                                        */
/* ------------------------------------------------------------------ */
function openImportModal() { openModal('importModal'); }

/* ------------------------------------------------------------------ */
/*  Modals                                                              */
/* ------------------------------------------------------------------ */
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

window.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

/* ------------------------------------------------------------------ */
/*  ADD setting                                                         */
/* ------------------------------------------------------------------ */
function openAddModal() {
    document.getElementById('add_key').value = '';
    document.getElementById('add_type').value = 'string';
    const firstGroupSelect = document.getElementById('add_group');
    if (firstGroupSelect.options.length) firstGroupSelect.selectedIndex = 0;
    document.getElementById('add_value').value = '';
    document.getElementById('add_description').value = '';
    document.getElementById('add_encrypted').checked = false;
    document.getElementById('add_custom_group_wrap').style.display = 'none';
    rebuildValueInput('add_value_wrap', 'add_value', 'string', '');
    openModal('addModal');
}

function onAddTypeChange() {
    const type = document.getElementById('add_type').value;
    rebuildValueInput('add_value_wrap', 'add_value', type, '');
}
function onAddGroupChange() {
    const v = document.getElementById('add_group').value;
    document.getElementById('add_custom_group_wrap').style.display = (v === '__custom__') ? 'block' : 'none';
}

async function submitAddSetting(btn) {
    const key  = document.getElementById('add_key').value.trim();
    const type = document.getElementById('add_type').value;
    let group  = document.getElementById('add_group').value;
    if (group === '__custom__') group = document.getElementById('add_custom_group').value.trim();

    const valueEl = document.getElementById('add_value');
    const value   = (type === 'boolean') ? (valueEl?.checked ? 1 : 0) : (valueEl?.value ?? '');
    const desc    = document.getElementById('add_description').value.trim();
    const enc     = document.getElementById('add_encrypted').checked;

    if (!key)   return toast('Setting key is required.', 'error');
    if (!group) return toast('Group is required.', 'error');

    btn.classList.add('btn-loading'); btn.disabled = true;
    try {
        const res = await apiRequest('POST', '/api/v1/settings', {
            key, value, group, data_type: type, description: desc, is_encrypted: enc
        });
        if (res.success) {
            toast(res.message, 'success');
            closeModal('addModal');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast(res.message || 'Failed to add setting.', 'error');
        }
    } catch(e) {
        toast('Network error.', 'error');
    } finally {
        btn.classList.remove('btn-loading'); btn.disabled = false;
    }
}

/* ------------------------------------------------------------------ */
/*  EDIT setting                                                        */
/* ------------------------------------------------------------------ */
function openEditModal(setting) {
    document.getElementById('edit_key').value         = setting.setting_key;
    document.getElementById('edit_key_display').value = setting.setting_key;
    document.getElementById('edit_type').value        = setting.data_type || 'string';
    document.getElementById('edit_group').value       = setting.setting_group || 'general';
    document.getElementById('edit_description').value = setting.description || '';
    document.getElementById('edit_encrypted').checked = !!parseInt(setting.is_encrypted);

    rebuildValueInput('edit_value_wrap', 'edit_value', setting.data_type, setting.setting_value);
    openModal('editModal');
}

function onEditTypeChange() {
    const type  = document.getElementById('edit_type').value;
    const curEl = document.getElementById('edit_value') || document.querySelector('#edit_value_wrap input, #edit_value_wrap textarea, #edit_value_wrap select');
    const curVal = curEl ? (curEl.type === 'checkbox' ? curEl.checked : curEl.value) : '';
    rebuildValueInput('edit_value_wrap', 'edit_value', type, curVal);
}

async function submitEditSetting(btn) {
    const key  = document.getElementById('edit_key').value;
    const type = document.getElementById('edit_type').value;
    const grp  = document.getElementById('edit_group').value.trim();
    const desc = document.getElementById('edit_description').value.trim();
    const enc  = document.getElementById('edit_encrypted').checked;

    const valueEl = document.getElementById('edit_value') || document.querySelector('#edit_value_wrap input, #edit_value_wrap textarea');
    const value   = (type === 'boolean') ? (valueEl?.checked ? 1 : 0) : (valueEl?.value ?? '');

    btn.classList.add('btn-loading'); btn.disabled = true;
    try {
        const res = await apiRequest('PUT', `/api/v1/settings/${encodeURIComponent(key)}`, {
            value, group: grp, data_type: type, description: desc, is_encrypted: enc
        });
        if (res.success) {
            toast(res.message || 'Setting updated! Reloading...', 'success');
            closeModal('editModal');
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            toast(res.message || 'Update failed.', 'error');
        }
    } catch(e) {
        toast('Network error.', 'error');
    } finally {
        btn.classList.remove('btn-loading'); btn.disabled = false;
    }
}

/* ------------------------------------------------------------------ */
/*  DELETE setting                                                      */
/* ------------------------------------------------------------------ */
async function deleteSetting(key) {
    if (!confirm(`Delete setting "${key}"? This cannot be undone.`)) return;
    try {
        const res = await apiRequest('DELETE', `/api/v1/settings/${encodeURIComponent(key)}`);
        if (res.success) {
            toast(res.message || 'Setting deleted! Reloading...', 'success');
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            toast(res.message || 'Delete failed.', 'error');
        }
    } catch(e) {
        toast('Network error.', 'error');
    }
}

/* ------------------------------------------------------------------ */
/*  SMTP Test                                                           */
/* ------------------------------------------------------------------ */
async function testSmtp() {
    const to  = document.getElementById('smtpTestEmail').value.trim();
    const res_div = document.getElementById('smtpTestResult');

    if (!to) { toast('Please enter a recipient email.', 'error'); return; }

    res_div.style.display = 'none';
    res_div.innerHTML = '';

    // First save email settings (silently, no page reload — we need this page
    // alive to show the test result right after).
    const saveRes = await saveGroupSettings('email', { silent: true, reload: false });
    if (!saveRes || !saveRes.success) {
        res_div.style.display = 'block';
        res_div.textContent = '';
        res_div.innerHTML = '<div class="alert-flash alert-flash-error"><i class="fas fa-circle-xmark"></i> <span></span></div>';
        res_div.querySelector('span').textContent = 'Could not save email settings before testing: ' + ((saveRes && saveRes.message) || 'unknown error');
        return;
    }

    try {
        const res = await apiRequest('POST', '/api/v1/settings/test-smtp', { to });
        res_div.style.display = 'block';
        const cls = res.success ? 'alert-flash-success' : 'alert-flash-error';
        const icon = res.success ? 'fa-check-circle' : 'fa-circle-xmark';
        res_div.innerHTML = `<div class="alert-flash ${cls}"><i class="fas ${icon}"></i> <span></span></div>`;
        res_div.querySelector('span').textContent = res.message;
    } catch(e) {
        res_div.style.display = 'block';
        res_div.innerHTML = '<div class="alert-flash alert-flash-error"><i class="fas fa-circle-xmark"></i> Network error.</div>';
    }
}

/* ------------------------------------------------------------------ */
/*  Dynamic value input builder                                        */
/* ------------------------------------------------------------------ */
function rebuildValueInput(wrapId, inputId, type, currentValue) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;

    let html = '';
    const v  = currentValue ?? '';

    if (type === 'boolean') {
        const chk = v === true || v === 1 || v === '1' || v === 'true';
        html = `<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" id="${inputId}" class="bool-toggle" ${chk ? 'checked' : ''} style="width:16px;height:16px;">
                    <span>Enabled</span>
                </label>`;
    } else if (type === 'text') {
        html = `<textarea id="${inputId}" rows="3" style="width:100%;resize:vertical;">${escHtml(v)}</textarea>`;
    } else if (type === 'json') {
        const display = (typeof v === 'object') ? JSON.stringify(v, null, 2) : v;
        html = `<textarea id="${inputId}" rows="4" style="width:100%;font-family:monospace;resize:vertical;">${escHtml(display)}</textarea>`;
    } else if (type === 'integer' || type === 'float' || type === 'number') {
        html = `<input type="number" id="${inputId}" value="${escHtml(String(v))}" step="${type === 'integer' ? '1' : 'any'}">`;
    } else if (type === 'email') {
        html = `<input type="email" id="${inputId}" value="${escHtml(String(v))}">`;
    } else if (type === 'url') {
        html = `<input type="url" id="${inputId}" value="${escHtml(String(v))}">`;
    } else {
        html = `<input type="text" id="${inputId}" value="${escHtml(String(v))}">`;
    }
    wrap.innerHTML = html;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

<?php
/* ------------------------------------------------------------------ *
 * PHP Helpers
 * ------------------------------------------------------------------ */
function renderSettingInput(array $setting): string {
    $key   = $setting['setting_key'];
    $value = $setting['setting_value'];
    $type  = $setting['data_type'];
    $name  = "settings[{$key}]";

    // app_url is deliberately never read from the database (see
    // Config::$protectedKeys) — changing the base URL live, from a page
    // served AT that URL, is a footgun (redirects/CSRF/cookies all key off
    // it). Show the real effective value instead of a field that looks
    // editable but silently does nothing.
    if ($key === 'app_url') {
        $effective = class_exists('Config') ? Config::get('app_url', $value) : $value;
        return "<div class='form-control-static'>"
            . htmlspecialchars((string) $effective)
            . " <span style='color:var(--text-secondary);font-size:0.75rem;'>(set via APP_URL in .env — not editable here)</span></div>";
    }

    if ($type === 'boolean' || $type === 'bool') {
        $checked = ($value === true || $value === 1 || $value === '1' || $value === 'true') ? 'checked' : '';
        return "<label style='display:flex;align-items:center;gap:8px;cursor:pointer;'>
                    <input type='checkbox' name='{$name}' class='bool-toggle' value='1' {$checked} style='width:16px;height:16px;'>
                    <span style='font-size:0.87rem;'>Enabled</span>
                </label>";
    }

    $displayValue = is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : (string)$value;

    if ($type === 'text' || $type === 'textarea') {
        return "<textarea name='{$name}' rows='2'>" . htmlspecialchars($displayValue) . '</textarea>';
    }
    if ($type === 'json') {
        return "<textarea name='{$name}' rows='2' style='font-family:monospace;'>" . htmlspecialchars($displayValue) . '</textarea>';
    }
    if ($type === 'email') {
        return "<input type='email' name='{$name}' value='" . htmlspecialchars($displayValue) . "'>";
    }
    if ($type === 'url') {
        return "<input type='url' name='{$name}' value='" . htmlspecialchars($displayValue) . "'>";
    }
    if (in_array($type, ['integer', 'number', 'float', 'double'], true)) {
        return "<input type='number' name='{$name}' value='" . htmlspecialchars($displayValue) . "' step='any'>";
    }

    // Default: string
    $inputType = ($setting['is_encrypted']) ? 'password' : 'text';
    return "<input type='{$inputType}' name='{$name}' value='" . htmlspecialchars($displayValue) . "'>";
}
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
