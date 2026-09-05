<?php
/**
 * Reports Controller
 */
class ReportController extends Controller {
    
    public function index() {
        // Check permission using Auth::can()
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        $this->view('reports/index');
    }
    
    public function assets() {
        // Check permission using Auth::can()
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }

        $type = $_GET['type'] ?? 'summary';
        $format = $_GET['format'] ?? 'html';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';

        // Exports need the complete data set — only the on-screen HTML view
        // is paginated. (land/buildings/rented/movable/ict assets each run
        // to ~100,000 rows in this deployment; fetching all of them into one
        // page on every view was the same "renders huge, then reflows" cause
        // as /ammunition and /reports/weapons.)
        if ($format === 'csv') {
            $data = $this->getAssetReportData($type, $startDate, $endDate, null, 50, $search, $status) ?: [];
            $this->exportAssetReportCSV($data, $type);
            return;
        }
        if ($format === 'excel') {
            // PhpSpreadsheet keeps every cell as an in-memory object — a
            // ~100k-row sheet exhausts the PHP memory limit outright (confirmed
            // by testing). Fetch a capped page directly instead of fetching
            // everything and discarding most of it. 'summary' is already
            // capped at 100 rows/table via SQL LIMIT.
            if ($type === 'summary') {
                $data = $this->getAssetReportData($type, $startDate, $endDate, null, 50, $search, $status) ?: [];
                $excelTotal = null;
            } else {
                $result = $this->getAssetReportData($type, $startDate, $endDate, 1, 5000, $search, $status);
                $data = $result['rows'] ?? [];
                $excelTotal = $result['total'] ?? count($data);
            }
            $this->exportGenericExcel($data, $type, 'assets', 'Asset Report', $excelTotal);
            return;
        }
        if ($format === 'pdf') {
            // TCPDF's HTML table renderer is far slower per row than a CSV
            // writer — dumping the full ~100,000-row result into it would
            // hang the request the same way the old unbounded fetchAll()s
            // did before pagination. 'summary' is already capped at 100
            // rows/table via SQL LIMIT; every other type is capped here by
            // reusing the pagination path with a generous single-page limit
            // instead of the "fetch everything" path CSV gets.
            if ($type === 'summary') {
                $data = $this->getAssetReportData($type, $startDate, $endDate, null, 50, $search, $status) ?: [];
                $pdfTotal = null;
            } else {
                $pdfLimit = 300;
                $result = $this->getAssetReportData($type, $startDate, $endDate, 1, $pdfLimit, $search, $status);
                $data = $result['rows'] ?? [];
                $pdfTotal = $result['total'] ?? count($data);
            }
            $this->exportAssetReportPDF($data, $type, $startDate, $endDate, $pdfTotal);
            return;
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $result = $this->getAssetReportData($type, $startDate, $endDate, $page, 50, $search, $status);

        // 'summary' returns a plain ['land'=>[], 'buildings'=>[], ...] array
        // (a fixed 100-row-per-table overview, not something to page
        // through); every other type returns scopedPaginate()'s shape.
        $zoneBreakdown = [];
        $valueByCategory = [];
        $summaryCounts = [];
        if ($type === 'summary') {
            $data = $result;
            $totalPages = 1;
            $totalCount = 0;
            foreach ($data as $rows) { $totalCount += count($rows); }
            $summaryCounts = $this->getAssetPageSummaryCounts($startDate, $endDate);
            $valueByCategory = $this->getAssetPageValueByCategory($startDate, $endDate);
            $zoneBreakdown = $this->getAssetsByZone();
        } else {
            $data = $result['rows'];
            $totalPages = $result['totalPages'];
            $totalCount = $result['total'];
            $page = $result['page'];
        }

        $this->view('reports/assets', [
            'data' => $data,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'summaryCounts' => $summaryCounts,
            'valueByCategory' => $valueByCategory,
            'zoneBreakdown' => $zoneBreakdown,
        ]);
    }
    
    public function weapons() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }

        $type = $_GET['type'] ?? 'inventory';
        $format = $_GET['format'] ?? 'html';

        // Command/Formation filter — Super Admin/admin/HQ Armorer/Armorer
        // only; a command-restricted viewer already only sees their own
        // command via scope(), so an ad-hoc filter would be meaningless.
        $commandFilterId = (!Auth::isCommandRestricted() && !empty($_GET['command_id'])) ? (int) $_GET['command_id'] : null;

        // CSV needs the complete data set (that's the point of the download);
        // PDF is capped — TCPDF's HTML table renderer is far slower per row
        // than a CSV writer, and weapons_inventory/weapon_issue_log are large
        // enough (15k/thousands of rows) that an uncapped PDF would hang the
        // request the same way the old unbounded fetchAll()s used to.
        if ($format === 'csv') {
            $data = $this->getWeaponsReportData($type, null, 50, $commandFilterId) ?: [];
            $this->exportWeaponsReportCSV($data, $type);
            return;
        }
        if ($format === 'excel') {
            if ($type === 'by_type') {
                $data = $this->getWeaponsReportData($type, null, 50, $commandFilterId) ?: [];
                $excelTotal = null;
            } else {
                $result = $this->getWeaponsReportData($type, 1, 5000, $commandFilterId);
                $data = $result['rows'] ?? [];
                $excelTotal = $result['total'] ?? count($data);
            }
            $this->exportGenericExcel($data, $type, 'weapons', 'Weapons Report', $excelTotal);
            return;
        }
        if ($format === 'pdf') {
            if ($type === 'by_type') {
                $data = $this->getWeaponsReportData($type, null, 50, $commandFilterId) ?: [];
                $pdfTotal = null;
            } else {
                $result = $this->getWeaponsReportData($type, 1, 300, $commandFilterId);
                $data = $result['rows'] ?? [];
                $pdfTotal = $result['total'] ?? count($data);
            }
            $this->exportWeaponsReportPDF($data, $type, $pdfTotal);
            return;
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $result = $this->getWeaponsReportData($type, $page, 50, $commandFilterId);

        // The condition-breakdown summary cards/chart used to be computed by
        // filtering $data in the view — fine when $data was the whole table,
        // wrong now that it's one page of it. Compute from the full
        // (command-scoped, and now optionally command/formation-filtered) set instead.
        $summary = null;
        if ($type === 'inventory') {
            $summarySql = "SELECT
                    SUM(CASE WHEN wi.condition = 'Serviceable' THEN 1 ELSE 0 END) as serviceable,
                    SUM(CASE WHEN wi.condition = 'Unserviceable' THEN 1 ELSE 0 END) as unserviceable,
                    SUM(CASE WHEN wi.condition = 'Under Repair' THEN 1 ELSE 0 END) as under_repair,
                    COUNT(*) as total
                 FROM weapons_inventory wi";
            $summaryParams = [];
            $summarySql = Database::applyOptionalFilter($summarySql, 'wi', 'command_id', $commandFilterId, $summaryParams);
            $row = $this->scopedOne($summarySql, $summaryParams, 'wi.command_id');
            $summary = [
                'serviceable' => (int) ($row['serviceable'] ?? 0),
                'unserviceable' => (int) ($row['unserviceable'] ?? 0),
                'under_repair' => (int) ($row['under_repair'] ?? 0),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        // Command/Formation filter options — "Formation" is just
        // commands.command_type = 'Formation', not a separate table.
        $commands = [];
        if (!Auth::isCommandRestricted()) {
            $commands = Database::fetchAll("SELECT id, command_name, command_type FROM commands WHERE is_active = 1 ORDER BY command_name") ?: [];
        }

        $this->view('reports/weapons', [
            'data' => $result['rows'],
            'type' => $type,
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'totalCount' => $result['total'],
            'summary' => $summary,
            'commands' => $commands,
            'selectedCommandId' => $commandFilterId,
        ]);
    }
    
    public function ammunition() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        $type = $_GET['type'] ?? 'inventory';
        $format = $_GET['format'] ?? 'html';

        // CSV needs the complete data set; PDF is capped for the same reason
        // as weapons/assets above — TCPDF's HTML table renderer is too slow
        // per row to safely dump thousands of rows into one download.
        if ($format === 'csv') {
            $data = $this->getAmmunitionReportData($type) ?: [];
            $this->exportAmmunitionReportCSV($data, $type);
            return;
        }
        if ($format === 'excel') {
            $result = $this->getAmmunitionReportData($type, 1, 5000);
            $data = $result['rows'] ?? [];
            $excelTotal = $result['total'] ?? count($data);
            $this->exportGenericExcel($data, $type, 'ammunition', 'Ammunition Report', $excelTotal);
            return;
        }
        if ($format === 'pdf') {
            $result = $this->getAmmunitionReportData($type, 1, 300);
            $data = $result['rows'] ?? [];
            $pdfTotal = $result['total'] ?? count($data);
            $this->exportAmmunitionReportPDF($data, $type, $pdfTotal);
            return;
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $result = $this->getAmmunitionReportData($type, $page, 50);

        $summaryRow = $this->scopedOne(
            "SELECT 
                SUM(quantity_received - quantity_issued) as total_rounds,
                SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as expiring_count,
                SUM(CASE WHEN (quantity_received - quantity_issued) < 100 THEN 1 ELSE 0 END) as low_stock_count,
                COUNT(*) as total_batches
             FROM ammunition_inventory ai",
            [], 'ai.command_id'
        );

        $summary = [
            'total_rounds' => (int) ($summaryRow['total_rounds'] ?? 0),
            'expiring_count' => (int) ($summaryRow['expiring_count'] ?? 0),
            'low_stock_count' => (int) ($summaryRow['low_stock_count'] ?? 0),
            'total_batches' => (int) ($summaryRow['total_batches'] ?? 0),
        ];

        $this->view('reports/ammunition', [
            'data' => $result['rows'],
            'type' => $type,
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'totalCount' => $result['total'],
            'summary' => $summary
        ]);
    }
    
    public function fleet() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }

        // Debug output is Super-Admin only.
        $debug = isset($_GET['debug']) && Auth::isSuperAdmin();
        if ($debug) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            if (isset($_GET['clear_cache']) && function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
        
        $type = $_GET['type'] ?? 'summary';
        $format = $_GET['format'] ?? 'html';
        
        try {
            $data = $this->getFleetReportData($type);
        } catch (Throwable $t) {
            error_log("Error in fleet report: " . $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine());
            if ($debug) {
                die("<strong>Fleet Report Debug Error:</strong> " . htmlspecialchars($t->getMessage()) . "<br>File: " . htmlspecialchars($t->getFile()) . ":" . (int) $t->getLine());
            }
            $data = ($type === 'summary') ? ['vehicles' => [], 'aircraft' => [], 'marine' => [], 'motorcycles' => []] : [];
        }
        
        // Ensure data is always an array
        if ($data === false || $data === null) {
            $data = [];
        }
        
        if ($format === 'csv') {
            $this->exportFleetReportCSV($data, $type);
        } elseif ($format === 'excel') {
            if ($type === 'summary') {
                $this->exportFleetSummaryExcel($data['counts'] ?? []);
            } else {
                $this->exportGenericExcel($data, $type, 'fleet', 'Fleet Report');
            }
        } elseif ($format === 'pdf') {
            $this->exportFleetReportPDF($data, $type);
        } else {
            $this->view('reports/fleet', [
                'data' => $data,
                'type' => $type
            ]);
        }
    }
    
    public function audit() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        $quarter = $_GET['quarter'] ?? 'Q' . ceil(date('n') / 3);
        $year = $_GET['year'] ?? date('Y');
        $format = $_GET['format'] ?? 'html';
        
        $data = $this->getAuditReportData($quarter, $year);
        
        // Ensure data is always an array
        if ($data === false || $data === null) {
            $data = [];
        }
        
        if ($format === 'csv') {
            $this->exportAuditReportCSV($data);
        } elseif ($format === 'pdf') {
            $this->exportAuditReportPDF($data);
        } else {
            $this->view('reports/audit', [
                'data' => $data,
                'quarter' => $quarter,
                'year' => $year
            ]);
        }
    }
    
    public function summary() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        // getAssetsByType() already counts every table getTotalAssets() would count
        // separately — reuse its result instead of running the same 11 COUNT(*)
        // queries twice. Same idea for value: getAssetValueByCategory() already
        // runs every query getTotalValue() would run, just broken down.
        $assetsByType = $this->getAssetsByType();
        $valueByCategory = $this->getAssetValueByCategory();
        $assetsByZone = $this->getAssetsByZone();
        $growthTrend = $this->getMonthlyGrowthTrend(6);
        $recentAdditions = $this->getRecentAdditions();
        $expiringItems = $this->getExpiringItems();

        $data = [
            'total_assets' => array_sum($assetsByType),
            'total_value' => array_sum($valueByCategory),
            'assets_by_type' => $assetsByType,
            'assets_by_zone' => $assetsByZone,
            'value_by_category' => $valueByCategory,
            'growth_trend' => $growthTrend,
            'recent_additions' => $recentAdditions,
            'expiring_items' => $expiringItems
        ];
        
        $this->view('reports/summary', ['data' => $data]);
    }
    
    public function save() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to save reports']);
            return;
        }
        
        // Check CSRF token - handle case where Security class might not exist
        $csrfToken = $_POST['csrf_token'] ?? '';
        $validToken = false;
        
        if (class_exists('Security') && method_exists('Security', 'validateCsrfToken')) {
            $validToken = Security::validateCsrfToken($csrfToken);
        } else {
            // Simple session-based CSRF check - use the correct key
            $validToken = isset($_SESSION['_csrf_token']) && $_SESSION['_csrf_token'] === $csrfToken;
        }
        
        if (!$validToken) {
            $this->redirect('reports', ['error' => 'Invalid security token']);
            return;
        }
        
        $reportName = $_POST['report_name'] ?? '';
        $reportType = $_POST['report_type'] ?? '';
        $parameters = $_POST['parameters'] ?? '{}';
        
        if (empty($reportName) || empty($reportType)) {
            $this->redirect('reports', ['error' => 'Report name and type are required']);
            return;
        }
        
        $saved = false;
        try {
            // Check if saved_reports table exists, if not create it
            $this->ensureSavedReportsTable();

            $saved = (bool) Database::insert('saved_reports', [
                'report_name' => $reportName,
                'report_type' => $reportType,
                'parameters' => is_array($parameters) ? json_encode($parameters) : $parameters,
                'created_by' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Save report error: " . $e->getMessage());
        }

        // Redirect back to the report the user was actually looking at (with
        // its filters intact) instead of dumping them on the reports hub —
        // that was jarring UX and made "Save Report" feel like it navigated
        // away rather than just saving.
        $returnRoutes = [
            'assets' => 'reports/assets',
            'weapons' => 'reports/weapons',
            'ammunition' => 'reports/ammunition',
            'fleet' => 'reports/fleet',
        ];
        $returnUrl = 'reports';
        if (isset($returnRoutes[$reportType])) {
            $decodedParams = json_decode(is_array($parameters) ? json_encode($parameters) : $parameters, true);
            $returnUrl = $returnRoutes[$reportType] . (is_array($decodedParams) && $decodedParams
                ? ('?' . http_build_query($decodedParams))
                : '');
        }

        $this->redirect($returnUrl, [$saved ? 'success' : 'error' => $saved ? 'Report saved successfully' : 'Failed to save report']);
    }
    
    public function saved() {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        // Check if saved_reports table exists
        $this->ensureSavedReportsTable();
        
        $reports = Database::fetchAll(
            "SELECT * FROM saved_reports WHERE created_by = ? ORDER BY created_at DESC",
            [Auth::id()]
        );
        
        if ($reports === false) {
            $reports = [];
        }
        
        $this->view('reports/saved', ['reports' => $reports]);
    }
    
    public function load($id) {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view reports']);
            return;
        }
        
        $report = Database::fetchOne("SELECT * FROM saved_reports WHERE id = ?", [$id]);
        
        if (!$report || $report['created_by'] != Auth::id()) {
            $_SESSION['error'] = 'Report not found';
            $this->redirect('reports/saved');
            return;
        }
        
        $parameters = json_decode($report['parameters'], true);
        if (!is_array($parameters)) {
            $parameters = [];
        }
        
        switch ($report['report_type']) {
            case 'assets':
                $this->redirect('reports/assets?' . http_build_query($parameters));
                break;
            case 'weapons':
                $this->redirect('reports/weapons?' . http_build_query($parameters));
                break;
            case 'ammunition':
                $this->redirect('reports/ammunition?' . http_build_query($parameters));
                break;
            case 'fleet':
                $this->redirect('reports/fleet?' . http_build_query($parameters));
                break;
            default:
                $this->redirect('reports');
        }
    }
    
    public function delete($id) {
        if (!Auth::can('reports.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete reports']);
            return;
        }
        
        $report = Database::fetchOne("SELECT * FROM saved_reports WHERE id = ?", [$id]);
        
        if (!$report || $report['created_by'] != Auth::id()) {
            $_SESSION['error'] = 'Report not found';
            $this->redirect('reports/saved');
            return;
        }
        
        try {
            $result = Database::delete('saved_reports', 'id = ?', [$id]);
            
            if ($result) {
                $_SESSION['success'] = 'Report deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete report';
            }
        } catch (Exception $e) {
            error_log("Delete report error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete report';
        }
        
        $this->redirect('reports/saved');
    }
    
    /**
     * Ensure saved_reports table exists
     */
    private function ensureSavedReportsTable() {
        try {
            // Check if table exists
            $result = Database::fetchOne("SHOW TABLES LIKE 'saved_reports'");
            if (!$result) {
                // Create saved_reports table
                $sql = "CREATE TABLE IF NOT EXISTS saved_reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    report_name VARCHAR(255) NOT NULL,
                    report_type VARCHAR(50) NOT NULL,
                    parameters TEXT,
                    created_by INT NOT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_created_by (created_by)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                Database::query($sql);
            }
        } catch (Exception $e) {
            error_log("Error ensuring saved_reports table: " . $e->getMessage());
        }
    }
    
    // =============================================
    // DATA COLLECTION METHODS
    // =============================================

    /**
     * Splice a command_id predicate into a query when the current user is
     * restricted to a single command. $column carries the table alias where
     * the query joins more than one table (e.g. "wi.command_id").
     */
    private function scope($sql, array $params = [], $column = 'command_id') {
        if (!Auth::isCommandRestricted()) {
            return [$sql, $params];
        }
        $cond = " {$column} = ? ";
        if (preg_match('/\sWHERE\s/i', $sql)) {
            $sql = preg_replace('/\sWHERE\s/i', " WHERE {$cond} AND ", $sql, 1);
            array_unshift($params, Auth::commandId());
        } else {
            $pos = strlen($sql);
            foreach ([' ORDER BY ', ' GROUP BY ', ' LIMIT '] as $kw) {
                $p = stripos($sql, $kw);
                if ($p !== false && $p < $pos) { $pos = $p; }
            }
            $sql = substr_replace($sql, " WHERE {$cond} ", $pos, 0);
            $params[] = Auth::commandId();
        }
        return [$sql, $params];
    }

    /** Run a query with an automatic command-scope predicate. */
    private function scopedAll($sql, array $params = [], $column = 'command_id') {
        [$sql, $params] = $this->scope($sql, $params, $column);
        return Database::fetchAll($sql, $params) ?: [];
    }

    /** Single-row variant of scopedAll(). */
    private function scopedOne($sql, array $params = [], $column = 'command_id') {
        [$sql, $params] = $this->scope($sql, $params, $column);
        return Database::fetchOne($sql, $params) ?: [];
    }

    /**
     * Command-scoped, paginated query. Some report queries (weapons/ammo
     * inventory, issue history) had no LIMIT at all and dumped thousands of
     * rows straight into the HTML on every view — the page would render an
     * enormous table, then visibly reflow/shrink once the browser finished,
     * which read as the whole page "zooming in and out". Use this for any
     * report list large enough to matter; exports still fetch the full set
     * via scopedAll() since a CSV/PDF download legitimately wants everything.
     *
     * Returns ['rows' => [...], 'total' => int, 'page' => int, 'totalPages' => int, 'limit' => int].
     */
    private function scopedPaginate($sql, array $params, $column, $page, $limit = 50) {
        [$scopedSql, $scopedParams] = $this->scope($sql, $params, $column);

        $countSql = preg_replace('/^SELECT .*? FROM/is', 'SELECT COUNT(*) as count FROM', $scopedSql, 1);
        $countSql = preg_replace('/\s+ORDER BY\s.*$/is', '', $countSql, 1);
        $total = (int) (Database::fetchOne($countSql, $scopedParams)['count'] ?? 0);

        $page = max(1, (int) $page);
        $offset = ($page - 1) * $limit;
        $rows = Database::fetchAll($scopedSql . ' LIMIT ? OFFSET ?', array_merge($scopedParams, [$limit, $offset])) ?: [];

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'limit' => $limit,
        ];
    }

    /** Splice an optional keyword search (OR'd across columns) and an exact
     *  status/condition match into a WHERE-having SQL string, before it's
     *  handed to scope()/scopedPaginate(). */
    private function applyAssetFilters($sql, array $params, array $searchColumns, $statusColumn, $search, $status) {
        if ($search !== '') {
            $conds = array_map(function ($col) { return "$col LIKE ?"; }, $searchColumns);
            $sql .= ' AND (' . implode(' OR ', $conds) . ')';
            $like = '%' . $search . '%';
            foreach ($searchColumns as $c) { $params[] = $like; }
        }
        if ($status !== '' && $statusColumn) {
            $sql .= " AND $statusColumn = ?";
            $params[] = $status;
        }
        return [$sql, $params];
    }

    /**
     * $page === null -> plain array/summary (CSV/PDF export — needs everything).
     * $page !== null -> paginated result for the individual type views
     *                   (land/buildings/rented/movable/ict/projects). 'summary'
     *                   is always capped at 100 rows per table regardless —
     *                   it's a multi-table overview, not a list to page
     *                   through.
     */
    private function getAssetReportData($type, $startDate, $endDate, $page = null, $limit = 50, $search = '', $status = '') {
        $search = trim($search);
        $status = trim($status);
        $paginate = ($page !== null);
        $range = [$startDate, $endDate . ' 23:59:59'];
        $empty = $paginate ? ['rows' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1, 'limit' => $limit] : [];

        try {
            switch ($type) {
                case 'summary':
                    $data['land'] = $this->scopedAll(
                        "SELECT * FROM land_assets WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    $data['buildings'] = $this->scopedAll(
                        "SELECT * FROM building_assets WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    $data['rented'] = $this->scopedAll(
                        "SELECT * FROM rented_properties WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    $data['movable'] = $this->scopedAll(
                        "SELECT * FROM movable_assets WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    $data['ict'] = $this->scopedAll(
                        "SELECT * FROM ict_assets WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    $data['projects'] = $this->scopedAll(
                        "SELECT * FROM ongoing_projects WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", $range);
                    return $data;

                case 'land':
                    $sql = "SELECT la.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM land_assets la
                         LEFT JOIN states s ON la.state_id = s.id
                         LEFT JOIN lgas l ON la.lga_id = l.id
                         LEFT JOIN zones z ON la.zone_id = z.id
                         LEFT JOIN commands c ON la.command_id = c.id
                         WHERE la.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['la.asset_code', 'la.title_holder', 'la.address'], 'la.status', $search, $status);
                    $sql .= " ORDER BY la.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'la.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'la.command_id');

                case 'buildings':
                    $sql = "SELECT ba.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM building_assets ba
                         LEFT JOIN states s ON ba.state_id = s.id
                         LEFT JOIN lgas l ON ba.lga_id = l.id
                         LEFT JOIN zones z ON ba.zone_id = z.id
                         LEFT JOIN commands c ON ba.command_id = c.id
                         WHERE ba.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['ba.asset_code', 'ba.building_name', 'ba.address'], 'ba.condition_status', $search, $status);
                    $sql .= " ORDER BY ba.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'ba.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'ba.command_id');

                case 'rented':
                    $sql = "SELECT rp.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM rented_properties rp
                         LEFT JOIN states s ON rp.state_id = s.id
                         LEFT JOIN lgas l ON rp.lga_id = l.id
                         LEFT JOIN zones z ON rp.zone_id = z.id
                         LEFT JOIN commands c ON rp.command_id = c.id
                         WHERE rp.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['rp.asset_code', 'rp.property_address', 'rp.owner_lessor_name'], 'rp.status', $search, $status);
                    $sql .= " ORDER BY rp.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'rp.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'rp.command_id');

                case 'movable':
                    $sql = "SELECT ma.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM movable_assets ma
                         LEFT JOIN states s ON ma.state_id = s.id
                         LEFT JOIN lgas l ON ma.lga_id = l.id
                         LEFT JOIN zones z ON ma.zone_id = z.id
                         LEFT JOIN commands c ON ma.command_id = c.id
                         WHERE ma.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['ma.asset_code', 'ma.asset_type', 'ma.serial_number'], 'ma.condition_status', $search, $status);
                    $sql .= " ORDER BY ma.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'ma.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'ma.command_id');

                case 'ict':
                    $sql = "SELECT ia.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM ict_assets ia
                         LEFT JOIN states s ON ia.state_id = s.id
                         LEFT JOIN lgas l ON ia.lga_id = l.id
                         LEFT JOIN zones z ON ia.zone_id = z.id
                         LEFT JOIN commands c ON ia.command_id = c.id
                         WHERE ia.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['ia.asset_code', 'ia.asset_description', 'ia.serial_number'], 'ia.current_status', $search, $status);
                    $sql .= " ORDER BY ia.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'ia.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'ia.command_id');

                case 'projects':
                    $sql = "SELECT op.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM ongoing_projects op
                         LEFT JOIN states s ON op.state_id = s.id
                         LEFT JOIN lgas l ON op.lga_id = l.id
                         LEFT JOIN zones z ON op.zone_id = z.id
                         LEFT JOIN commands c ON op.command_id = c.id
                         WHERE op.created_at BETWEEN ? AND ?";
                    [$sql, $range] = $this->applyAssetFilters($sql, $range,
                        ['op.project_code', 'op.project_title', 'op.contractor'], 'op.status', $search, $status);
                    $sql .= " ORDER BY op.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, $range, 'op.command_id', $page, $limit)
                        : $this->scopedAll($sql, $range, 'op.command_id');

                default:
                    return $empty;
            }
        } catch (Exception $e) {
            error_log("Error in getAssetReportData: " . $e->getMessage());
            return $empty;
        }
    }
    
    /**
     * $page === null  -> plain array of every matching row (used by the
     *                    CSV/PDF export paths, which legitimately need the
     *                    full data set).
     * $page !== null  -> paginated result (used by the HTML view) in the
     *                    same shape scopedPaginate() returns.
     */
    private function getWeaponsReportData($type, $page = null, $limit = 50, $commandFilterId = null) {
        $paginate = ($page !== null);
        $empty = $paginate ? ['rows' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1, 'limit' => $limit] : [];

        try {
            switch ($type) {
                case 'inventory':
                    $sql = "SELECT wi.*, wt.type_name, wc.calibre_name
                         FROM weapons_inventory wi
                         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                         LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
                         ORDER BY wi.created_at DESC";
                    $params = [];
                    $sql = Database::applyOptionalFilter($sql, 'wi', 'command_id', $commandFilterId, $params);
                    return $paginate
                        ? $this->scopedPaginate($sql, $params, 'wi.command_id', $page, $limit)
                        : $this->scopedAll($sql, $params, 'wi.command_id');

                case 'issued':
                    $sql = "SELECT wi.*, wt.type_name, wil.*
                         FROM weapon_issue_log wil
                         JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                         WHERE wil.status = 'Issued'
                         ORDER BY wil.issue_date DESC";
                    $params = [];
                    $sql = Database::applyOptionalFilter($sql, 'wi', 'command_id', $commandFilterId, $params);
                    return $paginate
                        ? $this->scopedPaginate($sql, $params, 'wi.command_id', $page, $limit)
                        : $this->scopedAll($sql, $params, 'wi.command_id');

                case 'unserviceable':
                    $sql = "SELECT wi.*, wt.type_name, wc.calibre_name
                         FROM weapons_inventory wi
                         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                         LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
                         WHERE wi.condition = 'Unserviceable'
                         ORDER BY wi.created_at DESC";
                    $params = [];
                    $sql = Database::applyOptionalFilter($sql, 'wi', 'command_id', $commandFilterId, $params);
                    return $paginate
                        ? $this->scopedPaginate($sql, $params, 'wi.command_id', $page, $limit)
                        : $this->scopedAll($sql, $params, 'wi.command_id');

                case 'by_type':
                    // Small aggregate (one row per weapon type) — never worth paginating.
                    $rows = $this->scopedAll(
                        "SELECT COALESCE(wt.type_name, 'Other') as type, COUNT(*) as count
                         FROM weapons_inventory wi
                         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                         GROUP BY COALESCE(wt.type_name, 'Other')
                         ORDER BY count DESC",
                        [], 'wi.command_id'
                    );
                    if (!$paginate) return $rows;
                    return ['rows' => $rows, 'total' => count($rows), 'page' => 1, 'totalPages' => 1, 'limit' => $limit];

                default:
                    return $empty;
            }
        } catch (Exception $e) {
            error_log("Error in getWeaponsReportData: " . $e->getMessage());
            return $empty;
        }
    }

    private function getAmmunitionReportData($type, $page = null, $limit = 50) {
        $paginate = ($page !== null);
        $empty = $paginate ? ['rows' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1, 'limit' => $limit] : [];

        try {
            switch ($type) {
                case 'inventory':
                    $sql = "SELECT ai.*, at.ammo_type, ac.calibre
                         FROM ammunition_inventory ai
                         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                         ORDER BY ai.created_at DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, [], 'ai.command_id', $page, $limit)
                        : $this->scopedAll($sql, [], 'ai.command_id');

                case 'expiring':
                    $sql = "SELECT ai.*, at.ammo_type, ac.calibre,
                                DATEDIFF(expiry_date, CURDATE()) as days_remaining
                         FROM ammunition_inventory ai
                         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                         WHERE ai.expiry_date IS NOT NULL
                         AND ai.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                         ORDER BY ai.expiry_date ASC";
                    return $paginate
                        ? $this->scopedPaginate($sql, [], 'ai.command_id', $page, $limit)
                        : $this->scopedAll($sql, [], 'ai.command_id');

                case 'low_stock':
                    $sql = "SELECT ai.*, at.ammo_type, ac.calibre
                         FROM ammunition_inventory ai
                         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                         WHERE ai.balance < 100
                         ORDER BY ai.balance ASC";
                    return $paginate
                        ? $this->scopedPaginate($sql, [], 'ai.command_id', $page, $limit)
                        : $this->scopedAll($sql, [], 'ai.command_id');

                case 'issued':
                    $sql = "SELECT ail.*, at.ammo_type, ac.calibre
                         FROM ammunition_issue_log ail
                         LEFT JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                         ORDER BY ail.issue_date DESC";
                    return $paginate
                        ? $this->scopedPaginate($sql, [], 'ai.command_id', $page, $limit)
                        : $this->scopedAll($sql, [], 'ai.command_id');

                default:
                    return $empty;
            }
        } catch (Exception $e) {
            error_log("Error in getAmmunitionReportData: " . $e->getMessage());
            return $empty;
        }
    }
    
    private function getFleetReportData($type) {
        try {
            switch ($type) {
                case 'summary':
                    // 1. Vehicle assets query with fallback
                    $vehicles = [];
                    try {
                        $vStats = Database::fetchOne("
                            SELECT 
                                COUNT(*) as total,
                                SUM(IF(operational_status = 'Active', 1, 0)) as active_cnt,
                                SUM(IF(operational_status = 'In Repair', 1, 0)) as repair_cnt,
                                SUM(IF(operational_status = 'Grounded', 1, 0)) as grounded_cnt
                            FROM vehicle_assets
                        ");
                        $vActive = (int)($vStats['active_cnt'] ?? 0);
                        $vRepair = (int)($vStats['repair_cnt'] ?? 0);
                        $vGrounded = (int)($vStats['grounded_cnt'] ?? 0);
                        $vOther = (int)($vStats['total'] ?? 0) - ($vActive + $vRepair + $vGrounded);

                        $vehicles = array_merge(
                            array_fill(0, min(100, $vActive), ['operational_status' => 'Active']),
                            array_fill(0, min(50, $vRepair), ['operational_status' => 'In Repair']),
                            array_fill(0, min(50, $vGrounded), ['operational_status' => 'Grounded']),
                            array_fill(0, min(50, max(0, $vOther)), ['operational_status' => 'Other'])
                        );
                    } catch (Throwable $t) {
                        try {
                            $vStats = Database::fetchOne("SELECT COUNT(*) as total FROM vehicle_assets");
                            $vehicles = array_fill(0, min(100, (int)($vStats['total'] ?? 0)), ['operational_status' => 'Active']);
                        } catch (Throwable $t2) {
                            $vehicles = [];
                        }
                    }

                    // 2. Aircraft assets query with fallback
                    $aircraft = [];
                    try {
                        $aStats = Database::fetchOne("
                            SELECT 
                                COUNT(*) as total,
                                SUM(IF(operational_status = 'Operational', 1, 0)) as op_cnt,
                                SUM(IF(operational_status = 'Maintenance', 1, 0)) as maint_cnt
                            FROM aircraft_assets
                        ");
                        $aOp = (int)($aStats['op_cnt'] ?? 0);
                        $aMaint = (int)($aStats['maint_cnt'] ?? 0);
                        $aOther = (int)($aStats['total'] ?? 0) - ($aOp + $aMaint);

                        $aircraft = array_merge(
                            array_fill(0, min(50, $aOp), ['operational_status' => 'Operational']),
                            array_fill(0, min(50, $aMaint), ['operational_status' => 'Maintenance']),
                            array_fill(0, min(50, max(0, $aOther)), ['operational_status' => 'Grounded'])
                        );
                    } catch (Throwable $t) {
                        try {
                            $aStats = Database::fetchOne("SELECT COUNT(*) as total FROM aircraft_assets");
                            $aircraft = array_fill(0, min(50, (int)($aStats['total'] ?? 0)), ['operational_status' => 'Operational']);
                        } catch (Throwable $t2) {
                            $aircraft = [];
                        }
                    }

                    // 3. Marine assets query with fallback
                    $marine = [];
                    try {
                        $mStats = Database::fetchOne("
                            SELECT 
                                COUNT(*) as total,
                                SUM(IF(operational_status = 'Operational', 1, 0)) as op_cnt
                            FROM marine_assets
                        ");
                        $mOp = (int)($mStats['op_cnt'] ?? 0);
                        $mOther = (int)($mStats['total'] ?? 0) - $mOp;

                        $marine = array_merge(
                            array_fill(0, min(50, $mOp), ['operational_status' => 'Operational']),
                            array_fill(0, min(50, max(0, $mOther)), ['operational_status' => 'Grounded'])
                        );
                    } catch (Throwable $t) {
                        try {
                            $mStats = Database::fetchOne("SELECT COUNT(*) as total FROM marine_assets");
                            $marine = array_fill(0, min(50, (int)($mStats['total'] ?? 0)), ['operational_status' => 'Operational']);
                        } catch (Throwable $t2) {
                            $marine = [];
                        }
                    }

                    // 4. Motorcycle assets query with fallback
                    $motorcycles = [];
                    try {
                        $moStats = Database::fetchOne("SELECT COUNT(*) as total FROM motorcycle_assets");
                        $motorcycles = array_fill(0, min(100, (int)($moStats['total'] ?? 0)), ['operational_status' => 'Active']);
                    } catch (Throwable $t) {
                        $motorcycles = [];
                    }

                    return [
                        'vehicles' => $vehicles,
                        'aircraft' => $aircraft,
                        'marine' => $marine,
                        'motorcycles' => $motorcycles,
                        'counts' => [
                            'vehicles' => ['total' => (int)($vStats['total'] ?? 0), 'active' => $vActive ?? 0, 'repair' => $vRepair ?? 0, 'grounded' => $vGrounded ?? 0],
                            'aircraft' => ['total' => (int)($aStats['total'] ?? 0), 'operational' => $aOp ?? 0, 'maintenance' => $aMaint ?? 0],
                            'marine' => ['total' => (int)($mStats['total'] ?? 0), 'operational' => $mOp ?? 0],
                            'motorcycles' => ['total' => (int)($moStats['total'] ?? 0), 'active' => (int)($moStats['total'] ?? 0)]
                        ]
                    ];
                    
                case 'vehicles':
                    return $this->scopedAll(
                        "SELECT v.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM vehicle_assets v
                         LEFT JOIN states s ON v.state_id = s.id
                         LEFT JOIN lgas l ON v.lga_id = l.id
                         LEFT JOIN zones z ON v.zone_id = z.id
                         LEFT JOIN commands c ON v.command_id = c.id
                         ORDER BY v.created_at DESC LIMIT 500", [], 'v.command_id'
                    );

                case 'aircraft':
                    return $this->scopedAll(
                        "SELECT a.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM aircraft_assets a
                         LEFT JOIN states s ON a.state_id = s.id
                         LEFT JOIN lgas l ON a.lga_id = l.id
                         LEFT JOIN zones z ON a.zone_id = z.id
                         LEFT JOIN commands c ON a.command_id = c.id
                         ORDER BY a.created_at DESC LIMIT 500", [], 'a.command_id'
                    );

                case 'marine':
                    return $this->scopedAll(
                        "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM marine_assets m
                         LEFT JOIN states s ON m.state_id = s.id
                         LEFT JOIN lgas l ON m.lga_id = l.id
                         LEFT JOIN zones z ON m.zone_id = z.id
                         LEFT JOIN commands c ON m.command_id = c.id
                         ORDER BY m.created_at DESC LIMIT 500", [], 'm.command_id'
                    );

                case 'motorcycles':
                    return $this->scopedAll(
                        "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name
                         FROM motorcycle_assets m
                         LEFT JOIN states s ON m.state_id = s.id
                         LEFT JOIN lgas l ON m.lga_id = l.id
                         LEFT JOIN zones z ON m.zone_id = z.id
                         LEFT JOIN commands c ON m.command_id = c.id
                         ORDER BY m.created_at DESC LIMIT 500", [], 'm.command_id'
                    );

                case 'maintenance':
                    return $this->scopedAll(
                        "SELECT v.*,
                                DATEDIFF(next_service_date, CURDATE()) as days_to_service
                         FROM vehicle_assets v
                         WHERE next_service_date IS NOT NULL
                         AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         ORDER BY next_service_date ASC LIMIT 500", [], 'v.command_id'
                    );

                case 'insurance':
                    return $this->scopedAll(
                        "SELECT v.*,
                                DATEDIFF(insurance_expiry, CURDATE()) as days_to_expiry
                         FROM vehicle_assets v
                         WHERE insurance_expiry IS NOT NULL
                         AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                         ORDER BY insurance_expiry ASC LIMIT 500", [], 'v.command_id'
                    );
                    
                default:
                    return [];
            }
        } catch (Exception $e) {
            error_log("Error in getFleetReportData: " . $e->getMessage());
            return [];
        }
    }
    
    private function getAuditReportData($quarter, $year) {
        try {
            $audits = $this->scopedAll(
                "SELECT qa.*,
                        u.full_name as auditor_name,
                        c.command_name
                 FROM quarterly_audits qa
                 LEFT JOIN users u ON qa.created_by = u.id
                 LEFT JOIN commands c ON qa.command_id = c.id
                 WHERE qa.quarter = ? AND qa.year = ?
                 ORDER BY qa.audit_date DESC",
                [$quarter, $year], 'qa.command_id'
            );
            
            foreach ($audits as &$audit) {
                $audit['weapons'] = Database::fetchAll(
                    "SELECT * FROM audit_weapons WHERE audit_id = ?",
                    [$audit['id']]
                ) ?: [];
                
                $audit['ammunition'] = Database::fetchAll(
                    "SELECT * FROM audit_ammunition WHERE audit_id = ?",
                    [$audit['id']]
                ) ?: [];
                
                $audit['missing'] = Database::fetchAll(
                    "SELECT * FROM audit_missing_weapons WHERE audit_id = ?",
                    [$audit['id']]
                ) ?: [];
            }
            
            return $audits;
        } catch (Exception $e) {
            error_log("Error in getAuditReportData: " . $e->getMessage());
            return [];
        }
    }
    
    private function getTotalAssets() {
        $tables = ['land_assets', 'building_assets', 'rented_properties', 
                   'movable_assets', 'ict_assets', 'vehicle_assets', 
                   'aircraft_assets', 'marine_assets', 'motorcycle_assets',
                   'weapons_inventory', 'ammunition_inventory'];
        
        $total = 0;
        foreach ($tables as $table) {
            try {
                $result = Database::fetchOne("SELECT COUNT(*) as count FROM $table");
                $total += $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error counting $table: " . $e->getMessage());
            }
        }
        
        return $total;
    }
    
    /**
     * Total asset value, broken down by category. total_value on the summary
     * report is just array_sum() of this — computed once here instead of
     * running the same 7 SUM queries twice under two different names.
     */
    private function getAssetValueByCategory() {
        // [display label => [table, value column]]
        $categories = [
            'Buildings'    => ['building_assets', 'contract_sum'],
            'Movable'      => ['movable_assets', 'purchase_value'],
            'ICT'          => ['ict_assets', 'purchase_value'],
            'Vehicles'     => ['vehicle_assets', 'purchase_value'],
            'Aircraft'     => ['aircraft_assets', 'capital_value'],
            'Marine'       => ['marine_assets', 'capital_value'],
            'Motorcycles'  => ['motorcycle_assets', 'purchase_value'],
        ];

        $result = [];
        foreach ($categories as $label => [$table, $column]) {
            try {
                $row = Database::fetchOne("SELECT SUM($column) as total FROM $table");
                $result[$label] = (float) ($row['total'] ?? 0);
            } catch (Exception $e) {
                error_log("Error getting value for $table: " . $e->getMessage());
                $result[$label] = 0;
            }
        }

        return $result;
    }

    private function getTotalValue() {
        $total = 0;

        try {
            $total = array_sum($this->getAssetValueByCategory());
        } catch (Exception $e) {
            error_log("Error calculating total value: " . $e->getMessage());
        }
        
        return $total;
    }
    
    private function getAssetsByType() {
        $result = [];
        
        $types = [
            'Land' => 'land_assets',
            'Buildings' => 'building_assets',
            'Rented' => 'rented_properties',
            'Movable' => 'movable_assets',
            'ICT' => 'ict_assets',
            'Vehicles' => 'vehicle_assets',
            'Aircraft' => 'aircraft_assets',
            'Marine' => 'marine_assets',
            'Motorcycles' => 'motorcycle_assets',
            'Weapons' => 'weapons_inventory',
            'Ammunition' => 'ammunition_inventory'
        ];
        
        foreach ($types as $key => $table) {
            try {
                $count = Database::fetchOne("SELECT COUNT(*) as count FROM $table")['count'] ?? 0;
                $result[$key] = $count;
            } catch (Exception $e) {
                error_log("Error counting $table: " . $e->getMessage());
                $result[$key] = 0;
            }
        }
        
        return $result;
    }
    
    private function getAssetsByZone() {
        try {
            // Pre-aggregating each table by zone_id avoids cross-product explosions.
            // Scoped to command_id if the user is command-restricted.
            $cmdWhere = '';
            $params = [];
            if (Auth::isCommandRestricted() && Auth::commandId()) {
                $cmdWhere = ' WHERE command_id = ? ';
                $cmdId = Auth::commandId();
                $params = [$cmdId, $cmdId, $cmdId, $cmdId, $cmdId, $cmdId];
            }
            return Database::fetchAll(
                "SELECT z.zone_name,
                        COALESCE(la.cnt, 0) as land_count,
                        COALESCE(ba.cnt, 0) as building_count,
                        COALESCE(rp.cnt, 0) as rented_count,
                        COALESCE(ma.cnt, 0) as movable_count,
                        COALESCE(ia.cnt, 0) as ict_count,
                        COALESCE(va.cnt, 0) as vehicle_count
                 FROM zones z
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM land_assets {$cmdWhere} GROUP BY zone_id) la ON la.zone_id = z.id
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM building_assets {$cmdWhere} GROUP BY zone_id) ba ON ba.zone_id = z.id
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM rented_properties {$cmdWhere} GROUP BY zone_id) rp ON rp.zone_id = z.id
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM movable_assets {$cmdWhere} GROUP BY zone_id) ma ON ma.zone_id = z.id
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM ict_assets {$cmdWhere} GROUP BY zone_id) ia ON ia.zone_id = z.id
                 LEFT JOIN (SELECT zone_id, COUNT(*) cnt FROM vehicle_assets {$cmdWhere} GROUP BY zone_id) va ON va.zone_id = z.id
                 ORDER BY z.zone_name",
                $params
            ) ?: [];
        } catch (Exception $e) {
            error_log("Error getting assets by zone: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Accurate per-category counts for the assets/summary KPI cards, within
     * the selected date range and respecting command scope. The summary
     * data itself (getAssetReportData('summary', ...)) caps each category
     * at its 100 most recent rows for display, so count()'ing that array
     * would silently under-report any period with more than 100 matches —
     * these are real COUNT(*) queries instead.
     */
    private function getAssetPageSummaryCounts($startDate, $endDate) {
        $range = [$startDate, $endDate . ' 23:59:59'];
        $tables = [
            'land' => ['land_assets', 'command_id'],
            'buildings' => ['building_assets', 'command_id'],
            'rented' => ['rented_properties', 'command_id'],
            'movable' => ['movable_assets', 'command_id'],
            'ict' => ['ict_assets', 'command_id'],
            'projects' => ['ongoing_projects', 'command_id'],
        ];
        $counts = [];
        foreach ($tables as $key => [$table, $column]) {
            try {
                $row = $this->scopedOne(
                    "SELECT COUNT(*) as cnt FROM $table WHERE created_at BETWEEN ? AND ?", $range, $column
                );
                $counts[$key] = (int) ($row['cnt'] ?? 0);
            } catch (Exception $e) {
                error_log("Error counting $table for asset summary: " . $e->getMessage());
                $counts[$key] = 0;
            }
        }
        return $counts;
    }

    /**
     * Monetary breakdown for the asset types this report actually covers
     * (land has no value field in the schema, so it's excluded rather than
     * faked). Scoped to the same date range and command as the rest of the
     * summary report.
     */
    private function getAssetPageValueByCategory($startDate, $endDate) {
        $range = [$startDate, $endDate . ' 23:59:59'];
        // [display label => [table, value column, command column]]
        $categories = [
            'Buildings' => ['building_assets', 'contract_sum', 'command_id'],
            'Rented (Annual Rent)' => ['rented_properties', 'annual_rent', 'command_id'],
            'Movable' => ['movable_assets', 'current_value', 'command_id'],
            'ICT' => ['ict_assets', 'current_value', 'command_id'],
            'Projects' => ['ongoing_projects', 'contract_sum', 'command_id'],
        ];
        $result = [];
        foreach ($categories as $label => [$table, $column, $commandColumn]) {
            try {
                $row = $this->scopedOne(
                    "SELECT SUM($column) as total FROM $table WHERE created_at BETWEEN ? AND ?", $range, $commandColumn
                );
                $result[$label] = (float) ($row['total'] ?? 0);
            } catch (Exception $e) {
                error_log("Error getting value for $table in asset summary: " . $e->getMessage());
                $result[$label] = 0;
            }
        }
        return $result;
    }

    /**
     * Monthly intake trend for the summary report's growth chart: how many
     * of each major asset type were added per month over the trailing
     * window. Each table is queried once with its own date-bounded GROUP BY
     * (rather than one combined query) so a missing/renamed table in one
     * series can't take the rest down with it.
     */
    private function getMonthlyGrowthTrend($months = 6) {
        $series = [
            'Land'      => 'land_assets',
            'Buildings' => 'building_assets',
            'Vehicles'  => 'vehicle_assets',
            'Weapons'   => 'weapons_inventory',
        ];

        // Oldest-to-newest month buckets so the chart reads left-to-right.
        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $buckets[date('Y-m', strtotime("-$i months"))] = date('M Y', strtotime("-$i months"));
        }

        $result = ['labels' => array_values($buckets), 'series' => []];

        foreach ($series as $label => $table) {
            $counts = array_fill_keys(array_keys($buckets), 0);
            try {
                $rows = Database::fetchAll(
                    "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt
                     FROM $table
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                     GROUP BY ym",
                    [$months]
                ) ?: [];
                foreach ($rows as $row) {
                    if (isset($counts[$row['ym']])) {
                        $counts[$row['ym']] = (int) $row['cnt'];
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting growth trend for $table: " . $e->getMessage());
            }
            $result['series'][$label] = array_values($counts);
        }

        return $result;
    }

    private function getRecentAdditions() {
        $recent = [];
        
        try {
            $land = Database::fetchAll("SELECT 'Land' as type, id, asset_code as code, created_at FROM land_assets ORDER BY created_at DESC LIMIT 5") ?: [];
            $buildings = Database::fetchAll("SELECT 'Building' as type, id, asset_code as code, created_at FROM building_assets ORDER BY created_at DESC LIMIT 5") ?: [];
            $vehicles = Database::fetchAll("SELECT 'Vehicle' as type, id, asset_code as code, created_at FROM vehicle_assets ORDER BY created_at DESC LIMIT 5") ?: [];
            $weapons = Database::fetchAll("SELECT 'Weapon' as type, id, weapon_id as code, created_at FROM weapons_inventory ORDER BY created_at DESC LIMIT 5") ?: [];
            
            $recent = array_merge($land, $buildings, $vehicles, $weapons);
            usort($recent, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        } catch (Exception $e) {
            error_log("Error getting recent additions: " . $e->getMessage());
        }
        
        return array_slice($recent, 0, 10);
    }
    
    private function getExpiringItems() {
        $result = [
            'ammunition' => [],
            'insurance' => []
        ];
        
        try {
            $result['ammunition'] = Database::fetchAll(
                "SELECT ai.id, ai.ammo_id, at.ammo_type, ac.calibre, ai.expiry_date
                 FROM ammunition_inventory ai
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 WHERE ai.expiry_date IS NOT NULL
                 AND ai.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                 AND ai.expiry_date >= CURDATE()
                 ORDER BY ai.expiry_date ASC
                 LIMIT 20"
            ) ?: [];
            
            $result['insurance'] = Database::fetchAll(
                "SELECT id, asset_code, make_manufacturer, registration_number, insurance_expiry
                 FROM vehicle_assets
                 WHERE insurance_expiry IS NOT NULL
                 AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND insurance_expiry >= CURDATE()
                 ORDER BY insurance_expiry ASC
                 LIMIT 20"
            ) ?: [];
        } catch (Exception $e) {
            error_log("Error getting expiring items: " . $e->getMessage());
        }
        
        return $result;
    }
    
    // =============================================
    // EXPORT METHODS
    // =============================================
    
    private function exportAssetReportCSV($data, $type) {
        $filename = $type . '_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if ($type === 'summary') {
            foreach ($data as $assetType => $items) {
                if (!empty($items) && is_array($items)) {
                    Security::fputcsv($output, [strtoupper($assetType)]);
                    if (!empty($items[0]) && is_array($items[0])) {
                        Security::fputcsv($output, array_keys($items[0]));
                        foreach ($items as $item) {
                            Security::fputcsv($output, $item);
                        }
                    }
                    Security::fputcsv($output, []);
                }
            }
        } else {
            if (!empty($data) && is_array($data) && !empty($data[0]) && is_array($data[0])) {
                Security::fputcsv($output, array_keys($data[0]));
                foreach ($data as $item) {
                    Security::fputcsv($output, $item);
                }
            }
        }
        
        fclose($output);
        
        // Log export if AuditLogger exists
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('assets', 'csv');
        }
        
        exit;
    }
    
    /**
     * Excel (.xlsx) export shared by every report type. Unlike the PDF path,
     * PhpSpreadsheet writes cells directly with no HTML/CSS parsing overhead,
     * so it comfortably handles the full unbounded data set the same way
     * CSV does — but unlike CSV, PhpSpreadsheet holds every cell as an
     * in-memory object while building the sheet (confirmed by testing: a
     * ~49k-row/million-cell export exhausted the 512MB PHP memory limit
     * outright). So this DOES still cap large single-sheet exports — 'summary'
     * type sheets are already small (the underlying queries cap those at
     * ~100 rows each) and don't need it.
     *
     * $data is either a flat array of associative rows, or (for a 'summary'
     * type) an associative array of section => rows, in which case each
     * section becomes its own sheet.
     *
     * $actualTotal: for a flat (non-'summary') export, pass the true match
     * count when the caller already fetched a capped page rather than
     * everything (mirrors the PDF exporters) — that avoids wastefully
     * fetching+discarding tens of thousands of rows just to find out they'd
     * get truncated here anyway. Left null, $data's own count is trusted and
     * (defensively) capped here too.
     */
    private function exportGenericExcel($data, $type, $filenamePrefix, $reportLabel, $actualTotal = null) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->redirect('reports/' . $filenamePrefix, ['type' => $type, 'format' => 'html', 'error' => 'Excel export is currently unavailable.']);
            return;
        }

        $excelRowCap = 5000;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $isNested = $type === 'summary' && is_array($data) && !isset($data[0]);
        if ($isNested) {
            $any = false;
            foreach ($data as $section => $rows) {
                if (empty($rows) || !is_array($rows)) continue;
                $any = true;
                $total = count($rows);
                $capped = $total > $excelRowCap ? array_slice($rows, 0, $excelRowCap) : $rows;
                $this->writeExcelSheet($spreadsheet, ucfirst((string) $section), $capped, $total > $excelRowCap ? $total : null);
            }
            if (!$any) {
                $this->writeExcelSheet($spreadsheet, 'Summary', []);
            }
        } else {
            $total = $actualTotal ?? (is_array($data) ? count($data) : 0);
            $ownCount = is_array($data) ? count($data) : 0;
            $capped = $ownCount > $excelRowCap ? array_slice($data, 0, $excelRowCap) : $data;
            $this->writeExcelSheet($spreadsheet, $reportLabel, $capped, $total > count($capped) ? $total : null);
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport($filenamePrefix, 'excel');
        }

        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filenamePrefix . '_' . $type . '_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Fleet's 'summary' type is the odd one out — getFleetReportData() fills
     * it with placeholder rows (just for the HTML chart), so exporting those
     * verbatim would produce a meaningless spreadsheet. Export the real
     * per-category counts instead.
     */
    private function exportFleetSummaryExcel($counts) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->redirect('reports/fleet', ['type' => 'summary', 'format' => 'html', 'error' => 'Excel export is currently unavailable.']);
            return;
        }

        $rows = [];
        foreach ($counts as $category => $c) {
            $rows[] = array_merge(['category' => ucfirst((string) $category)], $c);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $this->writeExcelSheet($spreadsheet, 'Fleet Summary', $rows);

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('fleet', 'excel');
        }

        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="fleet_summary_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Write one array of associative rows into a new sheet, headers from the
     * first row's keys.
     *
     * Uses fromArray() (one bulk call) rather than looping
     * setCellValueByColumnAndRow() per cell — that loop, combined with
     * getColumnDimensionByColumn(...)->setAutoSize(true) (which makes
     * PhpSpreadsheet scan every cell in a column to measure its rendered
     * width), is what took a weapons "issued" export from ~1s to 76s and
     * then to an outright out-of-memory fatal error on a ~49k-row export.
     * fromArray() is the library's optimized bulk-write path and a fixed
     * column width is O(1) instead of O(rows) — same visual result, no
     * per-cell scan.
     */
    private function writeExcelSheet($spreadsheet, $sheetName, $rows, $actualTotal = null) {
        $sheet = $spreadsheet->createSheet();
        // Sheet names: max 31 chars, no \ / ? * [ ] :
        $sheet->setTitle(substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', (string) $sheetName), 0, 31) ?: 'Sheet');

        if (empty($rows) || !is_array($rows[0] ?? null)) {
            $sheet->setCellValue('A1', 'No data available');
            return;
        }

        $headerRow = 1;
        if ($actualTotal !== null) {
            $sheet->setCellValue('A1', 'Showing the ' . number_format(count($rows)) . ' most recent of '
                . number_format($actualTotal) . ' matching records. Use CSV export for the complete data set.');
            $sheet->getStyle('A1')->getFont()->setItalic(true)->getColor()->setRGB('B42318');
            $headerRow = 2;
        }

        $headers = array_keys($rows[0]);
        $sheet->fromArray($headers, null, 'A' . $headerRow);

        $dataRows = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            $dataRows[] = $line;
        }
        $sheet->fromArray($dataRows, null, 'A' . ($headerRow + 1));

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A' . $headerRow . ':' . $lastColLetter . $headerRow)->getFont()->setBold(true);
        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setWidth(20);
        }
    }

    /**
     * Shared TCPDF setup + NIS letterhead used by every report's PDF export
     * (assets/weapons/ammunition/fleet) — one place for the branding instead
     * of four copies drifting apart.
     */
    private function newReportPdf($title, array $subtitleLines = []) {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('NIS Asset Management System');
        $pdf->SetAuthor('Nigeria Immigration Service');
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(19, 70, 23);
        $pdf->Cell(0, 8, 'Nigeria Immigration Service', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(19, 70, 23);
        $pdf->Cell(0, 6, 'Asset Management System — ' . $title, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);

        if (Auth::isCommandRestricted() && Auth::commandId()) {
            $cmd = Database::fetchOne("SELECT command_name FROM commands WHERE id = ?", [Auth::commandId()]);
            if (!empty($cmd['command_name'])) {
                array_unshift($subtitleLines, 'Command: ' . $cmd['command_name']);
            }
        }

        foreach ($subtitleLines as $i => $line) {
            if ($i === 0 || strpos($line, 'Command:') === 0) {
                $pdf->SetTextColor(83, 102, 94);
            } else {
                $pdf->SetTextColor(180, 35, 24);
            }
            $pdf->Cell(0, 5, $line, 0, 1, 'C');
        }
        $pdf->Ln(3);
        return $pdf;
    }

    private function exportAssetReportPDF($data, $type, $startDate = null, $endDate = null, $actualTotal = null) {
        if (!class_exists('TCPDF')) {
            // Composer dependency not installed in this environment — fail
            // safe back to the HTML view instead of a broken/blank download.
            $this->redirect('reports/assets', ['type' => $type, 'format' => 'html', 'error' => 'PDF export is currently unavailable.']);
            return;
        }

        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-d');

        $typeLabels = [
            'summary' => 'Asset Summary Report',
            'land' => 'Land Assets Report',
            'buildings' => 'Building Assets Report',
            'rented' => 'Rented Properties Report',
            'movable' => 'Movable Assets Report',
            'ict' => 'ICT Assets Report',
            'projects' => 'Ongoing Projects Report',
        ];
        $title = $typeLabels[$type] ?? 'Asset Report';

        $subtitleLines = ['Period: ' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate))
            . '    Generated: ' . date('d/m/Y H:i')];
        if ($actualTotal !== null && $actualTotal > count($data)) {
            $subtitleLines[] = 'Showing the ' . number_format(count($data)) . ' most recent of '
                . number_format($actualTotal) . ' matching records. Use CSV export for the complete data set.';
        }
        $pdf = $this->newReportPdf($title, $subtitleLines);

        if ($type === 'summary') {
            $sections = [
                'land' => 'Land Assets', 'buildings' => 'Building Assets', 'rented' => 'Rented Properties',
                'movable' => 'Movable Assets', 'ict' => 'ICT Assets', 'projects' => 'Ongoing Projects',
            ];
            $any = false;
            foreach ($sections as $key => $label) {
                $rows = $data[$key] ?? [];
                if (empty($rows)) continue;
                $any = true;
                $this->renderAssetPdfTable($pdf, $label, $rows, $this->getAssetPdfColumns($key));
            }
            if (!$any) {
                $pdf->SetFont('helvetica', '', 11);
                $pdf->SetTextColor(83, 102, 94);
                $pdf->Cell(0, 10, 'No asset data found for the selected period.', 0, 1, 'C');
            }
        } else {
            if (empty($data)) {
                $pdf->SetFont('helvetica', '', 11);
                $pdf->SetTextColor(83, 102, 94);
                $pdf->Cell(0, 10, 'No records found for the selected period/filters.', 0, 1, 'C');
            } else {
                $this->renderAssetPdfTable($pdf, $title, $data, $this->getAssetPdfColumns($type));
            }
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('assets', 'pdf');
        }

        $pdf->Output('asset_report_' . $type . '_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    /** Render one bordered, header-repeating table into the PDF for a set of rows. */
    private function renderAssetPdfTable($pdf, $title, $rows, $columns) {
        if (empty($columns)) return;

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(19, 70, 23);
        $pdf->Cell(0, 7, $title . ' (' . number_format(count($rows)) . ' records)', 0, 1, 'L');

        $html = '<table cellpadding="3" cellspacing="0" border="0.5">';
        $html .= '<thead><tr style="background-color:#E8F0E9; font-weight:bold; font-size:7pt;">';
        foreach ($columns as $col) {
            $html .= '<th>' . htmlspecialchars($col[0]) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr style="font-size:7pt;">';
            foreach ($columns as $col) {
                $html .= '<td>' . htmlspecialchars($this->formatAssetPdfCell($row, $col[1], $col[2])) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(6);
    }

    /**
     * Column spec per asset type for the PDF export: [label, source key or
     * null, format]. format is one of text|date|currency|percent|location|size.
     * Deliberately a curated, readable subset rather than a raw column dump
     * (that's what CSV export is for) — and independent of the HTML view's
     * badge/link markup, which doesn't translate to a static PDF table.
     */
    private function getAssetPdfColumns($type) {
        switch ($type) {
            case 'land':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Ownership', 'ownership_type', 'text'],
                    ['Title Holder', 'title_holder', 'text'],
                    ['Address', 'address', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Size', null, 'size'],
                    ['Purpose', 'purpose_use', 'text'],
                    ['Date Acquired', 'date_acquired', 'date'],
                    ['Status', 'status', 'text'],
                ];
            case 'buildings':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Building Name', 'building_name', 'text'],
                    ['Type', 'building_type', 'text'],
                    ['Address', 'address', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Floors', 'floor_count', 'text'],
                    ['Area (sqm)', 'total_area', 'text'],
                    ['Contract Sum', 'contract_sum', 'currency'],
                    ['Completion Date', 'completion_date', 'date'],
                    ['Condition', 'condition_status', 'text'],
                ];
            case 'rented':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Address', 'property_address', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Annual Rent', 'annual_rent', 'currency'],
                    ['Landlord', 'owner_lessor_name', 'text'],
                    ['Landlord Phone', 'owner_phone', 'text'],
                    ['Lease Start', 'start_date', 'date'],
                    ['Lease Expiry', 'expiry_date', 'date'],
                    ['Status', 'status', 'text'],
                ];
            case 'movable':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Asset Type', 'asset_type', 'text'],
                    ['Make/Model', 'make_model', 'text'],
                    ['Serial No.', 'serial_number', 'text'],
                    ['Condition', 'condition_status', 'text'],
                    ['Location', 'current_location', 'text'],
                    ['Custodian', 'custodian_name', 'text'],
                    ['Value', 'current_value', 'currency'],
                ];
            case 'ict':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Description', 'asset_description', 'text'],
                    ['Category', 'asset_category', 'text'],
                    ['Manufacturer', 'manufacturer', 'text'],
                    ['Model/Version', 'model_version', 'text'],
                    ['Serial No.', 'serial_number', 'text'],
                    ['Status', 'current_status', 'text'],
                    ['Assigned To', 'responsible_officer', 'text'],
                    ['Value', 'current_value', 'currency'],
                ];
            case 'projects':
                return [
                    ['Project Code', 'project_code', 'text'],
                    ['Project Title', 'project_title', 'text'],
                    ['Type', 'project_type', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Contractor', 'contractor', 'text'],
                    ['Contract Sum', 'contract_sum', 'currency'],
                    ['Date Awarded', 'date_awarded', 'date'],
                    ['Expected Completion', 'expected_completion_date', 'date'],
                    ['Physical %', 'physical_progress', 'percent'],
                    ['Status', 'status', 'text'],
                ];
            default:
                return [];
        }
    }

    private function formatAssetPdfCell($row, $key, $format) {
        if ($format === 'location') {
            $val = trim(($row['state_name'] ?? '') . (!empty($row['lga_name']) ? '/' . $row['lga_name'] : ''));
            return $val !== '' ? $val : '-';
        }
        if ($format === 'size') {
            return isset($row['size']) ? number_format((float) $row['size'], 2) . ' ' . ($row['size_unit'] ?? '') : '-';
        }
        $val = $row[$key] ?? null;
        if ($val === null || $val === '') return '-';
        switch ($format) {
            case 'date':
                $ts = strtotime($val);
                return $ts ? date('d/m/Y', $ts) : '-';
            case 'currency':
                return 'NGN ' . number_format((float) $val, 2);
            case 'percent':
                return number_format((float) $val, 1) . '%';
            default:
                return (string) $val;
        }
    }
    
    private function exportWeaponsReportCSV($data, $type) {
        $filename = 'weapons_' . $type . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data) && is_array($data) && !empty($data[0]) && is_array($data[0])) {
            Security::fputcsv($output, array_keys($data[0]));
            foreach ($data as $item) {
                Security::fputcsv($output, $item);
            }
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('weapons', 'csv');
        }
        
        exit;
    }
    
    private function exportWeaponsReportPDF($data, $type, $actualTotal = null) {
        if (!class_exists('TCPDF')) {
            $this->redirect('reports/weapons', ['type' => $type, 'format' => 'html', 'error' => 'PDF export is currently unavailable.']);
            return;
        }

        $typeLabels = [
            'inventory' => 'Weapons Inventory Report',
            'issued' => 'Issued Weapons Report',
            'unserviceable' => 'Unserviceable Weapons Report',
            'by_type' => 'Weapons by Type Report',
        ];
        $title = $typeLabels[$type] ?? 'Weapons Report';

        $subtitleLines = ['Generated: ' . date('d/m/Y H:i')];
        if ($actualTotal !== null && $actualTotal > count($data)) {
            $subtitleLines[] = 'Showing the ' . number_format(count($data)) . ' most recent of '
                . number_format($actualTotal) . ' matching records. Use CSV export for the complete data set.';
        }
        $pdf = $this->newReportPdf($title, $subtitleLines);

        if (empty($data)) {
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(83, 102, 94);
            $pdf->Cell(0, 10, 'No records found.', 0, 1, 'C');
        } else {
            $this->renderAssetPdfTable($pdf, $title, $data, $this->getWeaponsPdfColumns($type));
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('weapons', 'pdf');
        }

        $pdf->Output('weapons_report_' . $type . '_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    private function getWeaponsPdfColumns($type) {
        switch ($type) {
            case 'inventory':
            case 'unserviceable':
                return [
                    ['Weapon ID', 'weapon_id', 'text'],
                    ['Type', 'type_name', 'text'],
                    ['Make/Model', 'make_model', 'text'],
                    ['Serial No.', 'serial_no', 'text'],
                    ['Calibre', 'calibre_name', 'text'],
                    ['Condition', 'condition', 'text'],
                    ['Location', 'current_location', 'text'],
                    ['Custodian', 'custodian', 'text'],
                    ['Date Acquired', 'date_acquired', 'date'],
                ];
            case 'issued':
                return [
                    ['Weapon ID', 'weapon_id', 'text'],
                    ['Type', 'type_name', 'text'],
                    ['Officer', 'officer_name', 'text'],
                    ['Rank', 'officer_rank', 'text'],
                    ['Unit', 'unit', 'text'],
                    ['Purpose', 'purpose', 'text'],
                    ['Issue Date', 'issue_date', 'date'],
                    ['Expected Return', 'expected_return_date', 'date'],
                    ['Status', 'status', 'text'],
                ];
            case 'by_type':
                return [
                    ['Weapon Type', 'type', 'text'],
                    ['Count', 'count', 'text'],
                ];
            default:
                return [];
        }
    }
    
    private function exportAmmunitionReportCSV($data, $type) {
        $filename = 'ammunition_' . $type . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data) && is_array($data) && !empty($data[0]) && is_array($data[0])) {
            Security::fputcsv($output, array_keys($data[0]));
            foreach ($data as $item) {
                Security::fputcsv($output, $item);
            }
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('ammunition', 'csv');
        }
        
        exit;
    }
    
    private function exportAmmunitionReportPDF($data, $type, $actualTotal = null) {
        if (!class_exists('TCPDF')) {
            $this->redirect('reports/ammunition', ['type' => $type, 'format' => 'html', 'error' => 'PDF export is currently unavailable.']);
            return;
        }

        $typeLabels = [
            'inventory' => 'Ammunition Inventory Report',
            'expiring' => 'Expiring Ammunition Report',
            'low_stock' => 'Low Stock Ammunition Report',
            'issued' => 'Issued Ammunition Report',
        ];
        $title = $typeLabels[$type] ?? 'Ammunition Report';

        $subtitleLines = ['Generated: ' . date('d/m/Y H:i')];
        if ($actualTotal !== null && $actualTotal > count($data)) {
            $subtitleLines[] = 'Showing the ' . number_format(count($data)) . ' most recent of '
                . number_format($actualTotal) . ' matching records. Use CSV export for the complete data set.';
        }
        $pdf = $this->newReportPdf($title, $subtitleLines);

        if (empty($data)) {
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(83, 102, 94);
            $pdf->Cell(0, 10, 'No records found.', 0, 1, 'C');
        } else {
            $this->renderAssetPdfTable($pdf, $title, $data, $this->getAmmunitionPdfColumns($type));
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('ammunition', 'pdf');
        }

        $pdf->Output('ammunition_report_' . $type . '_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    private function getAmmunitionPdfColumns($type) {
        switch ($type) {
            case 'inventory':
            case 'low_stock':
                return [
                    ['Ammo ID', 'ammo_id', 'text'],
                    ['Type', 'ammo_type', 'text'],
                    ['Calibre', 'calibre', 'text'],
                    ['Batch No.', 'batch_number', 'text'],
                    ['Received', 'quantity_received', 'text'],
                    ['Issued', 'quantity_issued', 'text'],
                    ['Balance', 'balance', 'text'],
                    ['Storage', 'storage_location', 'text'],
                    ['Expiry Date', 'expiry_date', 'date'],
                    ['Condition', 'condition', 'text'],
                ];
            case 'expiring':
                return [
                    ['Ammo ID', 'ammo_id', 'text'],
                    ['Type', 'ammo_type', 'text'],
                    ['Calibre', 'calibre', 'text'],
                    ['Batch No.', 'batch_number', 'text'],
                    ['Expiry Date', 'expiry_date', 'date'],
                    ['Days Remaining', 'days_remaining', 'text'],
                    ['Balance', 'balance', 'text'],
                    ['Storage', 'storage_location', 'text'],
                ];
            case 'issued':
                return [
                    ['Type', 'ammo_type', 'text'],
                    ['Calibre', 'calibre', 'text'],
                    ['Issue Date', 'issue_date', 'date'],
                    ['Officer', 'officer_name', 'text'],
                    ['Rank', 'officer_rank', 'text'],
                    ['Unit', 'unit', 'text'],
                    ['Purpose', 'purpose', 'text'],
                    ['Rounds Issued', 'rounds_issued', 'text'],
                ];
            default:
                return [];
        }
    }
    
    private function exportFleetReportCSV($data, $type) {
        $filename = 'fleet_' . $type . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if ($type === 'summary') {
            foreach ($data as $fleetType => $items) {
                if (!empty($items) && is_array($items)) {
                    Security::fputcsv($output, [strtoupper($fleetType)]);
                    if (!empty($items[0]) && is_array($items[0])) {
                        Security::fputcsv($output, array_keys($items[0]));
                        foreach ($items as $item) {
                            Security::fputcsv($output, $item);
                        }
                    }
                    Security::fputcsv($output, []);
                }
            }
        } else {
            if (!empty($data) && is_array($data) && !empty($data[0]) && is_array($data[0])) {
                Security::fputcsv($output, array_keys($data[0]));
                foreach ($data as $item) {
                    Security::fputcsv($output, $item);
                }
            }
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('fleet', 'csv');
        }
        
        exit;
    }
    
    private function exportFleetReportPDF($data, $type) {
        if (!class_exists('TCPDF')) {
            $this->redirect('reports/fleet', ['type' => $type, 'format' => 'html', 'error' => 'PDF export is currently unavailable.']);
            return;
        }

        $typeLabels = [
            'summary' => 'Fleet Summary Report',
            'vehicles' => 'Vehicle Fleet Report',
            'aircraft' => 'Aircraft Fleet Report',
            'marine' => 'Marine Fleet Report',
            'motorcycles' => 'Motorcycle Fleet Report',
            'maintenance' => 'Vehicles Due for Maintenance',
            'insurance' => 'Vehicles with Expiring Insurance',
        ];
        $title = $typeLabels[$type] ?? 'Fleet Report';
        $pdf = $this->newReportPdf($title, ['Generated: ' . date('d/m/Y H:i')
            . ($type !== 'summary' ? '    (capped at the 500 most recent — use CSV export for the complete data set)' : '')]);

        if ($type === 'summary') {
            // 'summary' isn't a list of real asset rows (getFleetReportData() fills
            // it with placeholder rows purely to drive the HTML chart) — the real
            // information is the per-category counts, so render those directly.
            $counts = $data['counts'] ?? [];
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(19, 70, 23);
            $pdf->Cell(0, 8, 'Fleet Overview', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $lines = [
                'vehicles' => 'Vehicles: %s total (Active: %s, In Repair: %s, Grounded: %s)',
                'aircraft' => 'Aircraft: %s total (Operational: %s, Maintenance: %s)',
                'marine' => 'Marine: %s total (Operational: %s)',
                'motorcycles' => 'Motorcycles: %s total (Active: %s)',
            ];
            foreach ($lines as $key => $format) {
                if (!isset($counts[$key])) continue;
                $c = $counts[$key];
                $args = array_map(function ($v) { return number_format((int) $v); }, array_values($c));
                array_unshift($args, $format);
                $pdf->Cell(0, 7, call_user_func_array('sprintf', $args), 0, 1, 'L');
            }
        } elseif (empty($data)) {
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(83, 102, 94);
            $pdf->Cell(0, 10, 'No records found.', 0, 1, 'C');
        } else {
            $this->renderAssetPdfTable($pdf, $title, $data, $this->getFleetPdfColumns($type));
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('fleet', 'pdf');
        }

        $pdf->Output('fleet_report_' . $type . '_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    private function getFleetPdfColumns($type) {
        switch ($type) {
            case 'vehicles':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Type', 'vehicle_type', 'text'],
                    ['Make/Manufacturer', 'make_manufacturer', 'text'],
                    ['Registration No.', 'registration_number', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Status', 'operational_status', 'text'],
                    ['Condition', 'condition', 'text'],
                    ['Assigned Officer', 'assigned_officer', 'text'],
                    ['Value', 'current_value', 'currency'],
                ];
            case 'aircraft':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Type', 'aircraft_type', 'text'],
                    ['Model/Manufacturer', 'model_manufacturer', 'text'],
                    ['Tail Number', 'tail_number', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Status', 'operational_status', 'text'],
                    ['Flight Hours', 'flight_hours', 'text'],
                    ['Value', 'capital_value', 'currency'],
                ];
            case 'marine':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Type', 'boat_type', 'text'],
                    ['Registration No.', 'registration_number', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Status', 'operational_status', 'text'],
                    ['Condition', 'condition', 'text'],
                    ['Value', 'capital_value', 'currency'],
                ];
            case 'motorcycles':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Type', 'motorcycle_type', 'text'],
                    ['Make/Model', 'make_model', 'text'],
                    ['Registration No.', 'registration_number', 'text'],
                    ['State/LGA', null, 'location'],
                    ['Condition', 'condition', 'text'],
                    ['Value', 'current_value', 'currency'],
                ];
            case 'maintenance':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Make/Manufacturer', 'make_manufacturer', 'text'],
                    ['Registration No.', 'registration_number', 'text'],
                    ['Next Service Date', 'next_service_date', 'date'],
                    ['Days to Service', 'days_to_service', 'text'],
                    ['Status', 'operational_status', 'text'],
                ];
            case 'insurance':
                return [
                    ['Asset Code', 'asset_code', 'text'],
                    ['Make/Manufacturer', 'make_manufacturer', 'text'],
                    ['Registration No.', 'registration_number', 'text'],
                    ['Insurance Status', 'insurance_status', 'text'],
                    ['Insurance Expiry', 'insurance_expiry', 'date'],
                    ['Days to Expiry', 'days_to_expiry', 'text'],
                ];
            default:
                return [];
        }
    }
    
    private function exportAuditReportCSV($data) {
        $filename = 'audit_report_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        foreach ($data as $audit) {
            Security::fputcsv($output, ['AUDIT SUMMARY']);
            Security::fputcsv($output, ['Audit ID', 'Date', 'Quarter', 'Officer', 'Location', 'Status']);
            Security::fputcsv($output, [
                $audit['audit_number'] ?? '',
                $audit['audit_date'] ?? '',
                ($audit['quarter'] ?? '') . ' ' . ($audit['year'] ?? ''),
                $audit['audit_officer'] ?? '',
                $audit['audit_location'] ?? '',
                $audit['status'] ?? ''
            ]);
            
            if (!empty($audit['weapons'])) {
                Security::fputcsv($output, []);
                Security::fputcsv($output, ['WEAPONS AUDITED']);
                if (!empty($audit['weapons'][0])) {
                    Security::fputcsv($output, array_keys($audit['weapons'][0]));
                    foreach ($audit['weapons'] as $weapon) {
                        Security::fputcsv($output, $weapon);
                    }
                }
            }
            
            if (!empty($audit['ammunition'])) {
                Security::fputcsv($output, []);
                Security::fputcsv($output, ['AMMUNITION AUDITED']);
                if (!empty($audit['ammunition'][0])) {
                    Security::fputcsv($output, array_keys($audit['ammunition'][0]));
                    foreach ($audit['ammunition'] as $ammo) {
                        Security::fputcsv($output, $ammo);
                    }
                }
            }
            
            if (!empty($audit['missing'])) {
                Security::fputcsv($output, []);
                Security::fputcsv($output, ['MISSING WEAPONS']);
                if (!empty($audit['missing'][0])) {
                    Security::fputcsv($output, array_keys($audit['missing'][0]));
                    foreach ($audit['missing'] as $missing) {
                        Security::fputcsv($output, $missing);
                    }
                }
            }
            
            Security::fputcsv($output, []);
            Security::fputcsv($output, []);
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('audit', 'csv');
        }
        
        exit;
    }
    
    private function exportAuditReportPDF($data) {
        $this->redirect('reports/audit?format=html');
    }
}